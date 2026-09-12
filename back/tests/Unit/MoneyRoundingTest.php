<?php

namespace Tests\Unit;

use App\Models\Buy;
use App\Models\Product;
use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyRoundingTest extends TestCase
{
    public function test_product_base_prices_preserve_cents_and_cost_keeps_its_rounding(): void
    {
        $product = new Product();
        $product->precio = 10.06;
        $product->precioAntes = 12.04;
        $product->costo = 7.76;

        $this->assertEquals(10.06, $product->precio);
        $this->assertEquals(12.04, $product->precioAntes);
        $this->assertEquals(7.8, $product->costo);
    }

    public function test_discounted_sale_price_is_rounded_to_one_tenth(): void
    {
        $product = new Product();
        $product->precio = 10.5;
        $product->porcentaje = 15;

        $this->assertEquals(8.9, $product->precioVentaRedondeado());
        $this->assertEquals(1.6, round($product->precio - $product->precioVentaRedondeado(), 1));
    }

    public function test_small_discount_uses_the_same_tenth_rounding_as_the_sale(): void
    {
        $product = new Product();
        $product->precio = 0.3;
        $product->porcentaje = 15;

        $this->assertEquals(0.3, $product->precioVentaRedondeado());
    }

    public function test_manual_discount_is_applied_before_rounding_to_tenths(): void
    {
        $product = new Product();
        $product->precio = 0.46;
        $product->porcentaje = 8;

        $this->assertEquals(0.46, $product->precio);
        $this->assertEquals(0.4, $product->precioVenta);
        $this->assertEquals(1.2, Money::roundToCents($product->precioVenta * 3));

        $product->precio = 0.49;
        $this->assertEquals(0.5, $product->precioVenta);
        $product->porcentaje = 0;
        $this->assertEquals(0.5, $product->precioVenta);
    }

    public function test_cart_can_multiply_the_official_rounded_unit_price_directly(): void
    {
        $product = new Product();
        $product->precio = 10.9;
        $product->porcentaje = 8;

        $this->assertEquals(10.0, $product->precioVenta);
        $this->assertEquals(30.0, $product->precioVenta * 3);
        $this->assertEquals(10.0, $product->toArray()['precioVenta']);
    }

    public function test_contextual_promotion_price_overrides_only_the_serialized_sale_price(): void
    {
        $product = new Product();
        $product->precio = 20;
        $product->porcentaje = 10;
        $product->setAttribute('precioVenta', 15.0);

        $this->assertEquals(15.0, $product->precioVenta);
        $this->assertEquals(15.0, $product->toArray()['precioVenta']);
        $this->assertEquals(10, $product->porcentaje);
    }

    public function test_only_the_payable_total_is_rounded_to_one_decimal(): void
    {
        $this->assertEquals(2.5, Money::roundToTenth(2.54));
        $this->assertEquals(2.6, Money::roundToTenth(2.55));
        $this->assertEquals(0.3, Money::roundToTenth(0.26));
    }

    public function test_purchase_unit_price_preserves_cents_and_total_keeps_its_rounding(): void
    {
        $buy = new Buy();
        $buy->price = 10.06;
        $buy->total = 30.18;

        $this->assertEquals(10.06, $buy->price);
        $this->assertEquals(30.2, $buy->total);
    }
}
