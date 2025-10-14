<?php

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Literature\LiteratureTempMain;
use Illuminate\Http\Request;

class LiteratureHomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('literature.home.index');
    }

    /**
     * Update the database counts for Literature module
     */
    public function countAll()
    {
        DatabaseEntity::where('code', 'literature')->update([
            'last_update' => LiteratureTempMain::max('updated_at'),
            'number_of_records' => LiteratureTempMain::count()
        ]);
        session()->flash('success', 'Literature database counts updated successfully');
        return redirect()->back();
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
