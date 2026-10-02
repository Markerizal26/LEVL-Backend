<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DiagnoseStorageBucket extends Command
{
    protected $signature = 'storage:diagnose
                          {--disk= : Disk to check, defaulting to the configured media disk}
                          {--test-upload : Write and remove a temporary local file}';

    protected $description = 'Diagnose local VPS media storage configuration and connectivity';

    public function handle(): int
    {
        $disk = (string) ($this->option('disk') ?: config('media-library.disk_name', 'public'));

        $this->info('=== Local Storage Diagnostics ===');
        $this->newLine();

        $this->checkEnvironmentConfig($disk);
        $this->newLine();
        $this->checkDatabaseMedia($disk);
        $this->newLine();
        $this->checkLocalDisk($disk);
        $this->newLine();

        if ($this->option('test-upload')) {
            $this->testFileUpload($disk);
            $this->newLine();
        }

        $this->info('=== Diagnostics Complete ===');

        return self::SUCCESS;
    }

    protected function checkEnvironmentConfig(string $disk): void
    {
        $this->info('Environment Configuration:');
        $this->line('──────────────────────────');
        $this->line('FILESYSTEM_DISK: '.(env('FILESYSTEM_DISK') ?: '<not set>'));
        $this->line('MEDIA_DISK: '.(env('MEDIA_DISK') ?: '<not set>'));
        $this->line('Selected disk: '.$disk);
        $this->line('Disk driver: '.(config("filesystems.disks.{$disk}.driver") ?: '<not configured>'));
        $this->line('Disk root: '.(config("filesystems.disks.{$disk}.root") ?: '<not applicable>'));
        $this->line('Max regular file: '.((int) config('uploads.max_file_size_kb', 2048) / 1024).' MB');
        $this->line('Max video file: '.((int) config('uploads.max_video_size_kb', 51200) / 1024).' MB');
    }

    protected function checkDatabaseMedia(string $disk): void
    {
        $this->info('Database Media Records:');
        $this->line('────────────────────────');

        try {
            $totalMedia = DB::table('media')->count();
            $this->line("Total media records: {$totalMedia}");

            $byDisk = DB::table('media')
                ->select('disk', DB::raw('count(*) as count'))
                ->groupBy('disk')
                ->get();

            foreach ($byDisk as $record) {
                $this->line("  - {$record->disk}: {$record->count} files");
            }

            $legacyDiskCount = DB::table('media')->whereNotIn('disk', [$disk])->count();
            if ($legacyDiskCount > 0) {
                $this->warn("Media records still point to another disk: {$legacyDiskCount}");
            }

            $this->line('Configured media disk: '.$disk);
        } catch (\Throwable $exception) {
            $this->error('Database media check failed: '.$exception->getMessage());
        }
    }

    protected function checkLocalDisk(string $disk): void
    {
        $this->info('Local Disk Check:');
        $this->line('─────────────────');

        $filesystem = config("filesystems.disks.{$disk}");
        if (! is_array($filesystem)) {
            $this->error("Disk '{$disk}' is not configured.");
            return;
        }

        if (($filesystem['driver'] ?? null) !== 'local') {
            $this->warn("Disk '{$disk}' is not using the local driver.");
        }

        $root = $filesystem['root'] ?? null;
        if (is_string($root) && is_dir($root)) {
            $this->line('<fg=green>✓</> Storage root exists: '.$root);
            $this->line('  Writable: '.(is_writable($root) ? 'yes' : 'no'));
            $files = Storage::disk($disk)->allFiles();
            $this->line('  Files: '.count($files));
        } else {
            $this->error('Storage root does not exist: '.($root ?: '<empty>'));
        }
    }

    protected function testFileUpload(string $disk): void
    {
        $this->info('Local Test Upload:');
        $this->line('──────────────────');

        $path = 'diagnostics/test-'.now()->timestamp.'.txt';
        $content = 'LEVL local storage diagnostic';

        try {
            Storage::disk($disk)->put($path, $content);
            $verified = Storage::disk($disk)->get($path) === $content;
            Storage::disk($disk)->delete($path);

            if ($verified) {
                $this->line('<fg=green>✓</> Write, read, and delete succeeded.');
            } else {
                $this->error('File content verification failed.');
            }
        } catch (\Throwable $exception) {
            $this->error('Local storage test failed: '.$exception->getMessage());
        }
    }
}
