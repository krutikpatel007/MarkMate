@php
    $isExamDept = auth()->user()->isCoe() || auth()->user()->facultyProfile?->department?->department_code === 'EXAM';
@endphp

@if($isExamDept)
    <div class="grid grid-4">
        @for($i = 0; $i < 4; $i++)
            <div class="card stat-card shimmer-card" style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; height: 110px;">
                <div class="shimmer-placeholder" style="width: 70%; height: 1rem; border-radius: 4px;"></div>
                <div class="shimmer-placeholder" style="width: 40%; height: 2.25rem; border-radius: 4px; margin-top: 0.5rem;"></div>
                <div class="shimmer-placeholder" style="width: 55%; height: 0.75rem; border-radius: 4px; margin-top: 0.25rem;"></div>
            </div>
        @endfor
    </div>
@else
    <div class="grid grid-4">
        @for($i = 0; $i < 8; $i++)
            <div class="card stat-card shimmer-card" style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; height: 90px;">
                <div class="shimmer-placeholder" style="width: 75%; height: 0.875rem; border-radius: 4px;"></div>
                <div class="shimmer-placeholder" style="width: 35%; height: 2rem; border-radius: 4px; margin-top: 0.5rem;"></div>
            </div>
        @endfor
    </div>
@endif
