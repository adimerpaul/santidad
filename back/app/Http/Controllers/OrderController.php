<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Money;
use App\Services\PromotionPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
  public function __construct(private readonly PromotionPricingService $promotionPricing)
  {
  }

  public function store(Request $request)
  {
    $data = $request->validate([
      'items' => 'required|array|min:1',
      'items.*.product_id' => 'nullable|integer|exists:products,id',
      'items.*.nombre' => 'required|string',
      'items.*.precio' => 'required|numeric|min:0',
      'items.*.cantidad' => 'required|integer|min:1',
      'items.*.imagen' => 'nullable|string',
      'customer' => 'nullable|array',
      'customer.name' => 'nullable|string',
      'customer.phone' => 'nullable|string',
      'customer.address' => 'nullable|string',
      'source' => 'nullable|string|max:50',
      'sucursal_id' => 'nullable|integer',
      'sucursal_nombre' => 'nullable|string',
    ]);

    $order = DB::transaction(function () use ($data) {
      $agenciaId = !empty($data['sucursal_id']) ? (int) $data['sucursal_id'] : null;
      $channel = ($data['source'] ?? 'web') === 'app' ? 'app' : 'web';
      $items = collect($data['items'])->map(function ($item) use ($agenciaId, $channel) {
        $product = !empty($item['product_id']) ? Product::find($item['product_id']) : null;
        $pricing = $product
          ? $this->promotionPricing->resolve($product, $channel, $agenciaId)
          : [
              'precio_original' => Money::roundToTenth($item['precio']),
              'precio_venta' => Money::roundToTenth($item['precio']),
              'porcentaje' => 0,
              'promocion_id' => null,
              'promocion_nombre' => null,
            ];
        $precio = $pricing['precio_venta'];
        $cantidad = (int) $item['cantidad'];

        return array_merge($item, [
          'precio' => $precio,
          'cantidad' => $cantidad,
          'subtotal' => Money::roundToCents($precio * $cantidad),
          'pricing' => $pricing,
        ]);
      });
      $subtotalCentavos = $items->sum(fn ($item) => Money::toCents($item['subtotal']));
      $subtotal = Money::fromCents($subtotalCentavos);
      $totalCentavos = Money::roundCentsToTenth($subtotalCentavos);
      $total = Money::fromCents($totalCentavos);
      $ajusteRedondeo = Money::fromCents($totalCentavos - $subtotalCentavos);

      // 1) Crea orden con valores básicos (aún sin número)
      $order = Order::create([
        'order_number'    => 'tmp', // placeholder, actualizamos abajo
        'customer_name'   => $data['customer']['name']    ?? null,
        'customer_phone'  => $data['customer']['phone']   ?? null,
        'customer_address'=> $data['customer']['address'] ?? null,
        'subtotal'        => $subtotal,
        'shipping'        => 0,
        'total'           => $total,
        'calculated_total'=> $subtotal,
        'rounding_adjustment' => $ajusteRedondeo,
        'status'          => 'pending',
        'source'          => $data['source'] ?? 'web',
        'meta'            => isset($data['sucursal_id']) || isset($data['sucursal_nombre'])
          ? [
              'sucursal_id'     => $data['sucursal_id'] ?? null,
              'sucursal_nombre' => $data['sucursal_nombre'] ?? null,
            ]
          : null,
      ]);

      // 2) Número de pedido estable y único usando el ID autoincremental
      //    -> puedes formatear con ceros a la izquierda si quieres
      //    $order->order_number = sprintf('PEDIDOWEB_Nº%06d', $order->id);
      $order->order_number = 'PEDIDOWEB_Nº' . $order->id;
      $order->save();

      // 3) Ítems
      foreach ($items as $i) {
        OrderItem::create([
          'order_id'   => $order->id,
          'product_id' => $i['product_id'] ?? null,
          'name'       => $i['nombre'],
          'price'      => $i['precio'],
          'original_price' => $i['pricing']['precio_original'],
          'promotion_id' => $i['pricing']['promocion_id'],
          'promotion_name' => $i['pricing']['promocion_nombre'],
          'discount_percentage' => $i['pricing']['porcentaje'],
          'quantity'   => $i['cantidad'],
          'subtotal'   => $i['subtotal'],
          'image'      => $i['imagen'] ?? null,
        ]);
      }

      return $order;
    });

    $order->load('items');

    return response()->json([
      'id' => $order->id,
      'order_number' => $order->order_number,
      'subtotal' => $order->subtotal,
      'calculated_total' => $order->calculated_total,
      'rounding_adjustment' => $order->rounding_adjustment,
      'total' => $order->total,
      'items' => $order->items,
    ], 201);
  }

  // Para recuperar luego en “ventas” usando el número de pedido
  public function showByNumber(string $orderNumber)
  {
    $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();
    return response()->json($order);
  }

  // (Opcional) guardar el mensaje de WhatsApp en la orden
  // public function saveMessage(Request $request, int $id)
  // {
  //   $data = $request->validate(['whatsapp_message' => 'required|string']);
  //   $order = Order::findOrFail($id);
  //   $order->whatsapp_message = $data['whatsapp_message'];
  //   $order->save();
  //   return response()->json(['ok' => true]);
  // }
}
