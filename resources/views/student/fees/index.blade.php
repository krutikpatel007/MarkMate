@extends('layouts.app')

@section('title', 'Exam Fees | SCSA Attendance')
@section('page-title', 'Exam Fee Clearance')
@section('page-subtitle', 'Manage and pay your end-semester examination fees')

@section('content')
    <div style="max-width: 54rem; margin: 0 auto;">
        <!-- Current Semester Fee Box -->
        @if($examFee)
            @if($payment && $payment->status === 'paid')
                <div class="card" style="border-left: 5px solid var(--color-scsa-success); padding: 2rem; margin-bottom: 2rem; border-radius: var(--border-radius-xl); box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; gap: 2rem; flex-wrap: wrap;">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <span style="font-size: 3rem;">✅</span>
                        <div>
                            <h2 style="margin: 0 0 0.25rem 0; font-weight: 800; color: var(--color-scsa-success); border-bottom: 0; padding-bottom: 0;">Exam Fee Paid</h2>
                            <p class="muted" style="margin: 0; font-size: 0.9rem;">
                                Your exam fee for <strong>{{ $student->semester->semester_name }} ({{ $student->program->program_code }})</strong> has been fully paid.
                            </p>
                            <div style="display: flex; gap: 1rem; margin-top: 0.75rem; font-size: 0.8rem; font-weight: 500;" class="muted">
                                <span>Ref: <strong style="color: var(--color-scsa-ink);">{{ $payment->transaction_reference }}</strong></span>
                                <span>Paid At: <strong style="color: var(--color-scsa-ink);">{{ $payment->paid_at->format('d M Y h:i A') }}</strong></span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('exam-fees.receipt', $payment) }}" target="_blank" class="button" style="background-color: var(--color-scsa-success); border-color: var(--color-scsa-success); padding: 0.65rem 1.5rem; gap: 0.5rem;">
                            📥 View Receipt
                        </a>
                    </div>
                </div>
            @else
                <div class="card" style="border-left: 5px solid var(--color-scsa-gold); padding: 2rem; margin-bottom: 2rem; border-radius: var(--border-radius-xl); box-shadow: var(--shadow-md);">
                    <div style="display: flex; gap: 1.25rem; align-items: flex-start; flex-wrap: wrap;">
                        <span style="font-size: 3rem; line-height: 1;">💸</span>
                        <div style="flex: 1; min-width: 15rem;">
                            <h2 style="margin: 0 0 0.5rem 0; font-weight: 800; color: var(--color-scsa-accent-deep); border-bottom: 0; padding-bottom: 0;">Pending End-Semester Exam Fee</h2>
                            <p class="muted" style="margin: 0 0 1rem 0; font-size: 0.9375rem; line-height: 1.5;">
                                An exam fee demand has been created for <strong>{{ $student->semester->semester_name }} ({{ $student->program->program_code }})</strong>. 
                                Please complete the payment online to clear your financial status and unlock your hall ticket for download.
                            </p>
                            
                            <div class="grid grid-3" style="gap: 1rem; margin-bottom: 1.5rem; max-width: 32rem;">
                                <div style="background: var(--bg-secondary); padding: 0.75rem; border-radius: var(--border-radius-md); border: 1px solid var(--color-scsa-line);">
                                    <div class="muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; margin-bottom: 0.15rem;">Fee Amount</div>
                                    <strong style="font-size: 1.25rem; color: var(--color-scsa-ink);">₹{{ number_format($examFee->amount, 2) }}</strong>
                                </div>
                                <div style="background: var(--bg-secondary); padding: 0.75rem; border-radius: var(--border-radius-md); border: 1px solid var(--color-scsa-line);">
                                    <div class="muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; margin-bottom: 0.15rem;">Due Date</div>
                                    <strong style="font-size: 1rem; color: var(--color-scsa-danger);">{{ $examFee->due_date->format('d M Y') }}</strong>
                                </div>
                                <div style="background: var(--bg-secondary); padding: 0.75rem; border-radius: var(--border-radius-md); border: 1px solid var(--color-scsa-line);">
                                    <div class="muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; margin-bottom: 0.15rem;">Status</div>
                                    <span class="badge danger" style="margin-top: 0.25rem; font-size: 0.7rem; font-weight: 800; padding: 0.2rem 0.5rem;">Unpaid</span>
                                </div>
                            </div>

                            <button type="button" class="button" onclick="openPaymentModal()" style="padding: 0.75rem 2rem; font-weight: 800; background-color: var(--color-scsa-accent); border-color: var(--color-scsa-accent); box-shadow: var(--shadow-sm);">
                                💳 Pay Exam Fee Online
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        @else
            <div class="card" style="padding: 3rem 1.5rem; text-align: center; margin-bottom: 2rem;">
                <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🎉</span>
                <h2 style="font-weight: 800; color: var(--color-scsa-success); border-bottom: 0; padding-bottom: 0; margin-bottom: 0.5rem;">No Pending Exam Fees</h2>
                <p class="muted" style="max-width: 28rem; margin: 0 auto; line-height: 1.5;">
                    There are no active exam fee demands configured for your class section or semester at this time. You are cleared financially.
                </p>
            </div>
        @endif

        <!-- Past Payment Transactions -->
        <section class="card" style="padding: 1.5rem; border-radius: var(--border-radius-xl); box-shadow: var(--shadow-sm);">
            <h2 style="margin-bottom: 1rem;">Payment History & receipts</h2>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                    <tr>
                        <th>Semester / Term</th>
                        <th>Amount Paid</th>
                        <th>Method</th>
                        <th>Transaction Ref</th>
                        <th>Date Paid</th>
                        <th style="text-align: right;">Receipt</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($pastPayments as $hist)
                        <tr>
                            <td>
                                <strong>{{ $hist->examFee->semester->semester_name }}</strong>
                                <div class="muted">{{ $hist->examFee->semester->program->program_code }}</div>
                            </td>
                            <td><strong>₹{{ number_format($hist->amount_paid, 2) }}</strong></td>
                            <td>
                                <span class="badge" style="background-color: var(--bg-secondary); border: 1px solid var(--color-scsa-line); color: var(--color-scsa-muted);">
                                    {{ ucfirst($hist->payment_method) }}
                                </span>
                            </td>
                            <td><code style="font-family: monospace; font-size: 0.8125rem;">{{ $hist->transaction_reference }}</code></td>
                            <td>{{ $hist->paid_at ? $hist->paid_at->format('d M Y H:i') : '-' }}</td>
                            <td style="text-align: right;">
                                <a href="{{ route('exam-fees.receipt', $hist) }}" target="_blank" class="button secondary" style="font-size: 0.75rem; padding: 0.35rem 0.75rem; min-height: unset;">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted" style="text-align: center; padding: 2rem 0;">
                                No fee payment records found.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- MOCK PAYMENT MODAL -->
    @if($examFee && (!$payment || $payment->status !== 'paid'))
        <div id="payment-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.65); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 32rem; padding: 2rem; border-radius: var(--border-radius-xl); box-shadow: var(--shadow-xl); position: relative; margin: 1rem; background: var(--bg-primary);">
                <!-- Close Button -->
                <button type="button" onclick="closePaymentModal()" style="position: absolute; right: 1.25rem; top: 1.25rem; border: 0; background: none; font-size: 1.5rem; color: var(--color-scsa-muted); cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='var(--color-scsa-danger)'" onmouseout="this.style.color='var(--color-scsa-muted)'">
                    &times;
                </button>

                <h2 style="margin-top: 0; border-bottom: 0; padding-bottom: 0; font-weight: 800; color: var(--color-scsa-accent-deep);">Shreyarth Pay Gateway</h2>
                <p class="muted" style="margin-top: 0.25rem; font-size: 0.85rem; margin-bottom: 1.5rem;">
                    Secure end-semester examination fee clearance gateway simulator.
                </p>

                <!-- Payment Details Summary -->
                <div style="background: var(--bg-secondary); border: 1px dashed var(--color-scsa-accent-soft); padding: 1rem; border-radius: var(--border-radius-lg); margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div class="muted" style="font-size: 0.725rem; text-transform: uppercase; font-weight: 600;">Paying For</div>
                        <strong style="font-size: 0.9rem; color: var(--color-scsa-ink);">{{ $student->semester->semester_name }} Exam Fee</strong>
                    </div>
                    <div style="text-align: right;">
                        <div class="muted" style="font-size: 0.725rem; text-transform: uppercase; font-weight: 600;">Total Amount</div>
                        <strong style="font-size: 1.25rem; color: var(--color-scsa-accent);">₹{{ number_format($examFee->amount, 2) }}</strong>
                    </div>
                </div>

                <form method="post" action="{{ route('exam-fees.pay', $examFee) }}" onsubmit="submitFormLoading(this)">
                    @csrf

                    <!-- Method Selector -->
                    <div class="muted" style="font-size: 0.8125rem; font-weight: 700; margin-bottom: 0.5rem;">Select Payment Method</div>
                    <div style="display: flex; gap: 1rem; margin-bottom: 1.25rem;">
                        <label style="flex: 1; border: 1px solid var(--color-scsa-line); padding: 0.75rem; border-radius: var(--border-radius-md); display: flex; align-items: center; gap: 0.5rem; cursor: pointer; transition: all 0.2s;" class="method-option active" id="option-card">
                            <input type="radio" name="payment_method" value="card" checked onchange="switchMethod('card')">
                            <span style="font-weight: 700; font-size: 0.85rem;">💳 Credit/Debit Card</span>
                        </label>
                        <label style="flex: 1; border: 1px solid var(--color-scsa-line); padding: 0.75rem; border-radius: var(--border-radius-md); display: flex; align-items: center; gap: 0.5rem; cursor: pointer; transition: all 0.2s;" class="method-option" id="option-upi">
                            <input type="radio" name="payment_method" value="upi" onchange="switchMethod('upi')">
                            <span style="font-weight: 700; font-size: 0.85rem;">📱 UPI ID</span>
                        </label>
                    </div>

                    <!-- CARD FORM BLOCK -->
                    <div id="card-form-block">
                        <div style="margin-bottom: 1rem;">
                            <label class="muted" style="font-size: 0.75rem; font-weight: 700; display: block; margin-bottom: 0.25rem;">Cardholder Name</label>
                            <input type="text" name="card_name" placeholder="John Doe" style="width: 100%; padding: 0.55rem;" value="{{ $student->user->name }}">
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label class="muted" style="font-size: 0.75rem; font-weight: 700; display: block; margin-bottom: 0.25rem;">Card Number</label>
                            <input type="text" name="card_number" placeholder="4111 2222 3333 4444" style="width: 100%; padding: 0.55rem; font-family: monospace;" value="4111222233334444">
                        </div>
                        <div class="grid grid-2" style="gap: 1rem; margin-bottom: 1.5rem;">
                            <div>
                                <label class="muted" style="font-size: 0.75rem; font-weight: 700; display: block; margin-bottom: 0.25rem;">Expiry Date</label>
                                <input type="text" name="card_expiry" placeholder="12/28" style="width: 100%; padding: 0.55rem; font-family: monospace;" value="12/28">
                            </div>
                            <div>
                                <label class="muted" style="font-size: 0.75rem; font-weight: 700; display: block; margin-bottom: 0.25rem;">CVV Code</label>
                                <input type="password" name="card_cvv" placeholder="123" maxlength="3" style="width: 100%; padding: 0.55rem; font-family: monospace;" value="123">
                            </div>
                        </div>
                    </div>

                    <!-- UPI FORM BLOCK -->
                    <div id="upi-form-block" style="display: none; margin-bottom: 1.5rem;">
                        <div>
                            <label class="muted" style="font-size: 0.75rem; font-weight: 700; display: block; margin-bottom: 0.25rem;">UPI Address (VPA)</label>
                            <input type="text" name="upi_id" placeholder="student@upi" style="width: 100%; padding: 0.55rem; font-family: monospace;" value="{{ strtolower(str_replace(' ', '', $student->user->name)) }}@okaxis">
                            <span class="muted" style="font-size: 0.7rem; margin-top: 0.25rem; display: block;">Enter your Google Pay, PhonePe, or Paytm virtual payment address.</span>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div style="display: flex; gap: 1rem; border-top: 1px solid var(--color-scsa-line); padding-top: 1.25rem; margin-top: 1.25rem; justify-content: flex-end;">
                        <button type="button" class="button secondary" onclick="closePaymentModal()" style="padding: 0.6rem 1.25rem;">Cancel</button>
                        <button type="submit" id="pay-submit-btn" class="button" style="padding: 0.6rem 1.75rem; font-weight: 700; background-color: var(--color-scsa-success); border-color: var(--color-scsa-success);">
                            Authorize Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openPaymentModal() {
                document.getElementById('payment-modal').style.display = 'flex';
            }

            function closePaymentModal() {
                document.getElementById('payment-modal').style.display = 'none';
            }

            function switchMethod(method) {
                const cardBlock = document.getElementById('card-form-block');
                const upiBlock = document.getElementById('upi-form-block');
                const optCard = document.getElementById('option-card');
                const optUpi = document.getElementById('option-upi');

                if (method === 'card') {
                    cardBlock.style.display = 'block';
                    upiBlock.style.display = 'none';
                    optCard.classList.add('active');
                    optUpi.classList.remove('active');
                } else {
                    cardBlock.style.display = 'none';
                    upiBlock.style.display = 'block';
                    optCard.classList.remove('active');
                    optUpi.classList.add('active');
                }
            }

            function submitFormLoading(form) {
                const btn = document.getElementById('pay-submit-btn');
                btn.innerHTML = '⚙️ Authorizing...';
                btn.disabled = true;
            }
        </script>
        
        <style>
            .method-option.active {
                border-color: var(--color-scsa-accent) !important;
                background-color: var(--color-scsa-accent-soft) !important;
                color: var(--color-scsa-accent) !important;
            }
        </style>
    @endif
@endsection
