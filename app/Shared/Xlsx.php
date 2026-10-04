<?php

namespace App\Shared;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams an .xlsx download built with OpenSpout (low memory, no PHP extensions).
 */
final class Xlsx
{
    /**
     * @param  list<string>  $header
     * @param  iterable<array<int, string|int|float|null>>  $rows
     */
    public static function download(string $filename, array $header, iterable $rows, string $sheet = 'Sheet1'): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows, $sheet) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->getCurrentSheet()->setName(mb_substr($sheet, 0, 31));
            $writer->addRow(Row::fromValuesWithStyle($header, (new Style)->withFontBold(true)));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues(array_values($row)));
            }

            $writer->close();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
