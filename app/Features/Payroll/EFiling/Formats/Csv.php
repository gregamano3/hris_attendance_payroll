<?php

namespace App\Features\Payroll\EFiling\Formats;

final class Csv
{
    /**
     * @param  list<list<string|float|int|null>>  $rows
     */
    public static function build(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $contents = (string) stream_get_contents($handle);
        fclose($handle);

        return $contents;
    }
}
