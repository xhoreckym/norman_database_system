<?php

namespace App\Http\Controllers\Factsheet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;

class FactsheetHomeController extends Controller
{
    public function index()
    {
        // here we will only redirect to the filter page, where the user will choose substance via livewire factsheet/SubstanceSearch component
        return redirect()->route('factsheets.search.filter');
    }

    public function countAll()
    {
        return 0;
    }
}
