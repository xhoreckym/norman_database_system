<?php

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\Literature\HabitatType;
use Illuminate\Http\Request;

class HabitatTypeController extends Controller
{
    public function index()
    {
        $habitatTypes = HabitatType::orderBy('name')->paginate(25);
        return view('literature.habitat_types.index', compact('habitatTypes'));
    }

    public function create()
    {
        $habitatType = new HabitatType();
        $isCreate = true;
        return view('literature.habitat_types.upsert', compact('habitatType', 'isCreate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_habitat_types,name',
        ]);

        HabitatType::create($validated);

        return redirect()->route('literature.habitat_types.index')
            ->with('success', 'Habitat type created successfully.');
    }

    public function edit(HabitatType $habitatType)
    {
        $isCreate = false;
        return view('literature.habitat_types.upsert', compact('habitatType', 'isCreate'));
    }

    public function update(Request $request, HabitatType $habitatType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_habitat_types,name,' . $habitatType->id,
        ]);

        $habitatType->update($validated);

        return redirect()->route('literature.habitat_types.index')
            ->with('success', 'Habitat type updated successfully.');
    }

    public function destroy(HabitatType $habitatType)
    {
        $habitatType->delete();

        return redirect()->route('literature.habitat_types.index')
            ->with('success', 'Habitat type deleted successfully.');
    }
}
