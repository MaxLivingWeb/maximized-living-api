<?php

namespace App\Helpers;

class ExportHelper
{
    /**
     * Exports an array of data as a .csv file.
     *
     * @param $data The array of data to export as a .csv file.
     * @param $filename The name of the file to export.
     * @param \stdClass $dateObject An generic object containing a startDate and endDate.
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportCsv($data, $filename, $dateObject = NULL)
    {
        $fileDate = (!empty($dateObject)
            ? $dateObject->startDate->format('Y-m-d') . '_to_' . $dateObject->endDate->format('Y-m-d') . '_'
            : date('Y-m-d') . '_');

        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $fileDate . $filename . '.csv',
            'Expires'             => '0',
            'Pragma'              => 'public'
        ];

        $callback = function() use ($data)
        {
            $csv = fopen('php://output', 'wb');
            foreach ($data as $row) {
                fputcsv($csv, $row);
            }
            fclose($csv);
        };

        return response()->stream($callback, 200, $headers);
    }
}
