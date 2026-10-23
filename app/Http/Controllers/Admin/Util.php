<?php 

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Rules\ExcelFile;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\User;

trait Util 
{
    public function validateExcelFile(Request $request)
    {
        $request->validate([
            //'xls_file' => 'required|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls_file' => [
                'required',
                'file',
                new ExcelFile()
            ]
        ]);
    }

    public function getFilterYearandMonth()
    {
        $startYear = 2015; 
        $endYear = intval(date('Y'));

        $years = []; 
        for ($i = $startYear; $i <= $endYear; $i++ )
        {
            $years[] = $i; 
        }

        $currentMonth = intval(date('n'));
        
        return [
            'years' => $years, 
            'year' => $endYear,
            'months' => $this->getMonth(),
            'month' => $currentMonth,
            'monthName' => $this->getMonthName($currentMonth)
        ];
    }

    private function getMonthName($month)
    {
        $months = collect($this->getMonth()); 

        $found = $months->first(function($value) use ($month) {
            return $value['value'] == $month;
        });

        if ($found) return $found['text']; 

        return '';
    }

    private function getMonth()
    {
        $months = [
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ];

        $arrayMonth = []; 
        foreach($months as $key => $month)
        {
            $arrayMonth [] = [
                'text' => $month, 
                'value' => $key + 1
            ];
        }

        return $arrayMonth;
    }

    private function bayarTagihanFromPembayaran(Pembayaran $pembayaran, User $user, $tagihanIds = [])
    {
        $billings = Tagihan::where('organization_id', $pembayaran->organization_id)
            ->where('user_id', $pembayaran->user_id)
            ->whereIn('id', $tagihanIds)
            ->orderBy('billing_generated_date', 'asc')
            ->orderBy('billing_priority', 'asc')
            ->where('payment_amount', '<', \DB::raw('billing_amount'))
            ->get();

        $remainingAmount = floatval($pembayaran->payment_amount);
        $processedBillings = []; // Track which billings were paid

        foreach($billings as $tagihan)
        {
            $outstandingAmount = floatval($tagihan->billing_amount) - floatval($tagihan->payment_amount);
            if ($remainingAmount <= 0) break;

            if ($remainingAmount >= $outstandingAmount)
            {
                $tagihan->payment_amount += $outstandingAmount;
                $remainingAmount -= $outstandingAmount;
                $pembayaran->tagihans()->attach($tagihan->id, ['amount' => $outstandingAmount]);
                $processedBillings[] = "#{$tagihan->id} (Rp " . number_format($outstandingAmount, 0, ',', '.') . ")";
            }
            else
            {
                $tagihan->payment_amount += $remainingAmount;
                $pembayaran->tagihans()->attach($tagihan->id, ['amount' => $remainingAmount]);
                $processedBillings[] = "#{$tagihan->id} (Rp " . number_format($remainingAmount, 0, ',', '.') . ")";
                $remainingAmount = 0;
            }

            $tagihan->save();
        }

        // Create activity log
        $warga = User::find($pembayaran->user_id);
        $logDescription = "Pembayaran #{$pembayaran->id} sebesar Rp " . number_format($pembayaran->payment_amount, 0, ',', '.') . " untuk warga '{$warga->name}' ({$warga->blok}) telah didistribusikan ke " . count($processedBillings) . " tagihan";
        
        if (!empty($processedBillings)) {
            $logDescription .= "\nTagihan: " . implode(', ', $processedBillings);
        }
        
        $pembayaran->logs()->create([
            'organization_id' => $user->organization_id,
            'username' => $user->name,
            'description' => $logDescription,
            'url_link' => '/admin/master/warga/' . $pembayaran->user_id . '/pembayaran/' . $pembayaran->id,
        ]);
    }

    private function pembayaranOtomatisFromUserDeposit(User $user, $tagihanIds = [])
    {
        // first get total user deposit
        $totalUserDeposit = Pembayaran::where('payment_type', 'user_deposit')
            ->where('status', 'verified')
            ->where('user_id', $user->id)
            ->sum('payment_amount');

        // then get total used deposit for pembayaran
        $totalUsedDeposit = Pembayaran::where('payment_type', 'deposit_usage')
            ->where('status', 'verified')
            ->where('user_id', $user->id)
            ->sum('payment_amount');

        $availableDeposit = floatval($totalUserDeposit - $totalUsedDeposit);

        if ($availableDeposit <= 0) return; // no available deposit
        
        // get current month billings if no specific tagihanIds provided
        $query = Tagihan::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_amount', '<', \DB::raw('billing_amount'))
            ->orderBy('billing_generated_date', 'asc')
            ->orderBy('billing_priority', 'asc');

        if (!empty($tagihanIds)) {
            $query->whereIn('id', $tagihanIds);
        } else {
            $query->where('billing_type', 'rutin_perbulan')
                ->whereYear('billing_generated_date', now()->year)
                ->whereMonth('billing_generated_date', now()->month);
        }

        $currentMonthBillings = $query->get();

        // Process current month billings
        foreach ($currentMonthBillings as $tagihan) {
            if ($availableDeposit <= 0) break;

            $outstandingAmount = floatval($tagihan->billing_amount) - floatval($tagihan->payment_amount);
            if ($outstandingAmount <= 0) continue;

            // Calculate amount to pay (minimum of available deposit and outstanding amount)
            $amountToPay = min($availableDeposit, $outstandingAmount);

            // Create new pembayaran record with deposit_usage type
            $pembayaran = Pembayaran::create([
                'organization_id' => $user->organization_id,
                'payment_type' => 'deposit_usage',
                'payment_name' => 'Pembayaran otomatis dari deposit untuk tagihan '. $tagihan->billing_name,
                'payment_amount' => $amountToPay,
                'payment_date' => now(),
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_blok' => $user->blok,
                'created_by_user_id' => null, // system
                'created_by_user_name' => 'System',
                'status' => 'verified',
                'verified_by_user_id' => null, // system
                'verified_by_user_name' => 'System',
                'verified_at' => now(),
                'notes' => "Pembayaran otomatis dari deposit untuk tagihan #{$tagihan->id}",
            ]);

            // Link pembayaran to tagihan via pivot table
            $pembayaran->tagihans()->attach($tagihan->id);

            // Update tagihan payment amount
            $tagihan->payment_amount =  $tagihan->payment_amount + $amountToPay;
            $tagihan->save();

            // Reduce available deposit
            $availableDeposit -= $amountToPay;

            // Create activity log
            $logDescription = "Pembayaran otomatis sebesar Rp " . number_format($amountToPay, 0, ',', '.') . " dari deposit untuk warga '{$user->name}' ({$user->blok}) telah diterapkan ke tagihan #{$tagihan->id}";
            
            $pembayaran->logs()->create([
                'organization_id' => $user->organization_id,
                'username' => 'System',
                'description' => $logDescription,
                'url_link' => '/admin/master/warga/' . $user->id . '/pembayaran/' . $pembayaran->id,
            ]);
        }

        if (!empty($tagihanIds)) {
            return; // if specific tagihanIds is provided, only process those and do not continue to other billings
        }

        // if still have available deposit, then process other unpaid billings
        if ($availableDeposit > 0) {
            $otherBillings = Tagihan::where('organization_id', $user->organization_id)
                ->where('user_id', $user->id)
                ->whereIn('billing_type', ['rutin_perbulan', 'tunggakan_warga'])
                ->where('payment_amount', '<', \DB::raw('billing_amount'))
                ->whereNotIn('id', $currentMonthBillings->pluck('id')->toArray()) // exclude already processed
                ->orderBy('billing_generated_date', 'asc')
                ->orderBy('billing_priority', 'asc')
                ->get();

            foreach ($otherBillings as $tagihan) {
                if ($availableDeposit <= 0) break;

                $outstandingAmount = floatval($tagihan->billing_amount) - floatval($tagihan->payment_amount);
                if ($outstandingAmount <= 0) continue;

                // Calculate amount to pay (minimum of available deposit and outstanding amount)
                $amountToPay = min($availableDeposit, $outstandingAmount);

                // Create new pembayaran record with deposit_usage type
                $pembayaran = Pembayaran::create([
                    'organization_id' => $user->organization_id,
                    'payment_name' => 'Pembayaran otomatis dari deposit untuk tagihan '. $tagihan->billing_name,
                    'payment_type' => 'deposit_usage',
                    'payment_amount' => $amountToPay,
                    'payment_date' => now(),
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_blok' => $user->blok,
                    'created_by_user_id' => null, // system
                    'created_by_user_name' => 'System',
                    'status' => 'verified',
                    'verified_by_user_id' => null, // system
                    'verified_by_user_name' => 'System',
                    'verified_at' => now(),
                    'notes' => "Pembayaran otomatis dari deposit untuk tagihan #{$tagihan->id}",
                ]);

                // Link pembayaran to tagihan via pivot table
                $pembayaran->tagihans()->attach($tagihan->id);

                // Update tagihan payment amount
                $tagihan->payment_amount += $amountToPay;
                $tagihan->save();

                // Reduce available deposit
                $availableDeposit -= $amountToPay;

                // Create activity log
                $logDescription = "Pembayaran otomatis sebesar Rp " . number_format($amountToPay, 0, ',', '.') . " dari deposit untuk warga '{$user->name}' ({$user->blok}) telah diterapkan ke tagihan #{$tagihan->id}";
                
                $pembayaran->logs()->create([
                    'organization_id' => $user->organization_id,
                    'username' => 'System',
                    'description' => $logDescription,
                    'url_link' => '/admin/master/warga/' . $user->id . '/pembayaran/' . $pembayaran->id,
                ]);
            }
        }
    }
}

