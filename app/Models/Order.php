<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Product;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Order extends Model
{

    use LogsActivity;
     public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->useLogName('order')
        ->logFillable()
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn(string $eventName) => "Order {$eventName}");
}

public function activityLogs()
{
    return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject')
        ->orderBy('created_at', 'desc');
}


    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'total_price',
        'status',
        'payment_status',
    ];

    // Link order to user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Link order to product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Link order to payments
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Get the latest payment
    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latest();
    }

    // Check if order is paid
    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    // Check if order payment is pending
    public function isPaymentPending()
    {
        return $this->payment_status === 'pending';
    }

    // Mark order as paid
    public function markAsPaid()
    {
        $this->update([
            'payment_status' => 'paid',
            'status' => 'processing', // Update order status when paid
        ]);
    }

    // Mark order payment as failed
    public function markPaymentFailed()
    {
        $this->update([
            'payment_status' => 'failed',
        ]);
    }
}
