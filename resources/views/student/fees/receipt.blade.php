<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt - Shreyarth University</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            background: #ffffff;
            margin: 0;
            padding: 2rem;
            line-height: 1.5;
        }
        .container {
            max-width: 45rem;
            margin: 0 auto;
            border: 1px solid #e2e8f0;
            padding: 2.5rem;
            border-radius: 8px;
            position: relative;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        .logo-section h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0d9488;
            margin: 0 0 0.25rem 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .logo-section p {
            margin: 0;
            font-size: 0.8125rem;
            color: #64748b;
            font-weight: 600;
        }
        .receipt-title {
            text-align: right;
        }
        .receipt-title h2 {
            font-size: 1.25rem;
            font-weight: 800;
            margin: 0;
            color: #0f172a;
        }
        .receipt-title p {
            margin: 0.25rem 0 0 0;
            font-size: 0.875rem;
            color: #64748b;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .card {
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            padding: 1.25rem;
            border-radius: 6px;
        }
        .card h3 {
            margin: 0 0 0.75rem 0;
            font-size: 0.8125rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.35rem;
        }
        .details-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        .details-row span:first-child {
            color: #64748b;
            font-weight: 500;
        }
        .details-row span:last-child {
            color: #0f172a;
            font-weight: 700;
        }
        .paid-badge {
            display: inline-block;
            border: 3px solid #10b981;
            color: #10b981;
            font-size: 1.15rem;
            font-weight: 900;
            text-transform: uppercase;
            padding: 0.35rem 1rem;
            border-radius: 4px;
            transform: rotate(-10deg);
            position: absolute;
            right: 2.5rem;
            bottom: 8rem;
            opacity: 0.85;
            letter-spacing: 0.05em;
        }
        .total-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 1.25rem;
            border-radius: 6px;
            margin-top: 1rem;
            margin-bottom: 2rem;
        }
        .total-box span {
            font-size: 1rem;
            font-weight: 700;
            color: #166534;
        }
        .total-box strong {
            font-size: 1.75rem;
            font-weight: 800;
            color: #15803d;
        }
        .footer {
            border-top: 1px solid #f1f5f9;
            padding-top: 1.5rem;
            text-align: center;
            font-size: 0.75rem;
            color: #94a3b8;
        }
        .actions {
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            background: #0f172a;
            color: #ffffff;
            border: 0;
            padding: 0.5rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn.secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        @media print {
            body {
                padding: 0;
            }
            .container {
                border: 0;
                padding: 0;
            }
            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="actions">
        <a href="javascript:void(0)" onclick="window.print()" class="btn">🖨️ Print Receipt</a>
        <a href="javascript:window.close()" class="btn secondary">Close Window</a>
    </div>

    <div class="container">
        <div class="header">
            <div class="logo-section">
                <h1>Shreyarth University</h1>
                <p>Office of the Controller of Examinations</p>
            </div>
            <div class="receipt-title">
                <h2>Fee Payment Receipt</h2>
                <p>E-Transaction Copy</p>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h3>Student Details</h3>
                <div class="details-row">
                    <span>Name</span>
                    <span>{{ $payment->student->user->name }}</span>
                </div>
                <div class="details-row">
                    <span>Enrollment No.</span>
                    <span>{{ $payment->student->enrollment_no }}</span>
                </div>
                <div class="details-row">
                    <span>Roll No.</span>
                    <span>{{ $payment->student->roll_no }}</span>
                </div>
                <div class="details-row">
                    <span>Program</span>
                    <span>{{ $payment->student->program->program_name }}</span>
                </div>
            </div>

            <div class="card">
                <h3>Payment Details</h3>
                <div class="details-row">
                    <span>Receipt No.</span>
                    <span>REC-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="details-row">
                    <span>Reference Ref</span>
                    <span style="font-family: monospace;">{{ $payment->transaction_reference }}</span>
                </div>
                <div class="details-row">
                    <span>Payment Method</span>
                    <span>{{ ucfirst($payment->payment_method) }}</span>
                </div>
                <div class="details-row">
                    <span>Date Paid</span>
                    <span>{{ $payment->paid_at->format('d M Y h:i A') }}</span>
                </div>
            </div>
        </div>

        <div class="total-box">
            <span>Total Exam Fee Amount Paid</span>
            <strong>₹{{ number_format($payment->amount_paid, 2) }}</strong>
        </div>

        @if($payment->payment_method === 'manual')
            <div style="font-size: 0.8125rem; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.75rem; border-radius: 4px; margin-bottom: 2rem;">
                ℹ️ Manual clearance recorded and verified by: <strong>{{ $payment->verifiedBy ? $payment->verifiedBy->name : 'Administrator' }}</strong>
            </div>
        @endif

        <div class="paid-badge">
            Paid
        </div>

        <div class="footer">
            <p>This is a system-generated document and does not require a physical signature.</p>
            <p>&copy; {{ date('Y') }} Shreyarth University. All rights reserved.</p>
        </div>
    </div>

</body>
</html>
