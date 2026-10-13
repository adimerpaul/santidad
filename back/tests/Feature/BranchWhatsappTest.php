<?php

namespace Tests\Feature;

use App\Services\TiendaWebService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BranchWhatsappTest extends TestCase
{
    public function test_contacts_preserve_short_links_and_show_both_shifts(): void
    {
        config(['cache.default' => 'array']);
        Cache::put('tienda_web_agencias', [[
            'id' => 1, 'nombre' => 'Sucursal prueba', 'direccion' => 'Oruro',
            'whatsapp' => 'https://walink.co/224178',
            'whatsapp_turno_2' => '+591 72319869',
        ]], 60);

        $branches = app(TiendaWebService::class)->sucursales();
        $branch = $branches->first();
        $this->assertSame('', $branch->whatsapp);
        $this->assertSame('https://walink.co/224178', $branch->whatsappHref);
        $this->assertSame('https://wa.me/59172319869', $branch->whatsappTurno2Href);
        $html = view('tienda.partials.sucursales', ['sucursales' => $branches])->render();
        $this->assertStringContainsString('WhatsApp <strong>Turno 1</strong>', $html);
        $this->assertStringContainsString('WhatsApp <strong>Turno 2</strong>', $html);
        $this->assertStringNotContainsString('https://wa.me/224178', $html);
    }
}
