<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
  protected $fillable = [
    'order_id','product_id','name','price','original_price','promotion_id',
    'promotion_name','discount_percentage','quantity','subtotal','sku','image'
  ];

  protected $casts = [
    'price' => 'decimal:2',
    'original_price' => 'decimal:2',
    'discount_percentage' => 'decimal:2',
    'subtotal' => 'decimal:2',
  ];

  public function order(): BelongsTo {
    return $this->belongsTo(Order::class);
  }
}
