<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Hash;
use App\Services\GoogleCloudStorage;

class UserController extends Controller 
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user(); 
        return view('user.index', ['user' => $user]);
    }

    public function store(Request $request)
    {
        $user = auth()->user(); 
        
        $validation = [
            'email' => 'required|email|unique:users,email,'. $user->id,
        ]; 

        if ($request->password) {
            $validation['password'] = 'required|min:5';
            $user->password = Hash::make($request->password);
            $user->default_password = ''; 
        }

        $request->validate($validation); 

        $user->email = $request->email; 
        $user->save();

        return redirect('/')->with('success', 'Profile anda berhasil dirubah');
    }

    public function paymentHistory()
    {
        $user = auth()->user();
        
        // Fetch pembayarans where user_id matches auth user
        // Order by most recent first
        // Include tagihans relationship
        $pembayarans = \App\Models\Pembayaran::with('tagihans')
            ->where('user_id', $user->id)
            ->whereNot('payment_type', 'deposit_usage') // Exclude deposit usage
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('user.payment_history', [
            'pembayarans' => $pembayarans,
        ]);
    }

    public function tunggakan()
    {
        $user = auth()->user();

        $tunggakan = $user->tunggakanQuery(true)
            ->with('billing')
            ->orderBy('billing_generated_date', 'desc')
            ->get();

        $total = $tunggakan->sum(function ($item) {
            return $item->billing_amount - $item->payment_amount;
        });

        $paymentChannel = \App\Models\Payment::where('organization_id', $user->organization_id)
            ->where('disabled', false)
            ->get();

        return view('user.tunggakan', [
            'tunggakan' => $tunggakan,
            'total' => $total,
            'paymentChannel' => $paymentChannel,
        ]);
    }

    public function upcomingEvents()
    {
        $user = auth()->user();
        
        // Fetch persistent events (always displayed)
        $persistentEvents = \App\Models\Event::where('organization_id', $user->organization_id)
            ->where('persistent_event', true)
            ->orderBy('event_date', 'desc')
            ->paginate(100);
        
        // Fetch upcoming events (excluding persistent events)
        $events = \App\Models\Event::where('organization_id', $user->organization_id)
            ->where('persistent_event', false)
            ->orderBy('event_date', 'desc')
            ->paginate(10);

        return view('user.upcoming_events', [
            'events' => $events,
            'persistentEvents' => $persistentEvents,
        ]);
    }

    public function depositHistory()
    {
        $user = auth()->user();
        
        // Calculate total user deposit (verified only)
        $totalUserDeposit = \App\Models\Pembayaran::where('payment_type', 'user_deposit')
            ->where('status', 'verified')
            ->where('user_id', $user->id)
            ->sum('payment_amount');

        // Calculate total deposit usage (verified only)
        $totalDepositUsage = \App\Models\Pembayaran::where('payment_type', 'deposit_usage')
            ->where('status', 'verified')
            ->where('user_id', $user->id)
            ->sum('payment_amount');

        // Calculate remaining deposit
        $remainingDeposit = $totalUserDeposit - $totalDepositUsage;

        // Fetch deposit usage history with pagination
        $depositUsages = \App\Models\Pembayaran::where('user_id', $user->id)
            ->where('payment_type', 'deposit_usage')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('user.deposit_history', [
            'totalUserDeposit' => $totalUserDeposit,
            'totalDepositUsage' => $totalDepositUsage,
            'remainingDeposit' => $remainingDeposit,
            'depositUsages' => $depositUsages,
        ]);
    }

    public function financialReport()
    {
        $organization = auth()->user()->organization;

        // Calculate summary
        $summary = \App\Models\RtTransaction::where('organization_id', $organization->id)
            ->selectRaw('SUM(credit) as total_income, SUM(debit) as total_expense')
            ->whereNot(function($query) {
                $query->where('billing_id', 0)->where('credit', '>', 0)->where('created_by', 'system');
            })
            ->first();

        $totalIncome = $summary->total_income ?? 0;
        $totalExpense = $summary->total_expense ?? 0;
        $balance = $totalIncome - $totalExpense;

        // Fetch transactions with pagination (10 per page)
        $transactions = \App\Models\RtTransaction::where('organization_id', $organization->id)
            ->whereNot(function($query) {
                $query->where('billing_id', 0)->where('credit', '>', 0)->where('created_by', 'system');
            })
            ->orderBy('payment_date', 'desc')
            ->paginate(10);

        return view('user.financial_report', [
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $balance,
            'transactions' => $transactions,
        ]);
    }

    public function dawisFinancialReport()
    {
        $organization = auth()->user()->organization;

        // Calculate summary
        $summary = \App\Models\DawisTransaction::where('organization_id', $organization->id)
            ->selectRaw('SUM(credit) as total_income, SUM(debit) as total_expense')
            ->first();

        $totalIncome = $summary->total_income ?? 0;
        $totalExpense = $summary->total_expense ?? 0;
        $balance = $totalIncome - $totalExpense;

        // Fetch transactions with pagination (10 per page)
        $transactions = \App\Models\DawisTransaction::where('organization_id', $organization->id)
            ->orderBy('payment_date', 'desc')
            ->paginate(10);

        return view('user.dawis_financial_report', [
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $balance,
            'transactions' => $transactions,
        ]);
    }

    public function tenants()
    {
        $user = auth()->user();

        // Fetch tenants from the same organization
        $tenants = \App\Models\Tenant::where('organization_id', $user->organization_id)
            ->orderBy('unit_number', 'asc')
            ->paginate(30);

        return view('user.tenants', [
            'tenants' => $tenants,
        ]);
    }

    public function showEventFile($eventId, $fileIndex, Request $request)
    {
        $user = auth()->user();

        // Find event in user's organization
        $event = \App\Models\Event::where('organization_id', $user->organization_id)
            ->findOrFail($eventId);

        // Check if event has the requested file
        if (!$event->files || !isset($event->files[$fileIndex])) {
            return response('File not found', 404);
        }

        $fileData = $event->files[$fileIndex];

        try {
            if ($fileData['driver'] === 'gcs') {
                // Serve from Google Cloud Storage
                $gcs = app(GoogleCloudStorage::class);
                return $gcs->response($fileData['path']);
            } elseif ($fileData['driver'] === 'local') {
                // Serve from local storage
                if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($fileData['path'])) {
                    return response('File not found', 404);
                }

                $file = \Illuminate\Support\Facades\Storage::disk('local')->get($fileData['path']);
                $mimeType = \Illuminate\Support\Facades\Storage::disk('local')->mimeType($fileData['path']);

                return response($file, 200)
                    ->header('Content-Type', $mimeType)
                    ->header('Content-Disposition', 'inline; filename="' . basename($fileData['path']) . '"');
            }

            return response('Invalid driver', 400);

        } catch (\Exception $e) {
            \Log::error('Event File Serve Error: ' . $e->getMessage());
            return response('Error serving file', 500);
        }
    }

    public function letters()
    {
        $user = auth()->user();
        
        // Fetch published and public letters
        $letters = \App\Models\Letter::where('organization_id', $user->organization_id)
            ->publicVisible()
            ->orderBy('tanggal_surat', 'desc')
            ->paginate(10);

        return view('user.letters', [
            'letters' => $letters,
        ]);
    }

    public function showLetterFile($letterId, $fileIndex, Request $request)
    {
        $user = auth()->user();
        
        // Find letter in user's organization
        $letter = \App\Models\Letter::where('organization_id', $user->organization_id)
            ->publicVisible()
            ->findOrFail($letterId);
        
        // Check if letter has files
        if (!$letter->files || !isset($letter->files[$fileIndex])) {
            return response('File not found', 404);
        }

        $fileData = $letter->files[$fileIndex];

        try {
            if ($fileData['driver'] === 'gcs') {
                // Serve from Google Cloud Storage
                $gcs = app(GoogleCloudStorage::class);
                return $gcs->response($fileData['path']);
            } elseif ($fileData['driver'] === 'local') {
                // Serve from local storage
                if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($fileData['path'])) {
                    return response('File not found', 404);
                }
                
                $file = \Illuminate\Support\Facades\Storage::disk('local')->get($fileData['path']);
                $mimeType = \Illuminate\Support\Facades\Storage::disk('local')->mimeType($fileData['path']);
                
                return response($file, 200)
                    ->header('Content-Type', $mimeType)
                    ->header('Content-Disposition', 'inline; filename="' . basename($fileData['path']) . '"');
            }
            
            return response('Invalid driver', 400);
            
        } catch (\Exception $e) {
            \Log::error('Letter File Serve Error: ' . $e->getMessage());
            return response('Error serving file', 500);
        }
    }

    public function inventory()
    {
        $user = auth()->user();

        $inventories = \App\Models\Inventory::where('organization_id', $user->organization_id)
            ->orderBy('item_name')
            ->paginate(10);

        return view('user.inventory', [
            'inventories' => $inventories,
        ]);
    }

    public function showInventoryPhoto($inventoryId, $photoIndex)
    {
        $user = auth()->user();

        $inventory = \App\Models\Inventory::where('organization_id', $user->organization_id)
            ->findOrFail($inventoryId);

        if (!$inventory->photos || !isset($inventory->photos[$photoIndex])) {
            return response('File not found', 404);
        }

        $photoData = $inventory->photos[$photoIndex];

        try {
            if ($photoData['driver'] === 'gcs') {
                // Serve from Google Cloud Storage
                $gcs = app(GoogleCloudStorage::class);
                return $gcs->response($photoData['path']);
            } elseif ($photoData['driver'] === 'local') {
                // Serve from local storage
                if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($photoData['path'])) {
                    return response('File not found', 404);
                }

                $file = \Illuminate\Support\Facades\Storage::disk('local')->get($photoData['path']);
                $mimeType = \Illuminate\Support\Facades\Storage::disk('local')->mimeType($photoData['path']);

                return response($file, 200)
                    ->header('Content-Type', $mimeType)
                    ->header('Content-Disposition', 'inline; filename="' . basename($photoData['path']) . '"');
            }

            return response('Invalid driver', 400);

        } catch (\Exception $e) {
            \Log::error('Inventory Photo Serve Error: ' . $e->getMessage());
            return response('Error serving file', 500);
        }
    }

}

