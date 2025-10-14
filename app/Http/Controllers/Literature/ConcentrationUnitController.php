<?php

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\Literature\ConcentrationUnit;
use Illuminate\Http\Request;

class ConcentrationUnitController extends Controller
{
    public function index()
    {
        $concentrationUnits = ConcentrationUnit::orderBy('name')->paginate(25);
        return view('literature.concentration_units.index', compact('concentrationUnits'));
    }

    public function create()
    {
        $concentrationUnit = new ConcentrationUnit();
        $isCreate = true;
        return view('literature.concentration_units.upsert', compact('concentrationUnit', 'isCreate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_concentration_units,name',
        ]);

        ConcentrationUnit::create($validated);

        return redirect()->route('literature.concentration_units.index')
            ->with('success', 'Concentration unit created successfully.');
    }

    public function edit(ConcentrationUnit $concentrationUnit)
    {
        $isCreate = false;
        return view('literature.concentration_units.upsert', compact('concentrationUnit', 'isCreate'));
    }

    public function update(Request $request, ConcentrationUnit $concentrationUnit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_concentration_units,name,' . $concentrationUnit->id,
        ]);

        $concentrationUnit->update($validated);

        return redirect()->route('literature.concentration_units.index')
            ->with('success', 'Concentration unit updated successfully.');
    }

    public function destroy(ConcentrationUnit $concentrationUnit)
    {
        $concentrationUnit->delete();

        return redirect()->route('literature.concentration_units.index')
            ->with('success', 'Concentration unit deleted successfully.');
    }
}
