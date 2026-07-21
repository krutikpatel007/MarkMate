@extends('layouts.app')

@section('title', 'Audit Log Explorer | Admin Panel')
@section('page-title', 'Audit Log Explorer')
@section('page-subtitle', 'Track and investigate all system actions, modifications, and updates')

@section('content')
    <!-- Filters Panel -->
    <section class="card" style="margin-bottom: 1.5rem;">
        <h2 style="margin-bottom: 1rem;">Filter Activity Logs</h2>
        <form method="get" action="{{ route('admin.audit-logs.index') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: flex-end;">
            <div class="field" style="margin: 0;">
                <label for="filter_user_id">Performed By</label>
                <select id="filter_user_id" name="user_id">
                    <option value="">All Users</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field" style="margin: 0;">
                <label for="filter_action">Action Type</label>
                <select id="filter_action" name="action">
                    <option value="">All Actions</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" {{ request('action') == $act ? 'selected' : '' }}>
                            {{ $act }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field" style="margin: 0;">
                <label for="filter_ip_address">IP Address</label>
                <input type="text" id="filter_ip_address" name="ip_address" value="{{ request('ip_address') }}" placeholder="e.g. 127.0.0.1">
            </div>

            <div class="field" style="margin: 0;">
                <label for="filter_date_from">From Date</label>
                <input type="date" id="filter_date_from" name="date_from" value="{{ request('date_from') }}">
            </div>

            <div class="field" style="margin: 0;">
                <label for="filter_date_to">To Date</label>
                <input type="date" id="filter_date_to" name="date_to" value="{{ request('date_to') }}">
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="button" style="flex: 1; min-height: 40px;">🔍 Filter</button>
                <a href="{{ route('admin.audit-logs.index') }}" class="button secondary" style="min-height: 40px; display: inline-flex; align-items: center; justify-content: center;">Reset</a>
            </div>
        </form>
    </section>

    <!-- Logs Grid -->
    <section class="card">
        <h2>Activity Logs</h2>
        
        <div style="overflow-x: auto; margin-top: 1rem;">
            <table>
                <thead>
                <tr>
                    <th>Date &amp; Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Target Type</th>
                    <th>Target ID</th>
                    <th>IP Address</th>
                    <th style="text-align: right;">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>
                            <strong style="color: var(--color-scsa-ink);">{{ $log->created_at->format('d M Y') }}</strong>
                            <div class="muted" style="font-size: 0.75rem;">{{ $log->created_at->format('h:i A') }}</div>
                        </td>
                        <td>
                            <strong>{{ $log->user->name ?? 'System' }}</strong>
                            <div class="muted" style="font-size: 0.75rem;">ID: {{ $log->user_id ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <span class="badge" style="background-color: var(--bg-secondary); border: 1px solid var(--color-scsa-line); color: var(--color-scsa-accent); font-weight: 700;">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td><code style="font-family: monospace; font-size: 0.85rem;">{{ basename(str_replace('\\', '/', $log->entity_type)) }}</code></td>
                        <td><code>#{{ $log->entity_id }}</code></td>
                        <td><code style="font-family: monospace;">{{ $log->ip_address }}</code></td>
                        <td style="text-align: right;">
                            <button type="button" class="button secondary view-diff-btn" data-id="{{ $log->id }}" style="font-size: 0.75rem; padding: 0.4rem 0.75rem; min-height: unset;">
                                👁️ View Changes
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted" style="text-align: center; padding: 2.5rem 0;">No matching audit logs found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1.5rem;">
            {{ $logs->links() }}
        </div>
    </section>

    <!-- Side Drawer for Changes Diff -->
    <div id="diff-drawer" style="position: fixed; top: 0; right: -500px; width: 500px; height: 100vh; background: #ffffff; box-shadow: -4px 0 15px rgba(0,0,0,0.15); z-index: 1000; transition: right 0.3s ease; display: flex; flex-direction: column;">
        <div style="padding: 1.25rem; border-bottom: 1px solid var(--color-scsa-line); display: flex; justify-content: space-between; align-items: center; background-color: var(--color-scsa-accent); color: #ffffff;">
            <h3 style="margin: 0; font-size: 1.1rem; color: #ffffff;">Transaction Details</h3>
            <button type="button" id="close-drawer-btn" style="background: transparent; border: none; font-size: 1.5rem; cursor: pointer; color: #ffffff; line-height: 1;">&times;</button>
        </div>
        <div id="drawer-loading" style="padding: 2rem; text-align: center; display: none;">
            <div class="muted">Loading audit details...</div>
        </div>
        <div id="drawer-content" style="padding: 1.5rem; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 1.25rem;">
            <!-- Meta details -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.85rem; border-bottom: 1px solid var(--color-scsa-line); padding-bottom: 1rem;">
                <div><span class="muted">Performed By:</span> <strong id="meta-user"></strong></div>
                <div><span class="muted">IP Address:</span> <code id="meta-ip"></code></div>
                <div><span class="muted">Action:</span> <strong id="meta-action"></strong></div>
                <div><span class="muted">Timestamp:</span> <span id="meta-time"></span></div>
                <div><span class="muted">Entity Class:</span> <code id="meta-entity"></code></div>
                <div><span class="muted">Entity ID:</span> <code id="meta-entity-id"></code></div>
            </div>

            <!-- Diff Table -->
            <div>
                <h4 style="margin-bottom: 0.75rem;">State Changes</h4>
                <div style="overflow-x: auto;">
                    <table style="font-size: 0.825rem;">
                        <thead>
                        <tr style="background-color: var(--bg-secondary);">
                            <th>Attribute</th>
                            <th style="background-color: #fee2e2; color: #991b1b;">Before Value</th>
                            <th style="background-color: #dcfce7; color: #166534;">After Value</th>
                        </tr>
                        </thead>
                        <tbody id="diff-rows">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Drawer Backdrop overlay -->
    <div id="drawer-overlay" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.3); z-index: 999; display: none;"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const drawer = document.getElementById('diff-drawer');
            const overlay = document.getElementById('drawer-overlay');
            const closeBtn = document.getElementById('close-drawer-btn');
            const loading = document.getElementById('drawer-loading');
            const content = document.getElementById('drawer-content');
            const diffRows = document.getElementById('diff-rows');

            // Meta tags
            const metaUser = document.getElementById('meta-user');
            const metaIp = document.getElementById('meta-ip');
            const metaAction = document.getElementById('meta-action');
            const metaTime = document.getElementById('meta-time');
            const metaEntity = document.getElementById('meta-entity');
            const metaEntityId = document.getElementById('meta-entity-id');

            function openDrawer() {
                drawer.style.right = '0';
                overlay.style.display = 'block';
            }

            function closeDrawer() {
                drawer.style.right = '-500px';
                overlay.style.display = 'none';
            }

            closeBtn.addEventListener('click', closeDrawer);
            overlay.addEventListener('click', closeDrawer);

            document.querySelectorAll('.view-diff-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const logId = this.dataset.id;
                    
                    // Show loader
                    content.style.display = 'none';
                    loading.style.display = 'block';
                    openDrawer();

                    fetch(`/admin/audit-logs/${logId}`)
                        .then(res => res.json())
                        .then(data => {
                            loading.style.display = 'none';
                            content.style.display = 'flex';

                            // Populate meta details
                            metaUser.textContent = data.user;
                            metaIp.textContent = data.ip_address;
                            metaAction.textContent = data.action;
                            metaTime.textContent = data.created_at;
                            metaEntity.textContent = data.entity_type;
                            metaEntityId.textContent = '#' + data.entity_id;

                            // Parse and build Diff Rows
                            diffRows.innerHTML = '';
                            const oldVals = data.old_values || {};
                            const newVals = data.new_values || {};

                            const allKeys = Array.from(new Set([...Object.keys(oldVals), ...Object.keys(newVals)]));

                            if (allKeys.length === 0) {
                                diffRows.innerHTML = '<tr><td colspan="3" class="muted" style="text-align:center;">No direct fields changed (empty payload)</td></tr>';
                                return;
                            }

                            allKeys.forEach(k => {
                                // Skip common Laravel timestamp fields if they clutter
                                if (k === 'updated_at' || k === 'created_at') return;

                                const tr = document.createElement('tr');
                                
                                const tdKey = document.createElement('td');
                                tdKey.innerHTML = `<strong>${k}</strong>`;
                                
                                const tdOld = document.createElement('td');
                                tdOld.style.backgroundColor = '#fef2f2';
                                tdOld.style.color = '#b91c1c';
                                tdOld.style.fontFamily = 'monospace';
                                tdOld.textContent = formatValue(oldVals[k]);
                                
                                const tdNew = document.createElement('td');
                                tdNew.style.backgroundColor = '#fecaca'; // Default fallback, but let's color it green for new changes
                                tdNew.style.backgroundColor = '#ecfdf5';
                                tdNew.style.color = '#059669';
                                tdNew.style.fontFamily = 'monospace';
                                tdNew.textContent = formatValue(newVals[k]);

                                tr.appendChild(tdKey);
                                tr.appendChild(tdOld);
                                tr.appendChild(tdNew);
                                diffRows.appendChild(tr);
                            });
                        })
                        .catch(err => {
                            loading.style.display = 'none';
                            diffRows.innerHTML = '<tr><td colspan="3" class="muted" style="text-align:center; color:red;">Failed to retrieve transaction data.</td></tr>';
                        });
                });
            });

            function formatValue(val) {
                if (val === null || val === undefined) return 'NULL';
                if (typeof val === 'object') return JSON.stringify(val);
                if (typeof val === 'boolean') return val ? 'TRUE' : 'FALSE';
                return String(val);
            }
        });
    </script>
@endsection
