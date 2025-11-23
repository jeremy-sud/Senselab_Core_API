<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PlanillaCcss;
use Illuminate\Http\Request;

class PlanillaCcssController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', PlanillaCcss::class);
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', PlanillaCcss::class);
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PlanillaCcss $planillaCcss)
    {
        $this->authorize('view', $planillaCcss);
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PlanillaCcss $planillaCcss)
    {
        $this->authorize('update', $planillaCcss);
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlanillaCcss $planillaCcss)
    {
        $this->authorize('delete', $planillaCcss);
        //
    }
}
