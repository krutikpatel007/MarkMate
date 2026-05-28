@extends('layouts.app')

@section('title', 'Attendance Heatmap | SCSA Attendance')
@section('page-title', 'Faculty Submission Compliance')
@section('page-subtitle', 'Track daily attendance submission rates across the department over the last 30 days.')

@section('content')
    <style>
        .heatmap-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(6.5rem, 1fr));
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .heatmap-cell {
            aspect-ratio: 1 / 1;
            border-radius: 8px;
            padding: 0.6rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-decoration: none;
            transition: all 0.2s ease-in-out;
            position: relative;
            box-shadow: var(--shadow-sm);
        }

        .heatmap-cell:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.08);
        }

        .cell-date {
            font-size: 0.75rem;
            font-weight: 700;
            opacity: 0.9;
        }

        .cell-day {
            font-size: 0.6875rem;
            opacity: 0.75;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .cell-stats {
            text-align: right;
        }

        .cell-rate {
            font-size: 1.15rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .cell-ratio {
            font-size: 0.6875rem;
            opacity: 0.8;
            margin-top: 0.15rem;
        }

        /* Color classes */
        .cell-emerald {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            border: 1px solid #047857;
            color: #ffffff;
        }

        .cell-mint {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: 1px solid #059669;
            color: #ffffff;
        }

        .cell-amber {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border: 1px solid #d97706;
            color: #000000;
        }

        .cell-crimson {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: 1px solid #dc2626;
            color: #ffffff;
        }

        .cell-empty {
            background: rgba(148, 163, 184, 0.08);
            border: 1px dashed rgba(148, 163, 184, 0.3);
            color: var(--color-text-muted);
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .legend-bar {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            border: 1px solid var(--color-scsa-line);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
        }

        .legend-color {
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 4px;
        }
    </style>

    <div class="grid grid-1">
        <!-- Compliance Grid Panel -->
        <section class="card">
            <h2>30-Day Submission Compliance Tracker</h2>
            <p class="muted">
                Each cell represents a day's overall marking rate. Click on any active colored square to drill down and see the scheduled lectures list and compliance details for that day.
            </p>

            <div class="heatmap-grid">
                @foreach($heatmapData as $cell)
                    @if($cell['has_lectures'])
                        <a href="{{ route('attendance.heatmap.details', $cell['date']) }}" class="heatmap-cell cell-{{ $cell['color_class'] }}" title="View lectures list for {{ $cell['display_date'] }}">
                            <div>
                                <div class="cell-date">{{ \Carbon\Carbon::parse($cell['date'])->format('d M') }}</div>
                                <div class="cell-day">{{ substr($cell['day_of_week'], 0, 3) }}</div>
                            </div>
                            <div class="cell-stats">
                                <div class="cell-rate">{{ $cell['rate'] }}%</div>
                                <div class="cell-ratio">{{ $cell['submitted'] }}/{{ $cell['total'] }} slots</div>
                            </div>
                        </a>
                    @else
                        <div class="heatmap-cell cell-empty">
                            <div style="font-size: 0.75rem; font-weight: 700;">{{ \Carbon\Carbon::parse($cell['date'])->format('d M') }}</div>
                            <div style="font-size: 0.625rem; text-transform: uppercase; margin-top: 0.15rem;">{{ substr($cell['day_of_week'], 0, 3) }}</div>
                            <div style="font-size: 0.6875rem; opacity: 0.65; margin-top: 0.5rem;">No Lectures</div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Legend Bar -->
            <div class="legend-bar">
                <div class="legend-item">
                    <span class="legend-color" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); border: 1px solid #047857;"></span>
                    <span>100% submission (Emerald)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: 1px solid #059669;"></span>
                    <span>75% - 99% compliance (Mint)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: 1px solid #d97706;"></span>
                    <span>50% - 74% warning (Amber)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: 1px solid #dc2626;"></span>
                    <span>&lt; 50% critical overdue (Crimson)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: rgba(148, 163, 184, 0.08); border: 1px dashed rgba(148, 163, 184, 0.3);"></span>
                    <span>Holiday / No slots scheduled</span>
                </div>
            </div>
        </section>
    </div>
@endsection
