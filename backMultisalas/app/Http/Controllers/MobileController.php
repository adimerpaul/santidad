<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Proximo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileController extends Controller{
    public function movies(){
        $date = date('Y-m-d');
        //en vez de dantidad usada
        return DB::select("
    SELECT
        m.id,
        m.nombre,
        m.duracion,
        m.formato,
        m.imagen,
        (
            SELECT COUNT(*)
            FROM tickets
            WHERE programa_id = p.id AND devuelto = 0
        ) AS cantidad_usada,
        (
            SELECT capacidad
            FROM salas
            WHERE id = p.sala_id
        ) AS capacidad_sala,
        (
            SELECT COUNT(*)
            FROM tickets
            WHERE programa_id = p.id AND devuelto = 0
        ) < (
            SELECT capacidad
            FROM salas
            WHERE id = p.sala_id
        ) AS disponible
    FROM
        programas p
    INNER JOIN movies m ON p.movie_id = m.id
    WHERE
        p.fecha = '$date'
        AND p.activo = 'ACTIVO'
    GROUP BY
        m.id, m.nombre, m.duracion, m.formato, m.imagen;
");
    }
    public function eventos(){
        return Evento::where('estado','ACTIVO')->get();
    }
    public function proximos(){
        return Proximo::where('estado','ACTIVO')->with('casts')->get();
    }
}
