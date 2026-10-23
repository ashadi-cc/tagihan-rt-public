<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tagihan;
use App\Models\Pembayaran;
use App\Models\RtTransaction;
use App\Models\Inventory;
use App\Models\Event;
use App\Models\Tenant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AiCallFunctionController extends Controller
{
    /**
     * Get current user based on whatsapp_id
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('organization_id', auth()->user()->organization_id)
            ->where('whatsapp_id', $request->whatsapp_id)
            ->select('id', 'name', 'username', 'blok', 'blok_name', 'blok_number', 'whatsapp_id', 'jabatan')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->hasRole('admin')) {
            if (!$user->jabatan || trim($user->jabatan) == '') {
                $user->jabatan = 'Pengurus RT';
            }
        } else {
            $user->jabatan = 'Warga RT';
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Fetch users by blok and/or name
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blok' => 'nullable|string',
            'name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $organizationId = auth()->user()->organization_id;

        $query = User::where('organization_id', $organizationId)->warga(); // Exclude admin

        if ($request->has('blok') && $request->blok) {
            $query->where('blok', 'like', '%' . strtoupper($request->blok) . '%');
        }

        if ($request->has('name') && $request->name) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $users = $query->select('id', 'name', 'blok', 'whatsapp_id')
            ->limit(1000) // Limit results for performance
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
            'count' => $users->count()
        ]);
    }

    /**
     * Fetch bills for a specific user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserBills(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'billing_generated_date' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $organizationId = auth()->user()->organization_id;

        $query = Tagihan::where('organization_id', $organizationId)
            ->where('user_id', $request->user_id)
            ->where('billing_type', '!=', 'manual') // Manual billings are not counted as outstanding
            ->whereRaw('billing_amount > payment_amount') // Only unpaid bills
            ->orderBy('billing_generated_date', 'desc')
            ->orderBy('billing_priority', 'asc');

        if ($request->has('billing_generated_date') && $request->billing_generated_date) {
            $query->whereDate('billing_generated_date', $request->billing_generated_date);
        }

        $limit = $request->get('limit', 100);
        $bills = $query->limit($limit)->get();

        // Calculate total unpaid amount
        $totalUnpaid = $bills->sum(function ($bill) {
            return $bill->billing_amount - $bill->payment_amount;
        });

        return response()->json([
            'success' => true,
            'data' => $bills,
            'count' => $bills->count(),
            'summary' => [
                'total_unpaid' => round($totalUnpaid, 2),
                'total_bills' => $bills->count()
            ]
        ]);
    }

    /**
     * Fetch bills for all users (global)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGlobalUserBills(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $organizationId = auth()->user()->organization_id;
        $limit = $request->get('limit', 100);
        
        $currentMonth = now()->format('Y-m');
        $currentYear = now()->year;
        $currentMonthNum = now()->month;

        // Fetch bills grouped by user in a single query to avoid N+1
        $billsGrouped = Tagihan::select(
                'tagihans.user_id',
                'users.name',
                'users.blok',
                'users.whatsapp_id',
                DB::raw("SUM(CASE
                    WHEN tagihans.billing_type = 'rutin_perbulan'
                    AND YEAR(tagihans.billing_generated_date) = $currentYear
                    AND MONTH(tagihans.billing_generated_date) = $currentMonthNum
                    THEN tagihans.billing_amount - tagihans.payment_amount
                    ELSE 0
                END) as current_month_tagihan"),
                DB::raw("SUM(CASE
                    WHEN (tagihans.billing_type = 'rutin_perbulan' AND DATE_FORMAT(tagihans.billing_generated_date, '%Y-%m') < '$currentMonth')
                          OR tagihans.billing_type = 'tunggakan_warga'
                    AND tagihans.billing_amount > tagihans.payment_amount
                    THEN tagihans.billing_amount - tagihans.payment_amount
                    ELSE 0
                END) as tunggakan_total")
            )
            ->join('users', 'tagihans.user_id', '=', 'users.id')
            ->where('tagihans.organization_id', $organizationId)
            ->where('tagihans.billing_type', '!=', 'manual')
            ->whereRaw('tagihans.billing_amount > tagihans.payment_amount')
            ->groupBy('tagihans.user_id', 'users.name', 'users.blok', 'users.whatsapp_id')
            ->orderByRaw("(
                SUM(CASE
                    WHEN tagihans.billing_type = 'rutin_perbulan'
                    AND YEAR(tagihans.billing_generated_date) = $currentYear
                    AND MONTH(tagihans.billing_generated_date) = $currentMonthNum
                    THEN tagihans.billing_amount - tagihans.payment_amount
                    ELSE 0
                END) +
                SUM(CASE
                    WHEN (tagihans.billing_type = 'rutin_perbulan' AND DATE_FORMAT(tagihans.billing_generated_date, '%Y-%m') < '$currentMonth')
                          OR tagihans.billing_type = 'tunggakan_warga'
                    AND tagihans.billing_amount > tagihans.payment_amount
                    THEN tagihans.billing_amount - tagihans.payment_amount
                    ELSE 0
                END)
            ) DESC")
            ->limit($limit)
            ->get();

        // Format the data
        $data = $billsGrouped->map(function ($item) {
            $currentMonth = $item->current_month_tagihan ?? 0;
            $tunggakan = $item->tunggakan_total ?? 0;
            
            return [
                'user_id' => $item->user_id,
                'name' => $item->name,
                'blok' => $item->blok,
                'whatsapp_id' => $item->whatsapp_id,
                'current_month_tagihan' => round($currentMonth, 2),
                'tunggakan_total' => round($tunggakan, 2),
                'total_unpaid' => round($currentMonth + $tunggakan, 2)
            ];
        });

        // Calculate summary
        $totalCurrentMonth = $data->sum('current_month_tagihan');
        $totalTunggakan = $data->sum('tunggakan_total');

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
            'summary' => [
                'total_current_month' => round($totalCurrentMonth, 2),
                'total_tunggakan' => round($totalTunggakan, 2),
                'total_unpaid' => round($totalCurrentMonth + $totalTunggakan, 2),
                'total_users' => $data->count()
            ]
        ]);
    }

    /**
     * Fetch payment history for a specific user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserPayments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'status' => 'nullable|in:verified,pending,rejected',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $organizationId = auth()->user()->organization_id;

        $query = Pembayaran::where('organization_id', $organizationId)
            ->where('user_id', $request->user_id)
            ->orderBy('payment_date', 'desc');

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        $limit = $request->get('limit', 100);
        $payments = $query->limit($limit)->get();

        // Calculate summary
        $summary = [
            'total_payments' => $payments->count(),
            'total_amount' => round($payments->sum('payment_amount'), 2),
            'verified_count' => $payments->where('status', 'verified')->count(),
            'pending_count' => $payments->where('status', 'pending')->count(),
            'rejected_count' => $payments->where('status', 'rejected')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $payments,
            'count' => $payments->count(),
            'summary' => $summary
        ]);
    }

    /**
     * Fetch payment history for all users (global)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGlobalUserPayments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:verified,pending,rejected',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $organizationId = auth()->user()->organization_id;

        $query = Pembayaran::where('organization_id', $organizationId)
            ->where('payment_type', '!=', 'deposit_usage') // Exclude deposit usage payments
            ->orderBy('payment_date', 'desc');

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        $limit = $request->get('limit', 100);
        $payments = $query->limit($limit)->get();

        // Calculate summary
        $summary = [
            'total_payments' => $payments->count(),
            'total_amount' => round($payments->sum('payment_amount'), 2),
            'verified_count' => $payments->where('status', 'verified')->count(),
            'pending_count' => $payments->where('status', 'pending')->count(),
            'rejected_count' => $payments->where('status', 'rejected')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $payments,
            'count' => $payments->count(),
            'summary' => $summary
        ]);
    }

    /**
     * Fetch global RT transactions (income & expenses)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:income,expense',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'search' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $organizationId = auth()->user()->organization_id;

        $query = RtTransaction::where('organization_id', $organizationId)
            ->orderBy('payment_date', 'desc');

        // Search functionality
        if ($request->has('search') && trim($request->search) != "") {
            $search = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($search) {
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
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        $limit = $request->get('limit', 100);
        $transactions = $query->limit($limit)->get();

        // Calculate summary
        $summary = RtTransaction::where('organization_id', $organizationId);
        
        // Apply same filters to summary if date range is specified
        if ($request->has('start_date') && $request->start_date) {
            $summary->whereDate('payment_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $summary->whereDate('payment_date', '<=', $request->end_date);
        }

        $summaryData = $summary->selectRaw('SUM(credit) as total_income, SUM(debit) as total_expense')->first();

        $totalIncome = $summaryData->total_income ?? 0;
        $totalExpense = $summaryData->total_expense ?? 0;
        $balance = $totalIncome - $totalExpense;

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'count' => $transactions->count(),
            'summary' => [
                'total_income' => round($totalIncome, 2),
                'total_expense' => round($totalExpense, 2),
                'balance' => round($balance, 2)
            ]
        ]);
    }

    /**
     * Fetch inventory items
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInventoryItems(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string',
            'condition' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $organizationId = auth()->user()->organization_id;

        $query = Inventory::where('organization_id', $organizationId)
            ->orderBy('item_name', 'asc');

        if ($request->has('search') && trim($request->search) != "") {
            $search = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('stored_location', 'like', $search);
            });
        }

        if ($request->has('condition') && $request->condition) {
            $query->where('condition', $request->condition);
        }

        $limit = $request->get('limit', 100);
        $items = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $items,
            'count' => $items->count()
        ]);
    }

    /**
     * Fetch events
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEvents(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'search' => 'nullable|string',
            'upcoming' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $organizationId = auth()->user()->organization_id;

        $query = Event::where('organization_id', $organizationId);

        // Filter upcoming events only
        if ($request->get('upcoming', false)) {
            $query->whereDate('event_date', '>=', now()->toDateString())
                ->orderBy('event_date', 'asc');
        } else {
            $query->orderBy('event_date', 'desc');
        }

        if ($request->has('search') && trim($request->search) != "") {
            $search = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('location', 'like', $search);
            });
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('event_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('event_date', '<=', $request->end_date);
        }

        $limit = $request->get('limit', 100);
        $events = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $events,
            'count' => $events->count()
        ]);
    }

    /**
     * Fetch tenants (pengontrak) for the organization
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTenants(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string',
            'unit_number' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $organizationId = auth()->user()->organization_id;

        $query = Tenant::where('organization_id', $organizationId)
            ->orderBy('name', 'asc');

        if ($request->has('search') && $request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('contact_info', 'like', $search)
                    ->orWhere('address', 'like', $search)
                    ->orWhere('unit_number', 'like', $search);
            });
        }

        if ($request->has('unit_number') && $request->unit_number) {
            $query->where('unit_number', 'like', '%' . $request->unit_number . '%');
        }

        $limit = $request->get('limit', 100);
        $tenants = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $tenants,
            'count' => $tenants->count()
        ]);
    }


    public function getUserDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('organization_id', auth()->user()->organization_id)
            ->where('whatsapp_id', $request->whatsapp_id)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // get recent 10 pembayaran
        $recentPayments = Pembayaran::where('organization_id', auth()->user()->organization_id)
            ->where('user_id', $user->id)
            ->orderBy('payment_date', 'desc')
            ->limit(10)
            ->get();

        $data = [
            'user' => [
                'name' => $user->name,
                'blok' => $user->blok,
                'current_month_tagihan' => $user->getCurrentMonthTagihan(),
                'tunggakan_total' => $user->getTunggakanTotal(),
            ],
            'recent_payments' => $recentPayments
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);

    }
}