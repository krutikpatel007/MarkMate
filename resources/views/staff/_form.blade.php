<div class="grid grid-2">
    <div class="field">
        <label for="name">Name</label>
        <input id="name" name="name" value="{{ old('name', $staff?->name) }}" required>
    </div>

    <div class="field">
        <label for="username">Login username</label>
        <input id="username" name="username" value="{{ old('username', $staff?->username) }}" required>
    </div>
</div>

<div class="grid grid-2">
    <div class="field">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $staff?->email) }}">
    </div>

    <div class="field">
        <label for="role">Role</label>
        <select id="role" name="role" required>
            <option value="">Select role</option>
            @foreach($manageableRoles as $role)
                <option value="{{ $role }}" @selected(old('role', $staff?->role) === $role)>
                    {{ strtoupper($role) }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid grid-2">
    <div class="field">
        <label for="department_id">Department</label>
        <select id="department_id" name="department_id" required>
            <option value="">Select department</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $staff?->facultyProfile?->department_id) == $department->id)>
                    {{ $department->department_name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="employee_code">Employee code</label>
        <input id="employee_code" name="employee_code" value="{{ old('employee_code', $staff?->facultyProfile?->employee_code) }}" required>
    </div>
</div>

<div class="grid grid-2">
    <div class="field">
        <label for="designation">Designation</label>
        <input id="designation" name="designation" value="{{ old('designation', $staff?->facultyProfile?->designation) }}" placeholder="Assistant Professor">
    </div>

    <div class="field">
        <label for="display_initials">Display initials</label>
        <input id="display_initials" name="display_initials" maxlength="12" value="{{ old('display_initials', $staff?->facultyProfile?->display_initials) }}" placeholder="AD">
    </div>
</div>

<div class="grid grid-2">
    <div class="field">
        <label for="password">Temporary password</label>
        <input id="password" type="password" name="password" @required(!$staff)>
        @if($staff)
            <div class="muted">Leave blank to keep the current password.</div>
        @endif
    </div>

    <div class="field">
        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" @required(!$staff)>
    </div>
</div>

@if($staff)
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status" required>
            <option value="active" @selected(old('status', $staff->status) === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $staff->status) === 'inactive')>Inactive</option>
        </select>
    </div>
@endif
