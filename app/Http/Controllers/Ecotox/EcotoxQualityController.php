<?php

namespace App\Http\Controllers\Ecotox;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Backend\QueryLog;
use Illuminate\Support\Facades\Auth;

class EcotoxQualityController extends Controller
{
    /**
     * Show the search filter form.
     */
    public function filter()
    {
        return view('ecotox.quality.filter');
    }
    
    /**
     * Process the search and display results.
     */
    public function search(Request $request)
    {
        // Log the search query if user is authenticated
        if (Auth::check()) {
            QueryLog::create([
                'user_id' => Auth::id(),
                'module' => 'ecotox_quality',
                'query' => $request->getQueryString() ?? '',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
        
        // Search logic will be implemented later
        // For now, just return the search view
        
        return view('ecotox.quality.search');
    }
}
