<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use UnexpectedValueException;

class CitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sql = File::get(database_path('seeders/cities.sql'));
        $stateIdsByCode = State::query()->pluck('id', 'code');

        preg_match_all('/^\((?<row>.*?)\)[,;]\r?$/m', $sql, $matches);

        collect($matches['row'])
            ->chunk(500)
            ->each(function (Collection $rows) use ($stateIdsByCode): void {
                $cities = $rows
                    ->map(fn (string $row): array => $this->parseRow($row, $stateIdsByCode))
                    ->all();

                City::query()->upsert(
                    $cities,
                    ['code'],
                    ['name', 'state_id', 'tax_id', 'tax_ostan', 'anbar_code', 'created_at', 'updated_at'],
                );
            });
    }

    /**
     * @param  Collection<int|string, int>  $stateIdsByCode
     * @return array{id: int, name: string, code: int, state_id: int, tax_id: ?int, tax_ostan: ?int, anbar_code: ?string, created_at: ?string, updated_at: ?string}
     */
    private function parseRow(string $row, Collection $stateIdsByCode): array
    {
        preg_match_all(
            "/(?:'(?<string>(?:''|[^'])*)'|(?<scalar>NULL|-?\\d+))(?=,\\s*|$)/u",
            $row,
            $fields,
            PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL,
        );

        if (count($fields) !== 9) {
            throw new UnexpectedValueException('Invalid city row in database/seeders/cities.sql.');
        }

        $values = collect($fields)
            ->map(function (array $field): string|int|null {
                if ($field['string'] !== null) {
                    return str_replace("''", "'", $field['string']);
                }

                return $field['scalar'] === 'NULL' ? null : (int) $field['scalar'];
            })
            ->all();

        $stateCode = (int) $values[3];
        $stateId = $stateIdsByCode->get($stateCode);

        if ($stateId === null) {
            throw new UnexpectedValueException("State code [{$stateCode}] is missing.");
        }

        return [
            'id' => (int) $values[0],
            'name' => (string) $values[1],
            'code' => (int) $values[2],
            'state_id' => $stateId,
            'tax_id' => $values[4],
            'tax_ostan' => $values[5],
            'anbar_code' => $values[6],
            'created_at' => $values[7],
            'updated_at' => $values[8],
        ];
    }
}
