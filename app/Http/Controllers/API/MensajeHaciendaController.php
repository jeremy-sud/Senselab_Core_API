<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MensajeHacienda;
use Illuminate\Http\Request;

class MensajeHaciendaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', MensajeHacienda::class);
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', MensajeHacienda::class);
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(MensajeHacienda $mensajeHacienda)
    {
        $this->authorize('view', $mensajeHacienda);
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MensajeHacienda $mensajeHacienda)
    {
        $this->authorize('update', $mensajeHacienda);
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MensajeHacienda $mensajeHacienda)
    {
        $this->authorize('delete', $mensajeHacienda);
        //
    }
}
