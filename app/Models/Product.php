<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Order;

class Product extends Model
{


    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    use HasFactory;

    
protected $fillable = ['name', 'description', 'price', 'stock'];
}
