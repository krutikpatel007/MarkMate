@extends('layouts.app')

@section('title', 'Defaulter System | SCSA Attendance')
@section('page-title', 'Defaulter Management')
@section('page-subtitle', 'Automatically identify students below 75% attendance, generate warning letters, and notify parents.')

@section('content')
    <div class="grid grid-1">
        <section class="card">
            <h2>Students Below 75% University Threshold</h2>
            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table>
                    <thead>
                    <tr>
                        <th>Student</th>
                        <th>Class Section</th>
                        <th>Program &amp; Sem</th>
                        <th>Attendance %</th>
                        <th>Last Action</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($defaulters as $row)
                        @php($student = $row->student)
                        <tr class="list-divider">
                            <td>
                                <strong>{{ $student->user->name }}</strong>
                                <div class="muted">Enrollment: {{ $student->enrollment_no }}</div>
                            </td>
                            <td>
                                <strong>{{ $student->classSection->display_name }}</strong>
                            </td>
                            <td>
                                <strong>{{ $student->program->program_code }}</strong>
                                <div class="muted">Sem {{ $student->semester->semester_no }}</div>
                            </td>
                            <td>
                                <span class="badge danger" style="font-weight: 700; font-size: 0.875rem; padding: 0.35rem 0.65rem;">
                                    {{ $row->percentage }}%
                                </span>
                            </td>
                            <td>
                                @if($alertLogs->has($student->id))
                                    @php($lastAlert = $alertLogs->get($student->id)->first())
                                    <span class="badge success">Alert Dispatched</span>
                                    <div class="muted" style="font-size: 0.75rem; margin-top: 0.15rem;">
                                        By {{ $lastAlert->new_values['notified_by'] ?? 'HOD' }}<br>
                                        {{ \Carbon\Carbon::parse($lastAlert->created_at)->format('d M Y h:i A') }}
                                    </div>
                                @else
                                    <span class="muted" style="font-style: italic;">No alerts issued yet</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <a class="button secondary" href="{{ route('defaulters.warning-letter', $student) }}" target="_blank" style="padding: 0.35rem 0.75rem; min-height: unset; font-size: 0.8125rem;">
                                        📄 Warning Letter
                                    </a>
                                    <form method="post" action="{{ route('defaulters.parent-alert', $student) }}" onsubmit="return confirm('Simulate sending official parental alert notification?');" style="margin: 0;">
                                        @csrf
                                        <button class="button" type="submit" style="padding: 0.35rem 0.75rem; min-height: unset; font-size: 0.8125rem; background: var(--color-scsa-gold); color: black;">
                                            ⚡ Send Alert
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted" style="text-align: center; padding: 3rem 0;">
                                🎉 Brilliant! No students are currently below the 75% attendance threshold.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
