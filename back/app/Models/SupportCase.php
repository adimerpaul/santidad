<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupportCase extends Model
{
    protected $fillable = ['agencia_id', 'order_id', 'customer_name', 'source', 'status', 'mode'];

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ((int) $user->id === 1) {
            return $query;
        }

        return $user->agencia_id
            ? $query->where('agencia_id', $user->agencia_id)
            : $query->whereRaw('1 = 0');
    }

    public function agencia(): BelongsTo
    {
        return $this->belongsTo(Agencia::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(SupportMessage::class)->latestOfMany();
    }
}
