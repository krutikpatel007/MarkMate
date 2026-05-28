@extends('layouts.app')

@section('title', 'Faculty Leave Approvals | SCSA Attendance')
@section('page-title', 'Faculty Leave Approvals')
@section('page-subtitle', 'Review and approve/reject faculty leave requests.')

@section('content')
    <div class="grid grid-1">
        <!-- Pending Leave Requests -->
        <section class="card">
            <h2>Pending Faculty Leave Applications</h2>
            @php($pending = $requests->where('status', 'pending'))
            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table>
                    <thead>
                    <tr>
                        <th>Faculty</th>
                        <th>Department</th>
                        <th>Date Range</th>
                        <th>Days</th>
                        <th>Reason & Actions</th>
                        <th>Attachment</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($pending as $req)
                        <tr class="list-divider">
                            <td>
                                <strong>{{ $req->faculty->user->name }}</strong>
                            </td>
                            <td>
                                <strong>{{ optional($req->faculty->department)->department_name ?? 'N/A' }}</strong>
                            </td>
                            <td style="white-space: nowrap;">
                                <strong>{{ $req->start_date->format('d M Y') }}</strong>
                                <div class="muted">to {{ $req->end_date->format('d M Y') }}</div>
                            </td>
                            <td>
                                @php($days = $req->start_date->diffInDays($req->end_date) + 1)
                                {{ $days }} {{ Str::plural('Day', $days) }}
                            </td>
                            <td>
                                <div style="max-width: 20rem; overflow-wrap: break-word; white-space: normal; margin-bottom: 0.75rem;">
                                    {{ $req->reason }}
                                </div>
                                <div style="border-top: 1px dashed var(--color-scsa-line); padding-top: 0.5rem; margin-top: 0.5rem;">
                                    <form method="post" action="{{ route('leaves.faculty.hod.decide', $req) }}" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="decision_note" placeholder="Write an optional note..." style="width: clamp(14rem, 40vw, 24rem); padding: 0.4rem 0.6rem; font-size: 0.8125rem;">
                                        <button class="button" type="submit" name="status" value="approved" style="min-height: unset; padding: 0.4rem 0.875rem; font-size: 0.8125rem;">Approve</button>
                                        <button class="button danger" type="submit" name="status" value="rejected" style="min-height: unset; padding: 0.4rem 0.875rem; font-size: 0.8125rem;">Reject</button>
                                    </form>
                                </div>
                            </td>
                            <td>
                                @if($req->attachment_path)
                                    <a href="{{ asset('storage/' . $req->attachment_path) }}" target="_blank" class="button secondary" style="padding: 0.25rem 0.5rem; min-height: unset; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                        <span>Download</span>
                                    </a>
                                @else
                                    <span class="muted">No Proof Uploaded</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted" style="text-align: center; padding: 2rem 0;">No pending faculty leave applications.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Decided Leave History -->
        <section class="card" style="margin-top: 1rem;">
            <h2>Decided Faculty Leave History</h2>
            @php($decided = $requests->where('status', '!=', 'pending'))
            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table>
                    <thead>
                    <tr>
                        <th>Faculty</th>
                        <th>Department</th>
                        <th>Date Range</th>
                        <th>Days</th>
                        <th>Details</th>
                        <th>Attachment</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($decided as $req)
                        <tr class="list-divider">
                            <td>
                                <strong>{{ $req->faculty->user->name }}</strong>
                            </td>
                            <td>
                                <strong>{{ optional($req->faculty->department)->department_name ?? 'N/A' }}</strong>
                            </td>
                            <td style="white-space: nowrap;">
                                <strong>{{ $req->start_date->format('d M Y') }}</strong>
                                <div class="muted">to {{ $req->end_date->format('d M Y') }}</div>
                            </td>
                            <td>
                                @php($days = $req->start_date->diffInDays($req->end_date) + 1)
                                {{ $days }} {{ Str::plural('Day', $days) }}
                            </td>
                            <td>
                                <div style="max-width: 20rem; overflow-wrap: break-word; white-space: normal;">
                                    <div class="muted" style="font-size: 0.75rem; margin-bottom: 0.25rem;">Faculty Reason:</div>
                                    {{ $req->reason }}
                                </div>
                                @if($req->decision_note)
                                    <div style="margin-top: 0.5rem; padding: 0.4rem 0.6rem; background: #f8fafc; border-left: 3px solid #cbd5e1; border-radius: 4px; font-size: 0.8125rem;">
                                        <strong>Decision Note:</strong> {{ $req->decision_note }}
                                        <div class="muted" style="font-size: 0.75rem; margin-top: 0.15rem;">By {{ $req->approver?->name }} on {{ $req->approved_at?->format('d M Y h:i A') }}</div>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($req->attachment_path)
                                    <a href="{{ asset('storage/' . $req->attachment_path) }}" target="_blank" class="button secondary" style="padding: 0.25rem 0.5rem; min-height: unset; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                        <span>Download</span>
                                    </a>
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $req->status === 'approved' ? 'success' : 'danger' }}">
                                    {{ $req->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="muted" style="text-align: center; padding: 2rem 0;">No completed requests in history.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
