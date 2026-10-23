<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\ManualBillingSchedule;
use App\Jobs\AutoPemasukanFromIuranJob;
use App\Jobs\NotifTagihanBulananJob;
use App\Jobs\NotifPendingPembayaranJob;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Or run monthly on the 1st at 00:01
        $schedule->call(function () {
            \App\Jobs\BuatTagihanRutinJob::dispatch(now()->format('Y-m-d'));
        })->monthlyOn(1, '03:00')
          ->name('Buat Tagihan Rutin Bulanan')
          ->description('Generate tagihan rutin setiap tanggal 1');

        $schedule->call(function () {
            $list = ManualBillingSchedule::where('scheduled_at', now()->format('Y-m-d'))
                                        ->where('is_generated', false)
                                        ->get();
            foreach ($list as $item) {
                \App\Jobs\BuatTagihanManualJob::dispatch($item);
            }
            
        })->dailyAt('04:00')
          ->name('Buat Tagihan Manual Terjadwal')
          ->description('Generate tagihan manual sesuai jadwal');

        $schedule->call(function () {
            AutoPemasukanFromIuranJob::dispatch();
        })->daily()
          ->name('Auto Pemasukan Dari Iuran')
          ->description('Proses otomatis pemasukan dari iuran');

        $schedule->call(function () {
            NotifTagihanBulananJob::dispatch();
        })->monthlyOn(1, '07:00')
          ->name('Notifikasi Tagihan Bulanan')
          ->description('Kirim notifikasi tagihan bulanan');

        $schedule->call(function () {
            NotifPendingPembayaranJob::dispatch();
        })->dailyAt('12:30')
          ->name('Notifikasi Pending Pembayaran (Siang)')
          ->description('Kirim notifikasi pembayaran pending siang hari');

        $schedule->call(function () {
            NotifPendingPembayaranJob::dispatch();
        })->dailyAt('19:30')
          ->name('Notifikasi Pending Pembayaran (Malam)')
          ->description('Kirim notifikasi pembayaran pending malam hari');


        // Cleanup QRIS cache on 2nd of each month at 02:00
        $schedule->command('qris:cleanup-cache --hours=720')
          ->monthlyOn(2, '02:00')
          ->name('Cleanup QRIS Cache (Tanggal 2)')
          ->description('Bersihkan cache QRIS yang sudah expire (>30 hari)');

        // Database backup to GCS every day at 01:00
        $schedule->command('db:backup')
          ->dailyAt('01:00')
          ->withoutOverlapping()
          ->name('Database Backup to GCS')
          ->description('Backup database ke Google Cloud Storage');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
