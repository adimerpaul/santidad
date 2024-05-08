<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransferHistory extends Model
{
    use HasFactory, softDeletes;
    protected $fillable = ['user_id', 'agencia_id_origen', 'agencia_id_destino', 'producto_id', 'cantidad', 'fecha', 'fecha_entrega_vencimiento', 'hora'];
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function agenciaOrigen(){
        return $this->belongsTo(Agencia::class, 'agencia_id_origen');
    }
    public function agenciaDestino(){
        return $this->belongsTo(Agencia::class, 'agencia_id_destino');
    }
    public function producto(){
        return $this->belongsTo(Product::class);
    }
    protected $hidden = ['deleted_at','created_at','updated_at'];
}
