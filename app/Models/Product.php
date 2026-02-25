<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Order;

class Product extends Model

{
   
     use HasFactory, LogsActivity;

    protected $fillable = [
        'name', 
        'title', 
        'sku', 
        'category', 
        'price', 
        'cost_price', 
        'stock', 
        'weight', 
        'dimensions', 
        'description', 
        'image', 
        'images', 
        'rating', 
        'active', 
        'featured', 
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'images' => 'array',
        'active' => 'boolean',
        'featured' => 'boolean',
    ];

    protected static $logAttributes = [
        'name', 
        'title', 
        'sku', 
        'category', 
        'price', 
        'cost_price', 
        'stock', 
        'weight', 
        'dimensions', 
        'description', 
        'image', 
        'images', 
        'rating', 
        'active', 
        'featured', 
        'status'
    ];

   public function getActivitylogOptions(): LogOptions
{
    \Log::info('Getting activity log options for product');
    return LogOptions::defaults()
        ->useLogName('product')
        ->logFillable()
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()  
        ->setDescriptionForEvent(fn(string $eventName) => "Product {$eventName}");
}

    public function activityLogs()
{
    return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject')
    ->orderBy('created_at', 'desc');
}

    public function orders()
    {
        return $this->hasMany(Order::class);
    } 

    protected static function boot()
{
    parent::boot();

    static::created(function ($product) {
        \Log::info('Product created event fired', [
            'product_id' => $product->id,
            'name' => $product->name
        ]);
    });

    static::updated(function ($product) {
        \Log::info('Product updated event fired', [
            'product_id' => $product->id,
            'name' => $product->name,
            'changes' => $product->getChanges()
        ]);
    });

    static::saving(function ($product) {
        $product->status = $product->calculateStatus();
    });
}

public function calculateStatus()
{
    if ($this->stock === 0) {
        return 'out_of_stock';
    }
    if ($this->stock <= 3) {
        return 'low_stock';
    }
    return 'active';
}

public function getFormattedPriceAttribute()
{
    return 'KES ' . number_format($this->price, 2);
}

public function getFormattedCostPriceAttribute()
{
    return 'KES ' . number_format($this->cost_price, 2);
}

}
