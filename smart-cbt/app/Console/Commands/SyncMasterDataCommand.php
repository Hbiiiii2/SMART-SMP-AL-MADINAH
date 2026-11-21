<?php

namespace App\Console\Commands;

use App\Services\Integration\MasterDataSyncService;
use Illuminate\Console\Command;

class SyncMasterDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cbt:sync-master-data {--url= : Custom SMART+ API Base URL} {--token= : Custom API Bearer Token}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Classes and Students Master Data from SMART+ Core System';

    /**
     * Execute the console command.
     */
    public function handle(MasterDataSyncService $syncService): int
    {
        $this->info('Starting Master Data Synchronization from SMART+...');

        $url = $this->option('url');
        $token = $this->option('token');

        $result = $syncService->syncFromSmartPlus($url, $token);

        $this->line(" - Classes Synced : <info>{$result['classes_synced']}</info>");
        $this->line(" - Students Synced: <info>{$result['students_synced']}</info>");

        if (! empty($result['errors'])) {
            $this->warn('Warnings/Errors encountered during synchronization:');
            foreach ($result['errors'] as $error) {
                $this->error("  * {$error}");
            }
            return self::FAILURE;
        }

        $this->info('Master data synchronization completed successfully.');
        return self::SUCCESS;
    }
}
