<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// if (config('default.proxy_schema')) {
//     URL::forceScheme(config('default.proxy_schema'));
// }

// Authentication Routes
Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

// Registration Routes
// Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
// Route::post('register', 'Auth\RegisterController@register');

// Password Reset Routes
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');

//root page
Route::get('/', 'HomeController@index')->name('home');
Route::get('/pembayaran/konfirmasi', 'HomeController@confirmPayment')->name('confirm.payment');
Route::get('/pembayaran/konfirmasi/form', 'HomeController@confirmPaymentForm')->name('confirm.payment.form');
Route::post('/pembayaran/konfirmasi/form', 'HomeController@submitPaymentConfirmation')->name('submit.payment.confirmation');
Route::get('/qris/image', 'HomeController@serveQrisImage')->middleware('auth')->name('qris.image');

// Deposit routes
Route::get('/pembayaran/deposit', 'HomeController@depositForm')->name('deposit.form');
Route::post('/pembayaran/deposit', 'HomeController@submitDeposit')->name('submit.deposit');


/** User */
Route::get('/user/password', 'UserController@index'); 
Route::put('/user/password', 'UserController@store'); 
Route::get('/payment-history', 'UserController@paymentHistory')->middleware('auth')->name('user.payment.history');
Route::get('/tunggakan', 'UserController@tunggakan')->middleware('auth')->name('user.tunggakan');
Route::get('/user-deposit', 'UserController@depositHistory')->middleware('auth')->name('user.deposit.history'); 
Route::get('/upcoming-events', 'UserController@upcomingEvents')->middleware('auth')->name('user.upcoming.events'); 
Route::get('/event/{eventId}/file/{fileIndex}', 'UserController@showEventFile')->middleware('auth')->name('user.event.file');
Route::get('/letters', 'UserController@letters')->middleware('auth')->name('user.letters'); 
Route::get('/letter/{letterId}/file/{fileIndex}', 'UserController@showLetterFile')->middleware('auth')->name('user.letter.file');
Route::get('/inventaris', 'UserController@inventory')->middleware('auth')->name('user.inventory');
Route::get('/inventaris/{inventoryId}/photo/{photoIndex}', 'UserController@showInventoryPhoto')->middleware('auth')->name('user.inventory.photo');
Route::get('/financial-report', 'UserController@financialReport')->middleware('auth')->name('user.financial.report');
Route::get('/dawis-financial-report', 'UserController@dawisFinancialReport')->middleware('auth')->name('user.dawis.financial.report');
Route::get('/tenant', 'UserController@tenants')->middleware('auth')->name('user.tenants'); 

//admin route
Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->group(function() {
    
    //dashboard
    Route::get('/dashboard', 'Admin\DashboardController@index');
    
    //master warga
    Route::get('/master/warga', 'Admin\WargaController@index');
    Route::get('/master/warga/download', 'Admin\WargaController@download');
    Route::get('/master/warga/upload', 'Admin\WargaController@getUpload');
    Route::post('/master/warga/upload', 'Admin\WargaController@postUpload');
    /** CURD */
    Route::get('/master/warga/get', 'Admin\WargaController@getData');
    Route::get('/master/warga/create', 'Admin\WargaController@create');
    Route::post('/master/warga/create', 'Admin\WargaController@postCreate');
    Route::delete('/master/warga/{userID}', 'Admin\WargaController@delete');
    Route::get('/master/warga/edit/{userID}', 'Admin\WargaController@edit');
    Route::put('/master/warga/edit/{userID}', 'Admin\WargaController@postEdit');
    Route::get('/master/warga/view/{userID}', 'Admin\WargaController@view');
    Route::post('/master/warga/{userID}/reset-whatsapp', 'Admin\WargaController@resetWhatsapp')->name('admin.warga.reset-whatsapp');
    Route::post('/master/warga/{userID}/send-whatsapp', 'Admin\WargaController@sendWhatsapp')->name('admin.warga.send-whatsapp');
    Route::get('/master/warga/{userID}/tagihan', 'Admin\WargaTagihanController@index');
    Route::post('/master/warga/{userID}/tagihan/bayar-dari-deposit', 'Admin\WargaTagihanController@bayarTagihanFromDeposit');
    Route::put('/master/warga/{userID}/tagihan/{tagihanID}', 'Admin\WargaTagihanController@update');
    Route::delete('/master/warga/{userID}/tagihan/{tagihanID}', 'Admin\WargaTagihanController@destroy');
    Route::get('/master/warga/{userID}/tunggakan', 'Admin\TunggakanController@index');
    Route::post('/master/warga/{userID}/tunggakan', 'Admin\TunggakanController@store');
    Route::delete('/master/warga/{userID}/tunggakan/{tagihanID}', 'Admin\TunggakanController@destroy');
    Route::get('/master/warga/{userID}/pembayaran', 'Admin\WargaPembayaranController@index')->name('admin.warga.pembayaran');
    Route::get('/master/warga/{userID}/pembayaran/create', 'Admin\WargaPembayaranController@create')->name('admin.warga.pembayaran.create');
    Route::post('/master/warga/{userID}/pembayaran', 'Admin\WargaPembayaranController@store')->name('admin.warga.pembayaran.store');
    Route::get('/master/warga/{userID}/pembayaran/tagihan', 'Admin\WargaPembayaranController@getTagihan')->name('admin.warga.pembayaran.tagihan');
    Route::get('/master/warga/{userID}/pembayaran/{pembayaranID}', 'Admin\WargaPembayaranController@show')->name('admin.warga.pembayaran.show');
    Route::post('/master/warga/{userID}/pembayaran/{pembayaranID}/approve', 'Admin\WargaPembayaranController@approve')->name('admin.warga.pembayaran.approve');
    Route::post('/master/warga/{userID}/pembayaran/{pembayaranID}/reject', 'Admin\WargaPembayaranController@reject')->name('admin.warga.pembayaran.reject');
    Route::post('/master/warga/{userID}/pembayaran/{pembayaranID}/update-amount', 'Admin\WargaPembayaranController@updateAmount')->name('admin.warga.pembayaran.update-amount');
    Route::delete('/master/warga/{userID}/pembayaran/{pembayaranID}', 'Admin\WargaPembayaranController@destroy')->name('admin.warga.pembayaran.destroy');
    Route::get('/master/warga/{userID}/deposit', 'Admin\WargaDepositController@index')->name('admin.warga.deposit');
    Route::get('/master/warga/{userID}/deposit/create', 'Admin\WargaDepositController@create')->name('admin.warga.deposit.create');
    Route::post('/master/warga/{userID}/deposit', 'Admin\WargaDepositController@store')->name('admin.warga.deposit.store');
    Route::get('/master/warga/{userID}/deposit/{depositID}', 'Admin\WargaDepositController@show')->name('admin.warga.deposit.show');
    Route::put('/master/warga/{userID}/deposit/{depositID}', 'Admin\WargaDepositController@update')->name('admin.warga.deposit.update');
    Route::post('/master/warga/{userID}/deposit/{depositID}/approve', 'Admin\WargaDepositController@approve')->name('admin.warga.deposit.approve');
    Route::post('/master/warga/{userID}/deposit/{depositID}/reject', 'Admin\WargaDepositController@reject')->name('admin.warga.deposit.reject');

    //master tagihan
    Route::get('/master/tagihan', 'Admin\BillingController@index');
    Route::get('/master/tagihan/create', 'Admin\BillingController@create');
    Route::post('/master/tagihan/create', 'Admin\BillingController@postCreate');
    Route::get('/master/tagihan/manual/create', 'Admin\BillingController@createManualBilling');
    Route::post('/master/tagihan/manual/create', 'Admin\BillingController@postCreateManualBilling');
    Route::delete('/master/tagihan/manual/schedule/{scheduleId}', 'Admin\BillingController@deleteManualBillingSchedule');
    Route::post('/master/tagihan/manual/schedule/users', 'Admin\BillingController@getScheduleUsers');
    /** CURD */
    Route::get('/master/tagihan/get', 'Admin\BillingController@getData');
    Route::delete('/master/tagihan/{billingId}', 'Admin\BillingController@delete');
    Route::get('/master/tagihan/view/{billingId}', 'Admin\BillingController@view');
    Route::get('/master/tagihan/edit/{billingId}', 'Admin\BillingController@edit');
    Route::put('/master/tagihan/edit/{billingId}', 'Admin\BillingController@postEdit');

    //master payment
    Route::get('/master/payment', 'Admin\PaymentController@index');
    Route::get('/master/payment/create', 'Admin\PaymentController@create');
    /** CURD */
    Route::get('/master/payment/get', 'Admin\PaymentController@getData');
    Route::delete('/master/payment/{paymentId}', 'Admin\PaymentController@delete');
    Route::get('/master/payment/view/{paymentId}', 'Admin\PaymentController@show');
    Route::get('/master/payment/edit/{paymentId}', 'Admin\PaymentController@edit');
    Route::put('/master/payment/edit/{paymentId}', 'Admin\PaymentController@postEdit');
    Route::post('/master/payment', 'Admin\PaymentController@store');
    Route::get('/master/payment/{paymentId}/qr-image', 'Admin\PaymentController@serveQrImage')->name('payment.qr.image');
    Route::get('/report/payment', 'Admin\PaymentController@reportPayment')->name('admin.report.payment');
    Route::post('/report/payment/{pembayaranId}/move', 'Admin\PaymentController@movePembayaran')->name('admin.report.payment.move');

    //tagihan upload dan daftar
    // Route::get('/tagihan/upload', 'Admin\TagihanController@index');
    // Route::get('/tagihan/upload/download', 'Admin\TagihanController@download');
    // Route::post('/tagihan/upload', 'Admin\TagihanController@uploadTagihan');
    // Route::get('/tagihan/upload/template/{idTemplate}', 'Admin\TagihanController@getTemplate');
    // /**CURD */
    // Route::get('/tagihan/upload/get', 'Admin\TagihanController@getData');
    // Route::delete('/tagihan/upload/{idRecord}', 'Admin\TagihanController@delete');
    // Route::put('/tagihan/upload/{idRecord}', 'Admin\TagihanController@edit');

    // //tagihan bulanan 
    // Route::get('/tagihan/bulanan', 'Admin\TagihanBulanan@index');
    // Route::get('/tagihan/bulanan/get', 'Admin\TagihanBulanan@getData');
    // Route::get('/tagihan/bulanan/download', 'Admin\TagihanBulanan@download');
    // Route::put('/tagihan/bulanan/{idRecord}', 'Admin\TagihanBulanan@edit');
    // Route::get('/tagihan/bulanan/summary', 'Admin\TagihanBulanan@summary');
    // Route::get('/tagihan/bulanan/user', 'Admin\TagihanBulanan@byUser');

    //inventory routes
    Route::get('/inventory', 'Admin\InventoryController@index');
    Route::get('/inventory/create', 'Admin\InventoryController@create');
    Route::post('/inventory/create', 'Admin\InventoryController@store');
    Route::get('/inventory/{id}', 'Admin\InventoryController@show');
    Route::get('/inventory/{id}/edit', 'Admin\InventoryController@edit');
    Route::put('/inventory/{id}', 'Admin\InventoryController@update');
    Route::delete('/inventory/{id}', 'Admin\InventoryController@destroy');

    //pembayaran iuran routes
    Route::get('/payment', 'Admin\PembayaranController@index');

    //rt transaction routes (Pemasukan & Pengeluaran RT)
    Route::get('/rt-transaction', 'Admin\RtTransactionController@index');
    Route::get('/rt-transaction/download', 'Admin\RtTransactionController@download');
    Route::get('/rt-transaction/create', 'Admin\RtTransactionController@create');
    Route::post('/rt-transaction/create', 'Admin\RtTransactionController@store');
    Route::get('/rt-transaction/{id}', 'Admin\RtTransactionController@show');
    Route::get('/rt-transaction/{id}/edit', 'Admin\RtTransactionController@edit');
    Route::put('/rt-transaction/{id}', 'Admin\RtTransactionController@update');
    Route::delete('/rt-transaction/{id}', 'Admin\RtTransactionController@destroy');

    Route::get('/dawis-transaction', 'Admin\DawisTransactionController@index');
    Route::get('/dawis-transaction/download', 'Admin\DawisTransactionController@download');
    Route::get('/dawis-transaction/create', 'Admin\DawisTransactionController@create');
    Route::post('/dawis-transaction/create', 'Admin\DawisTransactionController@store');
    Route::get('/dawis-transaction/{id}', 'Admin\DawisTransactionController@show');
    Route::get('/dawis-transaction/{id}/edit', 'Admin\DawisTransactionController@edit');
    Route::put('/dawis-transaction/{id}', 'Admin\DawisTransactionController@update');
    Route::delete('/dawis-transaction/{id}', 'Admin\DawisTransactionController@destroy');

    //tenant routes
    Route::get('/tenant', 'Admin\TenantController@index');
    Route::get('/tenant/create', 'Admin\TenantController@create');
    Route::post('/tenant/create', 'Admin\TenantController@store');
    Route::get('/tenant/{id}', 'Admin\TenantController@show');
    Route::get('/tenant/{id}/edit', 'Admin\TenantController@edit');
    Route::put('/tenant/{id}', 'Admin\TenantController@update');
    Route::delete('/tenant/{id}', 'Admin\TenantController@destroy');

    //event routes
    Route::get('/event', 'Admin\EventController@index');
    Route::get('/event/create', 'Admin\EventController@create');
    Route::post('/event/create', 'Admin\EventController@store');
    Route::get('/event/{id}', 'Admin\EventController@show');
    Route::get('/event/{id}/edit', 'Admin\EventController@edit');
    Route::put('/event/{id}', 'Admin\EventController@update');
    Route::delete('/event/{id}', 'Admin\EventController@destroy');
    Route::get('/event/{id}/file/{fileIndex}', 'Admin\EventController@showFile');

    //letter routes (surat menyurat)
    Route::get('/surat-menyurat', 'Admin\LetterController@index');
    Route::get('/surat-menyurat/create', 'Admin\LetterController@create');
    Route::post('/surat-menyurat/create', 'Admin\LetterController@store');
    Route::get('/surat-menyurat/{id}', 'Admin\LetterController@show');
    Route::get('/surat-menyurat/{id}/edit', 'Admin\LetterController@edit');
    Route::put('/surat-menyurat/{id}', 'Admin\LetterController@update');
    Route::delete('/surat-menyurat/{id}', 'Admin\LetterController@destroy');
    Route::get('/surat-menyurat/{id}/file/{fileIndex}', 'Admin\LetterController@showFile');

    //activity log routes
    Route::get('/activity-log', 'Admin\ActivityLogController@index');

    //organization routes
    Route::get('/organization', 'Admin\OrganizationController@show');
    Route::get('/organization/edit', 'Admin\OrganizationController@edit');
    Route::put('/organization', 'Admin\OrganizationController@update');

    //bot service routes (superadmin only)
    Route::middleware('role:superadmin')->prefix('bot-service')->group(function () {
        Route::get('/', 'Admin\BotServiceController@index')->name('admin.bot-service');
        Route::get('/logs', 'Admin\BotServiceController@logs')->name('admin.bot-service.logs');
        Route::post('/command', 'Admin\BotServiceController@command')->name('admin.bot-service.command');
    });

    //report routes
    Route::get('/income-per-iuran', 'Admin\IncomePerIuranController@index')->name('admin.report.income_per_iuran');
    Route::get('/income-per-iuran/details', 'Admin\IncomePerIuranController@getUserDetails')->name('admin.report.income_per_iuran.details');
    Route::get('/iuran-per-warga', 'Admin\TagihanIuranPerWargaController@index')->name('admin.report.tagihan_iuran_per_warga');
    Route::get('/iuran-per-warga/export', 'Admin\TagihanIuranPerWargaController@export')->name('admin.report.tagihan_iuran_per_warga.export');
    Route::get('/deposit-per-warga', 'Admin\DepositPerWargaController@index')->name('admin.report.deposit_per_warga');

    // File serving route.
    Route::get('/file/{driver}', 'FileController@show')
        ->name('file.show');

});