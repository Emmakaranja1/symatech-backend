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
}
