<?php

namespace App\Services\Company\Report\Writers;

/**
 * Finds the font an admin dropped in the report font folder (see `waybill_report.pdf_font_directories`): the newest
 * .ttf/.otf file wins, so replacing or adding a file changes the font of the next PDF/Excel with no code change.
 */
class ReportFontResolver
{
    /**
     * @return array{file: string, directory: string, family: string, name: string}|null
     *         `family` is mpdf's key (file name + modification time, so a replaced file is never read from a stale
     *         cache), `name` is the font's own family name as written inside the file (what Excel needs).
     */
    public function resolve(): ?array
    {
        $files = [];

        foreach ((array)config('waybill_report.pdf_font_directories') as $directory) {
            if (is_string($directory) && is_dir($directory)) {
                $files = [...$files, ...(glob(rtrim($directory, '/\\') . '/*.{ttf,otf,TTF,OTF}', GLOB_BRACE) ?: [])];
            }
        }

        if ($files === []) {
            return null;
        }

        usort($files, fn(string $a, string $b): int => filemtime($b) <=> filemtime($a));
        $file = $files[0];
        $base = pathinfo($file, PATHINFO_FILENAME);
        $key = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $base) ?? '');

        return [
            'file' => basename($file),
            'directory' => dirname($file),
            'family' => ($key === '' ? 'customfont' : $key) . filemtime($file),
            'name' => $this->familyName($file) ?? $base,
        ];
    }

    /** Reads the font family name (name id 1, or 16 when present) from the `name` table of a TrueType/OpenType file. */
    private function familyName(string $file): ?string
    {
        $data = @file_get_contents($file, false, null, 0, 1024 * 1024);

        if ($data === false || strlen($data) < 12) {
            return null;
        }

        $tables = unpack('n', substr($data, 4, 2))[1] ?? 0;
        $offset = null;

        for ($index = 0; $index < $tables; $index++) {
            $entry = substr($data, 12 + $index * 16, 16);

            if (strlen($entry) === 16 && substr($entry, 0, 4) === 'name') {
                $offset = unpack('N', substr($entry, 8, 4))[1];

                break;
            }
        }

        if ($offset === null || strlen($data) < $offset + 6) {
            return null;
        }

        $count = unpack('n', substr($data, $offset + 2, 2))[1];
        $stringOffset = $offset + unpack('n', substr($data, $offset + 4, 2))[1];
        $found = [];

        for ($index = 0; $index < $count; $index++) {
            $record = substr($data, $offset + 6 + $index * 12, 12);

            if (strlen($record) < 12) {
                break;
            }

            $fields = unpack('nplatform/nencoding/nlanguage/nname/nlength/nposition', $record);

            if (!in_array($fields['name'], [1, 16], true)) {
                continue;
            }

            $raw = substr($data, $stringOffset + $fields['position'], $fields['length']);
            $text = in_array($fields['platform'], [0, 3], true)
                ? mb_convert_encoding($raw, 'UTF-8', 'UTF-16BE')
                : $raw;

            if ($text !== '' && (!isset($found[$fields['name']]) || $fields['platform'] === 3)) {
                $found[$fields['name']] = $text;
            }
        }

        return $found[16] ?? $found[1] ?? null;
    }
}
