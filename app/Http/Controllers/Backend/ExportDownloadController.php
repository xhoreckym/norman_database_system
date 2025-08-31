<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Backend\ExportDownload;

class ExportDownloadController extends Controller
{
    /**
     * Display a listing of export downloads for a specific user.
     */
    public function index(Request $request)
    {
        $userId = $request->input('user_id');
        
        if (!$userId) {
            return redirect()->back()->with('error', 'User ID is required');
        }

        $exportDownloads = ExportDownload::with('user')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('backend.export_downloads.index', compact('exportDownloads', 'userId'));
    }
}
