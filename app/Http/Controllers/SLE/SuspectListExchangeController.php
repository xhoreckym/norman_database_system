<?php

namespace App\Http\Controllers\SLE;

use App\Http\Controllers\Controller;
use App\Models\SLE\SuspectListExchangeSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;

class SuspectListExchangeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            'role:admin|super_admin|sle',
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sleSources = SuspectListExchangeSource::orderBy('order', 'asc')->where('show', 1)->get();
        return view('sle.index', [
            'sleSources' => $sleSources,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sleSource = new SuspectListExchangeSource();
        $isCreate = true;
        return view('sle.upsert', compact('sleSource', 'isCreate'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'show' => 'boolean',
            'link_full_list' => 'nullable|string',
            'link_inchikey_list' => 'nullable|string',
            'link_references' => 'nullable|string',
        ]);

        $validated['added_by'] = Auth::id();
        $validated['show'] = $request->has('show') ? 1 : 0;

        SuspectListExchangeSource::create($validated);

        return redirect()->route('sle.index')->with('success', 'Suspect List Exchange Source created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $sleSource = SuspectListExchangeSource::findOrFail($id);
        return view('sle.show', compact('sleSource'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $sleSource = SuspectListExchangeSource::findOrFail($id);
        $isCreate = false;
        return view('sle.upsert', compact('sleSource', 'isCreate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $sleSource = SuspectListExchangeSource::findOrFail($id);

        $validated = $request->validate([
            'code' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'show' => 'boolean',
            'link_full_list' => 'nullable|string',
            'link_inchikey_list' => 'nullable|string',
            'link_references' => 'nullable|string',
        ]);

        $validated['show'] = $request->has('show') ? 1 : 0;

        $sleSource->update($validated);

        return redirect()->route('sle.index')->with('success', 'Suspect List Exchange Source updated successfully.');
    }

    /**
     * Remove the specified resource in storage.
     */
    public function destroy(string $id)
    {
        $sleSource = SuspectListExchangeSource::findOrFail($id);
        $sleSource->delete();

        return redirect()->route('sle.index')->with('success', 'Suspect List Exchange Source deleted successfully.');
    }
}
