<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CurriculumImporter
{
    public function import(): int
    {
        $lines = file(database_path('sql/curriculum_data.sql'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $insertPattern = '~^INSERT\s+IGNORE\s+INTO\s+`?([A-Za-z_][A-Za-z0-9_]*)`?~i';
        $imported = 0;

        DB::transaction(function () use ($lines, $insertPattern, &$imported): void {
            foreach ($lines as $line) {
                $line = trim($line);

                if (! preg_match($insertPattern, $line, $matches)) {
                    continue;
                }

                $table = strtolower($matches[1]);
                $statement = preg_replace_callback(
                    $insertPattern,
                    static fn (array $match): string => 'INSERT INTO "'.strtolower($match[1]).'"',
                    $line,
                    1,
                );
                $statement = str_replace('`', '"', $statement);
                $statement = $this->convertBooleanValues($statement, $table);
                $statement = preg_replace('/;\s*$/', ' ON CONFLICT DO NOTHING;', $statement);

                DB::unprepared($statement);
                $imported++;
            }

            foreach (['program_majors', 'programs', 'subjects'] as $table) {
                DB::unprepared(sprintf(
                    "SELECT setval(pg_get_serial_sequence('%s', 'id'), GREATEST(COALESCE((SELECT MAX(id) FROM %s), 1), 1), TRUE)",
                    $table,
                    $table,
                ));
            }
        });

        return $imported;
    }

    private function convertBooleanValues(string $statement, string $table): string
    {
        $booleanColumns = [
            'program_majors' => ['is_active'],
            'programs' => ['is_active'],
            'subjects' => ['is_capstone', 'is_active'],
        ];

        if (empty($booleanColumns[$table]) || ! preg_match('/INSERT INTO "[^"]+"\s*\((.*?)\)\s*VALUES/is', $statement, $matches, PREG_OFFSET_CAPTURE)) {
            return $statement;
        }

        $columns = array_map(
            static fn (string $column): string => strtolower(trim(str_replace('"', '', $column))),
            explode(',', $matches[1][0]),
        );
        $booleanIndexes = [];

        foreach ($columns as $index => $column) {
            if (in_array($column, $booleanColumns[$table], true)) {
                $booleanIndexes[$index] = true;
            }
        }

        $valuesOffset = stripos($statement, 'VALUES') + strlen('VALUES');
        $prefix = substr($statement, 0, $valuesOffset);
        $values = substr($statement, $valuesOffset);
        $result = '';
        $token = '';
        $rowDepth = 0;
        $columnIndex = 0;
        $inString = false;

        for ($index = 0, $length = strlen($values); $index < $length; $index++) {
            $character = $values[$index];

            if ($rowDepth === 0) {
                if ($character === '(') {
                    $rowDepth = 1;
                    $columnIndex = 0;
                    $token = '';
                }

                $result .= $character;

                continue;
            }

            if ($inString) {
                $token .= $character;

                if ($character === '\\' && $index + 1 < $length) {
                    $token .= $values[++$index];

                    continue;
                }

                if ($character === "'" && ($index + 1 >= $length || $values[$index + 1] !== "'")) {
                    $inString = false;
                } elseif ($character === "'" && $index + 1 < $length && $values[$index + 1] === "'") {
                    $token .= $values[++$index];
                }

                continue;
            }

            if ($character === "'") {
                $inString = true;
                $token .= $character;

                continue;
            }

            if ($character === ',' || $character === ')') {
                $trimmed = trim($token);

                if (isset($booleanIndexes[$columnIndex]) && $trimmed === '1') {
                    $token = preg_replace('/^(\s*).*?(\s*)$/s', '$1TRUE$2', $token);
                } elseif (isset($booleanIndexes[$columnIndex]) && $trimmed === '0') {
                    $token = preg_replace('/^(\s*).*?(\s*)$/s', '$1FALSE$2', $token);
                }

                $result .= $token.$character;
                $token = '';

                if ($character === ')') {
                    $rowDepth = 0;
                } else {
                    $columnIndex++;
                }

                continue;
            }

            $token .= $character;
        }

        return $prefix.$result;
    }
}
