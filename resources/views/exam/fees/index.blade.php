@extends('layouts.app')

@section('title', 'Exam Fees | SCSA Attendance')
@section('page-title', 'Exam Fees Control Panel')
@section('page-subtitle', 'Configure semester fee demands, verify manual challans, and track financial clearances')

@section('content')
    <!-- Top Action Cards -->
    <div class="grid grid-3" style="gap: 1.5rem; margin-bottom: 2rem;">
        <!-- Configure Fees Form -->
        <section class="card" style="padding: 1.5rem; display: flex; flex-direction: column;">
            <h2 style="margin-top: 0; margin-bottom: 0.5rem; border-bottom: 0; padding-bottom: 0;">Configure Exam Fee</h2>
            <p class="muted" style="font-size: 0.8rem; margin-bottom: 1.25rem;">Define fee demand details for a specific class semester.</p>

            <form method="post" action="{{ route('exam-fees.admin.store-config') }}" style="display: flex; flex-direction: column; gap: 0.75rem; flex: 1;">
                @csrf
                <div>
                    <label class="muted" style="font-size: 0.75rem; font-weight: 700; display: block; margin-bottom: 0.25rem;">Target Semester Number</label>
                    <select name="semester_no" required style="width: 100%;">
                        <option value="">-- Select Semester Number --</option>
                        @for($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}">Semester {{ $i }} (All Courses)</option>
                        @endfor
                    </select>
                </div>
                
                <div>
                    <label class="muted" style="font-size: 0.75rem; font-weight: 700; display: block; margin-bottom: 0.25rem;">Fee Amount (INR)</label>
                    <input type="text" value="₹1,000.00 (Fixed per Semester)" disabled style="width: 100%; padding: 0.45rem; background: var(--bg-secondary); border: 1px solid var(--color-scsa-line);">
                </div>

                <div>
                    <label class="muted" style="font-size: 0.75rem; font-weight: 700; display: block; margin-bottom: 0.25rem;">Due Date</label>
                    <input type="date" name="due_date" required style="width: 100%; padding: 0.45rem;">
                </div>

                <button type="submit" class="button" style="margin-top: auto; padding: 0.55rem; width: 100%;">
                    ⚙️ Apply Configuration
                </button>
            </form>
        </section>

        <!-- Record Manual Payment Form -->
        <section class="card" id="manual-payment" style="padding: 1.5rem; display: flex; flex-direction: column;">
            <h2 style="margin-top: 0; margin-bottom: 0.5rem; border-bottom: 0; padding-bottom: 0;">Record Manual Payment</h2>
            <p class="muted" style="font-size: 0.8rem; margin-bottom: 1.25rem;">Override financial status for cash, bank draft, or manual challan.</p>

            <form method="post" action="{{ route('exam-fees.admin.manual-pay') }}" style="display: flex; flex-direction: column; gap: 0.75rem; flex: 1;">
                @csrf
                <div>
                    <label class="muted" style="font-size: 0.75rem; font-weight: 700; display: block; margin-bottom: 0.25rem;">Student Enrollment No.</label>
                    <input type="text" name="enrollment_no" placeholder="e.g. SU2026BCA001" required style="width: 100%; padding: 0.45rem;" value="{{ request()->query('enrollment_no') }}">
                </div>

                <div>
                    <label class="muted" style="font-size: 0.75rem; font-weight: 700; display: block; margin-bottom: 0.25rem;">Challan / Receipt Reference</label>
                    <input type="text" name="payment_reference" placeholder="e.g. CHN-10492" required style="width: 100%; padding: 0.45rem;">
                </div>

                <div style="background: rgba(13, 148, 136, 0.04); border: 1px dashed var(--color-scsa-accent-soft); padding: 0.75rem; border-radius: 4px; font-size: 0.75rem; color: var(--color-scsa-muted); line-height: 1.4; margin-top: 0.5rem;">
                    💡 Recording a manual payment immediately marks the student's exam fee as Paid and clears their hall ticket lock.
                </div>

                <button type="submit" class="button" style="margin-top: auto; padding: 0.55rem; width: 100%; background-color: var(--color-scsa-success); border-color: var(--color-scsa-success);">
                    ✅ Clear Fee Manually
                </button>
            </form>
        </section>

        <!-- Configured Fees Overview -->
        <section class="card" style="padding: 1.5rem; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <h2 style="margin: 0; border-bottom: 0; padding-bottom: 0;">Fee Demands</h2>
                <a href="{{ route('exam-fees.admin.export', request()->query()) }}" class="button secondary" style="font-size: 0.75rem; padding: 0.35rem 0.75rem; min-height: unset;">
                    📥 Export logs
                </a>
            </div>
            <p class="muted" style="font-size: 0.8rem; margin-bottom: 1.25rem;">Currently active exam fee configurations in the system.</p>

            <div style="flex: 1; overflow-y: auto; max-height: 14rem;">
                @forelse($feeConfigs as $semNo => $configs)
                    @php($firstConfig = $configs->first())
                    <div style="padding: 0.65rem; background: var(--bg-secondary); border: 1px solid var(--color-scsa-line); border-radius: var(--border-radius-md); margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="font-size: 0.85rem; color: var(--color-scsa-ink);">Semester {{ $semNo }} (All Courses)</strong>
                            <div class="muted" style="font-size: 0.725rem;">Due: {{ $firstConfig->due_date->format('d M Y') }}</div>
                        </div>
                        <strong style="color: var(--color-scsa-accent); font-size: 0.95rem;">₹{{ number_format($firstConfig->amount, 2) }}</strong>
                    </div>
                @empty
                    <p class="muted" style="text-align: center; padding: 3rem 0; font-size: 0.85rem; margin: 0;">No active fee configurations defined yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    <!-- Advanced Tools: DCR Generator and Directory Links -->
    <div class="grid grid-2" style="gap: 1.5rem; margin-bottom: 2rem;">
        <!-- DCR Generator -->
        <section class="card" style="padding: 1.5rem;">
            <h2 style="margin-top: 0; margin-bottom: 0.5rem; border-bottom: 0; padding-bottom: 0;">Daily Collections Report (DCR)</h2>
            <p class="muted" style="font-size: 0.8rem; margin-bottom: 1.25rem;">Generate and export a CSV transaction log filtered by dates and payment method.</p>
            
            <form method="get" action="{{ route('exam-fees.admin.reports.dcr') }}" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div class="grid grid-2" style="gap: 1rem;">
                    <div>
                        <label class="muted" style="font-size: 0.75rem; font-weight: 700; display: block; margin-bottom: 0.25rem;">Start Date</label>
                        <input type="date" name="start_date" required style="width: 100%; padding: 0.45rem;" value="{{ date('Y-m-d') }}">
                    </div>
                    <div>
                        <label class="muted" style="font-size: 0.75rem; font-weight: 700; display: block; margin-bottom: 0.25rem;">End Date</label>
                        <input type="date" name="end_date" required style="width: 100%; padding: 0.45rem;" value="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div>
                    <label class="muted" style="font-size: 0.75rem; font-weight: 700; display: block; margin-bottom: 0.25rem;">Payment Method</label>
                    <select name="payment_method" style="width: 100%;">
                        <option value="all">All Methods</option>
                        <option value="online">Online Only (UPI, Card)</option>
                        <option value="manual">Manual Only</option>
                    </select>
                </div>
                <button type="submit" class="button secondary" style="width: 100%; padding: 0.55rem; margin-top: 0.5rem;">
                    📊 Export Collections CSV
                </button>
            </form>
        </section>

        <!-- Defaulters Directory Panel -->
        <section class="card" style="padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h2 style="margin-top: 0; margin-bottom: 0.5rem; border-bottom: 0; padding-bottom: 0;">Defaulters & Outstanding Fees</h2>
                <p class="muted" style="font-size: 0.8rem; margin-bottom: 1.25rem;">Identify students with outstanding fee demands and track locks.</p>
                <div style="background: rgba(239, 68, 68, 0.04); border: 1px dashed rgba(239, 68, 68, 0.2); padding: 1rem; border-radius: 4px; font-size: 0.8rem; line-height: 1.5; color: var(--color-scsa-ink); margin-bottom: 1rem;">
                    ⚠️ Unpaid exam fee demands automatically restrict students from accessing their exam hall tickets. You can track, filter, and export the live outstanding list in the directory.
                </div>
            </div>
            <a href="{{ route('exam-fees.admin.defaulters') }}" class="button" style="text-align: center; text-decoration: none; padding: 0.55rem; width: 100%;">
                👥 View Defaulters Directory
            </a>
        </section>
    </div>

    <!-- Recent Transactions Log -->
    <section class="card" style="padding: 1.5rem; border-radius: var(--border-radius-xl); box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
            <h2 style="margin: 0; border-bottom: 0; padding-bottom: 0;">Recent Transactions</h2>
            
            <form method="get" action="{{ route('exam-fees.admin.index') }}" style="display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="text" name="search" placeholder="Search student or enrollment..." 
                           value="{{ request()->query('search') }}" 
                           style="padding: 0.4rem 0.75rem; font-size: 0.85rem; width: 15rem; min-height: unset; border: 1px solid var(--color-scsa-line); border-radius: var(--border-radius-md);">
                </div>
                <button type="submit" class="button" style="font-size: 0.8rem; padding: 0.4rem 1rem; min-height: unset;">
                    🔍 Search
                </button>
                @if(request()->filled('search'))
                    <a href="{{ route('exam-fees.admin.index') }}" class="button secondary" style="font-size: 0.8rem; padding: 0.4rem 1rem; min-height: unset; text-decoration: none; display: inline-flex; align-items: center;">
                        Clear
                    </a>
                @endif
            </form>
        </div>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                <tr>
                    <th>Enrollment No.</th>
                    <th>Student Name</th>
                    <th>Program & Semester</th>
                    <th>Amount Paid</th>
                    <th>Method</th>
                    <th>Reference No.</th>
                    <th>Transaction Date</th>
                    <th>Verified By</th>
                    <th style="text-align: right;">Receipt</th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentPayments as $payment)
                    <tr>
                        <td><strong>{{ $payment->student->enrollment_no }}</strong></td>
                        <td>{{ $payment->student->user->name }}</td>
                        <td>{{ $payment->examFee->semester->program->program_code }} Sem {{ $payment->examFee->semester->semester_no }}</td>
                        <td><strong>₹{{ number_format($payment->amount_paid, 2) }}</strong></td>
                        <td>
                            <span class="badge" style="background-color: var(--bg-secondary); border: 1px solid var(--color-scsa-line); color: var(--color-scsa-muted);">
                                {{ ucfirst($payment->payment_method) }}
                            </span>
                        </td>
                        <td><code style="font-family: monospace; font-size: 0.8125rem;">{{ $payment->transaction_reference }}</code></td>
                        <td>{{ $payment->paid_at ? $payment->paid_at->format('d M Y H:i') : '-' }}</td>
                        <td>
                            @if($payment->payment_method === 'manual')
                                <span style="font-size: 0.8rem; font-weight: 500;" class="muted">{{ $payment->verifiedBy->name ?? 'Admin' }}</span>
                            @else
                                <span class="badge success" style="font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.4rem;">System</span>
                            @endif
                        </td>
                        <td style="text-align: right; display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center;">
                            <a href="{{ route('exam-fees.receipt', $payment) }}" target="_blank" class="button secondary" style="font-size: 0.75rem; padding: 0.35rem 0.75rem; min-height: unset;">
                                View
                            </a>
                            @if($payment->payment_method === 'manual')
                                <form method="post" action="{{ route('exam-fees.admin.payments.void', $payment) }}" onsubmit="return confirm('Void this payment? This will lock the student\'s hall ticket again.');" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button danger" style="font-size: 0.75rem; padding: 0.35rem 0.75rem; min-height: unset;">
                                        Void
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="muted" style="text-align: center; padding: 3rem 0;">
                            No recent transaction payments recorded yet.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
