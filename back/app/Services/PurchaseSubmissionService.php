<?php

namespace App\Services;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseSubmissionService
{
    public function run(Request $request, string $operation, callable $save)
    {
        $request->validate(['request_id' => 'nullable|uuid']);
        if (!$request->filled('request_id')) {
            return $save(); // Preserve compatibility with existing callers.
        }

        $identity = [
            'request_id' => $request->input('request_id'),
            'user_id' => $request->user()->id,
            'operation' => $operation,
        ];
        $hash = hash('sha256', json_encode($request->except('request_id')));

        return DB::transaction(function () use ($identity, $hash, $save) {
            DB::table('purchase_submissions')->insertOrIgnore($identity + [
                'input_hash' => $hash, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $submission = DB::table('purchase_submissions')->where($identity)->lockForUpdate()->first();
            if ($submission->input_hash !== $hash) {
                throw ValidationException::withMessages([
                    'request_id' => 'Esta operación ya se envió con otros datos. No se volverá a registrar la compra.',
                ]);
            }
            if ($submission->response !== null) {
                return response()->json(json_decode($submission->response, true), $submission->status_code);
            }

            $response = $save();
            if ($response->getStatusCode() >= 400) {
                // Roll back the operation key too, allowing corrected failed requests.
                throw new HttpResponseException($response);
            }
            DB::table('purchase_submissions')->where('id', $submission->id)->update([
                'response' => $response->getContent(), 'status_code' => $response->getStatusCode(),
                'updated_at' => now(),
            ]);
            return $response;
        });
    }
}
