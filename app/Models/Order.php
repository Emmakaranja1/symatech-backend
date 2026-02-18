<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Product;

class Order extends Model
{
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
