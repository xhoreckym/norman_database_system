<?php

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\Literature\LifeStage;
use Illuminate\Http\Request;

class LifeStageController extends Controller
{
    public function index()
    {
        $lifeStages = LifeStage::orderBy('name')->paginate(25);
        return view('literature.life_stages.index', compact('lifeStages'));
    }

    public function create()
    {
        $lifeStage = new LifeStage();
        $isCreate = true;
        return view('literature.life_stages.upsert', compact('lifeStage', 'isCreate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_life_stages,name',
        ]);

        LifeStage::create($validated);

        return redirect()->route('literature.life_stages.index')
            ->with('success', 'Life stage created successfully.');
    }

    public function edit(LifeStage $lifeStage)
    {
        $isCreate = false;
        return view('literature.life_stages.upsert', compact('lifeStage', 'isCreate'));
    }

    public function update(Request $request, LifeStage $lifeStage)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_life_stages,name,' . $lifeStage->id,
        ]);

        $lifeStage->update($validated);

        return redirect()->route('literature.life_stages.index')
            ->with('success', 'Life stage updated successfully.');
    }

    public function destroy(LifeStage $lifeStage)
    {
        $lifeStage->delete();

        return redirect()->route('literature.life_stages.index')
            ->with('success', 'Life stage deleted successfully.');
    }
}
