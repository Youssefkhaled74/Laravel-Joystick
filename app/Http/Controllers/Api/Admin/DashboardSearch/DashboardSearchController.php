<?php

namespace App\Http\Controllers\Api\Admin\DashboardSearch;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tags;
use App\Models\Team;
use App\Models\MaintenanceStore;
use App\Models\User;
use App\Models\Category;
use App\Models\RepairCategory;
use App\Models\Order;
use App\Models\RepairRequestOrder;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\StoreKeeper;
use App\Models\Technicion;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;


class DashboardSearchController extends Controller
{
    use ApiResponse;
    public function __construct()
    {
        $this->middleware('checkLoginAdmin');
    }
    public function search(Request $request)
    {
        $query = $request->input('q');
        $page = $request->input('page');
        $perPage = $request->input('per_page', 10);
        if (!$query) {
            return response()->json(['error' => 'Query is required'], 400);
        }

        $results = [];

        switch ($page) {

            case 'Product':
                $results['Product'] = Product::where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->orWhere('small_description', 'LIKE', "%{$query}%")
                    ->orWhere('price', 'LIKE', "%{$query}%")
                    ->orWhere('status', 'LIKE', "%{$query}%")
                    ->paginate($perPage);
                break;
            case 'MaintenanceStore':
                $results['MaintenanceStore'] = MaintenanceStore::where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->orWhere('uuid', 'LIKE', "%{$query}%")
                    ->orWhere('price', 'LIKE', "%{$query}%")
                    ->orWhere('status', 'LIKE', "%{$query}%")
                    ->paginate($perPage);
                break;
            case 'Produtcategory':
                $results['Produtcategory'] = Category::where('name', 'LIKE', "%{$query}%")
                    ->orWhere('status', 'LIKE', "%{$query}%")
                    ->paginate($perPage);
                break;

            case 'RepairCategory':
                $results['RepairCategory'] = RepairCategory::where('name', 'LIKE', "%{$query}%")
                    ->orWhere('status', 'LIKE', "%{$query}%")
                    ->paginate($perPage);
                break;
            case 'Tag':
                $results['Tag'] = Tags::where('name', 'LIKE', "%{$query}%")
                    ->paginate($perPage);
                break;
            case 'Order':
                $results['Order'] = Order::where('order_number', 'LIKE', "%{$query}%")
                    ->orWhere('phone', 'LIKE', "%{$query}%")
                    ->orWhere('small_description', 'LIKE', "%{$query}%")
                    ->orWhere('total', 'LIKE', "%{$query}%")
                    ->orWhere('first_name', 'LIKE', "%{$query}%")
                    ->orWhere('last_name', 'LIKE', "%{$query}%")
                    ->orWhere('payment_method', 'LIKE', "%{$query}%")
                    ->orWhere('status', 'LIKE', "%{$query}%")
                    ->paginate($perPage);
                break;
            case 'RepairRequestOrder':
                $results['RepairRequestOrder'] = RepairRequestOrder::where('item', 'LIKE', "%{$query}%")
                    ->orWhere('quantity', 'LIKE', "%{$query}%")
                    ->orWhere('notes', 'LIKE', "%{$query}%")
                    ->orWhere('status', 'LIKE', "%{$query}%")
                    ->paginate($perPage);
                break;

            case 'Invoice':
                $results['Invoice'] = Invoice::where('serial_number', 'LIKE', "%{$query}%")
                    ->orWhere('date', 'LIKE', "%{$query}%")
                    ->orWhere('time', 'LIKE', "%{$query}%")
                    ->orWhere('items', 'LIKE', "%{$query}%")
                    ->orWhere('total_price', 'LIKE', "%{$query}%")
                    ->orWhere('payment_method', 'LIKE', "%{$query}%")
                    ->orWhere('status', 'LIKE', "%{$query}%")
                    ->paginate($perPage);
                break;
            case 'Location':
                $results['Location'] = Location::where('name', 'LIKE', "%{$query}%")
                    ->orWhere('status', 'LIKE', "%{$query}%")
                    ->paginate($perPage);
                break;

            case 'employee':
                $storeKeepers = StoreKeeper::where('username', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%")
                    ->orWhere('phone', 'LIKE', "%{$query}%")
                    ->paginate($perPage);

                $technicians = Technicion::where('username', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%")
                    ->orWhere('phone', 'LIKE', "%{$query}%")
                    ->paginate($perPage);

                $results['employees'] = [
                    'storekeepers' => $storeKeepers,
                    'technicians' => $technicians
                ];
                break;


            case 'Team':
                $results['Team'] = Team::where('name', 'LIKE', "%{$query}%")
                    ->paginate($perPage);
                break;

            case 'User':
                $results['User'] = User::where('username', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%")
                    ->orWhere('phone', 'LIKE', "%{$query}%")
                    ->orWhere('status', 'LIKE', "%{$query}%")
                    ->paginate($perPage);
                break;

            default:
                return $this->errorResponse(400, __('messages.Invalid_page_Type'));
        }
        return $this->successResponse(200, __('messages.search_returned_successfully'), $results);
    }
}
