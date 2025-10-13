<?php

namespace App\Http\Controllers\Literature;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\Backend\QueryLog;
use App\Http\Controllers\Controller;
use App\Models\Backend\ExportDownload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LiteratureController extends Controller
{
    public function filter(Request $request)
    {
        // TODO: Implement filter logic once database table is created
        
        return view('literature.filter', [
            'request' => $request,
        ]);
    }
    
    public function search(Request $request)
    {
        // TODO: Implement search logic once database table is created
        
        $database_key = 'literature';
        $literatureObjectsCount = DatabaseEntity::where('code', $database_key)->first()->number_of_records ?? 0;
        
        return view('literature.index', [
            'request' => $request,
            'literatureObjectsCount' => $literatureObjectsCount,
        ]);
    }

    public function startDownloadJob($query_log_id)
    {
        if (!Auth::check()) {
            session()->flash('error', 'You must be logged in to download the CSV file.');
            return back();
        }

        // TODO: Implement download logic once database table is created
        
        session()->flash('error', 'Download functionality not yet implemented.');
        return back();
    }

    public function downloadCsv($filename)
    {
        $directory = 'exports/literature';
        $path = Storage::path("{$directory}/{$filename}");
        
        if (!file_exists($path)) {
            return response()->json([
                'error' => 'File not found',
                'message' => 'The requested CSV file does not exist.',
            ], 404);
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function show($id)
    {
        // TODO: Implement show logic once database table is created
        
        return view('literature.show', [
            'id' => $id,
        ]);
    }

    public function edit($id)
    {
        if (!auth()->check() || 
            !(auth()->user()->hasRole('super_admin') || 
              auth()->user()->hasRole('admin') || 
              auth()->user()->hasRole('literature'))) {
            session()->flash('error', 'You do not have permission to edit Literature records.');
            return redirect()->route('literature.search.search');
        }

        // TODO: Implement edit logic once database table is created
        
        return view('literature.edit', [
            'id' => $id,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!auth()->check() || 
            !(auth()->user()->hasRole('super_admin') || 
              auth()->user()->hasRole('admin') || 
              auth()->user()->hasRole('literature'))) {
            session()->flash('error', 'You do not have permission to update Literature records.');
            return redirect()->route('literature.search.search');
        }

        // TODO: Implement update logic once database table is created
        
        session()->flash('success', 'Literature record updated successfully.');
        
        return redirect()->route('literature.search.show', $id);
    }

    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
