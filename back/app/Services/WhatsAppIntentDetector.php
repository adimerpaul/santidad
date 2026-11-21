<?php

namespace App\Services;

use Illuminate\Support\Str;

class WhatsAppIntentDetector
{
    public const OPERATIONAL = ['horario', 'abierta', 'direccion', 'cercana', 'producto', 'disponibilidad', 'precio'];

    public function normalize(string $value): string
    {
        $value = Str::lower(Str::ascii($value));
        $value = preg_replace('/([a-z])\1{2,}/', '$1', $value);

        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9:\/ .-]+/', ' ', $value)));
    }

    public function detect(string $text): array
    {
        $text = $this->normalize($text);
        $routes = [
            'manipulacion' => '/\b(prompt|system message|instrucciones (internas|anteriores|del sistema)|api key|credenciales|instruccion del sistema|ignora .*instruccion)\b/',
            'farmaceutica' => '/\b(dosis|dosificar|receta|antibioticos?|controlados?|tratamiento|sintomas?|efectos secundarios|embarazo|embarazada|amoxicilina|clonazepam|ibuprofeno|paracetamol|que (tomo|puedo tomar)|me duele|cuanto debo tomar|contraindicaciones|puedo mezclar)\b/',
            'queja' => '/\b(queja|reclam[oa]|estafa|insatisfech[oa]|pesim[oa]|mala atencion|mal servicio)\b/',
            'humano' => '/\b(persona|asesor[ao]?|humano|operador[a]?|emplead[oa]|farmaceutic[oa]|alguien que me atienda|hablar con alguien)\b/',
            'seguimiento' => '/\b(estado de mi|seguimiento|mi pedido|mi consulta|ya respondieron)\b/',
        ];
        foreach ($routes as $intent => $pattern) {
            if (preg_match($pattern, $text)) {
                return [$intent];
            }
        }

        $rules = [
            'cercana' => '/\b(cerca|cercan[ao]|cual me queda|mas proxima)\b/',
            'direccion' => '/\b(ubicacion|direccion|mapa|donde|como llego)\b/',
            'abierta' => '/\b(abiert[ao]s?|cerrad[ao]s?|atienden (ahorita|ahora))\b/',
            'horario' => '/\b(horari[oa]s?|orari[oa]s?|a que hora|hasta que hora|atiend[ea]n?|atencion|abren?|cierran?)\b/',
            'producto' => '/\b(producto|marca|presentacion|protector|bloqueador|shampoo|crema|jabon)\b/',
            'disponibilidad' => '/\b(tiene[n]?|hay|stock|disponib\w*|queda[n]?)\b/',
            'precio' => '/\b(precio|cuanto (vale|cuesta|sale)|precios|costo)\b/',
            'pedido' => '/\b(hacer un pedido|quiero pedir|hacer una compra|quiero comprar)\b/',
        ];
        $found = [];
        foreach ($rules as $intent => $pattern) {
            if (preg_match($pattern, $text)) {
                $found[] = $intent;
            }
        }
        if (in_array('abierta', $found, true)) {
            $found = array_diff($found, ['horario']);
        }
        if (in_array('cercana', $found, true)) {
            $found = array_diff($found, ['direccion']);
        }
        if (array_intersect($found, ['cercana', 'abierta', 'horario', 'direccion'])
            && !array_intersect($found, ['producto', 'precio']) && !preg_match('/\b(tienen?|stock|disponib\w*)\b/', $text)) {
            $found = array_diff($found, ['disponibilidad']);
        }
        if (array_intersect($found, ['precio', 'disponibilidad'])) {
            $found = array_diff($found, ['producto']);
        }
        if ($found) {
            return array_values($found);
        }

        return [match (true) {
            preg_match('/\b(gracias|agradezco|muy amable)\b/', $text) === 1 => 'agradecimiento',
            preg_match('/^(chao|chau|adios|hasta luego|nos vemos)[ .]*$/', $text) === 1 => 'despedida',
            preg_match('/^(?:(?:hola|ola|buenas|buenos dias|buenas tardes|buenas noches)[ .]*)+$/', $text) === 1 => 'saludo',
            default => 'desconocida',
        }];
    }
}
