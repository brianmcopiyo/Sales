<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Customer;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Sale;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\RestockOrder;
use App\Models\StockTransfer;
use App\Models\AgentStockRequest;
use App\Models\Vendor;
use App\Models\Bill;
use App\Models\PettyCashRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    /**
     * Perform global search across multiple models.
     */
    public function search(Request $request)
    {
        $query = trim($request->input('q', ''));
        
        if (empty($query)) {
            return response()->json(['suggestions' => []]);
        }

        $user = $request->user();
        $isFieldAgent = $user->fieldAgentProfile && $user->branch_id;
        $suggestions = [];

        // Search Products
        $products = Product::where(function($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('sku', 'like', '%' . $query . '%')
                  ->orWhere('description', 'like', '%' . $query . '%')
                  ->orWhere('model', 'like', '%' . $query . '%');
            })
            ->with('brand')
            ->limit(5)
            ->get();

        if ($products->isNotEmpty()) {
            $productItems = [];
            foreach ($products as $product) {
                $subtitle = [];
                if ($product->sku) {
                    $subtitle[] = 'SKU: ' . $product->sku;
                }
                if ($product->brand) {
                    $subtitle[] = $product->brand->name;
                }
                if ($product->model) {
                    $subtitle[] = $product->model;
                }
                
                $productItems[] = [
                    'title' => $product->name,
                    'subtitle' => implode(' • ', array_filter($subtitle)),
                    'url' => route('products.show', $product->id),
                    'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'
                ];
            }
            $suggestions[] = [
                'section' => 'Products',
                'items' => $productItems
            ];
        }

        // Search Customers
        $customersQuery = Customer::where(function($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('email', 'like', '%' . $query . '%')
                  ->orWhere('phone', 'like', '%' . $query . '%')
                  ->orWhere('id_number', 'like', '%' . $query . '%');
            });
        if ($isFieldAgent) {
            $customersQuery->whereHas('sales.items', fn($q) => $q->where('field_agent_id', $user->id));
        }
        $customers = $customersQuery->limit(5)->get();

        if ($customers->isNotEmpty()) {
            $customerItems = [];
            foreach ($customers as $customer) {
                $subtitle = [];
                if ($customer->email) {
                    $subtitle[] = $customer->email;
                }
                if ($customer->phone) {
                    $subtitle[] = $customer->phone;
                }
                
                $customerItems[] = [
                    'title' => $customer->name,
                    'subtitle' => implode(' • ', array_filter($subtitle)),
                    'url' => route('customers.show', $customer->id),
                    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'
                ];
            }
            $suggestions[] = [
                'section' => 'Customers',
                'items' => $customerItems
            ];
        }

        // Search Users (current user's branch and below; field agents only see themselves)
        $usersQuery = $isFieldAgent
            ? User::where('id', $user->id)
            : User::visibleTo($request->user());
        $users = $usersQuery
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('email', 'like', '%' . $query . '%')
                  ->orWhere('phone', 'like', '%' . $query . '%');
            })
            ->with('branch')
            ->limit(5)
            ->get();

        if ($users->isNotEmpty()) {
            $userItems = [];
            foreach ($users as $user) {
                $subtitle = [];
                if ($user->email) {
                    $subtitle[] = $user->email;
                }
                if ($user->branch) {
                    $subtitle[] = $user->branch->name;
                }
                
                $userItems[] = [
                    'title' => $user->name,
                    'subtitle' => implode(' • ', array_filter($subtitle)),
                    'url' => route('users.show', $user->id),
                    'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'
                ];
            }
            $suggestions[] = [
                'section' => 'Users',
                'items' => $userItems
            ];
        }

        // Search Tickets (field agents: only assigned to them or created by field_agent_id)
        $ticketsQuery = Ticket::where(function($q) use ($query) {
                $q->where('subject', 'like', '%' . $query . '%')
                  ->orWhere('ticket_number', 'like', '%' . $query . '%')
                  ->orWhere('description', 'like', '%' . $query . '%');
            });
        if ($isFieldAgent) {
            $ticketsQuery->where(function($q) use ($user) {
                $q->where('field_agent_id', $user->id)->orWhere('assigned_to', $user->id);
            });
        }
        $tickets = $ticketsQuery->with(['customer', 'assignedTo'])->limit(5)->get();

        if ($tickets->isNotEmpty()) {
            $ticketItems = [];
            foreach ($tickets as $ticket) {
                $subtitle = [];
                if ($ticket->ticket_number) {
                    $subtitle[] = $ticket->ticket_number;
                }
                if ($ticket->customer) {
                    $subtitle[] = $ticket->customer->name;
                }
                if ($ticket->status) {
                    $subtitle[] = ucfirst($ticket->status);
                }
                
                $ticketItems[] = [
                    'title' => $ticket->subject,
                    'subtitle' => implode(' • ', array_filter($subtitle)),
                    'url' => route('tickets.show', $ticket->id),
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
                ];
            }
            $suggestions[] = [
                'section' => 'Tickets',
                'items' => $ticketItems
            ];
        }

        // Search Sales (by sale number, customer, branch, notes, or total; field agents: only sales where they have items)
        $salesQuery = Sale::where(function ($q) use ($query) {
            $q->where('sale_number', 'like', '%' . $query . '%')
                ->orWhere('notes', 'like', '%' . $query . '%')
                ->orWhereHas('customer', function ($c) use ($query) {
                    $c->where('name', 'like', '%' . $query . '%')
                        ->orWhere('email', 'like', '%' . $query . '%')
                        ->orWhere('phone', 'like', '%' . $query . '%');
                })
                ->orWhereHas('branch', function ($b) use ($query) {
                    $b->where('name', 'like', '%' . $query . '%')
                        ->orWhere('code', 'like', '%' . $query . '%');
                });
            if (preg_match('/^\d+(\.\d{1,2})?$/', trim($query))) {
                $amount = (float) trim($query);
                $q->orWhereBetween('total', [$amount * 0.99, $amount * 1.01]);
            }
        });
        if ($isFieldAgent) {
            $salesQuery->whereHas('items', fn ($q) => $q->where('field_agent_id', $user->id));
        }
        $sales = $salesQuery->with(['customer', 'branch'])->latest()->limit(5)->get();

        if ($sales->isNotEmpty()) {
            $saleItems = [];
            foreach ($sales as $sale) {
                $subtitle = [];
                if ($sale->customer) {
                    $subtitle[] = $sale->customer->name;
                }
                if ($sale->branch) {
                    $subtitle[] = $sale->branch->name;
                }
                if ($sale->total !== null) {
                    $subtitle[] = config('app.currency_symbol') . ' ' . number_format((float) $sale->total, 2);
                }
                if ($sale->status) {
                    $subtitle[] = ucfirst($sale->status);
                }
                $saleItems[] = [
                    'title' => 'Sale #' . $sale->sale_number,
                    'subtitle' => implode(' • ', array_filter($subtitle)),
                    'url' => route('sales.show', $sale->id),
                    'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'
                ];
            }
            $suggestions[] = [
                'section' => 'Sales',
                'items' => $saleItems
            ];
        }

        // Search Restock Orders (scope by branch; field agents: only their branch)
        $allowedBranchIds = $user->branch_id ? \App\Models\Branch::selfAndDescendantIds($user->branch_id) : null;
        $restocksQuery = RestockOrder::where(function ($q) use ($query) {
            $q->where('order_number', 'like', '%' . $query . '%')
                ->orWhere('reference_number', 'like', '%' . $query . '%');
        })->with(['branch', 'product']);
        if ($allowedBranchIds !== null) {
            $restocksQuery->whereIn('branch_id', $allowedBranchIds);
        }
        $restocks = $restocksQuery->limit(5)->get();
        if ($restocks->isNotEmpty()) {
            $restockItems = [];
            foreach ($restocks as $restock) {
                $subtitle = [];
                if ($restock->product) {
                    $subtitle[] = $restock->product->name;
                }
                if ($restock->branch) {
                    $subtitle[] = $restock->branch->name;
                }
                if ($restock->status) {
                    $subtitle[] = ucfirst(str_replace('_', ' ', $restock->status));
                }
                $restockItems[] = [
                    'title' => $restock->order_number,
                    'subtitle' => implode(' • ', array_filter($subtitle)),
                    'url' => route('stock-management.orders.show', $restock),
                    'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                ];
            }
            $suggestions[] = [
                'section' => 'Restock Orders',
                'items' => $restockItems,
            ];
        }

        // Search Stock Transfers (scope: from/to user's branches; field agents: their branch)
        $stockTransfersQuery = StockTransfer::where(function ($q) use ($query) {
            $q->where('transfer_number', 'like', '%' . $query . '%');
        })->with(['fromBranch', 'toBranch', 'product']);
        if ($allowedBranchIds !== null) {
            $stockTransfersQuery->where(function ($q) use ($allowedBranchIds) {
                $q->whereIn('from_branch_id', $allowedBranchIds)
                    ->orWhereIn('to_branch_id', $allowedBranchIds);
            });
        }
        $stockTransfers = $stockTransfersQuery->limit(5)->get();
        if ($stockTransfers->isNotEmpty()) {
            $transferItems = [];
            foreach ($stockTransfers as $transfer) {
                $subtitle = [];
                if ($transfer->fromBranch) {
                    $subtitle[] = $transfer->fromBranch->name . ' → ';
                }
                if ($transfer->toBranch) {
                    $subtitle[] = ($transfer->fromBranch ? '' : '→ ') . $transfer->toBranch->name;
                }
                if ($transfer->product) {
                    $subtitle[] = $transfer->product->name;
                }
                if ($transfer->status) {
                    $subtitle[] = ucfirst($transfer->status);
                }
                $transferItems[] = [
                    'title' => $transfer->transfer_number,
                    'subtitle' => implode('', array_filter($subtitle)),
                    'url' => route('stock-transfers.show', $transfer),
                    'icon' => 'M8 7h12m0 0l-4-4m4 4l4-4M8 17H4m0 0l4 4m-4-4l4-4',
                ];
            }
            $suggestions[] = [
                'section' => 'Stock Transfers',
                'items' => $transferItems,
            ];
        }

        // Search Agent Stock Requests (field agents: only their requests; branch staff: their branch)
        $agentRequestsQuery = AgentStockRequest::where(function ($q) use ($query) {
            $q->where('notes', 'like', '%' . $query . '%')
                ->orWhere('status', 'like', '%' . $query . '%')
                ->orWhereHas('product', fn($p) => $p->where('name', 'like', '%' . $query . '%')->orWhere('sku', 'like', '%' . $query . '%'));
        })->with(['fieldAgent', 'branch', 'product']);
        if ($isFieldAgent) {
            $agentRequestsQuery->where('field_agent_id', $user->id);
        } elseif ($allowedBranchIds !== null) {
            $agentRequestsQuery->whereIn('branch_id', $allowedBranchIds);
        }
        $agentRequests = $agentRequestsQuery->limit(5)->get();
        if ($agentRequests->isNotEmpty()) {
            $agentRequestItems = [];
            foreach ($agentRequests as $req) {
                $subtitle = [];
                if ($req->product) {
                    $subtitle[] = $req->product->name;
                }
                if ($req->fieldAgent) {
                    $subtitle[] = $req->fieldAgent->name;
                }
                if ($req->status) {
                    $subtitle[] = ucfirst($req->status);
                }
                $agentRequestItems[] = [
                    'title' => 'Agent request #' . $req->id,
                    'subtitle' => implode(' • ', array_filter($subtitle)),
                    'url' => route('agent-stock-requests.index'),
                    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                ];
            }
            $suggestions[] = [
                'section' => 'Agent Stock Requests',
                'items' => $agentRequestItems,
            ];
        }

        // Search Branches (field agents: only their branch)
        $branchesQuery = Branch::where(function($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('code', 'like', '%' . $query . '%')
                  ->orWhere('address', 'like', '%' . $query . '%');
            });
        if ($isFieldAgent && $user->branch_id) {
            $branchesQuery->where('id', $user->branch_id);
        }
        $branches = $branchesQuery->with('region')->limit(5)->get();

        if ($branches->isNotEmpty()) {
            $branchItems = [];
            foreach ($branches as $branch) {
                $subtitle = [];
                if ($branch->code) {
                    $subtitle[] = 'Code: ' . $branch->code;
                }
                if ($branch->region) {
                    $subtitle[] = $branch->region->name;
                }
                
                $branchItems[] = [
                    'title' => $branch->name,
                    'subtitle' => implode(' • ', array_filter($subtitle)),
                    'url' => route('branches.show', $branch->id),
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
                ];
            }
            $suggestions[] = [
                'section' => 'Branches',
                'items' => $branchItems
            ];
        }

        // Search Brands
        $brands = Brand::where(function($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('description', 'like', '%' . $query . '%');
            })
            ->limit(5)
            ->get();

        if ($brands->isNotEmpty()) {
            $brandItems = [];
            foreach ($brands as $brand) {
                $subtitle = [];
                if ($brand->description) {
                    $subtitle[] = substr($brand->description, 0, 50) . (strlen($brand->description) > 50 ? '...' : '');
                }
                
                $brandItems[] = [
                    'title' => $brand->name,
                    'subtitle' => implode(' • ', array_filter($subtitle)),
                    'url' => route('brands.show', $brand->id),
                    'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'
                ];
            }
            $suggestions[] = [
                'section' => 'Brands',
                'items' => $brandItems
            ];
        }

        // Search Vendors (bills) – only if user can view bills
        $authUser = $request->user();
        if ($authUser->hasPermission('bills.view') || $authUser->hasPermission('bills.manage-vendors')) {
            $vendors = Vendor::where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                    ->orWhere('contact_person', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%')
                    ->orWhere('phone', 'like', '%' . $query . '%');
            })->limit(5)->get();

            if ($vendors->isNotEmpty()) {
                $vendorItems = [];
                foreach ($vendors as $vendor) {
                    $subtitle = [];
                    if ($vendor->email) {
                        $subtitle[] = $vendor->email;
                    }
                    if ($vendor->contact_person) {
                        $subtitle[] = $vendor->contact_person;
                    }
                    $vendorItems[] = [
                        'title' => $vendor->name,
                        'subtitle' => implode(' • ', array_filter($subtitle)),
                        'url' => $authUser->hasPermission('bills.manage-vendors') ? route('bills.vendors.edit', $vendor) : route('bills.index', ['vendor_id' => $vendor->id]),
                        'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    ];
                }
                $suggestions[] = [
                    'section' => 'Vendors',
                    'items' => $vendorItems,
                ];
            }
        }

        // Search Bills – only if user can view bills; scope by branch
        if ($authUser->hasPermission('bills.view')) {
            $billsQuery = Bill::where(function ($q) use ($query) {
                $q->where('invoice_number', 'like', '%' . $query . '%')
                    ->orWhere('description', 'like', '%' . $query . '%')
                    ->orWhereHas('vendor', fn ($v) => $v->where('name', 'like', '%' . $query . '%'));
            })->with(['vendor', 'branch']);
            if ($allowedBranchIds !== null) {
                $billsQuery->where(function ($q) use ($allowedBranchIds) {
                    $q->whereIn('branch_id', $allowedBranchIds)->orWhereNull('branch_id');
                });
            }
            $bills = $billsQuery->latest()->limit(5)->get();

            if ($bills->isNotEmpty()) {
                $billItems = [];
                foreach ($bills as $bill) {
                    $subtitle = [];
                    if ($bill->vendor) {
                        $subtitle[] = $bill->vendor->name;
                    }
                    if ($bill->invoice_number) {
                        $subtitle[] = $bill->invoice_number;
                    }
                    $subtitle[] = $bill->currency . ' ' . number_format((float) $bill->amount, 2);
                    if ($bill->status) {
                        $subtitle[] = ucfirst(str_replace('_', ' ', $bill->status));
                    }
                    $billItems[] = [
                        'title' => $bill->invoice_number ?: ('Bill – ' . ($bill->vendor?->name ?? 'Unknown')),
                        'subtitle' => implode(' • ', array_filter($subtitle)),
                        'url' => route('bills.show', $bill),
                        'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    ];
                }
                $suggestions[] = [
                    'section' => 'Bills',
                    'items' => $billItems,
                ];
            }
        }

        // Search Petty cash requests – only if user can view petty cash; scope by branch
        if ($authUser->hasPermission('petty-cash.view')) {
            $pettyCashQuery = PettyCashRequest::where(function ($q) use ($query) {
                $q->where('reason', 'like', '%' . $query . '%')
                    ->orWhere('amount', 'like', '%' . $query . '%')
                    ->orWhere('status', 'like', '%' . $query . '%')
                    ->orWhereHas('categoryRelation', fn ($c) => $c->where('name', 'like', '%' . $query . '%'));
            })->with(['fund.branch', 'requestedByUser', 'categoryRelation']);
            if ($allowedBranchIds !== null) {
                $pettyCashQuery->whereHas('fund', fn ($f) => $f->whereIn('branch_id', $allowedBranchIds));
            }
            $pettyCashRequests = $pettyCashQuery->latest()->limit(5)->get();

            if ($pettyCashRequests->isNotEmpty()) {
                $pettyItems = [];
                foreach ($pettyCashRequests as $req) {
                    $subtitle = [];
                    if ($req->fund && $req->fund->branch) {
                        $subtitle[] = $req->fund->branch->name;
                    }
                    $subtitle[] = config('app.currency_symbol') . ' ' . number_format((float) $req->amount, 2);
                    if ($req->categoryRelation) {
                        $subtitle[] = $req->categoryRelation->name;
                    } elseif ($req->category) {
                        $subtitle[] = ucfirst(str_replace('_', ' ', $req->category));
                    }
                    $subtitle[] = ucfirst($req->status ?? '');
                    $pettyItems[] = [
                        'title' => \Illuminate\Support\Str::limit($req->reason ?: 'Petty cash request', 40),
                        'subtitle' => implode(' • ', array_filter($subtitle)),
                        'url' => route('petty-cash.show-request', $req),
                        'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6m0 0a2 2 0 002 2h2m-6 0a2 2 0 002 2m0 0V7a2 2 0 012-2h2a2 2 0 012 2v10',
                    ];
                }
                $suggestions[] = [
                    'section' => 'Petty cash',
                    'items' => $pettyItems,
                ];
            }
        }

        return response()->json(['suggestions' => $suggestions]);
    }
}
