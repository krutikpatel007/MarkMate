<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:backup {--label= : Optional label appended to the filename}';

    /**
     * The console command description.
     */
    protected $description = 'Create a full MySQL dump of the attendance database';

    public function handle(): int
    {
        $backupDir = 'D:\\Attendance\\backups';

        // Ensure backup directory exists
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
            $this->line("  Created backup directory: {$backupDir}");
        }

        // Build filename
        $label     = $this->option('label') ? '_' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $this->option('label')) : '';
        $timestamp = now()->format('Y-m-d_H-i');
        $filename  = "attendance_{$timestamp}{$label}.sql";
        $filepath  = $backupDir . '\\' . $filename;

        // Read DB config
        $host     = config('database.connections.mysql.host', '127.0.0.1');
        $port     = config('database.connections.mysql.port', '3306');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

        $this->info('  Creating database backup...');

        // Build the full command — write output directly to file via cmd /c redirect
        $passwordArg = $password ? "--password=\"{$password}\"" : '';
        $cmd = sprintf(
            'cmd /c ""%s" --host=%s --port=%s --user=%s %s --single-transaction --routines --triggers %s > "%s" 2>&1"',
            $mysqldump,
            $host,
            $port,
            $username,
            $passwordArg,
            $database,
            $filepath
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($filepath) || filesize($filepath) < 100) {
            $this->error('  ✗ Backup FAILED!');
            if (!empty($output)) {
                $this->line('  Error: ' . implode(' ', $output));
            }
            $this->logBackup($backupDir, $filename, false);
            return self::FAILURE;
        }

        $sizeKb = round(filesize($filepath) / 1024, 1);

        $this->info("  ✓ Backup saved: {$filepath}");
        $this->info("  ✓ File size   : {$sizeKb} KB");

        // Auto-delete backups older than 30 days
        $deleted = $this->pruneOldBackups($backupDir, 30);
        if ($deleted > 0) {
            $this->line("  ℹ Pruned {$deleted} backup(s) older than 30 days.");
        }

        $this->logBackup($backupDir, $filename, true, $sizeKb);

        return self::SUCCESS;
    }

    /**
     * Delete backups older than $days days. Returns count deleted.
     */
    private function pruneOldBackups(string $dir, int $days): int
    {
        $cutoff  = now()->subDays($days)->timestamp;
        $deleted = 0;

        foreach (glob($dir . '\\attendance_*.sql') as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Append a line to backup.log.
     */
    private function logBackup(string $dir, string $filename, bool $success, float $sizeKb = 0): void
    {
        $status  = $success ? 'SUCCESS' : 'FAILED';
        $logLine = sprintf(
            "[%s] %s | %s | %.1f KB\n",
            now()->format('Y-m-d H:i:s'),
            $status,
            $filename,
            $sizeKb,
        );
        file_put_contents($dir . '\\backup.log', $logLine, FILE_APPEND | LOCK_EX);
    }
}
