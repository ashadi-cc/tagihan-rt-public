<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Utils\Upload\UploadUser;
use App\MasterTable\TableUser;
use App\Models\User;
use App\Models\PushMessage;
use Excel;
use App\Exports\WargaExport;
use Hash;


class WargaController extends Controller 
{
    use Util;

    public function index(Request $request)
    {
        $table = TableUser::NewTable(); 

        $data = [
            'headerTable' => $table->getHeaderCaption(),
            'headerData' => $table->getHeaderData(),
            'baseUrl' => url('/admin/master/warga/'),
        ];

        return view('admin.master.list_warga', $data);
    }

    public function getData(Request $request)
    {
        $table = TableUser::NewTable(); 

        return $table->getData($request);
    }

    public function view($userId)
    {
        $user = User::where('organization_id', auth()->user()->organization_id)->findOrFail($userId);

        return view('admin.master.view_warga', [
            'user' => $user,
            'currentTagihan' => $user->getCurrentMonthTagihan(),
            'tunggakanTotal' => $user->getTunggakanTotal(),
        ]);
    }

    public function create()
    {
        return view('admin.master.create_warga');
    }

    public function postCreate(Request $request)
    {
        $table = TableUser::NewTable(); 
        $table->create($request);

        return redirect('/admin/master/warga')->with('success', 'Data berhasil ditambahkan');
    }

    public function delete($userId)
    {
        $table = TableUser::NewTable(); 
        $success = $table->delete($userId); 

        return ['success' => $success]; 
    }

    public function edit($userId)
    {
        $user = User::where('organization_id', auth()->user()->organization_id)->findOrFail($userId);
        
        return view('admin.master.edit_warga', ['user' => $user]);
    }

    public function postEdit(Request $request, $userId)
    {
        $table = TableUser::NewTable(); 
        $table->edit($request, $userId);

        return redirect('/admin/master/warga')->with('success', 'Data berhasil dirubah');
    }

    public function getUpload()
    {
        return view('admin.master.upload_warga');
    }

    public function postUpload(Request $request)
    {
        $this->validateExcelFile($request);

        $file = $request->file('xls_file'); 

        $upload = UploadUser::NewUpload($request->user());
        $success = $upload->process($file); 
        
        if (!$success) {
            return redirect()->back()->withErrors(['file' =>  'format file tidak sesuai dengan template']);
        }

        $result = $upload->getResult(); 
        $message = [
            'success' => 'data warga berhasil di import', 
            'imported' => count($result['success']), 
            'fail' => count($result['fail']),
        ];
        return redirect()->back()->with($message);
    }

    public function download()
    {
        return Excel::download(new WargaExport, 'daftar_warga.xlsx');
    }

    public function resetWhatsapp($userId)
    {
        $user = User::where('organization_id', auth()->user()->organization_id)->findOrFail($userId);

        $newPassword = substr(str_shuffle(str_repeat('0123456789', 6)), 0, 6);

        $user->whatsapp_id = null;
        $user->default_password = $newPassword;
        $user->password = Hash::make($newPassword);
        $user->attempt_bot_register = 0;
        $user->save();

        $user->logs()->create([
            'organization_id' => auth()->user()->organization_id,
            'username' => auth()->user()->name,
            'description' => "Reset WhatsApp ID dan password default ke: {$newPassword}",
            'url_link' => '/admin/master/warga/view/' . $user->id,
        ]);

        return redirect()->back()->with('success', 'WhatsApp ID berhasil direset dan password default baru: ' . $newPassword);
    }

    public function sendWhatsapp(Request $request, $userId)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = User::where('organization_id', auth()->user()->organization_id)->findOrFail($userId);

        if (!$user->whatsapp_id) {
            return redirect()->back()->withErrors(['message' => 'Warga belum terdaftar WhatsApp']);
        }

        $pushMessage = new PushMessage();
        $pushMessage->organization_id = auth()->user()->organization_id;
        $pushMessage->message_type = 'dm';
        $pushMessage->recipient_id = $user->whatsapp_id;
        $pushMessage->message_content = $request->input('message');
        $pushMessage->save();

        return redirect()->back()->with('success', 'Pesan WhatsApp berhasil dikirim ke antrian');
    }
}