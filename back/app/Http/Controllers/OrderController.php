<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
  public function store(Request $request)
  {
    $data = $request->validate([
      'items' => 'required|array|min:1',
      'items.*.product_id' => 'nullable|integer',
      'items.*.nombre' => 'required|string',
      'items.*.precio' => 'required|numeric|min:0',
      'items.*.cantidad' => 'required|integer|min:1',
      'items.*.imagen' => 'nullable|string',
      'customer' => 'nullable|array',
      'customer.name' => 'nullable|string',
      'customer.phone' => 'nullable|string',
      'customer.address' => 'nullable|string',
    ]);

    $order = DB::transaction(function () use ($data) {
      $subtotal = collect($data['items'])->reduce(function ($carry, $i) {
        return $carry + ((float)$i['precio'] * (int)$i['cantidad']);
      }, 0);

      // 1) Crea orden con valores básicos (aún sin número)
      $order = Order::create([
        'order_number'    => 'tmp', // placeholder, actualizamos abajo
        'customer_name'   => $data['customer']['name']    ?? null,
        'customer_phone'  => $data['customer']['phone']   ?? null,
        'customer_address'=> $data['customer']['address'] ?? null,
        'subtotal'        => $subtotal,
        'shipping'        => 0,
        'total'           => $subtotal,
        'status'          => 'pending',
        'source'          => 'web',
      ]);

      // 2) Número de pedido estable y único usando el ID autoincremental
      //    -> puedes formatear con ceros a la izquierda si quieres
      //    $order->order_number = sprintf('PEDIDOWEB_Nº%06d', $order->id);
      $order->order_number = 'PEDIDOWEB_Nº' . $order->id;
      $order->save();

      // 3) Ítems
      foreach ($data['items'] as $i) {
        OrderItem::create([
          'order_id'   => $order->id,
          'product_id' => $i['product_id'] ?? null,
          'name'       => $i['nombre'],
          'price'      => (float)$i['precio'],
          'quantity'   => (int)$i['cantidad'],
          'subtotal'   => (float)$i['precio'] * (int)$i['cantidad'],
          'image'      => $i['imagen'] ?? null,
        ]);
      }

      return $order;
    });

    return response()->json([
      'id' => $order->id,
      'order_number' => $order->order_number,
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
