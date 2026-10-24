<?php

namespace App\Services\Database;

use Closure;
use Illuminate\Database\Connection;
use PDO;
use RuntimeException;

/**
 * Pure PHP MySQL/MariaDB dumper: no mysqldump or any other external binary.
 *
 * It reads the schema with SHOW CREATE TABLE/VIEW and the rows with plain SELECTs,
 * streaming everything into a gzip handle so memory stays flat on large databases.
 * The output is a standard .sql file that any MySQL client can restore.
 */
class DatabaseDumper
{
    /** Rows per SELECT and per multi-row INSERT statement. */
    private const BATCH_SIZE = 500;

    private PDO $pdo;

    public function __construct(private readonly Connection $connection)
    {
        $this->pdo = $connection->getPdo();
    }

    /**
     * Write the full dump to a file, gzip-compressed or as plain SQL.
     *
     * @return array{tables: int, views: int, rows: int}
     */
    public function dumpToFile(string $path, bool $compress = true): array
    {
        $handle = $compress ? gzopen($path, 'wb6') : fopen($path, 'wb');

        if ($handle === false) {
            throw new RuntimeException("Unable to open [{$path}] for writing.");
        }

        try {
            return $this->dump(function (string $chunk) use ($handle, $compress): void {
                $written = $compress ? gzwrite($handle, $chunk) : fwrite($handle, $chunk);

                if ($written === false || $written < strlen($chunk)) {
                    throw new RuntimeException('Unable to write to the backup file (disk full?).');
                }
            });
        } finally {
            $compress ? gzclose($handle) : fclose($handle);
        }
    }

    /**
     * @param Closure(string): void $write
     * @return array{tables: int, views: int, rows: int}
     */
    public function dump(Closure $write): array
    {
        // TIMESTAMP columns are read in the session time zone, while the dump restores them under
        // +00:00. Read in UTC too, then put the connection back exactly as it was.
        $previousTimeZone = (string)$this->pdo->query('SELECT @@session.time_zone')->fetchColumn();
        $this->pdo->exec("SET time_zone = '+00:00'");

        try {
            return $this->writeDump($write);
        } finally {
            $this->pdo->exec('SET time_zone = ' . $this->pdo->quote($previousTimeZone));
        }
    }

    /**
     * @param Closure(string): void $write
     * @return array{tables: int, views: int, rows: int}
     */
    private function writeDump(Closure $write): array
    {
        $database = (string)$this->connection->getDatabaseName();
        $stats = ['tables' => 0, 'views' => 0, 'rows' => 0];

        $write("-- Bijak database backup\n");
        $write('-- Database: ' . $database . "\n");
        $write('-- Generated at: ' . now()->toDateTimeString() . "\n\n");
        $write("SET NAMES utf8mb4;\n");
        $write("SET FOREIGN_KEY_CHECKS = 0;\n");
        $write("SET UNIQUE_CHECKS = 0;\n");
        $write("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
        $write("SET TIME_ZONE = '+00:00';\n\n");

        [$tables, $views] = $this->objects();

        foreach ($tables as $table) {
            $write("-- Table {$table}\n");
            $write('DROP TABLE IF EXISTS ' . $this->identifier($table) . ";\n");
            $write($this->createStatement('TABLE', $table) . ";\n\n");

            $stats['rows'] += $this->dumpRows($table, $write);
            $stats['tables']++;
        }

        // Views reference tables, so they go last.
        foreach ($views as $view) {
            $write("-- View {$view}\n");
            $write('DROP VIEW IF EXISTS ' . $this->identifier($view) . ";\n");
            $write($this->createStatement('VIEW', $view) . ";\n\n");
            $stats['views']++;
        }

        $write("SET UNIQUE_CHECKS = 1;\n");
        $write("SET FOREIGN_KEY_CHECKS = 1;\n");

        return $stats;
    }

    /**
     * Format one value as an SQL literal.
     *
     * @param Closure(string): string $quote string quoting (PDO::quote in production)
     */
    public static function literal(mixed $value, bool $binary, Closure $quote): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        $value = (string)$value;

        if ($binary) {
            return $value === '' ? "''" : '0x' . bin2hex($value);
        }

        return $quote($value);
    }

    /** @return array{0: list<string>, 1: list<string>} base tables and views */
    private function objects(): array
    {
        $tables = [];
        $views = [];

        foreach ($this->pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as [$name, $type]) {
            if (strtoupper((string)$type) === 'VIEW') {
                $views[] = $name;
            } else {
                $tables[] = $name;
            }
        }

        return [$tables, $views];
    }

    private function createStatement(string $kind, string $name): string
    {
        $row = $this->pdo->query("SHOW CREATE {$kind} " . $this->identifier($name))->fetch(PDO::FETCH_NUM);

        if (!$row || !isset($row[1])) {
            throw new RuntimeException("Unable to read the definition of [{$name}].");
        }

        return $row[1];
    }

    /**
     * @param Closure(string): void $write
     */
    private function dumpRows(string $table, Closure $write): int
    {
        $columns = $this->pdo->query('SHOW COLUMNS FROM ' . $this->identifier($table))->fetchAll(PDO::FETCH_ASSOC);
        $names = array_column($columns, 'Field');
        $binary = array_map(
            fn(array $column): bool => (bool)preg_match('/binary|blob|bit/i', (string)$column['Type']),
            $columns,
        );
        $primaryKey = $this->singleIntegerPrimaryKey($table, $columns);

        $columnList = implode(', ', array_map($this->identifier(...), $names));
        $quote = fn(string $value): string => $this->pdo->quote($value);
        $total = 0;
        $lastKey = null;
        $offset = 0;

        while (true) {
            $sql = 'SELECT * FROM ' . $this->identifier($table);

            if ($primaryKey !== null) {
                // Keyset pagination: stable and fast on any table size.
                $sql .= $lastKey === null ? '' : ' WHERE ' . $this->identifier($primaryKey) . ' > ' . (int)$lastKey;
                $sql .= ' ORDER BY ' . $this->identifier($primaryKey) . ' ASC LIMIT ' . self::BATCH_SIZE;
            } else {
                $sql .= ' LIMIT ' . self::BATCH_SIZE . ' OFFSET ' . $offset;
            }

            $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_NUM);

            if ($rows === []) {
                break;
            }

            $values = array_map(
                fn(array $row): string => '(' . implode(',', array_map(
                    fn(mixed $value, int $index): string => self::literal($value, $binary[$index], $quote),
                    $row,
                    array_keys($row),
                )) . ')',
                $rows,
            );

            $write("INSERT INTO " . $this->identifier($table) . " ({$columnList}) VALUES\n" . implode(",\n", $values) . ";\n");

            $total += count($rows);
            $offset += count($rows);

            if ($primaryKey !== null) {
                $lastKey = $rows[array_key_last($rows)][array_search($primaryKey, $names, true)];
            }

            if (count($rows) < self::BATCH_SIZE) {
                break;
            }
        }

        if ($total > 0) {
            $write("\n");
        }

        return $total;
    }

    /**
     * The primary key column when it is a single integer column, otherwise null.
     *
     * @param list<array<string, mixed>> $columns
     */
    private function singleIntegerPrimaryKey(string $table, array $columns): ?string
    {
        $keys = array_filter(
            $columns,
            fn(array $column): bool => ($column['Key'] ?? '') === 'PRI',
        );

        if (count($keys) !== 1) {
            return null;
        }

        $key = array_values($keys)[0];

        return preg_match('/int/i', (string)$key['Type']) ? $key['Field'] : null;
    }

    private function identifier(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }
}
