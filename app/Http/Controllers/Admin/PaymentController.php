<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\MasterTable\TablePayment;
use App\Models\Payment;
use App\Models\Pembayaran;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;


class PaymentController extends Controller 
{
    use Util; 

    public function index()
    {
        $table = TablePayment::NewTable(); 

        $data = [
            'headerTable' => $table->getHeaderCaption(),
            'headerData' => $table->getHeaderData(),
            'baseUrl' => url('/admin/master/payment'),
        ];

        return view('admin.master.list_payment', $data);
    }

    public function create()
    {
        return view('admin.master.create_payment');
    }

    public function getData(Request $request)
    {
        $table = TablePayment::NewTable(); 

        return $table->getData($request);
    }

    public function delete($paymentId)
    {
        $table = TablePayment::NewTable(); 
        $success = $table->delete($paymentId); 

        return ['success' => $success]; 
    }

    public function show($paymentId)
    {
        $payment = Payment::where('organization_id', auth()->user()->organization_id)
            ->where('id', $paymentId)
            ->firstOrFail();
        
        return view('admin.master.show_payment', ['payment' => $payment]);
    }

    public function edit($paymentId)
    {
        $payment = Payment::where('organization_id', auth()->user()->organization_id)
            ->where('id', $paymentId)
            ->firstOrFail();
        
        return view('admin.master.edit_payment', ['payment' => $payment]);
    }

    public function postEdit(Request $request, $paymentId)
    {
        $table = TablePayment::NewTable(); 
        $result = $table->edit($request, $paymentId);

        // If table->edit returns a redirect (error case), return it
        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        return redirect('/admin/master/payment')->with('success', 'Data berhasil dirubah');
    }

    public function store(Request $request)
    {
        $table = TablePayment::NewTable(); 
        $result = $table->create($request); 

        // If table->create returns a redirect (error case), return it
        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        return redirect('/admin/master/payment')->with('success', 'Data baru berhasil disimpan');
    }

    /**
     * Serve QR code image securely
     */
    public function serveQrImage($paymentId)
    {
        // Find payment and verify organization access
        $payment = Payment::where('organization_id', auth()->user()->organization_id)
            ->where('id', $paymentId)
            ->firstOrFail();

        // Check if payment has QR image
        if ($payment->payment_type !== 'qr_payment' || !isset($payment->metadata['qr_image_path'])) {
            abort(404, 'QR Code tidak ditemukan');
        }

        $imagePath = $payment->metadata['qr_image_path'];

        // Check if file exists in storage
        if (!Storage::disk('public')->exists($imagePath)) {
            abort(404, 'File QR Code tidak ditemukan');
        }

        // Get file content and mime type
        $file = Storage::disk('public')->get($imagePath);
        $mimeType = Storage::disk('public')->mimeType($imagePath);

        // Use payment updated_at timestamp for cache control
        $lastModified = $payment->updated_at ?? now();

        // Return image response with proper headers
        return Response::make($file, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="qr_' . $payment->id . '.png"',
            'Cache-Control' => 'public, max-age=3600', // Cache for 1 hour
            'Last-Modified' => $lastModified->toRfc7231String(),
            'ETag' => md5($paymentId . '-' . $lastModified->timestamp),
        ]);
    }

    /**
     * Display payment report with verified payments
     */
    public function reportPayment(Request $request)
    {
        $paymentId = $request->get('payment_id');
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        // Get payment information
        $payment = Payment::where('organization_id', auth()->user()->organization_id)
            ->where('id', $paymentId)
            ->firstOrFail();

        // Query verified payments for this payment type
        $query = Pembayaran::where('organization_id', auth()->user()->organization_id)
            ->where('payment_id', $paymentId)
            ->where('status', 'verified')
            ->where('payment_type', '!=', 'deposit_usage')
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->orderBy('payment_date', 'desc');

        // Payment type filter
        $paymentType = $request->get('payment_type', 'all');
        if (in_array($paymentType, ['current_billing', 'previous_billing', 'user_deposit', 'manual_billing'])) {
            $query->where('payment_type', $paymentType);
        }

        // Get paginated results
        $pembayarans = $query->paginate(100);

        // Calculate total
        $totalQuery = Pembayaran::where('organization_id', auth()->user()->organization_id)
            ->where('payment_id', $paymentId)
            ->where('status', 'verified')
            ->where('payment_type', '!=', 'deposit_usage')
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month);

        // Apply payment type filter to total calculation
        if (in_array($paymentType, ['current_billing', 'previous_billing', 'user_deposit', 'manual_billing'])) {
            $totalQuery->where('payment_type', $paymentType);
        }
        
        $total = $totalQuery->sum('payment_amount');

        // Get available months/years for filter
        $availableDates = Pembayaran::where('organization_id', auth()->user()->organization_id)
            ->where('payment_id', $paymentId)
            ->where('status', 'verified')
            ->where('payment_type', '!=', 'deposit_usage')
            ->selectRaw('YEAR(payment_date) as year, MONTH(payment_date) as month')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        // Get other active payments for move action
        $availablePayments = Payment::where('organization_id', auth()->user()->organization_id)
            ->where('id', '!=', $paymentId)
            ->orderBy('name', 'asc')
            ->get();

        return view('admin.report.payment', compact('payment', 'pembayarans', 'total', 'month', 'year', 'availableDates', 'availablePayments', 'paymentType'));
    }

    /**
     * Move pembayaran to another payment
     */
    public function movePembayaran(Request $request, $pembayaranId)
    {
        $validated = $request->validate([
            'new_payment_id' => 'required|integer|exists:payments,id',
        ]);

        $admin = auth()->user();
        
        $pembayaran = Pembayaran::where('organization_id', $admin->organization_id)
            ->findOrFail($pembayaranId);

        $newPayment = Payment::where('organization_id', $admin->organization_id)
            ->where('id', $validated['new_payment_id'])
            ->firstOrFail();

        // Store old payment info for logging
        $oldPaymentId = $pembayaran->payment_id;
        $oldPaymentName = $pembayaran->payment_name;

        // Update only payment_id and old_payment_id
        $pembayaran->old_payment_id = $oldPaymentId;
        $pembayaran->payment_id = $newPayment->id;
        $pembayaran->payment_name = $oldPaymentName . ' -> ' . $newPayment->name;
        $pembayaran->save();

        // Set session flash message
        session()->flash('success', 'Pembayaran berhasil dipindahkan ke ' . $newPayment->name);

        // Create activity log
        $pembayaran->logs()->create([
            'organization_id' => $admin->organization_id,
            'username' => $admin->name,
            'description' => "Pembayaran #{$pembayaran->id} sebesar Rp " . number_format($pembayaran->payment_amount, 0, ',', '.') . " untuk warga '{$pembayaran->user_name}' ({$pembayaran->user_blok}) telah dipindahkan dari payment '{$oldPaymentName}' ke '{$newPayment->name}'",
            'url_link' => '/admin/master/warga/' . $pembayaran->user_id . '/pembayaran/' . $pembayaran->id,
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

}