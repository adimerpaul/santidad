<?php

namespace App\Http\Controllers;

use App\Models\Publicidad;
use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class PublicidadController extends Controller
{
    private function getDriveService()
    {
        $tokenPath = storage_path('app/google-drive-token.json');
        if (!File::exists($tokenPath)) {
            return null;
        }

        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setAccessToken(json_decode(File::get($tokenPath), true));

        // Si el token expiró, lo refrescamos
        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                File::put($tokenPath, json_encode($client->getAccessToken()));
            } else {
                return null;
            }
        }

        return new Drive($client);
    }

    public function index()
    {
        return Publicidad::with('agencia')->orderBy('id', 'desc')->get();
    }

    public function store(Request $request)
    {
        $driveService = $this->getDriveService();
        if (!$driveService) {
            return response()->json(['error' => 'Drive no vinculado. Por favor, visita /api/drive/login en el navegador.'], 401);
        }

        $request->validate([
            'file' => 'required|file|max:204800', // 200MB max
            'name' => 'required|string',
            'agencia_id' => 'nullable|exists:agencias,id'
        ]);

        try {
            $file = $request->file('file');
            $name = $request->name;
            $agencia_id = $request->agencia_id;
            $mimeType = $file->getMimeType();
            $type = str_contains($mimeType, 'video') ? 'video' : 'image';
            $folderId = env('GOOGLE_DRIVE_FOLDER_ID');

            $fileMetadata = new \Google\Service\Drive\DriveFile([
                'name' => $name . '.' . $file->getClientOriginalExtension(),
                'parents' => [$folderId]
            ]);

            $content = file_get_contents($file->getRealPath());

            $driveFile = $driveService->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);

            // Permisos públicos
            $permission = new \Google\Service\Drive\Permission([
                'type' => 'anyone',
                'role' => 'reader'
            ]);
            $driveService->permissions->create($driveFile->id, $permission);

            // No guardamos copia local, solo dejamos el archivo en Drive

            $publicidad = Publicidad::create([
                'name' => $name,
                'file_id' => $driveFile->id,
                'type' => $type,
                'url' => 'https://docs.google.com/uc?export=download&id=' . $driveFile->id,
                'active' => true,
                'agencia_id' => $agencia_id
            ]);

            $this->notifySocket('new_publicidad', $publicidad);

            return response()->json($publicidad->load('agencia'), 201);
        } catch (\Exception $e) {
            Log::error('Error uploading to personal Drive: ' . $e->getMessage());
            return response()->json(['error' => 'Error al subir: ' . $e->getMessage()], 500);
        }
    }

    public function publicidadActual(Request $request)
    {
        try {
            $agencia_id = $request->agencia_id;
            Log::info('Consultando publicidad para agencia: ' . ($agencia_id ?? 'GLOBAL'));

            $query = Publicidad::where('active', true);

            if ($agencia_id) {
                $query->where(function($q) use ($agencia_id) {
                    $q->where('agencia_id', $agencia_id)
                      ->orWhereNull('agencia_id');
                });
            } else {
                $query->whereNull('agencia_id');
            }

            $publicidades = $query->orderBy('id', 'desc')->get();

            if ($publicidades->isEmpty()) {
                return response()->json(['message' => 'No hay publicidad activa'], 200);
            }

            return response()->json($publicidades);
        } catch (\Exception $e) {
            Log::error('Error en publicidadActual: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // El método streamFile ha sido eliminado porque pcpubli ahora reproduce de archivos locales descargados

    public function destroy($id)
    {
        try {
            $publicidad = Publicidad::findOrFail($id);
            $driveService = $this->getDriveService();
            if ($driveService) {
                try {
                    $driveService->files->delete($publicidad->file_id);
                } catch (\Exception $e) {
                    Log::warning('Could not delete file from personal Drive: ' . $e->getMessage());
                }
            }
            $tempPublicidad = clone $publicidad; // Clonar para tener los datos después de borrar
            $publicidad->delete();

            // No borramos copia local porque ya no se crea en el servidor
            
            $this->notifySocket('new_publicidad', $tempPublicidad); // Notificar borrado

            return response()->json(['message' => 'Publicidad eliminada correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
    }
    
    public function toggleActive($id)
    {
        $publicidad = Publicidad::findOrFail($id);
        $publicidad->active = !$publicidad->active;
        $publicidad->save();

        $this->notifySocket('new_publicidad', $publicidad); // Notificar siempre el cambio de estado

        return response()->json($publicidad);
    }

    private function notifySocket($event, $data)
    {
        try {
            \Illuminate\Support\Facades\Http::post(env('SOCKET_SERVER_URL') . '/notify', [
                'event' => $event,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::warning('Could not notify socket server: ' . $e->getMessage());
        }
    }
}
