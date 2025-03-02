<?php

namespace App\Http\Controllers\Api\Admin\Accounting;

use App\Models\Team;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\Invoice;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\WalletHistory;
use App\Models\RepairRequests;
use App\Http\Controllers\Controller;

class AccountingController extends Controller
{
    use ApiResponse;
    public function getTeamsWithWallets()
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $teams = Team::with(['wallet', 'repairRequests.invoices'])->get();

        $teamData = $teams->map(function ($team) {
            $totalInvoices = $team->repairRequests->flatMap(function ($repairRequest) {
                return $repairRequest->invoices->where('status', 'approved');
            })->sum('total_price');

            return [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'wallet' => [
                    'balance' => $team->wallet->balance ?? 0,
                    'created_at' => $team->wallet->created_at ?? null,
                    'updated_at' => $team->wallet->updated_at ?? null,
                ],
                'total_invoices' => $totalInvoices,
            ];
        });

        return $this->successResponse(200, 'Teams retrieved successfully', $teamData);
    }
    public function getTotalOrderInvoices()
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $totalOrderInvoices = Invoice::where('invoiceable_type', Order::class)->sum('total_price');

        return $this->successResponse(200, __('messages.total_order_invoices'), ['total_order_invoices' => $totalOrderInvoices]);
    }
    public function getRevenue()
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
        $orderInvoices = Invoice::where('invoiceable_type', Order::class)->sum('total_price');

        $repairRequestInvoices = Invoice::where('invoiceable_type', RepairRequests::class)->sum('total_price');

        return $this->successResponse(
            200,
            __('messages.total_revenue'),
            [
                'total_orders_revenue' => $orderInvoices,
                'total_repair_requests_revenue' => $repairRequestInvoices,
            ]
        );
    }
    public function getCollectedRevenue()
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $collectedOrderRevenue = WalletHistory::where('isCollected', true)
            ->whereHas('team', function ($query) {
                $query->whereHas('repairRequests', function ($query) {
                    $query->whereHas('invoices', function ($query) {
                        $query->where('invoiceable_type', Order::class);
                    });
                });
            })
            ->sum('amount');

        $collectedRepairRequestRevenue = WalletHistory::where('isCollected', true)
            ->whereHas('team', function ($query) {
                $query->whereHas('repairRequests', function ($query) {
                    $query->whereHas('invoices', function ($query) {
                        $query->where('invoiceable_type', RepairRequests::class);
                    });
                });
            })
            ->sum('amount');

        return $this->successResponse(200, __('messages.collected_revenue_retrieved'), [
            'collected_order_revenue' => $collectedOrderRevenue,
            'collected_repair_request_revenue' => $collectedRepairRequestRevenue,
        ]);
    }
    public function getTeamInvoices($id)
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
        $team = Team::find($id);
        if (!$team) {
            return $this->errorResponse(404, __('messages.team_not_found'));
        }
        $teamInvoices = $team->repairRequests->flatMap(function ($repairRequest) {
            return $repairRequest->invoices;
        });
        return $this->successResponse(200, __('messages.team_invoices_retrieved'), ['team_invoices' => $teamInvoices]);
    }
    public function getTeamCashInvoices($id)
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
        $team = Team::find($id);
        if (!$team) {
            return $this->errorResponse(404, __('messages.team_not_found'));
        }
        $teamInvoices = $team->repairRequests->flatMap(function ($repairRequest) {
            return $repairRequest->invoices->where('payment_method', 'cashOnDelivery');
        });

        $teamInvoicesWithCollectionStatus = $teamInvoices->map(function ($invoice) {
            $walletHistory = WalletHistory::where('team_id', $invoice->invoiceable->team_id)
                ->where('user_id', $invoice->invoiceable->user_id)
                ->where('isCollected', 'collected')
                ->first();

            return [
                'invoice' => $invoice,
                'is_collected' => $walletHistory ? true : false,
            ];
        });

        return $this->successResponse(200, __('messages.team_cash_invoices_retrieved'), ['team_cash_invoices' => $teamInvoicesWithCollectionStatus]);
    }
    public function getTeamBankTransferInvoice($id)
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }
        $team = Team::find($id);
        if (!$team) {
            return $this->errorResponse(404, __('messages.team_not_found'));
        }
        $teamInvoices = $team->repairRequests->flatMap(function ($repairRequest) {
            return $repairRequest->invoices->where('payment_method', 'bankTransfer');
        });
        return $this->successResponse(200, __('messages.team_bank_transfer_invoices_retrieved'), ['team_bank_transfer_invoices' => $teamInvoices]);
    }
    public function collectWallet($invoiceId)
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->errorResponse(401, __('messages.unauthorized'));
        }

        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            return $this->errorResponse(404, __('messages.invoice_not_found'));
        }

        $invoiceable = $invoice->invoiceable;
        if (!$invoiceable) {
            return $this->errorResponse(404, __('messages.invoiceable_not_found'));
        }

        $teamId = $invoiceable->team_id;
        $userId = $invoiceable->user_id;

        $walletHistory = WalletHistory::where('team_id', $teamId)
            ->where('user_id', $userId)
            ->where('isCollected', 'not_collected')
            ->first();

        if (!$walletHistory) {
            return $this->errorResponse(404, __('messages.wallet_history_not_found'));
        }

        $walletHistory->update(['isCollected' => 'collected']);

        $wallet = Wallet::where('team_id', $teamId)->first();
        if ($wallet) {
            $wallet->increment('balance', $walletHistory->amount);
        } else {
            return $this->errorResponse(404, __('messages.wallet_not_found'));
        }

        return $this->successResponse(200, __('messages.wallet_collected_successfully'), $walletHistory);
    }
}
