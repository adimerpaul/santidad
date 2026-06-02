<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class GoogleDriveAuthController extends Controller
{
    private function getClient()
    {
        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $client->addScope(Drive::DRIVE);
        return $client;
    }

    public function login()
    {
        $client = $this->getClient();
        $authUrl = $client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        $code = $request->get('code');
        if (!$code) {
            return "No se recibió código de autorización.";
        }

        try {
            $client = $this->getClient();
            $token = $client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                return "Error al obtener el token: " . $token['error_description'];
            }

            // Guardar el token de forma permanente
            File::put(storage_path('app/google-drive-token.json'), json_encode($token));

            return "¡Autorización exitosa! Ya puedes cerrar esta pestaña y volver al sistema.";
        } catch (\Exception $e) {
            return "Error en el proceso: " . $e->getMessage();
        }
    }
}
