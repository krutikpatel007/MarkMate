@extends('layouts.app')

@section('title', 'Notice Management | SCSA Attendance')
@section('page-title', 'Notice Management')
@section('page-subtitle', 'Post and manage manual notices for your students or departments.')

@section('content')
    <div class="grid grid-2">
        <section class="card">
            <h2>Post a New Notice</h2>
            <form method="post" action="{{ route('notices.store') }}">
                @csrf
                
                <div class="field">
                    <label for="title">Notice Title</label>
                    <input type="text" id="title" name="title" required maxlength="255" placeholder="e.g. Campus closed tomorrow due to weather" value="{{ old('title') }}">
                </div>
                
                <div class="field">
                    <label for="message">Detailed Message</label>
                    <textarea id="message" name="message" required style="min-height: 100px;" placeholder="Full details of the notice...">{{ old('message') }}</textarea>
                </div>
                
                <div class="grid grid-2">
                    <div class="field">
                        <label for="type">Notice Type</label>
                        <select id="type" name="type" required>
                            <option value="info">Info (Default)</option>
                            <option value="warning">Warning / Alert</option>
                            <option value="danger">Urgent / Danger</option>
                            <option value="success">Success / Good News</option>
                        </select>
                    </div>
                    
                    <div class="field">
                        <label for="audience_type">Target Audience</label>
                        <select id="audience_type" name="audience_type" required onchange="toggleAudienceDropdown()">
                            <option value="">Select Audience...</option>
                            @if(auth()->user()->isAdmin())
                                <option value="global">Global (All Users)</option>
                            @endif
                            @if(auth()->user()->isFeesDept())
                                <option value="global">All Students</option>
                            @endif
                            @if(auth()->user()->isAdmin() || auth()->user()->isHod())
                                <option value="department">All in Department</option>
                                <option value="department_faculty">Faculty of Department</option>
                                <option value="department_students">Students of Department</option>
                            @endif
                            @if(auth()->user()->isFaculty())
                                <option value="class">Specific Class Section</option>
                            @endif
                        </select>
                    </div>
                </div>
                
                <div class="field" id="audience_id_container" style="display: none;">
                    <label id="audience_id_label" for="audience_id">Select Target</label>
                    <select id="audience_id" name="audience_id">
                        <!-- Populated by JS -->
                    </select>
                </div>
                
                <div class="actions" style="margin-top: 1rem;">
                    <button class="button" type="submit">Post Notice</button>
                </div>
            </form>
        </section>
        
        <section class="card">
            <h2>Notices Published By You</h2>
            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Notice</th>
                            <th>Audience</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notices as $notice)
                            <tr class="list-divider">
                                <td style="white-space: nowrap;">{{ $notice->created_at->format('d M Y') }}</td>
                                <td>
                                    <strong>{{ $notice->title }}</strong>
                                    <span class="badge {{ $notice->type === 'danger' ? 'danger' : ($notice->type === 'warning' ? 'warning' : ($notice->type === 'success' ? 'success' : '')) }}">{{ ucfirst($notice->type) }}</span>
                                </td>
                                <td>
                                    @if($notice->audience_type === 'department')
                                        <span>All in Department</span>
                                    @elseif($notice->audience_type === 'department_faculty')
                                        <span>Faculty of Dept</span>
                                    @elseif($notice->audience_type === 'department_students')
                                        <span>Students of Dept</span>
                                    @else
                                        <span style="text-transform: capitalize;">{{ $notice->audience_type }}</span>
                                    @endif
                                    
                                    @if($notice->audience_type !== 'global')
                                        @php
                                            $audienceName = 'ID: ' . $notice->audience_id;
                                            if (in_array($notice->audience_type, ['department', 'department_faculty', 'department_students'])) {
                                                $dept = \App\Models\Department::find($notice->audience_id);
                                                if ($dept) {
                                                    $audienceName = $dept->department_code;
                                                }
                                            } elseif ($notice->audience_type === 'class') {
                                                $cls = \App\Models\ClassSection::find($notice->audience_id);
                                                if ($cls) {
                                                    $audienceName = $cls->display_name;
                                                }
                                            }
                                        @endphp
                                        <div class="muted">{{ $audienceName }}</div>
                                    @endif
                                </td>
                                <td>
                                    <form method="post" action="{{ route('notices.destroy', $notice) }}" onsubmit="return confirm('Remove this notice?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="button danger" type="submit" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; min-height: unset;">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="muted" style="text-align: center; padding: 2rem 0;">You haven't posted any notices.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    
    <script>
        const departments = @json($departments);
        const classes = @json($classes);
        
        function toggleAudienceDropdown() {
            const type = document.getElementById('audience_type').value;
            const container = document.getElementById('audience_id_container');
            const select = document.getElementById('audience_id');
            const label = document.getElementById('audience_id_label');
            
            select.innerHTML = '';
            
            if (type === 'global' || !type) {
                container.style.display = 'none';
                select.required = false;
            } else if (type === 'department' || type === 'department_faculty' || type === 'department_students') {
                container.style.display = 'block';
                label.innerText = 'Select Department';
                select.required = true;
                
                select.innerHTML = '<option value="">Choose department...</option>';
                departments.forEach(dept => {
                    select.innerHTML += `<option value="${dept.id}">${dept.department_name}</option>`;
                });
            } else if (type === 'class') {
                container.style.display = 'block';
                label.innerText = 'Select Class Section';
                select.required = true;
                
                select.innerHTML = '<option value="">Choose class section...</option>';
                classes.forEach(cls => {
                    select.innerHTML += `<option value="${cls.id}">${cls.display_name}</option>`;
                });
            }
        }
    </script>
@endsection
