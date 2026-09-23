<?php

namespace App\Http\Controllers;

use App\Models\Publicidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class PublicidadController extends Controller
{
    public function index()
    {
        return Publicidad::with('agencia')->orderBy('id', 'desc')->get();
    }

    private function getS3Client(): S3Client
    {
        return new S3Client([
            'version'                 => '2006-03-01',
            'region'                  => config('filesystems.disks.r2.region', 'us-east-1'),
            'endpoint'                => config('filesystems.disks.r2.endpoint'),
            'use_path_style_endpoint' => true,
            'credentials'             => [
                'key'    => config('filesystems.disks.r2.key'),
                'secret' => config('filesystems.disks.r2.secret'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file'       => 'required|file|max:204800',
            'name'       => 'required|string',
            'agencia_id' => 'nullable|exists:agencias,id'
        ]);

        try {
            $file       = $request->file('file');
            $name       = $request->name;
            $agencia_id = $request->agencia_id;
            $mimeType   = $file->getMimeType();
            $type       = str_contains($mimeType, 'video') ? 'video' : 'image';
            $extension  = $file->getClientOriginalExtension();
            $fileName   = $name . '.' . $extension;
            $filePath   = 'publicidad/' . $fileName;

            // Subir a Cloudflare R2 usando S3Client directo (sin chunked encoding)
            $s3 = $this->getS3Client();
            $s3->putObject([
                'Bucket'      => env('AWS_BUCKET'),
                'Key'         => $filePath,
                'Body'        => fopen($file->getRealPath(), 'rb'),
                'ContentType' => $mimeType,
            ]);

            // URL pública del archivo en R2
            $url = rtrim(config('filesystems.disks.r2.url'), '/') . '/' . $filePath;

            $publicidad = Publicidad::create([
                'name'       => $name,
                'file_id'    => $filePath,
                'type'       => $type,
                'url'        => $url,
                'active'     => true,
                'agencia_id' => $agencia_id
            ]);

            $this->notifySocket('new_publicidad', $publicidad);

            return response()->json($publicidad->load('agencia'), 201);
        } catch (AwsException $e) {
            Log::error('AWS/R2 Error: ' . $e->getAwsErrorMessage() . ' | Code: ' . $e->getAwsErrorCode());
            return response()->json(['error' => 'Error R2: ' . $e->getAwsErrorMessage()], 500);
        } catch (\Exception $e) {
            Log::error('Error uploading to Cloudflare R2: ' . $e->getMessage());
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
                $query->where(function ($q) use ($agencia_id) {
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

    public function destroy($id)
    {
        try {
            $publicidad = Publicidad::findOrFail($id);

            // Eliminar de Cloudflare R2
            if ($publicidad->file_id) {
                try {
                    $s3 = $this->getS3Client();
                    $s3->deleteObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'Key'    => $publicidad->file_id,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('No se pudo eliminar el archivo de R2: ' . $e->getMessage());
                }
            }

            $tempPublicidad = clone $publicidad;
            $publicidad->delete();

            $this->notifySocket('new_publicidad', $tempPublicidad);

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

        $this->notifySocket('new_publicidad', $publicidad);

        return response()->json($publicidad);
    }

    private function notifySocket($event, $data)
    {
        try {
            \Illuminate\Support\Facades\Http::post(env('SOCKET_SERVER_URL') . '/notify', [
                'event' => $event,
                'data'  => $data
            ]);
        } catch (\Exception $e) {
            Log::warning('Could not notify socket server: ' . $e->getMessage());
        }
    }
}
