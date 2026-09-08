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
        return [
            'client' => ['required', 'array'],
            'client.numeroDocumento' => ['required', 'string'],
            'client.nombreRazonSocial' => ['required', 'string'],
            'client.codigoTipoDocumentoIdentidad' => ['required'],
            'client.complemento' => ['nullable', 'string'],
            'client.email' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'integer', 'distinct', 'exists:products,id'],
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
