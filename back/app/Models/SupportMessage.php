<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'author', 'text', 'whatsapp_send_key', 'whatsapp_state'];
}
