<?php

namespace Tests\Unit;

use App\Models\Buy;
use App\Models\Product;
use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyRoundingTest extends TestCase
{
    public function test_product_prices_and_cost_are_stored_with_one_decimal(): void
    {
        $product = new Product();
        $product->precio = 10.06;
        $product->precioAntes = 12.04;
        $product->costo = 7.76;

        $this->assertEquals(10.1, $product->precio);
        $this->assertEquals(12.0, $product->precioAntes);
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

    public function test_purchase_price_and_total_are_stored_with_one_decimal(): void
    {
        $buy = new Buy();
        $buy->price = 10.06;
        $buy->total = 30.18;

        $this->assertEquals(10.1, $buy->price);
        $this->assertEquals(30.2, $buy->total);
    }
}
