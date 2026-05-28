@extends('layouts.app')

@section('title', 'Staff Users | SCSA Attendance')
@section('page-title', 'Staff Users')
@section('page-subtitle')
    @if(auth()->user()->isAdmin())
        Admin can add or remove HOD and faculty accounts.
    @else
        HOD can add or remove faculty accounts for their department.
    @endif
@endsection

@section('page-actions')
    <div class="actions">
        <a class="button secondary" href="{{ route('staff.import.create') }}">Bulk upload</a>
        <a class="button" href="{{ route('staff.create') }}">New Staff User</a>
    </div>
@endsection

@section('content')
    <div class="card">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Login</th>
                <th>Role</th>
                <th>Department</th>
                <th>Employee Code</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($staffUsers as $staff)
                <tr>
                    <td>
                        {{ $staff->name }}
                        @if($staff->email)
                            <div class="muted">{{ $staff->email }}</div>
                        @endif
                    </td>
                    <td>{{ $staff->username }}</td>
                    <td><span class="badge">{{ $staff->role }}</span></td>
                    <td>{{ $staff->facultyProfile?->department?->department_name ?? 'Not assigned' }}</td>
                    <td>{{ $staff->facultyProfile?->employee_code ?? 'Not assigned' }}</td>
                    <td>
                        <span class="badge {{ $staff->status === 'active' ? 'success' : 'warning' }}">
                            {{ $staff->status }}
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <a class="button secondary" href="{{ route('staff.edit', $staff) }}">Edit</a>

                            @if(!auth()->user()->is($staff))
                                @if($staff->status === 'active')
                                    <form method="post" action="{{ route('staff.status', $staff) }}" onsubmit="return confirm('Remove this staff user from active use?');">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="inactive">
                                        <button class="button danger" type="submit">Remove</button>
                                    </form>
                                @else
                                    <form method="post" action="{{ route('staff.status', $staff) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="active">
                                        <button class="button" type="submit">Reactivate</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No staff users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
