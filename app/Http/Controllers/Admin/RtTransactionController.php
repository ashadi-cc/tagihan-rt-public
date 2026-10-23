<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RtTransaction;
use App\Services\GoogleCloudStorage;
use Carbon\Carbon;
use App\Exports\TransactonExport;
use Excel;

class RtTransactionController extends Controller 
{
    public function index(Request $request)
    {
        $organization = auth()->user()->organization;
        
        $query = RtTransaction::where('organization_id', $organization->id)
            ->orderBy('payment_date', 'desc');
        
        // Search functionality
        if (trim($request->get('q')) != "") {
            $search = '%'. trim($request->get('q')) . '%'; 
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('description', 'like', $search)
                  ->orWhere('created_by', 'like', $search);
            });
        }

        // Filter by type (income/expense)
        if ($request->get('type') == 'income') {
            $query->where('credit', '>', 0)->where('debit', 0);
        } elseif ($request->get('type') == 'expense') {
            $query->where('debit', '>', 0)->where('credit', 0);
        }

        // Filter by date range
        if ($request->get('start_date')) {
            $query->whereDate('payment_date', '>=', $request->get('start_date'));
        }
        if ($request->get('end_date')) {
            $query->whereDate('payment_date', '<=', $request->get('end_date'));
        }

        $transactions = $query->paginate(50);

        // Calculate summary
        $summary = RtTransaction::where('organization_id', $organization->id)
            ->selectRaw('SUM(credit) as total_income, SUM(debit) as total_expense')
            ->first();

        $totalIncome = $summary->total_income ?? 0;
        $totalExpense = $summary->total_expense ?? 0;
        $balance = $totalIncome - $totalExpense;

        return view('admin.rt-transaction.index', compact('transactions', 'totalIncome', 'totalExpense', 'balance'));
    }

    public function create()
    {
        return view('admin.rt-transaction.create');
    }

    public function store(Request $request)
    {
        $organization = auth()->user()->organization;

        $request->validate([
            'payment_date' => 'required|date',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'proof_file' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:5120', // Max 5MB
        ]);

        $transaction = new RtTransaction();
        $transaction->organization_id = $organization->id;
        $transaction->payment_date = $request->payment_date;
        $transaction->name = $request->name;
        $transaction->description = $request->description;
        $transaction->status = 'verified';
        $transaction->created_by = auth()->user()->name;
        $transaction->created_user_id = auth()->user()->id;

        // Set debit or credit based on type
        if ($request->type == 'income') {
            $transaction->credit = $request->amount;
            $transaction->debit = 0;
        } else {
            $transaction->debit = $request->amount;
            $transaction->credit = 0;
        }

        // Handle file upload
        if ($request->hasFile('proof_file')) {
            $gcs = app(GoogleCloudStorage::class);
            $file = $request->file('proof_file');
            
            // Generate filename
            $slug = \Illuminate\Support\Str::slug($request->name);
            $slug = substr($slug, 0, 30);
            $extension = $file->getClientOriginalExtension();
            $timestamp = time();
            $fileName = 'rt-transactions/' . $slug . '-' . $timestamp . '.' . $extension;
            
            // Read file contents and upload
            $fileStream = fopen($file->getRealPath(), 'r');
            $result = $gcs->put($fileName, $fileStream);
            
            if ($result) {
                $transaction->file_driver = 'gcs';
                $transaction->file_path = $fileName;
            }
        }

        $transaction->save();

        // Create log
        $type = $request->type == 'income' ? 'Pemasukan' : 'Pengeluaran';
        $transaction->logs()->create([
            'organization_id' => auth()->user()->organization_id,
            'username' => auth()->user()->name,
            'description' => "{$type} baru '{$transaction->name}' sebesar Rp " . number_format($request->amount, 0, ',', '.') . " telah ditambahkan",
            'url_link' => '/admin/rt-transaction/' . $transaction->id,
        ]);

        return redirect('/admin/rt-transaction')->with('success', 'Transaksi berhasil ditambahkan');
    }

    public function show($id)
    {
        $organization = auth()->user()->organization;
        $transaction = RtTransaction::where('organization_id', $organization->id)->findOrFail($id);
        
        return view('admin.rt-transaction.show', compact('transaction'));
    }

    public function edit($id)
    {
        $organization = auth()->user()->organization;
        $transaction = RtTransaction::where('organization_id', $organization->id)->findOrFail($id);
        
        // Prevent editing system transactions
        if (strtolower($transaction->created_by ?? '') === 'system') {
            return redirect('/admin/rt-transaction')->with('error', 'Transaksi sistem tidak dapat diedit');
        }
        
        return view('admin.rt-transaction.edit', compact('transaction'));
    }

    public function update(Request $request, $id)
    {
        $organization = auth()->user()->organization;
        $transaction = RtTransaction::where('organization_id', $organization->id)->findOrFail($id);

        // Prevent updating system transactions
        if (strtolower($transaction->created_by ?? '') === 'system') {
            return redirect('/admin/rt-transaction')->with('error', 'Transaksi sistem tidak dapat diupdate');
        }

        $request->validate([
            'payment_date' => 'required|date',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'proof_file' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        // Track changes for logging
        $changes = [];

        if ($transaction->payment_date != $request->payment_date) {
            $changes[] = "Tanggal diubah dari {$transaction->payment_date->format('d/m/Y')} menjadi " . Carbon::parse($request->payment_date)->format('d/m/Y');
        }

        if ($transaction->name != $request->name) {
            $changes[] = "Nama diubah dari '{$transaction->name}' menjadi '{$request->name}'";
        }

        $oldAmount = $transaction->isIncome() ? $transaction->credit : $transaction->debit;
        if ($oldAmount != $request->amount) {
            $changes[] = "Jumlah diubah dari Rp " . number_format($oldAmount, 0, ',', '.') . " menjadi Rp " . number_format($request->amount, 0, ',', '.');
        }

        if ($transaction->description != $request->description) {
            $changes[] = "Deskripsi diperbarui";
        }

        // Handle file upload
        if ($request->hasFile('proof_file')) {
            $gcs = app(GoogleCloudStorage::class);
            $file = $request->file('proof_file');
            
            $slug = \Illuminate\Support\Str::slug($request->name);
            $slug = substr($slug, 0, 30);
            $extension = $file->getClientOriginalExtension();
            $timestamp = time();
            $fileName = 'rt-transactions/' . $slug . '-' . $timestamp . '.' . $extension;
            
            $fileStream = fopen($file->getRealPath(), 'r');
            $result = $gcs->put($fileName, $fileStream);
            
            if ($result) {
                $transaction->file_driver = 'gcs';
                $transaction->file_path = $fileName;
                $changes[] = "Bukti file diperbarui";
            }
        }

        $transaction->payment_date = $request->payment_date;
        $transaction->name = $request->name;
        $transaction->description = $request->description;

        // Update debit/credit based on type
        if ($request->type == 'income') {
            $transaction->credit = $request->amount;
            $transaction->debit = 0;
        } else {
            $transaction->debit = $request->amount;
            $transaction->credit = 0;
        }

        $transaction->save();

        // Create log if there are changes
        if (!empty($changes)) {
            $description = "Transaksi '{$transaction->name}' diupdate:\n" . implode("\n", $changes);
            $transaction->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => auth()->user()->name,
                'description' => $description,
                'url_link' => '/admin/rt-transaction/' . $transaction->id,
            ]);
        }

        return redirect('/admin/rt-transaction')->with('success', 'Transaksi berhasil diperbarui');
    }

    public function destroy($id)
    {
        $organization = auth()->user()->organization;
        
        try {
            $transaction = RtTransaction::where('organization_id', $organization->id)->findOrFail($id);
            
            // Prevent deleting system transactions
            if (strtolower($transaction->created_by ?? '') === 'system') {
                return response()->json(['success' => false, 'message' => 'Transaksi sistem tidak dapat dihapus']);
            }
            
            $name = $transaction->name;
            $amount = $transaction->isIncome() ? $transaction->credit : $transaction->debit;
            $type = $transaction->isIncome() ? 'Pemasukan' : 'Pengeluaran';
            
            // Create log before deletion
            auth()->user()->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => auth()->user()->name,
                'description' => "{$type} '{$name}' sebesar Rp " . number_format($amount, 0, ',', '.') . " telah dihapus",
                'url_link' => '/admin/rt-transaction/',
            ]);

            $transaction->delete();

            return response()->json(['success' => true]); 
        } catch(\Exception $e) {
            return response()->json(['success' => false]); 
        }
    }

    public function download()
    {
        return Excel::download(new TransactonExport, 'laporan_keuangan.xlsx');
    }
}
