<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BanecoQrService
{
    private string $baseUrl;
    private ?string $aesKey;
    private ?string $username;
    private ?string $password;
    private ?string $accountCredit;
    private ?string $branchCode;

    public function __construct()
    {
        $this->baseUrl       = rtrim(config('services.baneco.base_url'), '/');
        $this->aesKey        = config('services.baneco.aes_key');
        $this->username      = config('services.baneco.username');
        $this->password      = config('services.baneco.password');
        $this->accountCredit = config('services.baneco.account_credit');
        $this->branchCode    = config('services.baneco.branch_code');
    }

    public function generateQr(float $amount, string $transactionId, string $description = ''): array
    {
        $token = $this->authenticate();
        $accountCreditEncrypted = $this->encrypt($this->accountCredit);

        $response = Http::withToken($token)->post("{$this->baseUrl}/api/qrsimple/generateQR", [
            'transactionId' => $transactionId,
            'accountCredit' => $accountCreditEncrypted,
            'currency'      => 'BOB',
            'amount'        => $amount,
            'description'   => $description,
            'dueDate'       => now()->addDay()->format('Y-m-d'),
            'singleUse'     => true,
            'modifyAmount'  => false,
            'branchCode'    => $this->branchCode,
        ]);

        $data = $response->json();

        if (!$response->successful() || ($data['responseCode'] ?? null) !== 0) {
            throw new RuntimeException('Fallo al generar QR: ' . ($data['message'] ?? $response->body()));
        }

        $qrImage = (string) $data['qrImage'];
        if (!str_starts_with($qrImage, 'data:image')) {
            $qrImage = 'data:image/png;base64,' . $qrImage;
        }

        return [
            'qrId'    => (string) $data['qrId'],
            'qrImage' => $qrImage,
        ];
    }

    public function statusQr(string $qrId): array
    {
        $token = $this->authenticate();

        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/api/qrsimple/v2/statusQR/" . rawurlencode($qrId));

        $data = $response->json();

        if (!$response->successful() || ($data['responseCode'] ?? null) !== 0) {
            throw new RuntimeException('Fallo al consultar estado del QR: ' . ($data['message'] ?? $response->body()));
        }

        return [
            'statusQrCode' => (int) ($data['statusQrCode'] ?? $data['statusQRCode'] ?? -1),
            'payment'      => $data['payment'] ?? null,
        ];
    }

    public function cancelQr(string $qrId): void
    {
        $token = $this->authenticate();

        $response = Http::withToken($token)
            ->asJson()
            ->delete("{$this->baseUrl}/api/qrsimple/cancelQR", ['qrId' => $qrId]);

        $data = $response->json();

        if (!$response->successful() || ($data['responseCode'] ?? null) !== 0) {
            throw new RuntimeException('Fallo al anular QR: ' . ($data['message'] ?? $response->body()));
        }
    }

    private function authenticate(): string
    {
        return Cache::remember('baneco_qr_token', 1500, function () {
            $passwordEncrypted = $this->encrypt($this->password);

            $response = Http::post("{$this->baseUrl}/api/authentication/authenticate", [
                'userName' => $this->username,
                'password' => $passwordEncrypted,
            ]);

            $data = $response->json();

            if (!$response->successful() || ($data['responseCode'] ?? null) !== 0 || empty($data['token'])) {
                throw new RuntimeException('Fallo autenticación Baneco: ' . ($data['message'] ?? $response->body()));
            }

            return $data['token'];
        });
    }

    private function encrypt(string $text): string
    {
        $response = Http::get("{$this->baseUrl}/api/authentication/encrypt", [
            'text'   => $text,
            'aesKey' => $this->aesKey,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Error al encriptar datos con Baneco: ' . $response->body());
        }

        return trim($response->body(), "\" \r\n\t");
    }
}
