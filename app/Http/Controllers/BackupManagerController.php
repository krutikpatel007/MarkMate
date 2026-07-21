<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesAcademicManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupManagerController extends Controller
{
    use AuthorizesAcademicManagement;

    private string $backupDir = 'D:\\Attendance\\backups';

    private function ensureAdmin(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isAdmin(), 403, 'Unauthorized access to database backups.');
    }

    public function index()
    {
        $this->ensureAdmin();

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        $files = [];
        $sqlFiles = glob($this->backupDir . '\\attendance_*.sql');
        
        if ($sqlFiles) {
            foreach ($sqlFiles as $file) {
                $files[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => round(filesize($file) / 1024, 1), // in KB
                    'created_at' => filemtime($file),
                ];
            }
        }

        // Sort files by creation time descending
        usort($files, fn($a, $b) => $b['created_at'] <=> $a['created_at']);

        // Read log if exists
        $logs = [];
        $logPath = $this->backupDir . '\\backup.log';
        if (file_exists($logPath)) {
            $logContent = file($logPath);
            if ($logContent) {
                $logs = array_reverse(array_map('trim', $logContent));
                $logs = array_slice($logs, 0, 20); // Keep last 20 entries
            }
        }

        return view('admin.backups.index', [
            'files' => $files,
            'logs' => $logs,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);

        $params = [];
        if (!empty($validated['label'])) {
            $params['--label'] = $validated['label'];
        }

        try {
            $exitCode = Artisan::call('db:backup', $params);
            
            if ($exitCode === 0) {
                return redirect()->route('admin.backups.index')->with('success', 'Database backup completed successfully.');
            }
            
            return redirect()->route('admin.backups.index')->with('error', 'Backup failed. Check logs for details.');
        } catch (\Exception $e) {
            return redirect()->route('admin.backups.index')->with('error', 'Backup encountered error: ' . $e->getMessage());
        }
    }

    public function download(string $filename): BinaryFileResponse
    {
        $this->ensureAdmin();

        $filepath = $this->backupDir . '\\' . basename($filename);
        
        if (!file_exists($filepath) || !str_ends_with($filename, '.sql')) {
            abort(404, 'Backup file not found.');
        }

        return response()->download($filepath);
    }

    public function destroy(string $filename)
    {
        $this->ensureAdmin();

        $filepath = $this->backupDir . '\\' . basename($filename);
        
        if (file_exists($filepath) && str_ends_with($filename, '.sql')) {
            unlink($filepath);
            return redirect()->route('admin.backups.index')->with('success', 'Backup file deleted.');
        }

        return redirect()->route('admin.backups.index')->with('error', 'File not found.');
    }

    public function restore(Request $request)
    {
        $this->ensureAdmin();

        $request->validate([
            'filename' => ['nullable', 'string'],
            'backup_file' => ['nullable', 'file', 'mimetypes:text/plain,application/octet-stream,application/sql'],
        ]);

        $filepath = null;
        $isTemporary = false;

        if ($request->hasFile('backup_file')) {
            $file = $request->file('backup_file');
            $tempName = 'temp_restore_' . time() . '.sql';
            $filepath = storage_path('app\\' . $tempName);
            $file->move(storage_path('app'), $tempName);
            $isTemporary = true;
        } elseif ($request->filled('filename')) {
            $filepath = $this->backupDir . '\\' . basename($request->input('filename'));
        }

        if (!$filepath || !file_exists($filepath)) {
            return redirect()->route('admin.backups.index')->with('error', 'No valid backup file was specified.');
        }

        $mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';
        $host     = config('database.connections.mysql.host', '127.0.0.1');
        $port     = config('database.connections.mysql.port', '3306');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $passwordArg = $password ? "--password=\"{$password}\"" : '';
        $cmd = sprintf(
            'cmd /c ""%s" --host=%s --port=%s --user=%s %s %s < "%s" 2>&1"',
            $mysql,
            $host,
            $port,
            $username,
            $passwordArg,
            $database,
            $filepath
        );

        exec($cmd, $output, $exitCode);

        if ($isTemporary && file_exists($filepath)) {
            unlink($filepath);
        }

        if ($exitCode === 0) {
            // Invalidate current session because data structures/users might have changed
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('success', 'Database restored successfully. Please log in again.');
        }

        $errorMessage = !empty($output) ? implode(' ', $output) : 'Unknown restore error';
        return redirect()->route('admin.backups.index')->with('error', 'Restore failed: ' . $errorMessage);
    }
}
