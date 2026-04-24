<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role->nom !== 'Admin') {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        if ($request->has('level') && $request->level !== 'all') {
            $query->where('level', $request->level);
        }

        if ($request->has('search') && $request->search) {
            $query->where('message', 'like', '%' . $request->search . '%');
        }

        $logs = $query->paginate(50);

        return response()->json(['success' => true, 'data' => $logs]);
    }
}