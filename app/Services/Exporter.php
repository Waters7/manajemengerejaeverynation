<?php

namespace App\Services;

use App\Enums\AuditAction;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams tabular data as Excel (.xlsx) or CSV. Every export is written to the audit log.
 */
class Exporter
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  list<string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public function download(string $name, string $format, array $headers, iterable $rows): StreamedResponse
    {
        $format = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = Str::slug($name).'.'.$format;

        $this->audit->log(AuditAction::Export, null, "Exported {$filename}");

        return response()->streamDownload(function () use ($format, $headers, $rows) {
            $writer = $format === 'csv' ? new CsvWriter : new XlsxWriter;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValuesWithStyle($headers, new Style(fontBold: true)));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues(array_map(
                    fn ($value) => $value instanceof \BackedEnum ? (method_exists($value, 'label') ? $value->label() : $value->value) : $value,
                    array_values($row),
                )));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => $format === 'csv'
                ? 'text/csv; charset=UTF-8'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
