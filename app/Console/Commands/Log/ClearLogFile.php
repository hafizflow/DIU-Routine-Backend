<?php

namespace App\Console\Commands\Log;

use Illuminate\Console\Command;

class ClearLogFile extends Command
{
    // Define command name
    protected $signature = 'log:clear';

// Add description to your command
    protected $description = 'Clear Laravel log';

// Create your own custom command
    public function handle(): void
    {
        exec('echo "" > ' . storage_path('logs/laravel.log'));
        $this->info('Logs have been cleared');
    }
}
