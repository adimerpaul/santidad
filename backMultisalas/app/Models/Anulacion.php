<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Anulacion extends Model{
    use HasFactory, SoftDeletes;
    protected $table = 'anulaciones';

    protected $fillable = [
        'fecha',
        'cajero',
        'monto',
        'user_id',
        'user_autoriza_id',
        'user_anulacion_id',
        'motivo',
        'sale_id',
        'seccion',
        'detalle',
        'estado',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Usuario que autoriza la anulación
    public function userAutoriza()
    {
        return $this->belongsTo(User::class, 'user_autoriza_id');
    }

    // Usuario que ejecuta la anulación
    public function userAnulacion()
    {
        return $this->belongsTo(User::class, 'user_anulacion_id');
    }

    // Venta asociada a la anulación
    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
}
