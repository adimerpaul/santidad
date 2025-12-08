<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
  protected $fillable = [
    'order_number','customer_id','customer_name','customer_phone','customer_address',
    'subtotal','shipping','total','status','source','whatsapp_message','meta'
  ];

  protected $casts = [
    'meta' => 'array',
    'subtotal' => 'decimal:2',
    'shipping' => 'decimal:2',
    'total' => 'decimal:2',
  ];

  public function items(): HasMany
  {
    return $this->hasMany(OrderItem::class);
  }
}
