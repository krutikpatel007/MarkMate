<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamFeePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'exam_fee_id',
        'amount_paid',
        'payment_method',
        'status',
        'transaction_reference',
        'paid_at',
        'verified_by',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function examFee(): BelongsTo
    {
        return $this->belongsTo(ExamFee::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
