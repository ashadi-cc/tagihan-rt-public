<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Utils\Upload\UploadBilling;
use App\MasterTable\TableBilling;
use App\Models\Billing;
use App\Models\User;
use App\Models\ManualBillingSchedule;
use App\Jobs\BuatTagihanManualJob;
use App\Jobs\BuatTagihanRutinJob;

class BillingController extends Controller 
{
    use Util; 

    public function index()
    {
        $table = TableBilling::NewTable(); 

        $data = [
            'headerTable' => $table->getHeaderCaption(),
            'headerData' => $table->getHeaderData(),
            'baseUrl' => url('/admin/master/tagihan/'),
        ];

        return view('admin.master.list_billing', $data);
    }

    public function getData(Request $request)
    {
        $table = TableBilling::NewTable(); 

        return $table->getData($request);
    }

    public function delete($billingId)
    {
        $table = TableBilling::NewTable(); 
        $success = $table->delete($billingId); 

        return ['success' => $success]; 
    }

    public function createManualBilling()
    {
        $billingList = Billing::where('organization_id', auth()->user()->organization_id)
                            ->where('billing_type', 'manual')
                            ->where('disabled', false)
                            ->orderBy('priority', 'asc')
                            ->get();
        $userList = User::where('organization_id', auth()->user()->organization_id)
                        ->warga()
                        ->orderBy('blok_name')
                        ->orderBy('blok_number')
                        ->get();

        $manualBillingSchedules = ManualBillingSchedule::whereHas('billing', function($query) {
                                        $query->where('organization_id', auth()->user()->organization_id);
                                    })
                                    ->orderBy('scheduled_at', 'asc')
                                    ->get();

        return view('admin.master.create_manual_billing', ['billingList' => $billingList, 'userList' => $userList, 'manualBillingSchedules' => $manualBillingSchedules]);
    }

    public function postCreateManualBilling(Request $request)
    {
        $validationRules = [
            'billing_id' => 'required|exists:billings,id',
            'amount' => 'required|numeric|min:0',
            'generated_at_type' => 'required|in:now,specific_date',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ];

        if ($request->generated_at_type === 'specific_date') {
            $validationRules['generated_at_date'] = 'required|date';
        }

        $request->validate($validationRules);

        // Get billing information
        $billing = Billing::where('organization_id', auth()->user()->organization_id)
                         ->where('billing_type', 'manual')
                         ->findOrFail($request->billing_id);

        if ($request->generated_at_type === 'specific_date') {

            $existingSchedule = ManualBillingSchedule::where('billing_id', $billing->id)
                                    ->where('scheduled_at', $request->generated_at_date)
                                    ->first();

            if ($existingSchedule) {
                return redirect('/admin/master/tagihan/manual/create')->withErrors(['Jadwal untuk iuran ini sudah ada pada tanggal yang dipilih. Silakan pilih tanggal lain.']);
            }

            // Create ManualBillingSchedule record
            $object = ManualBillingSchedule::create([
                'billing_id' => $billing->id,                
                'amount' => $request->amount,                
                'user_ids' => $request->user_ids,
                'scheduled_at' => $request->generated_at_date,
                'is_generated' => false,
                'created_by_user_id' => auth()->user()->id,
            ]);

            BuatTagihanManualJob::dispatch($object);

            return redirect('/admin/master/tagihan/manual/create')->with('success', 'Tagihan manual berhasil dijadwalkan untuk tanggal ' . date('d M Y', strtotime($request->generated_at_date)));
        }

        // check if we already have a schedule for this date (today) 
        $existingSchedule = ManualBillingSchedule::where('billing_id', $billing->id)
                                ->where('scheduled_at', now()->toDateString())
                                ->first();

        if ($existingSchedule) {
            return redirect('/admin/master/tagihan/manual/create')->withErrors(['Jadwal untuk iuran ini sudah ada untuk hari ini. Silakan pilih iuran lain.']);
        }

        $object = new ManualBillingSchedule();
        $object->billing_id = $billing->id;
        $object->amount = $request->amount;
        $object->user_ids = $request->user_ids;
        $object->scheduled_at = now();
        $object->is_generated = false;
        $object->created_by_user_id =  auth()->user()->id;
        $object->save();

        BuatTagihanManualJob::dispatch($object);

        return redirect('/admin/master/tagihan/manual/create')->with('success', 'Tagihan manual akan segera dibuat');
    }

    public function deleteManualBillingSchedule($scheduleId)
    {
        $schedule = ManualBillingSchedule::whereHas('billing', function($query) {
                        $query->where('organization_id', auth()->user()->organization_id);
                    })
                    ->findOrFail($scheduleId);

        if ($schedule->is_generated) {
            return redirect('/admin/master/tagihan/manual/create')->withErrors(['Jadwal tagihan yang sudah dibuat tidak dapat dihapus']);
        }

        $schedule->delete();

        return redirect('/admin/master/tagihan/manual/create')->with('success', 'Jadwal tagihan manual berhasil dihapus');
    }

    public function getScheduleUsers(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer',
        ]);

        $users = User::where('organization_id', auth()->user()->organization_id)
                    ->whereIn('id', $request->user_ids)
                    ->orderBy('blok_name')
                    ->orderBy('blok_number')
                    ->get(['id', 'name', 'blok']);

        return response()->json(['users' => $users]);
    }

    public function view($billingId)
    {
        $billing = Billing::where('organization_id', auth()->user()->organization_id)->findOrFail($billingId);

        return view('admin.master.view_billing', ['billing' => $billing]);
    }

    public function create()
    {
        $userList = User::where('organization_id', auth()->user()->organization_id)
                        ->warga()
                        ->orderBy('blok_name')
                        ->orderBy('blok_number')
                        ->get();
        return view('admin.master.create_billing', ['userList' => $userList]);
    }

    public function postCreate(Request $request)
    {
        $table = TableBilling::NewTable(); 
        $billing = $table->create($request);
        if ($request->billing_type === 'rutin_perbulan') {
            BuatTagihanRutinJob::dispatch(now()->format('Y-m-d'), $billing->id);
        }
        
        return redirect('/admin/master/tagihan')->with('success', 'Iuran warga berhasil ditambahkan');
    }

    public function edit($billingId)
    {
        $billing = Billing::findOrFail($billingId);

        $userList = User::where('organization_id', auth()->user()->organization_id)
                        ->warga()
                        ->orderBy('blok_name')
                        ->orderBy('blok_number')
                        ->get();

        $selectedUserIds = $billing->users()->pluck('user_id')->toArray();
        
        return view('admin.master.edit_billing', ['billing' => $billing, 'userList' => $userList, 'selectedUserIds' => $selectedUserIds]);
    }

    public function postEdit(Request $request, $billingId)
    {
        $table = TableBilling::NewTable(); 
        $table->edit($request, $billingId);

        return redirect('/admin/master/tagihan')->with('success', 'Data berhasil dirubah');
    }


    public function getUpload()
    {
        return view('admin.master.upload_billing');
    }

    public function postUpload(Request $request)
    {

        $this->validateExcelFile($request);

        $file = $request->file('xls_file'); 

        $upload = UploadBilling::NewUpload($request->user());
        $success = $upload->process($file); 
        
        if (!$success) {
            return redirect()->back()->withErrors(['file' =>  'format file tidak sesuai dengan template']);
        }

        $result = $upload->getResult(); 
        $message = [
            'success' => 'data master iuran berhasil di import', 
            'imported' => count($result['success']), 
            'fail' => count($result['fail']),
        ];
        return redirect()->back()->with($message);
    }

}