<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $hasCombos = collect($this->input('products', []))->contains(fn ($p) => !empty($p['combo_id']));
        return [
            'request_id' => ['nullable', 'uuid'],
            'canal_venta' => ['nullable', 'in:sucursal,trae'],
            'client' => ['required', 'array'],
            'client.numeroDocumento' => ['required', 'string'],
            'client.nombreRazonSocial' => ['required', 'string'],
            'client.codigoTipoDocumentoIdentidad' => ['required'],
            'client.complemento' => ['nullable', 'string'],
            'client.email' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'integer', ...($hasCombos ? [] : ['distinct']), 'exists:products,id'],
            'products.*.combo_id' => ['nullable', 'required_with:products.*.combo_grupo', 'integer', 'exists:internal_combos,id'],
            'products.*.combo_grupo' => ['nullable', 'required_with:products.*.combo_id', 'uuid'],
            'products.*.combo_cantidad' => ['nullable', 'required_with:products.*.combo_id', 'integer', 'min:1', 'max:100'],
            'products.*.combo_version' => ['nullable', 'required_with:products.*.combo_id', 'integer', 'min:1'],
            'products.*.combo_producto_original_id' => ['nullable', 'integer', 'min:1'],
            'products.*.online_order_number' => ['nullable', 'string', 'max:100', 'exists:orders,order_number'],
            'products.*.cantidadPedida' => ['required', 'integer', 'min:1'],
            'products.*.precioVenta' => ['required', 'numeric', 'gt:0'],
            'products.*.buys' => ['nullable', 'array'],
            'products.*.buys.*.id' => ['required', 'integer', 'exists:buys,id'],
            'products.*.buys.*.cantidadAVender' => ['nullable', 'numeric', 'min:0'],
            'aporte' => ['nullable', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'metodoPago' => ['required', 'in:Efectivo,Tarjeta,Transferencia,Qr,Personalizado'],
            'montoEfectivo' => ['nullable', 'numeric', 'min:0'],
            'montoQr' => ['nullable', 'numeric', 'min:0'],
            'agencia_id' => ['required', 'integer', 'exists:agencias,id'],
        ];
    }
}
