<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Services\PdfParserService;
use App\Models\Routine;

class ImportRoutine extends Command
{
    protected $signature = 'import:class-routine {pdfPath}';
    protected $description = 'Import class routine from a PDF file into the database';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $pdfPath = $this->argument('pdfPath');
        $parserService = new PdfParserService();
        $schedule = $parserService->parseRoutine($pdfPath);

        foreach ($schedule as $entry) {
            Routine::create($entry);
        }

        $this->info('Class routine imported successfully!');
    }
}
