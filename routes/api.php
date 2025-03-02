<?php

use App\Models\AboutUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\TagsController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\CartController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\Days\DayController;
use App\Http\Controllers\User\AddressController;
use App\Http\Controllers\Api\Admin\User\ContactUsController;
use App\Http\Controllers\Api\Device\DeviceController;
use App\Http\Controllers\Api\Review\ReviewController;
use App\Http\Controllers\Api\AboutUs\AboutUsController;
use App\Http\Controllers\Api\Admin\Accounting\AccountingController;
use App\Http\Controllers\Api\Admin\Chat\ChatController;
use App\Http\Controllers\Api\History\HistoryController;
use App\Http\Controllers\Api\HomePage\BannerController;
use App\Http\Controllers\Api\Admin\Teams\TeamController;
use App\Http\Controllers\Api\Admin\AllRequestsController;
use App\Http\Controllers\Api\HomePage\HomePageController;
use App\Http\Controllers\Api\Tracking\TrackingController;
use App\Http\Controllers\Api\HomePage\AdvantageController;
use App\Http\Controllers\Api\Admin\Invoices\InvoiceController;
use App\Http\Controllers\Api\Admin\Technicion\TasksController;
use App\Http\Controllers\Api\Admin\Location\LocationController;
use App\Http\Controllers\Api\Admin\Technicion\WalletController;
use App\Http\Controllers\Api\Admin\User\UserDashboardController;
use App\Http\Controllers\Api\Notification\NotificationController;
use App\Http\Controllers\Api\ProplemParts\ProplemPartsController;
use App\Http\Controllers\Api\Admin\StoreKeeperTechnicionController;
use App\Http\Controllers\Api\AvailableTime\AvailableTimeController;
use App\Http\Controllers\Api\RepairRequest\RepairRequestController;
use App\Http\Controllers\Api\Admin\Technicion\NotaficationController;
use App\Http\Controllers\Api\Admin\UserDetials\UserdetialsController;
use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\RepairCategory\RepairCategoryController;
use App\Http\Controllers\Api\Admin\Technicion\RepairRequestOrderController;
use App\Http\Controllers\Api\Admin\DashboardSearch\DashboardSearchController;
use App\Http\Controllers\Api\Admin\StoreKeeper\RepairRequestDetailsController;
use App\Http\Controllers\Api\Admin\MaintenanceStore\MaintenanceStoreController;
use App\Http\Controllers\Api\Admin\Technicion\AuthController as TechnicionAuthController;
use App\Http\Controllers\Api\Admin\Notification\NotificationController as AdminNotificationController;
use App\Http\Controllers\Api\Admin\Storekeeper\NotificationController as StorekeeperNotificationController;
use App\Http\Controllers\Api\TrackingDashboard\DashboardTrackingController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('user')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('send-otp', [AuthController::class, 'sendOtp']);
    Route::post('verify-otp-phone', [AuthController::class, 'verifyOtpPhone']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('send-otp-forgot-password', [AuthController::class, 'sendOtpForgotPassword']);
    Route::post('otp-forgot-password', [AuthController::class, 'otpForgotPassword']);
    Route::post('confirm-password', [AuthController::class, 'resetPassword']);
    Route::post('update-password', [AuthController::class, 'updatePassword']);
    Route::post('update-profile', [AuthController::class, 'update']);
    Route::post('update-user-email', [AuthController::class, 'updateUserEmail']);
    Route::post('update-user-phone', [AuthController::class, 'updateUserPhone']);
    Route::post('verify-otp-email', [AuthController::class, 'verifyOtpEmail']);
    Route::post('verify-otp-update-phone', [AuthController::class, 'VerifyOtpUpdatePhone']);
    Route::get('get-details', [AuthController::class, 'getUserDetails']);
    Route::post('send-Otp-To-Active-User', [AuthController::class, 'sendOtpToActiveUser']);
    Route::get('noifications', [NotificationController::class, 'getNotifications']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('wallet/add-money', [WalletController::class, 'addMoney']);
    Route::prefix('invoices')->group(function () {
        // Route::get('getInvoiceByUser', [InvoiceController::class, 'getInvoiceByUser']);
        Route::get('/repair-request', [InvoiceController::class, 'getInvoiceByRepairRequest']);
        Route::get('/order', [InvoiceController::class, 'getInvoiceByOrder']);
        Route::post('approve/{id}', [InvoiceController::class, 'ApproveByUser']);
    });

    //avialable Days Logic
    Route::get('available-days/get-all', [DayController::class, 'show']);
    Route::prefix('tracking')->group(function () {
        Route::get('track/{id}', [TrackingController::class, 'track']);
    });
});

Route::prefix('technicions')->group(function () {
    Route::post('login', [TechnicionAuthController::class, 'technicionLogin']);
    Route::get('notafication', [NotaficationController::class, 'getNotifications']);
    Route::post('notifications/{id}/read', [NotaficationController::class, 'markAsRead']);
    Route::get('tasks', [TasksController::class, 'getAll']);
    Route::get('grandGet/{id}', [TasksController::class, 'techniciongrandGet']);
    Route::get('/items-used', [TasksController::class, 'getTechnicianTeamItemsUsed']);
    Route::prefix('wallet')->group(function () {
        Route::post('add-money', [WalletController::class, 'addMoney']);
        Route::post('reset', [WalletController::class, 'resetWallet']);
        Route::get('history', [WalletController::class, 'walletHistory']);
        Route::get('wallet-history', [WalletController::class, 'walletHistoryTransactions']);
    });
    Route::prefix('ordersRepairs')->group(function () {
        Route::post('add', [RepairRequestOrderController::class, 'store']);
    });
    Route::prefix('invoices')->group(function () {
        Route::post('update/{id}', [InvoiceController::class, 'update']);
    });
});

Route::group(['prefix' => 'admin/invoices'], function () {
    Route::get('/', [InvoiceController::class, 'index']);
});

Route::prefix('storekeeper')->group(function () {
    Route::get('show/{id}', [RepairRequestDetailsController::class, 'show']);
    Route::post('/{id}/approve', [RepairRequestDetailsController::class, 'approveOrder']);
    Route::get('all-repiar-requests-orders', [RepairRequestDetailsController::class, 'allRepiarRequestsOrder']);
    Route::get('/notifications', [StorekeeperNotificationController::class, 'getNotifications']);
    Route::post('/notifications/{id}/read', [StorekeeperNotificationController::class, 'markAsRead']);
});


Route::prefix('advantages')->group(function () {
    Route::get('/', [AdvantageController::class, 'getAllAdvantages']);
    Route::post('/', [AdvantageController::class, 'storeAdvantages']);
    Route::get('{id}', [AdvantageController::class, 'showAdvantages']);
    Route::put('{id}', [AdvantageController::class, 'updateAdvantages']);
    Route::delete('{id}', [AdvantageController::class, 'destroyAdvantages']);
});

//Reviews logic
Route::prefix('reviews')->group(function () {
    Route::post('add', [ReviewController::class, 'store']);
    Route::get('get', [ReviewController::class, 'index']);
});

//About logic
Route::prefix('about-us')->group(function () {
    Route::get('section-one', [AboutUsController::class, 'getSectionOne']);
    Route::get('section-two', [AboutUsController::class, 'getSectionTwo']);
    Route::get('video', [AboutUsController::class, 'getVideo']);
});

//Devices logic
Route::prefix('devices')->group(function () {
    Route::get('all', [DeviceController::class, 'index']);
    Route::post('add', [DeviceController::class, 'store']);
    Route::delete('delete/{id}', [DeviceController::class, 'delete']);
});

//Days logic
Route::prefix('days')->group(function () {
    Route::get('all', [DayController::class, 'index']);
    Route::post('add', [DayController::class, 'store']);
});

//avialable Times Logic
Route::prefix('available-times')->group(function () {
    Route::get('get/{id}', [AvailableTimeController::class, 'show']);
});

// Repair Request Logic
Route::prefix('repair-requests')->group(function () {
    Route::post('store', [RepairRequestController::class, 'store']);
    Route::get('history', [RepairRequestController::class, 'history']);
    Route::get('grandGet', [RepairRequestController::class, 'grandGet']);
});

//History logic
Route::prefix('History')->group(function () {
    Route::get('/GetAll', [HistoryController::class, 'getHistory']);
});





Route::get('/allAdvantages', [AdvantageController::class, 'getAllAdvantages']);
Route::get('GetAllIds', [AuthController::class, 'getAllProductIds']);
Route::post('addresses', [AddressController::class, 'addAddress']);
Route::delete('addresses/{addressId}', [AddressController::class, 'deleteAddress']);
Route::post('addresses-update/{addressId}', [AddressController::class, 'updateAddress']);
Route::get('addresses', [AddressController::class, 'getAddresses']);
Route::post('/favorite/{productId}', [AuthController::class, 'toggleFavorite']);
Route::get('/products/favorited', [AuthController::class, 'getfavoritedProducts']);
Route::apiResource('carts', CartController::class);
Route::post('carts/decreaseQuantity', [CartController::class, 'decreaseQuantity']);
Route::post('carts/increaseQuantity', [CartController::class, 'increaseQuantity']);
Route::post('coupons/apply-coupon', [CouponController::class, 'applyCoupon']);
Route::post('products/filter', [ProductController::class, 'filter']);


// Admin
Route::apiResource('brands', BrandController::class);
Route::apiResource('categories', CategoryController::class);
Route::post('/categories/{id}/toggle-status', [CategoryController::class, 'toggleStatus']);
Route::apiResource('coupons', CouponController::class);
Route::post('orders/change-status', [OrderController::class, 'changeStatus']);
Route::post('orders/return-shipping/{id}', [OrderController::class, 'returnShipping']);
Route::apiResource('orders', OrderController::class);
Route::apiResource('products', ProductController::class);
Route::post('/products/{id}/toggle-status', [ProductController::class, 'toggleStatus']);
Route::apiResource('tags', TagsController::class);
Route::apiResource('areas', AreaController::class);
Route::post('store-banner', [BannerController::class, 'store']);
Route::get('HomePage', [HomePageController::class, 'index']);
Route::get('admin-get-products', [ProductController::class, 'indexAdmin']);
Route::get('admin-get-categories', [CategoryController::class, 'indexAdminCategory']);

//All Requests

Route::prefix('admin/requests')->group(function () {
    Route::get('orders', [AllRequestsController::class, 'getAllOrdersByDay']);
    Route::get('order/{id}', [AllRequestsController::class, 'getOrderById']);
    Route::get('repiar-requests', [AllRequestsController::class, 'getRepiarRequestsByDay']);
    Route::get('repiar-request/{id}', [AllRequestsController::class, 'getRepairRequestById']);
});

Route::prefix('admin/invoices')->group(function () {
    Route::get('orders', [InvoiceController::class, 'getOrderInvoices']);
    Route::get('repair-requests', [InvoiceController::class, 'getRepairRequestInvoices']);
    Route::get('order/{id}', [InvoiceController::class, 'getOrderInvoiceDetails']);
    Route::get('repair-request/{id}', [InvoiceController::class, 'getRepairRequestInvoiceDetails']);
});

Route::prefix('admin/Notification')->group(function () {
    Route::get('/notifications', [AdminNotificationController::class, 'getNotifications']);
    Route::post('/notifications/{id}/read', [AdminNotificationController::class, 'markAsRead']);
});

Route::prefix('admin/user-details')->group(function () {
    Route::get('{id}', [UserdetialsController::class, 'getUserDetails']);
    Route::get('{id}/invoices-order', [UserdetialsController::class, 'getOrderInvoices']);
    Route::get('{id}/invoices-repair-request', [UserdetialsController::class, 'getRepairRequestInvoices']);
    Route::get('{id}/devices', [UserdetialsController::class, 'getDevicesUser']);
});


Route::put('/reviews/{review}/approve', [ReviewController::class, 'approve']);
Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

Route::post('/about-us/video', [AboutUsController::class, 'addVideo']);
Route::post('/about-us/section-one', [AboutUsController::class, 'addSectionOne']);
Route::post('/about-us/section-two', [AboutUsController::class, 'addSectionTwo']);

Route::prefix('teams')->group(function () {
    Route::get('all', [TeamController::class, 'index']);
    Route::post('add', [TeamController::class, 'store']);
    Route::get('show/{id}', [TeamController::class, 'show']);
    Route::put('update/{id}', [TeamController::class, 'update']);
    Route::delete('delete/{id}', [TeamController::class, 'destroy']);
    Route::post('{id}/toggle-status', [TeamController::class, 'toggleStatus']);
});



Route::prefix('problem-parts')->group(function () {

    Route::get('all', [ProplemPartsController::class, 'index']);
    Route::post('add', [ProplemPartsController::class, 'store']);
    Route::put('update/{id}', [ProplemPartsController::class, 'update']);
    Route::delete('delete/{id}', [ProplemPartsController::class, 'destroy']);
});

Route::prefix('storekeepers-technicions')->group(function () {
    Route::get('all', [StoreKeeperTechnicionController::class, 'index']);
    Route::post('/', [StoreKeeperTechnicionController::class, 'store']);
    Route::get('{id}', [StoreKeeperTechnicionController::class, 'show']);
    Route::put('{id}', [StoreKeeperTechnicionController::class, 'update']);
    Route::delete('{id}', [StoreKeeperTechnicionController::class, 'destroy']);
    Route::post('{id}/toggle-status', [StoreKeeperTechnicionController::class, 'toggleStatus']);
});

//User dashboard
Route::prefix('user-dashboard')->group(function () {
    Route::get('all', [UserDashboardController::class, 'index']);
    Route::get('{id}', [UserDashboardController::class, 'show']);
    Route::post('{id}/toggle-status', [UserDashboardController::class, 'toggleStatus']);
    Route::delete('{id}', [UserDashboardController::class, 'destroy']);
});

// RepairCategory
Route::prefix('RepairCategory')->group(function () {
    Route::get('all', [RepairCategoryController::class, 'index']);
    Route::post('/', [RepairCategoryController::class, 'store']);
    Route::get('{id}', [RepairCategoryController::class, 'show']);
    Route::put('{id}', [RepairCategoryController::class, 'update']);
    Route::delete('{id}', [RepairCategoryController::class, 'destroy']);
    Route::post('{id}/toggle-status', [RepairCategoryController::class, 'toggleStatus']);
});

// مخزن الصيانية
Route::prefix('maintenance-stores')->group(function () {
    Route::get('/', [MaintenanceStoreController::class, 'index']);
    Route::get('/{id}', [MaintenanceStoreController::class, 'show']);
    Route::post('/', [MaintenanceStoreController::class, 'store']);
    Route::put('/{id}', [MaintenanceStoreController::class, 'update']);
    Route::delete('/{id}', [MaintenanceStoreController::class, 'destroy']);
    Route::post('/{id}/toggle-status', [MaintenanceStoreController::class, 'toggleStatus']);
});

Route::prefix('locations')->group(function () {
    Route::get('/', [LocationController::class, 'index']);
    Route::get('/{id}', [LocationController::class, 'show']);
    Route::post('/', [LocationController::class, 'store']);
    Route::post('/{id}', [LocationController::class, 'update']);
    Route::delete('/{id}', [LocationController::class, 'destroy']);
    Route::post('/{id}/toggle-status', [LocationController::class, 'toggleStatus']);
});

Route::prefix('dashboard-tracking')->group(function () {
    Route::post('/start', [DashboardTrackingController::class, 'startTracking']);
    Route::post('/update-location', [DashboardTrackingController::class, 'updateLocation']);
    Route::post('/stop', [DashboardTrackingController::class, 'stopTracking']);
});

Route::get('/search', [DashboardSearchController::class, 'search']);

// Admin Login
Route::post('admin/login', [AdminAuthController::class, 'login']);
Route::post('admin/logout', [AdminAuthController::class, 'logout']);




Route::prefix('chat')->group(function () {
    Route::post('send', [ChatController::class, 'sendMessage']);
    Route::post('reply', [ChatController::class, 'replyMessage']);
    Route::get('get', [ChatController::class, 'getMessages']);
    Route::get('listConversations', [ChatController::class, 'listConversations']);
    Route::get('suggest', [ChatController::class, 'getUserSuggestion']);
});

Route::prefix('contact-us')->group(function () {
    Route::post('/store', [ContactUsController::class, 'store']);
    Route::get('/get', [ContactUsController::class, 'index']);
});


Route::prefix('admin/accounting')->group(function () {
    Route::get('get-teams-with-wallets', [AccountingController::class, 'getTeamsWithWallets']);
    Route::get('get-total-order-invoices', [AccountingController::class, 'getTotalOrderInvoices']);
    Route::get('get-revenue', [AccountingController::class, 'getRevenue']);
    Route::get('get-collected-revenue', [AccountingController::class, 'getCollectedRevenue']);
    Route::get('get-team-invoices/{id}', [AccountingController::class, 'getTeamInvoices']);
    Route::get('get-team-cash-invoices/{id}', [AccountingController::class, 'getTeamCashInvoices']);
    Route::get('get-team-bank-transfer-invoices/{id}', [AccountingController::class, 'getTeamBankTransferInvoice']);
    Route::post('collect-wallet/{id}', [AccountingController::class, 'collectWallet']);
});
