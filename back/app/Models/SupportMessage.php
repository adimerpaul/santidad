<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'author', 'text', 'whatsapp_send_key', 'whatsapp_state'];

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
