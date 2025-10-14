<?php

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\Literature\Species;
use Illuminate\Http\Request;

class SpeciesController extends Controller
{
    public function index()
    {
        $species = Species::orderBy('name')->paginate(25);
        return view('literature.species.index', compact('species'));
    }

    public function create()
    {
        $species = new Species();
        $isCreate = true;
        return view('literature.species.upsert', compact('species', 'isCreate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'name_latin' => 'nullable|string|max:255',
            'kingdom' => 'nullable|string|max:255',
            'phylum' => 'nullable|string|max:255',
            'order' => 'nullable|string|max:255',
            'class' => 'nullable|string|max:255',
            'genus' => 'nullable|string|max:255',
        ]);

        Species::create($validated);

        return redirect()->route('literature.species.index')
            ->with('success', 'Species created successfully.');
    }

    public function edit(Species $species)
    {
        $isCreate = false;
        return view('literature.species.upsert', compact('species', 'isCreate'));
    }

    public function update(Request $request, Species $species)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'name_latin' => 'nullable|string|max:255',
            'kingdom' => 'nullable|string|max:255',
            'phylum' => 'nullable|string|max:255',
            'order' => 'nullable|string|max:255',
            'class' => 'nullable|string|max:255',
            'genus' => 'nullable|string|max:255',
        ]);

        $species->update($validated);

        return redirect()->route('literature.species.index')
            ->with('success', 'Species updated successfully.');
    }

    public function destroy(Species $species)
    {
        $species->delete();

        return redirect()->route('literature.species.index')
            ->with('success', 'Species deleted successfully.');
    }
}
