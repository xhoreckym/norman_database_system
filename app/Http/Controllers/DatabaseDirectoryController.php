<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use Illuminate\Database\QueryException;
use Illuminate\Database\ConnectionException;

class DatabaseDirectoryController extends Controller
{
    //

    public function index()
    {
        try {
            $databases = DatabaseEntity::orderby('id', 'asc')->where('show_in_dashboard', true)->get();
            return view('landing.index', [
                'databases' => $databases
            ]);
        } catch (QueryException $e) {
            return view('errors.database-offline');
        }
    }
}
