<?php
declare(strict_types=1);

$baseUrl = 'https://apimktdesa.baneco.com.bo/ApiGateway';
$aesKey = '40A318B299F245C2B697176723088629';
$plainText = '1234';
$userName = '26551010';
$passwordPlain = '1234';
$accountCredit = '1061602532';
$currency = 'BOB';
$amount = 1.20;
$description = 'Prueba QR desde PHP';
$singleUse = true;
$modifyAmount = false;
$branchCode = 'E0001';
$outputImagePath = __DIR__ . DIRECTORY_SEPARATOR . 'pago.png';
$pollIntervalSeconds = 2;
$maxPollAttempts = 9000;

function banecoGet(string $baseUrl, string $path, array $query = []): string
{
    $url = rtrim($baseUrl, '/') . $path;
    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException("Error GET cURL: $error");
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($statusCode < 200 || $statusCode >= 300) {
        throw new RuntimeException("GET HTTP $statusCode: $response");
    }

    return trim($response, "\" \r\n\t");
}

function banecoPost(string $baseUrl, string $path, array $body): array
{
    $url = rtrim($baseUrl, '/') . $path;
    $payload = json_encode($body, JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        throw new RuntimeException('No se pudo serializar el body JSON.');
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException("Error POST cURL: $error");
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($statusCode < 200 || $statusCode >= 300) {
        throw new RuntimeException("POST HTTP $statusCode: $response");
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException("Respuesta JSON inválida: $response");
    }

    return $decoded;
}

function banecoPostAuth(string $baseUrl, string $path, array $body, string $token): array
{
    $url = rtrim($baseUrl, '/') . $path;
    $payload = json_encode($body, JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        throw new RuntimeException('No se pudo serializar el body JSON.');
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException("Error POST cURL: $error");
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($statusCode < 200 || $statusCode >= 300) {
        throw new RuntimeException("POST HTTP $statusCode: $response");
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException("Respuesta JSON inválida: $response");
    }

    return $decoded;
}

function banecoGetAuth(string $baseUrl, string $path, string $token): array
{
    $url = rtrim($baseUrl, '/') . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException("Error GET cURL: $error");
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($statusCode < 200 || $statusCode >= 300) {
        throw new RuntimeException("GET HTTP $statusCode: $response");
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException("Respuesta JSON inválida: $response");
    }

    return $decoded;
}

try {
    echo "== Prueba 1: Encriptación ==\n";
    $cipherText = banecoGet($baseUrl, '/api/authentication/encrypt', [
        'text' => $plainText,
        'aesKey' => $aesKey,
    ]);
    echo "Texto plano: $plainText\n";
    echo "Texto cifrado: $cipherText\n\n";

    echo "== Prueba 2: Desencriptación ==\n";
    $decryptedText = banecoGet($baseUrl, '/api/authentication/decrypt', [
        'text' => $cipherText,
        'aesKey' => $aesKey,
    ]);
    echo "Texto desencriptado: $decryptedText\n";
    if ($decryptedText !== $plainText) {
        throw new RuntimeException('Fallo de validación: desencriptado distinto al original.');
    }
    echo "OK: encriptación/desencriptación válida.\n";
    echo "Cuenta para abonos configurada: $accountCredit\n\n";

    echo "== Prueba 3: Autenticación básica ==\n";
    $passwordEncrypted = banecoGet($baseUrl, '/api/authentication/encrypt', [
        'text' => $passwordPlain,
        'aesKey' => $aesKey,
    ]);

    $auth = banecoPost($baseUrl, '/api/authentication/authenticate', [
        'userName' => $userName,
        'password' => $passwordEncrypted,
    ]);

    $responseCode = $auth['responseCode'] ?? null;
    $message = (string)($auth['message'] ?? '');
    $token = (string)($auth['token'] ?? '');

    echo "responseCode: " . (string)$responseCode . "\n";
    echo "message: $message\n";

    if ($responseCode !== 0) {
        throw new RuntimeException("Fallo autenticación: $message");
    }
    if ($token === '') {
        throw new RuntimeException('Fallo autenticación: no se recibió token.');
    }

    echo "token (inicio): " . substr($token, 0, 30) . "...\n\n";

    echo "== Prueba 4: Generación de QR (7.2) ==\n";
    $accountCreditEncrypted = banecoGet($baseUrl, '/api/authentication/encrypt', [
        'text' => $accountCredit,
        'aesKey' => $aesKey,
    ]);

    $transactionId = 'TRX' . date('YmdHis');
    $dueDate = date('Y-m-d', strtotime('+7 days'));
    $qrResponse = banecoPostAuth($baseUrl, '/api/qrsimple/generateQR', [
        'transactionId' => $transactionId,
        'accountCredit' => $accountCreditEncrypted,
        'currency' => $currency,
        'amount' => $amount,
        'description' => $description,
        'dueDate' => $dueDate,
        'singleUse' => $singleUse,
        'modifyAmount' => $modifyAmount,
        'branchCode' => $branchCode,
    ], $token);

    $qrResponseCode = $qrResponse['responseCode'] ?? null;
    $qrMessage = (string)($qrResponse['message'] ?? '');
    $qrId = (string)($qrResponse['qrId'] ?? '');
    $qrImageBase64 = (string)($qrResponse['qrImage'] ?? '');

    echo "responseCode: " . (string)$qrResponseCode . "\n";
    echo "message: $qrMessage\n";
    echo "qrId: $qrId\n";

    if ($qrResponseCode !== 0) {
        throw new RuntimeException("Fallo generateQR: $qrMessage");
    }
    if ($qrImageBase64 === '') {
        throw new RuntimeException('Fallo generateQR: no se recibió qrImage.');
    }

    if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $qrImageBase64) === 1) {
        $parts = explode(',', $qrImageBase64, 2);
        $qrImageBase64 = $parts[1] ?? '';
    }

    $imageBinary = base64_decode($qrImageBase64, true);
    if ($imageBinary === false) {
        throw new RuntimeException('Fallo generateQR: qrImage no es base64 válido.');
    }

    $bytesWritten = file_put_contents($outputImagePath, $imageBinary);
    if ($bytesWritten === false || $bytesWritten === 0) {
        throw new RuntimeException('No se pudo guardar el archivo de imagen del QR.');
    }

    echo "Imagen QR guardada en: $outputImagePath\n\n";

    echo "== Prueba 5: Verificación de pago (7.4 statusQR) ==\n";
    $isPaid = false;

    for ($attempt = 1; $attempt <= $maxPollAttempts; $attempt++) {
        $status = banecoGetAuth($baseUrl, '/api/qrsimple/v2/statusQR/' . rawurlencode($qrId), $token);
        $statusResponseCode = $status['responseCode'] ?? null;
        $statusMessage = (string)($status['message'] ?? '');
        $statusQrCode = (int)($status['statusQrCode'] ?? $status['statusQRCode'] ?? -1);

        if ($statusResponseCode !== 0) {
            throw new RuntimeException("Fallo statusQR: $statusMessage");
        }

        echo "[Intento $attempt/$maxPollAttempts] statusQrCode: $statusQrCode\n";

        if ($statusQrCode === 1) {
            $isPaid = true;
            $payment = $status['payment'] ?? [];
            if (is_array($payment) && isset($payment[0]) && is_array($payment[0])) {
                $paymentDate = (string)($payment[0]['paymentDate'] ?? '');
                $paymentTime = (string)($payment[0]['paymentTime'] ?? '');
                $paymentAmount = (string)($payment[0]['amount'] ?? '');
                echo "PAGO CONFIRMADO: amount=$paymentAmount date=$paymentDate time=$paymentTime\n";
            } else {
                echo "PAGO CONFIRMADO.\n";
            }
            break;
        }

        if ($statusQrCode === 9) {
            throw new RuntimeException('El QR fue anulado (statusQrCode=9).');
        }

        sleep($pollIntervalSeconds);
    }

    if (!$isPaid) {
        throw new RuntimeException('No se confirmó el pago dentro del tiempo de espera.');
    }

    echo "\n";
    echo "Resultado final: smoke test completado.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

