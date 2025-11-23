<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LogAccesoSistema;
use Illuminate\Http\Request;

class LogAccesoSistemaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', LogAccesoSistema::class);
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', LogAccesoSistema::class);
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(LogAccesoSistema $logAccesoSistema)
    {
        $this->authorize('view', $logAccesoSistema);
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LogAccesoSistema $logAccesoSistema)
    {
        $this->authorize('update', $logAccesoSistema);
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LogAccesoSistema $logAccesoSistema)
    {
        $this->authorize('delete', $logAccesoSistema);
        //
    }
}
