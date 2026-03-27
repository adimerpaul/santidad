<?php

namespace App\Services;

use SoapClient;

class SiatCodeService
{
    public function solicitarCuis(array $payload): array
    {
        $result = $this->client()->cuis([
            'SolicitudCuis' => $payload,
        ]);

        return $this->normalize($result);
    }

    public function solicitarCufd(array $payload): array
    {
        $result = $this->client()->cufd([
            'SolicitudCufd' => $payload,
        ]);

        return $this->normalize($result);
    }

    private function client(): SoapClient
    {
        return new SoapClient(config('siat.wsdl_codigos'), [
            'stream_context' => stream_context_create([
                'http' => [
                    'header' => 'apikey: TokenApi ' . config('siat.token'),
                ],
            ]),
            'cache_wsdl' => WSDL_CACHE_NONE,
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
            'trace' => 1,
            'use' => SOAP_LITERAL,
            'style' => SOAP_DOCUMENT,
        ]);
    }

    private function normalize(mixed $result): array
    {
        return json_decode(json_encode($result), true) ?: [];
    }
}
