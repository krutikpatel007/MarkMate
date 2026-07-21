@extends('layouts.app')

@section('title', 'Database Backups | Admin Panel')
@section('page-title', 'Database Backups')
@section('page-subtitle', 'Generate, download, delete, and restore database checkpoints')

@section('content')
    @if(session('success'))
        <div style="background-color: var(--color-scsa-success-light, #ecfdf5); color: var(--color-scsa-success, #059669); border: 1px solid var(--color-scsa-success, #059669); padding: 1rem; border-radius: var(--border-radius-md); margin-bottom: 1.5rem; font-weight: 500;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background-color: #fef2f2; color: var(--color-scsa-danger, #dc2626); border: 1px solid var(--color-scsa-danger, #dc2626); padding: 1rem; border-radius: var(--border-radius-md); margin-bottom: 1.5rem; font-weight: 500;">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-3" style="align-items: start;">
        <!-- Backup Actions -->
        <div style="grid-column: span 2; display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Trigger Backup Form -->
            <section class="card">
                <h2>Generate New Checkpoint</h2>
                <p class="muted" style="margin-bottom: 1.25rem;">Create a fresh backup of the database containing all tables, triggers, and stored procedures.</p>
                <form method="post" action="{{ route('admin.backups.create') }}" style="display: flex; align-items: flex-end; gap: 1rem; flex-wrap: wrap;">
                    @csrf
                    <div class="field" style="flex: 1; min-width: 200px; margin: 0;">
                        <label for="label" style="font-weight: 600;">Optional Checkpoint Label</label>
                        <input type="text" id="label" name="label" placeholder="e.g. before-upgrade, after-migration" pattern="^[a-zA-Z0-9_-]+$" title="Only letters, numbers, underscores, and dashes allowed.">
                    </div>
                    <button type="submit" class="button" style="min-height: 42px;">
                        💾 Backup Now
                    </button>
                </form>
            </section>

            <!-- Backups List -->
            <section class="card">
                <h2>Available Database Backups</h2>
                <p class="muted" style="margin-bottom: 1rem;">Backups are saved locally on the server under <code>D:\Attendance\backups\</code>.</p>
                
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Size</th>
                            <th>Created Date</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($files as $file)
                            <tr>
                                <td>
                                    <strong style="color: var(--color-scsa-ink); font-family: monospace;">{{ $file['name'] }}</strong>
                                </td>
                                <td>{{ $file['size'] }} KB</td>
                                <td>{{ date('d M Y, h:i A', $file['created_at']) }}</td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="{{ route('admin.backups.download', $file['name']) }}" class="button secondary" style="font-size: 0.75rem; padding: 0.4rem 0.75rem; min-height: unset;">
                                        📥 Download
                                    </a>
                                    
                                    <form method="post" action="{{ route('admin.backups.restore') }}" style="display: inline;" onsubmit="return confirm('WARNING: This will restore the database to this checkpoint. All unsaved changes after this backup will be lost. You will be logged out. Continue?');">
                                        @csrf
                                        <input type="hidden" name="filename" value="{{ $file['name'] }}">
                                        <button type="submit" class="button" style="font-size: 0.75rem; padding: 0.4rem 0.75rem; min-height: unset; background-color: var(--color-scsa-gold); border-color: var(--color-scsa-gold);">
                                            🔄 Restore
                                        </button>
                                    </form>

                                    <form method="post" action="{{ route('admin.backups.destroy', $file['name']) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to permanently delete this backup file?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="button danger" style="font-size: 0.75rem; padding: 0.4rem 0.75rem; min-height: unset;">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="muted" style="text-align: center; padding: 2rem 0;">No backup files found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Sidebar: Upload Restore & Logs -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Upload Restore -->
            <section class="card">
                <h2>Upload &amp; Restore</h2>
                <p class="muted" style="font-size: 0.825rem; margin-bottom: 1rem;">Restore database directly by uploading a valid <code>.sql</code> backup file.</p>
                <form method="post" action="{{ route('admin.backups.restore') }}" enctype="multipart/form-data" onsubmit="return confirm('WARNING: You are uploading and restoring a database file. This will wipe existing data. Continue?');">
                    @csrf
                    <div class="field" style="margin-bottom: 1rem;">
                        <input type="file" name="backup_file" accept=".sql" required style="font-size: 0.875rem;">
                    </div>
                    <button type="submit" class="button danger" style="width: 100%;">
                        ⚡ Upload &amp; Restore
                    </button>
                </form>
            </section>

            <!-- Backup Log -->
            <section class="card">
                <h2>Recent Backup Logs</h2>
                <p class="muted" style="font-size: 0.825rem; margin-bottom: 1rem;">Last 20 transactions from <code>backup.log</code>.</p>
                <div style="background-color: var(--bg-secondary); border: 1px solid var(--color-scsa-line); padding: 0.75rem; border-radius: var(--border-radius-sm); max-height: 250px; overflow-y: auto; font-family: monospace; font-size: 0.75rem; line-height: 1.4; color: var(--color-scsa-muted);">
                    @forelse($logs as $log)
                        <div style="padding: 0.25rem 0; border-bottom: 1px solid rgba(0,0,0,0.05); word-break: break-all;">
                            {{ $log }}
                        </div>
                    @empty
                        <span class="muted">No log entries found.</span>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
