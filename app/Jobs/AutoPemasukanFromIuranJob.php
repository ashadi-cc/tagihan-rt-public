<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Models\Tagihan;
use App\Models\RtTransaction;

class AutoPemasukanFromIuranJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware()
    {
        return [
            (new WithoutOverlapping('auto-pemasukan-from-iuran'))
                ->releaseAfter(60) // Release lock after 60 seconds if job fails
                ->expireAfter(180) // Expire lock after 3 minutes
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get all Tagihan if payment_amount > 0
        // group by organization_id and billing_id and YEAR-MONTH of billing_generated_date
        $groupedTagihans = Tagihan::query()
            ->where('payment_amount', '>', 0)
            ->selectRaw('
                organization_id,
                billing_id,
                billing_name,
                DATE_FORMAT(billing_generated_date, "%Y-%m") as billing_month,
                SUM(payment_amount) as total_payment
            ')
            ->groupBy('organization_id', 'billing_id', 'billing_name', 'billing_month')
            ->get();

        $otherPaymentAmounts = [];
        foreach ($groupedTagihans as $tagihanGroup) {
            if (!$tagihanGroup->billing_id) {
                $otherPaymentAmounts[$tagihanGroup->organization_id][$tagihanGroup->billing_month][] = $tagihanGroup->total_payment;
                continue;
            }

            // check if RtTransaction already exists for this organization_id, billing_id and month and year
            $exist = RtTransaction::where('organization_id', $tagihanGroup->organization_id)
                ->where('billing_id', $tagihanGroup->billing_id)
                ->whereYear('payment_date', '=', substr($tagihanGroup->billing_month, 0, 4))
                ->whereMonth('payment_date', '=', substr($tagihanGroup->billing_month, 5, 2))
                ->where('created_by', 'system')
                ->first();

            $paymentDate = $tagihanGroup->billing_month . '-01';
            if ($exist) {
                // update RtTransaction credit amount
                $exist->payment_date = $paymentDate;
                $exist->credit = $tagihanGroup->total_payment;
                // Save only if there are changes
                if ($exist->isDirty()) {
                    $exist->save();
                }

            } else {
                // create RtTransaction
                RtTransaction::create([
                    'payment_date' => $paymentDate,
                    'organization_id' => $tagihanGroup->organization_id,
                    'billing_id' => $tagihanGroup->billing_id,
                    'name' => 'Pemasukan dari Iuran: ' . $tagihanGroup->billing_name . ' (' . $tagihanGroup->billing_month . ')',
                    'description' => 'Otomatis tercatat dari pembayaran iuran bulan ' . $tagihanGroup->billing_month,
                    'status' => 'verified',
                    'debit' => 0,
                    'credit' => $tagihanGroup->total_payment,
                    'created_by' => 'system',
                    'created_user_id' => null,
                ]);
            }
        }

        // Process other payments without billing_id
        foreach ($otherPaymentAmounts as $organizationId => $months) {
            foreach ($months as $month => $payments) {
                $totalPayment = array_sum($payments);
                $paymentDate = $month . '-01';
                
                $exist = RtTransaction::where('organization_id', $organizationId)
                    ->where('billing_id', 0)
                    ->whereYear('payment_date', '=', substr($month, 0, 4))
                    ->whereMonth('payment_date', '=', substr($month, 5, 2))
                    ->where('created_by', 'system')
                    ->first();

                if ($exist) {
                    // update RtTransaction credit amount
                    $exist->payment_date = $paymentDate;
                    $exist->credit = $totalPayment;
                    // Save only if there are changes
                    if ($exist->isDirty()) {
                        $exist->save();
                    }
                } else {
                    // create RtTransaction
                    RtTransaction::create([
                        'payment_date' => $paymentDate,
                        'organization_id' => $organizationId,
                        'billing_id' => 0,
                        'name' => 'Pemasukan dari Iuran Lainnya Tunggakan dll (' . $month . ')',
                        'description' => 'Otomatis tercatat dari pembayaran iuran lainnya bulan ' . $month,
                        'status' => 'verified',
                        'debit' => 0,
                        'credit' => $totalPayment,
                        'created_by' => 'system',
                        'created_user_id' => null,
                    ]);
                }
            }
        }
    }
}
