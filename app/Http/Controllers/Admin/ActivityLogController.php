<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Log;

class ActivityLogController extends Controller 
{
    public function index()
    {
        $logs = Log::where('organization_id', auth()->user()->organization_id)
            ->orderBy('id', 'desc')
            ->paginate(50);

        return view('admin.activity_log.index', compact('logs'));
    }
}
