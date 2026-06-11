<?php

namespace App\Http\Controllers;

use App\Models\Agencia;
use App\Http\Requests\StoreAgenciaRequest;
use App\Http\Requests\UpdateAgenciaRequest;
use Illuminate\Support\Facades\Cache;

class AgenciaController extends Controller
{
    public function index()
    {
        return Cache::rememberForever('agencias_list', function () {
            return Agencia::all();
        });
    }

    public function store(StoreAgenciaRequest $request)
    {
        $agencia = Agencia::create($request->all());
        $this->clearCache();
        return $agencia;
    }

    public function show(Agencia $agencia)
    {
        return $agencia;
    }

    public function update(UpdateAgenciaRequest $request, Agencia $agencia)
    {
        $res = $agencia->update($request->all());
        $this->clearCache();
        return $res;
    }

    public function destroy(Agencia $agencia)
    {
        $res = $agencia->delete();
        $this->clearCache();
        return $res;
    }

    private function clearCache()
    {
        Cache::forget('agencias_list');
    }
}
