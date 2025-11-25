<?php

namespace App\Http\Controllers\Prioritisation;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Prioritisation\MonitoringDanube;

class MonitoringDanubeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $resultsObjects = MonitoringDanube::orderby('id', 'asc')->get();

        return view('prioritisation.monitoring-danube.index', compact(
            'resultsObjects'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource with all metadata.
     */
    public function show(string $id)
    {
        $record = MonitoringDanube::findOrFail($id);

        return view('prioritisation.monitoring-danube.show', [
            'record' => $record,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
