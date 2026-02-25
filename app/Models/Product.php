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

    protected $fillable = ['name', 'description', 'price', 'stock', 'category', 'image', 'rating'];

    protected static $logAttributes = ['name', 'price', 'stock', 'category', 'image', 'rating'];

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
}

}
