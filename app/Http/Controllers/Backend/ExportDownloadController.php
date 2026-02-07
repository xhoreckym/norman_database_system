<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\ExportDownload;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExportDownloadController extends Controller
{
    /**
     * Display a listing of export downloads for a specific user.
     */
    public function index(Request $request): View
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser->hasRole('super_admin');

        // Get users with exports (for super_admin dropdown)
        $usersWithExports = collect();
        if ($isSuperAdmin) {
            $usersWithExports = User::query()
                ->whereHas('exportDownloads')
                ->select('id', 'first_name', 'last_name', 'email')
                ->withCount('exportDownloads')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        // Determine which user's downloads to show
        if ($isSuperAdmin) {
            $userId = $request->input('user_id');
        } else {
            // Non-super_admin can only view their own downloads
            $userId = $currentUser->id;
        }

        // For super_admin with no user selected, show empty state with dropdown
        if (! $userId && $isSuperAdmin) {
            return view('backend.export_downloads.index', [
                'exportDownloads' => collect(),
                'userId' => null,
                'user' => null,
                'isSuperAdmin' => $isSuperAdmin,
                'usersWithExports' => $usersWithExports,
            ]);
        }

        if (! $userId) {
            return redirect()->back()->with('error', 'User ID is required');
        }

        $exportDownloads = ExportDownload::with('user')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        // Get user information for display
        $user = User::find($userId);

        return view('backend.export_downloads.index', compact(
            'exportDownloads',
            'userId',
            'user',
            'isSuperAdmin',
            'usersWithExports'
        ));
    }
}
