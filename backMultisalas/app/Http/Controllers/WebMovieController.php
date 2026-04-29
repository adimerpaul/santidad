<?php

namespace App\Http\Controllers;

use App\Models\WebMovie;
use App\Models\WebMovieActor;
use App\Models\WebStudio;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebMovieController extends Controller
{
    public function index()
    {
        return WebMovie::with(['studio', 'actores', 'programas'])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $studio = $this->resolveStudio($data['studio_nombre'] ?? null);
        $actors = $data['actores'] ?? [];
        unset($data['studio_nombre'], $data['actores']);

        $movie = WebMovie::create([
            ...$data,
            'web_studio_id' => $studio?->id,
        ]);

        $this->syncActors($movie, $actors);

        return $this->show($movie);
    }

    public function show(WebMovie $webMovie)
    {
        return WebMovie::with(['studio', 'actores', 'programas'])->findOrFail($webMovie->id);
    }

    public function update(Request $request, WebMovie $webMovie)
    {
        $data = $this->validatePayload($request);
        $studio = $this->resolveStudio($data['studio_nombre'] ?? null);
        $actors = $data['actores'] ?? [];
        unset($data['studio_nombre'], $data['actores']);

        $webMovie->update([
            ...$data,
            'web_studio_id' => $studio?->id,
        ]);

        $this->syncActors($webMovie, $actors);

        return $this->show($webMovie);
    }

    public function destroy(WebMovie $webMovie)
    {
        $webMovie->delete();
        return response()->noContent();
    }

    public function searchExternal(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        if (!$this->hasTmdbCredentials()) {
            return response()->json([
                'message' => 'Configura TMDB_BEARER_TOKEN o TMDB_API_KEY en el backend.',
            ], 422);
        }

        $response = $this->tmdbHttp()
            ->get(config('services.tmdb.url') . '/search/movie', [
                'query' => $request->input('query'),
                'include_adult' => false,
                'page' => 1,
                'language' => config('services.tmdb.language', 'es-ES'),
            ]);

        if (!$response->ok()) {
            return response()->json([
                'message' => 'No se pudo consultar TMDB.',
            ], 502);
        }

        return collect($response->json('results') ?? [])->map(function ($item) {
            return [
                'tmdb_id' => $item['id'] ?? null,
                'imdb_id' => null,
                'titulo' => $item['title'] ?? null,
                'anio' => $this->toYear($item['release_date'] ?? null),
                'tipo' => 'movie',
                'imagen' => $this->tmdbImageUrl($item['poster_path'] ?? null, 'w342'),
            ];
        })->values();
    }

    public function importExternal(Request $request)
    {
        $request->validate([
            'tmdb_id' => 'required|integer',
            'tipo' => 'nullable|in:pelicula,proximo',
            'carrusel_tipo' => 'nullable|in:ninguno,carrusel_1,carrusel_2,ambos',
            'calidad' => 'nullable|string|max:50',
            'bucket' => 'nullable|string|max:100',
            'descuento' => 'nullable|numeric|min:0|max:100',
            'tele' => 'nullable|string|max:50',
            'me_gusta' => 'nullable|integer|min:0',
            'trailer_youtube' => 'nullable|string|max:255',
        ]);

        if (!$this->hasTmdbCredentials()) {
            return response()->json([
                'message' => 'Configura TMDB_BEARER_TOKEN o TMDB_API_KEY en el backend.',
            ], 422);
        }

        $tmdbId = (int) $request->tmdb_id;
        $response = $this->tmdbHttp()->get(
            config('services.tmdb.url') . '/movie/' . $tmdbId,
            [
                'append_to_response' => 'credits,videos,images,release_dates,external_ids',
                'language' => config('services.tmdb.language', 'es-ES'),
                'include_image_language' => 'es,null',
            ]
        );

        if (!$response->ok()) {
            return response()->json([
                'message' => 'No se pudo consultar el detalle en TMDB.',
            ], 502);
        }

        $movieData = $response->json();
        if (!isset($movieData['id'])) {
            return response()->json([
                'message' => 'TMDB no devolvio datos de pelicula.',
            ], 404);
        }

        $studioName = $movieData['production_companies'][0]['name'] ?? null;
        $studio = $this->resolveStudio($studioName);
        $trailer = $request->trailer_youtube ?: $this->resolveYoutubeTrailer($movieData['videos']['results'] ?? []);
        $posterLocal = $this->downloadImageLocally(
            $this->tmdbImageUrl($movieData['poster_path'] ?? null, 'original'),
            'webmovies'
        );

        $backdropLocal = $this->downloadImageLocally(
            $this->tmdbImageUrl($movieData['backdrop_path'] ?? null, 'original'),
            'webmovies'
        );

        $imdbId = $movieData['external_ids']['imdb_id'] ?? null;

        $movie = WebMovie::updateOrCreate(
            ['tmdb_id' => $movieData['id']],
            [
                'titulo' => $movieData['title'] ?? 'Sin titulo',
                'tipo' => $request->tipo ?? 'pelicula',
                'carrusel_tipo' => $request->carrusel_tipo ?? 'ninguno',
                'tmdb_id' => $movieData['id'],
                'imdb_id' => $imdbId,
                'titulo_original' => $movieData['original_title'] ?? null,
                'web_studio_id' => $studio?->id,
                'imagen' => $posterLocal,
                'backdrop_imagen' => $backdropLocal,
                'trailer_youtube' => $trailer,
                'url_video_youtube' => $trailer,
                'bucket' => $request->bucket ?? 'local',
                'puntaje_web' => $movieData['vote_average'] ?? null,
                'puntaje_tomatoes' => null,
                'puntaje_ibm' => $movieData['vote_average'] ?? null,
                'puntaje_metacritic' => null,
                'votos_total' => $movieData['vote_count'] ?? null,
                'popularidad' => $movieData['popularity'] ?? null,
                'tagline' => $movieData['tagline'] ?? null,
                'url_oficial' => $movieData['homepage'] ?? null,
                'calidad' => $request->calidad ?? 'HD',
                'descuento' => $request->descuento ?? 0,
                'anio' => $this->toYear($movieData['release_date'] ?? null),
                'fecha_estreno' => $this->parseDate($movieData['release_date'] ?? null),
                'tele' => $request->tele,
                'descripcion' => $movieData['overview'] ?? null,
                'me_gusta' => $request->me_gusta ?? 0,
                'duracion' => isset($movieData['runtime']) ? $movieData['runtime'] . ' min' : null,
                'genero' => collect($movieData['genres'] ?? [])->pluck('name')->implode(', '),
                'pais' => collect($movieData['production_countries'] ?? [])->pluck('name')->implode(', '),
                'idioma' => collect($movieData['spoken_languages'] ?? [])->pluck('name')->implode(', '),
                'clasificacion' => $this->resolveCertification($movieData['release_dates']['results'] ?? []),
                'premios' => null,
                'estado' => 'ACTIVO',
                'api_payload' => [
                    'source' => 'tmdb',
                    'movie' => $movieData,
                    'local_assets' => [
                        'poster_local' => $posterLocal,
                        'backdrop_local' => $backdropLocal,
                        'bucket' => $request->bucket ?? 'local',
                    ],
                ],
            ]
        );

        $movie->actores()->delete();
        $cast = array_slice($movieData['credits']['cast'] ?? [], 0, 20);
        foreach ($cast as $actor) {
            $actorImage = $this->downloadImageLocally(
                $this->tmdbImageUrl($actor['profile_path'] ?? null, 'original'),
                'webmovies/actors'
            );

            WebMovieActor::create([
                'web_movie_id' => $movie->id,
                'nombre' => $actor['name'] ?? 'Sin nombre',
                'imagen' => $actorImage,
            ]);
        }

        return $this->show($movie);
    }

    public function listProgramaciones()
    {
        return \App\Models\Programa::whereDate('fecha', '>=', now()->toDateString())
            ->with('movie', 'sala', 'price')
            ->orderBy('fecha')
            ->orderBy('horaInicio')
            ->get();
    }

    public function syncProgramaciones(Request $request, WebMovie $webMovie)
    {
        $data = $request->validate([
            'programa_ids' => 'nullable|array',
            'programa_ids.*' => 'integer|exists:programas,id',
        ]);

        $ids = $data['programa_ids'] ?? [];
        $webMovie->programas()->sync($ids);

        return $this->show($webMovie);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'titulo' => 'required|string|max:255',
            'tipo' => 'nullable|in:pelicula,proximo',
            'carrusel_tipo' => 'nullable|in:ninguno,carrusel_1,carrusel_2,ambos',
            'tmdb_id' => 'nullable|integer',
            'imdb_id' => 'nullable|string|max:50',
            'titulo_original' => 'nullable|string|max:255',
            'studio_nombre' => 'nullable|string|max:255',
            'imagen' => 'nullable|string|max:255',
            'backdrop_imagen' => 'nullable|string|max:255',
            'trailer_youtube' => 'nullable|string|max:255',
            'url_video_youtube' => 'nullable|string|max:255',
            'bucket' => 'nullable|string|max:100',
            'puntaje_web' => 'nullable|numeric|min:0|max:10',
            'puntaje_tomatoes' => 'nullable|numeric|min:0|max:10',
            'puntaje_ibm' => 'nullable|numeric|min:0|max:10',
            'puntaje_metacritic' => 'nullable|numeric|min:0|max:10',
            'votos_total' => 'nullable|integer|min:0',
            'popularidad' => 'nullable|numeric|min:0',
            'tagline' => 'nullable|string|max:255',
            'url_oficial' => 'nullable|string|max:255',
            'calidad' => 'nullable|string|max:50',
            'descuento' => 'nullable|numeric|min:0|max:100',
            'anio' => 'nullable|integer|min:1888|max:2100',
            'fecha_estreno' => 'nullable|date',
            'tele' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
            'me_gusta' => 'nullable|integer|min:0',
            'duracion' => 'nullable|string|max:50',
            'genero' => 'nullable|string|max:255',
            'pais' => 'nullable|string|max:255',
            'idioma' => 'nullable|string|max:255',
            'clasificacion' => 'nullable|string|max:50',
            'premios' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:50',
            'api_payload' => 'nullable|array',
            'actores' => 'nullable|array',
            'actores.*.nombre' => 'required|string|max:255',
            'actores.*.imagen' => 'nullable|string|max:255',
        ]);
    }

    private function resolveStudio(?string $studioName): ?WebStudio
    {
        if (!$studioName) {
            return null;
        }

        return WebStudio::firstOrCreate([
            'nombre' => trim($studioName),
        ]);
    }

    private function syncActors(WebMovie $movie, array $actors): void
    {
        $movie->actores()->delete();
        foreach ($actors as $actor) {
            WebMovieActor::create([
                'web_movie_id' => $movie->id,
                'nombre' => trim($actor['nombre']),
                'imagen' => $actor['imagen'] ?? null,
            ]);
        }
    }

    private function hasTmdbCredentials(): bool
    {
        return (bool) (config('services.tmdb.bearer_token') || config('services.tmdb.api_key'));
    }

    private function tmdbHttp(): PendingRequest
    {
        $request = Http::timeout(20)->acceptJson();
        $bearerToken = config('services.tmdb.bearer_token');
        $apiKey = config('services.tmdb.api_key');

        if ($bearerToken) {
            $request = $request->withToken($bearerToken);
        } elseif ($apiKey) {
            $request = $request->withQueryParameters(['api_key' => $apiKey]);
        }

        return $request;
    }

    private function tmdbImageUrl(?string $path, string $size = 'original'): ?string
    {
        if (!$path) {
            return null;
        }

        return rtrim(config('services.tmdb.image_url', 'https://image.tmdb.org/t/p'), '/') . '/' . $size . $path;
    }

    private function resolveYoutubeTrailer(array $videos): ?string
    {
        $trailer = collect($videos)->first(function ($video) {
            return ($video['site'] ?? '') === 'YouTube' && in_array($video['type'] ?? '', ['Trailer', 'Teaser']);
        });

        if (!$trailer || empty($trailer['key'])) {
            return null;
        }

        return 'https://www.youtube.com/watch?v=' . $trailer['key'];
    }

    private function resolveCertification(array $releaseDates): ?string
    {
        $cert = collect($releaseDates)->first(function ($item) {
            return in_array($item['iso_3166_1'] ?? '', ['US', 'BO']);
        });

        if (!$cert) {
            return null;
        }

        $entry = collect($cert['release_dates'] ?? [])->first(function ($release) {
            return !empty($release['certification']);
        });

        return $entry['certification'] ?? null;
    }

    private function parseDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private function toYear(?string $value): ?int
    {
        if (!$value) {
            return null;
        }

        preg_match('/\d{4}/', $value, $matches);
        return isset($matches[0]) ? (int) $matches[0] : null;
    }

    private function downloadImageLocally(?string $imageUrl, string $subDirectory = 'webmovies'): ?string
    {
        if (!$imageUrl) {
            return null;
        }

        try {
            $response = Http::timeout(20)->get($imageUrl);
            if (!$response->ok()) {
                return null;
            }

            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            $extension = $extension ?: 'jpg';
            $filename = time() . '_' . Str::random(8) . '.' . $extension;
            $directory = public_path('imagen/' . trim($subDirectory, '/'));

            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($directory . DIRECTORY_SEPARATOR . $filename, $response->body());
            return trim($subDirectory, '/') . '/' . $filename;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
