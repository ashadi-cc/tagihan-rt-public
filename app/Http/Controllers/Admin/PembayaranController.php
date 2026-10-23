<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pembayaran;

class PembayaranController extends Controller 
{
    public function index(Request $request)
    {
        $organization = auth()->user()->organization;
        
        $query = Pembayaran::with('user')
            ->where('organization_id', $organization->id)
            ->where('payment_type', '!=', 'deposit_usage');
        
        // Status filter - default to pending
        $status = $request->get('status', 'pending');
        if (in_array($status, ['pending', 'verified', 'rejected'])) {
            $query->where('status', $status);
        }
        
        // Order by created_at - ASC for pending, DESC for others
        $orderDirection = ($status === 'pending') ? 'asc' : 'desc';
        $query->orderBy('created_at', $orderDirection);
        
        // Payment type filter
        $paymentType = $request->get('payment_type', 'all');
        if (in_array($paymentType, ['current_billing', 'previous_billing', 'user_deposit', 'manual_billing'])) {
            $query->where('payment_type', $paymentType);
        }
        
        // Search functionality - search by user name or user blok
        if (trim($request->get('q')) != "") {
            $search = '%'. trim($request->get('q')) . '%'; 
            $query->where(function($q) use ($search) {
                $q->where('user_name', 'like', $search)
                  ->orWhere('user_blok', 'like', $search);
            });
        }

        $pembayarans = $query->paginate(100);

        return view('admin.payment.index', compact('pembayarans', 'status', 'paymentType'));
    }
}
