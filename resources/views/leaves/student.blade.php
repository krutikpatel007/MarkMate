@extends('layouts.app')

@section('title', 'Leave Applications | SCSA Attendance')
@section('page-title', 'Leave Applications')
@section('page-subtitle', 'Apply and track your academic leave requests.')

@section('content')
    <div class="grid grid-2">
        <!-- Leave Application Form -->
        <section class="card">
            <h2>Apply for Leave</h2>
            <form method="post" action="{{ route('leaves.student.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="grid grid-2">
                    <div class="field">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" required value="{{ old('start_date') }}">
                    </div>
                    <div class="field">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" required value="{{ old('end_date') }}">
                    </div>
                </div>

                <div class="field">
                    <label for="reason">Reason for Leave</label>
                    <textarea id="reason" name="reason" required placeholder="Explain why you need leave..." style="min-height: 8rem;">{{ old('reason') }}</textarea>
                </div>

                <div class="field">
                    <label for="attachment">Supporting Attachment (Optional)</label>
                    <input type="file" id="attachment" name="attachment" accept=".pdf,.png,.jpg,.jpeg">
                    <div class="muted" style="font-size: 0.75rem; margin-top: 0.25rem;">Accepted formats: PDF, PNG, JPG, JPEG (Max 5MB)</div>
                </div>

                <div class="actions" style="margin-top: 1rem;">
                    <button class="button" type="submit">Submit Leave Request</button>
                </div>
            </form>
        </section>

        <!-- Leave History -->
        <section class="card">
            <h2>Leave Application History</h2>
            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table>
                    <thead>
                    <tr>
                        <th>Date Range</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Attachment</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($requests as $req)
                        <tr class="list-divider" style="border-bottom: 0;">
                            <td style="white-space: nowrap;">
                                <strong>{{ $req->start_date->format('d M Y') }}</strong>
                                <div class="muted">to {{ $req->end_date->format('d M Y') }}</div>
                            </td>
                            <td>
                                @php($days = $req->start_date->diffInDays($req->end_date) + 1)
                                {{ $days }} {{ Str::plural('Day', $days) }}
                            </td>
                            <td>
                                <div style="max-width: 14rem; overflow-wrap: break-word; white-space: normal;">
                                    {{ $req->reason }}
                                </div>
                                @if($req->decision_note)
                                    <div style="margin-top: 0.5rem; padding: 0.4rem 0.6rem; background: #f8fafc; border-left: 3px solid #cbd5e1; border-radius: 4px; font-size: 0.8125rem;">
                                        <strong>Decision Note:</strong> {{ $req->decision_note }}
                                        <div class="muted" style="font-size: 0.75rem; margin-top: 0.15rem;">By {{ $req->approver?->name }}</div>
                                    </div>
                                @endif
                            </td>
                            <td style="vertical-align: middle;">
                                @if($req->attachment_path)
                                    <a href="{{ asset('storage/' . $req->attachment_path) }}" target="_blank" class="button secondary" style="padding: 0.25rem 0.5rem; min-height: unset; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                        <span>Download</span>
                                    </a>
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                            <td style="vertical-align: middle;">
                                <span class="badge {{ $req->status === 'approved' ? 'success' : ($req->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ $req->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted" style="text-align: center; padding: 2rem 0;">You have not applied for any leaves yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
