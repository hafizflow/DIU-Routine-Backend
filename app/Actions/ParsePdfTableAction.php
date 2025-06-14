<?php

namespace App\Actions;

class ParsePdfTableAction
{
    public function execute($path)
    {
        $jarFilePath = base_path('tabula/tabula.jar');
        $csvData = shell_exec("java -jar {$jarFilePath} -p all -f CSV $path");

        // parse CSV data into an array
        $lines = explode("\n", trim($csvData));
        $table = [];

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (count($row) > 1) {
                $table[] = $row;
            }
        }

        return $table;
    }
}
