<?php

namespace App\Http\Controllers\Ecotox;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PNECDerivationController extends Controller
{
    public function index()
    {
        return view('ecotox.pnecderivation.index');
    }
}
