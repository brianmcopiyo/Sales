<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StockRequestController;
use App\Http\Controllers\AgentStockRequestController;
use App\Http\Controllers\StockTakeController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalesStatsController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\BranchStockController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockManagementController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DealershipController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceRequestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\FieldAgentController;
use App\Http\Controllers\ProductPricingController;
use App\Http\Controllers\CommissionDisbursementController;
use App\Http\Controllers\CustomerDisbursementController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\BillCategoryController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\RecurringBillController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\HubController;

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if (method_exists($user, 'hasPermission') && $user->hasPermission('dashboard.view')) {
            return redirect()->route('dashboard');
        }
        return redirect()->route('profile.show');
    }
    return view('landing.welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // OTP Verification Routes (after password login)
    Route::get('/login/otp', [AuthController::class, 'showLoginOtp'])->name('login.otp');
    Route::post('/login/otp', [AuthController::class, 'verifyLoginOtp'])->name('login.otp.verify');
    Route::post('/login/otp/resend', [AuthController::class, 'resendLoginOtp'])->name('login.otp.resend');

    // Forgot Password Routes
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.forgot');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('permission:dashboard.view');

    // Global Search Route
    Route::get('/search', [SearchController::class, 'search'])->name('search');

    // Notifications (in-app)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

    // Hub routes (merged menu destinations) — access gated by permission
    Route::get('/stock-operations', [HubController::class, 'stockOperations'])->name('stock-operations.index')
        ->middleware('permission:stock-takes.view|stock-adjustments.view|stock-transfers.view');
    Route::get('/catalog', [HubController::class, 'catalog'])->name('catalog.index')
        ->middleware('permission:products.view|brands.view|devices.view|products.pricing');
    Route::get('/locations', [HubController::class, 'locations'])->name('locations.index')
        ->middleware('permission:branches.view|regions.view');
    Route::get('/sales-transactions', [HubController::class, 'salesTransactions'])->name('sales-transactions.index')
        ->middleware('permission:sales.view|transactions.view');
    Route::get('/customers-hub', [HubController::class, 'customers'])->name('customers.hub')
        ->middleware('permission:customers.view');
    Route::get('/support', [HubController::class, 'support'])->name('support.index')
        ->middleware('permission:tickets.view');
    Route::get('/team', [HubController::class, 'team'])->name('team.index')
        ->middleware('permission:users.view');

    // Profile - accessible to all authenticated users (no permission required for own profile)
    Route::get('/profile', [ProfileController::class, 'show'])
        ->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::post('/profile/picture', [ProfileController::class, 'updateProfilePicture'])
        ->name('profile.picture.update');
    Route::delete('/profile/picture', [ProfileController::class, 'deleteProfilePicture'])
        ->name('profile.picture.delete');
    Route::put('/profile/dashboard-background', [ProfileController::class, 'updateDashboardBackground'])
        ->name('profile.dashboard-background.update');
    Route::put('/profile/theme', [ProfileController::class, 'updateTheme'])
        ->name('profile.theme.update');

    // Stock Management
    // Inventory Management
    Route::get('/inventory', [InventoryController::class, 'dashboard'])
        ->name('inventory.dashboard')
        ->middleware('permission:inventory.view');
    Route::get('/inventory/movements', [InventoryController::class, 'movements'])
        ->name('inventory.movements')
        ->middleware('permission:inventory.movements.view');
    Route::get('/inventory/alerts', [InventoryController::class, 'alerts'])
        ->name('inventory.alerts')
        ->middleware('permission:inventory.alerts.view');
    Route::post('/inventory/alerts/{alert}/resolve', [InventoryController::class, 'resolveAlert'])
        ->name('inventory.alerts.resolve')
        ->middleware('permission:inventory.alerts.manage');
    Route::get('/inventory/stock-history', [InventoryController::class, 'stockHistory'])
        ->name('inventory.stock-history')
        ->middleware('permission:inventory.view');

    Route::get('/stock-management', [StockManagementController::class, 'index'])
        ->name('stock-management.index')
        ->middleware('permission:stock-management.view');
    Route::get('/stock-management/reconciliation', [StockManagementController::class, 'reconciliation'])
        ->name('stock-management.reconciliation')
        ->middleware('permission:stock-management.view');
    Route::post('/stock-management/reconciliation/fix', [StockManagementController::class, 'applyReconciliationFix'])
        ->name('stock-management.reconciliation.fix')
        ->middleware('permission:stock-management.view');
    Route::post('/stock-management/sync-stock-from-devices', [StockManagementController::class, 'syncStockFromDevices'])
        ->name('stock-management.sync-stock-from-devices')
        ->middleware('permission:stock-management.view');
    Route::get('/stock-management/restock-wizard', [StockManagementController::class, 'restockWizard'])
        ->name('stock-management.restock-wizard')
        ->middleware('permission:stock-management.restock|stock-management.initiate-restock');
    Route::get('/stock-management/restock-orders', [StockManagementController::class, 'restockOrdersIndex'])
        ->name('stock-management.restock-orders.index')
        ->middleware('permission:stock-management.view');
    Route::get('/stock-management/restock-orders/export', [StockManagementController::class, 'exportRestockOrders'])
        ->name('stock-management.restock-orders.export')
        ->middleware('permission:stock-management.view');
    Route::post('/stock-transfers/{stockTransfer}/approve', [StockManagementController::class, 'approve'])
        ->name('stock-management.approve')
        ->middleware('permission:stock-management.approve');
    Route::get('/stock-management/orders/{restockOrder}', [StockManagementController::class, 'showOrder'])
        ->name('stock-management.orders.show')
        ->middleware('permission:stock-management.view');
    Route::post('/stock-management/orders', [StockManagementController::class, 'storeOrder'])
        ->name('stock-management.orders.store')
        ->middleware('permission:stock-management.restock|stock-management.initiate-restock');
    Route::post('/stock-management/orders/{restockOrder}/approve', [StockManagementController::class, 'approveOrder'])
        ->name('stock-management.orders.approve')
        ->middleware('permission:stock-management.restock');
    Route::post('/stock-management/orders/{restockOrder}/receive', [StockManagementController::class, 'receiveOrder'])
        ->name('stock-management.orders.receive')
        ->middleware('permission:stock-management.restock');
    Route::post('/stock-management/orders/{restockOrder}/reject', [StockManagementController::class, 'rejectOrder'])
        ->name('stock-management.orders.reject')
        ->middleware('permission:stock-management.restock');
    Route::put('/stock-management/orders/{restockOrder}/quantity', [StockManagementController::class, 'updateOrderQuantity'])
        ->name('stock-management.orders.update-quantity')
        ->middleware('permission:stock-management.restock');
    Route::post('/stock-management/orders/{restockOrder}/transfer-catalog', [StockManagementController::class, 'transferCatalogToBranch'])
        ->name('stock-management.orders.transfer-catalog')
        ->middleware('permission:stock-management.restock');

    // Products
    Route::get('products', [ProductController::class, 'index'])
        ->name('products.index')
        ->middleware('permission:products.view');
    Route::get('products/export', [ProductController::class, 'export'])
        ->name('products.export')
        ->middleware('permission:products.view');
    Route::get('products/create', [ProductController::class, 'create'])
        ->name('products.create')
        ->middleware('permission:products.create');
    Route::post('products', [ProductController::class, 'store'])
        ->name('products.store')
        ->middleware('permission:products.create');
    Route::get('products/merge', [ProductController::class, 'mergeForm'])
        ->name('products.merge.form')
        ->middleware('permission:products.update');
    Route::post('products/merge', [ProductController::class, 'merge'])
        ->name('products.merge')
        ->middleware('permission:products.update');
    Route::get('products/{product}', [ProductController::class, 'show'])
        ->name('products.show')
        ->middleware('permission:products.view');
    Route::get('products/{product}/edit', [ProductController::class, 'edit'])
        ->name('products.edit')
        ->middleware('permission:products.update');
    Route::put('products/{product}', [ProductController::class, 'update'])
        ->name('products.update')
        ->middleware('permission:products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->name('products.delete')
        ->middleware('permission:products.delete');

    // Product Pricing
    Route::get('product-pricing', [ProductPricingController::class, 'index'])
        ->name('product-pricing.index')
        ->middleware('permission:products.pricing');
    Route::get('product-pricing/{product}/edit', [ProductPricingController::class, 'edit'])
        ->name('product-pricing.edit')
        ->middleware('permission:products.pricing');
    Route::put('product-pricing/{product}', [ProductPricingController::class, 'update'])
        ->name('product-pricing.update')
        ->middleware('permission:products.pricing');

    // Brands
    Route::get('brands', [BrandController::class, 'index'])
        ->name('brands.index')
        ->middleware('permission:brands.view');
    Route::get('brands/create', [BrandController::class, 'create'])
        ->name('brands.create')
        ->middleware('permission:brands.create');
    Route::post('brands', [BrandController::class, 'store'])
        ->name('brands.store')
        ->middleware('permission:brands.create');
    Route::get('brands/{brand}', [BrandController::class, 'show'])
        ->name('brands.show')
        ->middleware('permission:brands.view');
    Route::get('brands/{brand}/edit', [BrandController::class, 'edit'])
        ->name('brands.edit')
        ->middleware('permission:brands.update');
    Route::put('brands/{brand}', [BrandController::class, 'update'])
        ->name('brands.update')
        ->middleware('permission:brands.update');
    Route::delete('brands/{brand}', [BrandController::class, 'destroy'])
        ->name('brands.delete')
        ->middleware('permission:brands.delete');

    // Regions
    Route::get('regions', [RegionController::class, 'index'])
        ->name('regions.index')
        ->middleware('permission:regions.view');
    Route::get('regions/create', [RegionController::class, 'create'])
        ->name('regions.create')
        ->middleware('permission:regions.create');
    Route::post('regions', [RegionController::class, 'store'])
        ->name('regions.store')
        ->middleware('permission:regions.create');
    Route::get('regions/{region}', [RegionController::class, 'show'])
        ->name('regions.show')
        ->middleware('permission:regions.view');
    Route::get('regions/{region}/edit', [RegionController::class, 'edit'])
        ->name('regions.edit')
        ->middleware('permission:regions.update');
    Route::put('regions/{region}', [RegionController::class, 'update'])
        ->name('regions.update')
        ->middleware('permission:regions.update');
    Route::delete('regions/{region}', [RegionController::class, 'destroy'])
        ->name('regions.delete')
        ->middleware('permission:regions.delete');

    // Branches
    Route::get('branches', [BranchController::class, 'index'])
        ->name('branches.index')
        ->middleware('permission:branches.view');
    Route::get('branches/create', [BranchController::class, 'create'])
        ->name('branches.create')
        ->middleware('permission:branches.create');
    Route::post('branches', [BranchController::class, 'store'])
        ->name('branches.store')
        ->middleware('permission:branches.create');
    Route::get('branches/{branch}', [BranchController::class, 'show'])
        ->name('branches.show')
        ->middleware('permission:branches.view');
    Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])
        ->name('branches.edit')
        ->middleware('permission:branches.update');
    Route::put('branches/{branch}', [BranchController::class, 'update'])
        ->name('branches.update')
        ->middleware('permission:branches.update');
    Route::delete('branches/{branch}', [BranchController::class, 'destroy'])
        ->name('branches.delete')
        ->middleware('permission:branches.delete');
    Route::get('branches/{branch}/add-users', [BranchController::class, 'addUsers'])
        ->name('branches.add-users')
        ->middleware('permission:branches.manage-users');
    Route::post('branches/{branch}/assign-users', [BranchController::class, 'assignUsers'])
        ->name('branches.assign-users')
        ->middleware('permission:branches.manage-users');

    // Dealerships
    Route::get('dealerships', [DealershipController::class, 'index'])
        ->name('dealerships.index')
        ->middleware('permission:stock-management.view');
    Route::get('dealerships/create', [DealershipController::class, 'create'])
        ->name('dealerships.create')
        ->middleware('permission:stock-management.view');
    Route::post('dealerships', [DealershipController::class, 'store'])
        ->name('dealerships.store')
        ->middleware('permission:stock-management.view');
    Route::get('dealerships/{dealership}/edit', [DealershipController::class, 'edit'])
        ->name('dealerships.edit')
        ->middleware('permission:stock-management.view');
    Route::put('dealerships/{dealership}', [DealershipController::class, 'update'])
        ->name('dealerships.update')
        ->middleware('permission:stock-management.view');

    // Stock Transfers
    Route::get('stock-transfers', [StockTransferController::class, 'index'])
        ->name('stock-transfers.index')
        ->middleware('permission:stock-transfers.view');
    Route::get('stock-transfers/create', [StockTransferController::class, 'create'])
        ->name('stock-transfers.create')
        ->middleware('permission:stock-transfers.create');
    Route::post('stock-transfers', [StockTransferController::class, 'store'])
        ->name('stock-transfers.store')
        ->middleware('permission:stock-transfers.create');
    Route::get('stock-transfers/export', [StockTransferController::class, 'export'])
        ->name('stock-transfers.export')
        ->middleware('permission:stock-transfers.view');
    Route::get('stock-transfers/{stockTransfer}', [StockTransferController::class, 'show'])
        ->name('stock-transfers.show')
        ->middleware('permission:stock-transfers.view');
    Route::post('stock-transfers/{stockTransfer}/receive', [StockTransferController::class, 'receive'])
        ->name('stock-transfers.receive')
        ->middleware('permission:stock-transfers.receive');
    Route::post('stock-transfers/{stockTransfer}/attach-devices', [StockTransferController::class, 'attachDevices'])
        ->name('stock-transfers.attach-devices')
        ->middleware('permission:stock-transfers.view');
    Route::post('stock-transfers/{stockTransfer}/cancel', [StockTransferController::class, 'cancel'])
        ->name('stock-transfers.cancel')
        ->middleware('permission:stock-transfers.cancel');
    Route::post('stock-transfers/{stockTransfer}/reject', [StockTransferController::class, 'reject'])
        ->name('stock-transfers.reject')
        ->middleware('permission:stock-transfers.reject');
    Route::post('stock-transfers/{stockTransfer}/confirm-partial-reception', [StockTransferController::class, 'confirmPartialReception'])
        ->name('stock-transfers.confirm-partial-reception')
        ->middleware('permission:stock-transfers.receive');
    Route::post('stock-transfers/{stockTransfer}/return-partial-reception', [StockTransferController::class, 'returnPartialReception'])
        ->name('stock-transfers.return-partial-reception')
        ->middleware('permission:stock-transfers.receive');
    Route::get('stock-transfers/reception-attachments/{attachment}/download', [StockTransferController::class, 'downloadReceptionAttachment'])
        ->name('stock-transfers.reception-attachment.download')
        ->middleware('permission:stock-transfers.view');

    // Stock Requests (request stock from another branch when running low)
    Route::get('stock-requests', [StockRequestController::class, 'index'])
        ->name('stock-requests.index')
        ->middleware('permission:stock-requests.view');
    Route::get('stock-requests/create', [StockRequestController::class, 'create'])
        ->name('stock-requests.create')
        ->middleware('permission:stock-requests.create');
    Route::get('stock-requests/{stockRequest}', [StockRequestController::class, 'show'])
        ->name('stock-requests.show')
        ->middleware('permission:stock-requests.view');
    Route::post('stock-requests', [StockRequestController::class, 'store'])
        ->name('stock-requests.store')
        ->middleware('permission:stock-requests.create');
    Route::post('stock-requests/{stockRequest}/approve', [StockRequestController::class, 'approve'])
        ->name('stock-requests.approve')
        ->middleware('permission:stock-requests.create');
    Route::post('stock-requests/{stockRequest}/reject', [StockRequestController::class, 'reject'])
        ->name('stock-requests.reject')
        ->middleware('permission:stock-requests.create');
    Route::post('stock-requests/{stockRequest}/close', [StockRequestController::class, 'close'])
        ->name('stock-requests.close')
        ->middleware('permission:stock-requests.create');

    // Agent Stock Requests (field agents request stock from their branch; branch staff need agent-stock-requests.* permission)
    Route::get('agent-stock-requests', [AgentStockRequestController::class, 'index'])
        ->name('agent-stock-requests.index')
        ->middleware('agent_stock_request:view');
    Route::get('agent-stock-requests/create', [AgentStockRequestController::class, 'create'])
        ->name('agent-stock-requests.create')
        ->middleware('agent_stock_request:view');
    Route::post('agent-stock-requests', [AgentStockRequestController::class, 'store'])
        ->name('agent-stock-requests.store')
        ->middleware('agent_stock_request:store');
    Route::post('agent-stock-requests/{agentStockRequest}/approve', [AgentStockRequestController::class, 'approve'])
        ->name('agent-stock-requests.approve')
        ->middleware('agent_stock_request:manage');
    Route::post('agent-stock-requests/{agentStockRequest}/reject', [AgentStockRequestController::class, 'reject'])
        ->name('agent-stock-requests.reject')
        ->middleware('agent_stock_request:manage');
    Route::post('agent-stock-requests/{agentStockRequest}/close', [AgentStockRequestController::class, 'close'])
        ->name('agent-stock-requests.close')
        ->middleware('agent_stock_request:manage');

    // Stock Takes
    Route::get('stock-takes', [StockTakeController::class, 'index'])
        ->name('stock-takes.index')
        ->middleware('permission:stock-takes.view');
    Route::get('stock-takes/create', [StockTakeController::class, 'create'])
        ->name('stock-takes.create')
        ->middleware('permission:stock-takes.create');
    Route::post('stock-takes', [StockTakeController::class, 'store'])
        ->name('stock-takes.store')
        ->middleware('permission:stock-takes.create');
    Route::get('stock-takes/export', [StockTakeController::class, 'export'])
        ->name('stock-takes.export')
        ->middleware('permission:stock-takes.view');
    Route::get('stock-takes/{stockTake}', [StockTakeController::class, 'show'])
        ->name('stock-takes.show')
        ->middleware('permission:stock-takes.view');
    Route::get('stock-takes/{stockTake}/edit', [StockTakeController::class, 'edit'])
        ->name('stock-takes.edit')
        ->middleware('permission:stock-takes.update');
    Route::put('stock-takes/{stockTake}', [StockTakeController::class, 'update'])
        ->name('stock-takes.update')
        ->middleware('permission:stock-takes.update');
    Route::post('stock-takes/{stockTake}/add-item', [StockTakeController::class, 'addItem'])
        ->name('stock-takes.add-item')
        ->middleware('permission:stock-takes.update');
    Route::put('stock-takes/{stockTake}/items/{item}', [StockTakeController::class, 'updateItem'])
        ->name('stock-takes.update-item')
        ->middleware('permission:stock-takes.update');
    Route::post('stock-takes/{stockTake}/items/{item}/upload-imei-file', [StockTakeController::class, 'uploadImeiFile'])
        ->name('stock-takes.upload-imei-file')
        ->middleware('permission:stock-takes.update');
    Route::post('stock-takes/{stockTake}/items/{item}/process-imei-batch', [StockTakeController::class, 'processImeiBatch'])
        ->name('stock-takes.process-imei-batch')
        ->middleware('permission:stock-takes.update');
    Route::delete('stock-takes/{stockTake}/items/{item}', [StockTakeController::class, 'removeItem'])
        ->name('stock-takes.remove-item')
        ->middleware('permission:stock-takes.update');
    Route::post('stock-takes/{stockTake}/complete', [StockTakeController::class, 'complete'])
        ->name('stock-takes.complete')
        ->middleware('permission:stock-takes.update');
    Route::post('stock-takes/{stockTake}/approve', [StockTakeController::class, 'approve'])
        ->name('stock-takes.approve')
        ->middleware('permission:stock-takes.approve');
    Route::post('stock-takes/{stockTake}/cancel', [StockTakeController::class, 'cancel'])
        ->name('stock-takes.cancel')
        ->middleware('permission:stock-takes.cancel');

    // Stock Adjustments
    Route::get('stock-adjustments', [StockAdjustmentController::class, 'index'])
        ->name('stock-adjustments.index')
        ->middleware('permission:stock-adjustments.view');
    Route::get('stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show'])
        ->name('stock-adjustments.show')
        ->middleware('permission:stock-adjustments.view');

    // Sales
    Route::get('sales', [SaleController::class, 'index'])
        ->name('sales.index')
        ->middleware('permission:sales.view');
    Route::get('sales/create', [SaleController::class, 'create'])
        ->name('sales.create')
        ->middleware('permission:sales.create');
    Route::post('sales', [SaleController::class, 'store'])
        ->name('sales.store')
        ->middleware('permission:sales.create');
    Route::get('sales/export', [SaleController::class, 'export'])
        ->name('sales.export')
        ->middleware('permission:sales.view');
    Route::get('sales/stats', [SalesStatsController::class, 'index'])
        ->name('sales-stats.index')
        ->middleware('permission:sales.view');
    Route::get('sales/stats/users/{user}', [SalesStatsController::class, 'userShow'])
        ->name('sales-stats.user')
        ->middleware('permission:sales.view');
    Route::get('sales/stats/products/{product}', [SalesStatsController::class, 'productShow'])
        ->name('sales-stats.product')
        ->middleware('permission:sales.view');
    Route::get('sales/evidence-attachments/{attachment}/download', [SaleController::class, 'downloadEvidence'])
        ->name('sales.evidence.download')
        ->middleware('permission:sales.view');
    Route::get('sales/{sale}', [SaleController::class, 'show'])
        ->name('sales.show')
        ->middleware('permission:sales.view');
    Route::post('sales/{sale}/complete', [SaleController::class, 'complete'])
        ->name('sales.complete')
        ->middleware('permission:sales.view|sales.create');
    Route::post('sales/{sale}/attach-customer', [SaleController::class, 'attachCustomer'])
        ->name('sales.attach-customer')
        ->middleware('permission:sales.view|sales.create');
    Route::post('sales/{sale}/reopen', [SaleController::class, 'reopen'])
        ->name('sales.reopen')
        ->middleware('permission:sales.view|sales.create');
    Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])
        ->name('sales.cancel')
        ->middleware('permission:sales.view');
    Route::post('sales/{sale}/replace-device', [SaleController::class, 'replaceDevice'])
        ->name('sales.replace-device')
        ->middleware('permission:sales.view');

    // Device requests (request device from another branch; host branch approves)
    Route::get('device-requests', [DeviceRequestController::class, 'index'])
        ->name('device-requests.index')
        ->middleware('permission:stock-requests.view|sales.view');
    Route::post('device-requests', [DeviceRequestController::class, 'store'])
        ->name('device-requests.store')
        ->middleware('permission:stock-requests.create|sales.create');
    Route::get('device-requests/{deviceRequest}', [DeviceRequestController::class, 'show'])
        ->name('device-requests.show')
        ->middleware('permission:stock-requests.view|sales.view');
    Route::post('device-requests/{deviceRequest}/approve', [DeviceRequestController::class, 'approve'])
        ->name('device-requests.approve')
        ->middleware('permission:stock-requests.view|sales.view');
    Route::post('device-requests/{deviceRequest}/reject', [DeviceRequestController::class, 'reject'])
        ->name('device-requests.reject')
        ->middleware('permission:stock-requests.view|sales.view');

    // Transactions
    Route::get('transactions', [TransactionController::class, 'index'])
        ->name('transactions.index')
        ->middleware('permission:transactions.view');
    Route::get('transactions/export', [TransactionController::class, 'export'])
        ->name('transactions.export')
        ->middleware('permission:transactions.view');

    // Tickets
    Route::get('tickets', [TicketController::class, 'index'])
        ->name('tickets.index')
        ->middleware('permission:tickets.view');
    Route::get('tickets/export', [TicketController::class, 'export'])
        ->name('tickets.export')
        ->middleware('permission:tickets.view');
    Route::get('tickets/create', [TicketController::class, 'create'])
        ->name('tickets.create')
        ->middleware('permission:tickets.create');
    Route::post('tickets', [TicketController::class, 'store'])
        ->name('tickets.store')
        ->middleware('permission:tickets.create');
    Route::get('tickets/{ticket}', [TicketController::class, 'show'])
        ->name('tickets.show')
        ->middleware('permission:tickets.view');
    Route::get('tickets/{ticket}/edit', [TicketController::class, 'edit'])
        ->name('tickets.edit')
        ->middleware('permission:tickets.update');
    Route::put('tickets/{ticket}', [TicketController::class, 'update'])
        ->name('tickets.update')
        ->middleware('permission:tickets.update');
    Route::post('tickets/{ticket}/reply', [TicketController::class, 'reply'])
        ->name('tickets.reply')
        ->middleware('permission:tickets.reply');
    Route::post('tickets/{ticket}/create-disbursement', [TicketController::class, 'createDisbursement'])
        ->name('tickets.create-disbursement')
        ->middleware('permission:tickets.disbursements');
    Route::get('tickets/attachments/{attachment}/download', [TicketController::class, 'downloadAttachment'])
        ->name('tickets.attachments.download')
        ->middleware('permission:tickets.view');
    Route::delete('tickets/attachments/{attachment}', [TicketController::class, 'deleteAttachment'])
        ->name('tickets.attachments.delete')
        ->middleware('permission:tickets.update');

    // Ticket Escalation Routes
    Route::post('tickets/{ticket}/escalate', [TicketController::class, 'requestEscalation'])
        ->name('tickets.escalate')
        ->middleware('permission:tickets.update');
    Route::post('tickets/{ticket}/escalations/{escalation}/accept', [TicketController::class, 'acceptEscalation'])
        ->name('tickets.escalations.accept')
        ->middleware('permission:tickets.update');
    Route::post('tickets/{ticket}/escalations/{escalation}/reject', [TicketController::class, 'rejectEscalation'])
        ->name('tickets.escalations.reject')
        ->middleware('permission:tickets.update');
    Route::post('tickets/{ticket}/escalations/{escalation}/cancel', [TicketController::class, 'cancelEscalation'])
        ->name('tickets.escalations.cancel')
        ->middleware('permission:tickets.update');
    Route::post('tickets/{ticket}/assignment-activity', [TicketController::class, 'updateAssignmentActivity'])
        ->name('tickets.assignment-activity')
        ->middleware('permission:tickets.update');

    // Branch Stocks
    Route::get('branch-stocks', [BranchStockController::class, 'index'])
        ->name('branch-stocks.index')
        ->middleware('permission:branch-stocks.view');
    Route::get('branch-stocks/create', [BranchStockController::class, 'create'])
        ->name('branch-stocks.create')
        ->middleware('permission:branch-stocks.create');
    Route::post('branch-stocks', [BranchStockController::class, 'store'])
        ->name('branch-stocks.store')
        ->middleware('permission:branch-stocks.create');
    Route::get('branch-stocks/{branchStock}/edit', [BranchStockController::class, 'edit'])
        ->name('branch-stocks.edit')
        ->middleware('permission:branch-stocks.update');
    Route::put('branch-stocks/{branchStock}', [BranchStockController::class, 'update'])
        ->name('branch-stocks.update')
        ->middleware('permission:branch-stocks.update');

    // Customers
    Route::get('customers', [CustomerController::class, 'index'])
        ->name('customers.index')
        ->middleware('permission:customers.view');
    Route::get('customers/export', [CustomerController::class, 'export'])
        ->name('customers.export')
        ->middleware('permission:customers.view');
    Route::get('customers/create', [CustomerController::class, 'create'])
        ->name('customers.create')
        ->middleware('permission:customers.create');
    Route::post('customers', [CustomerController::class, 'store'])
        ->name('customers.store')
        ->middleware('permission:customers.create');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])
        ->name('customers.show')
        ->middleware('permission:customers.view');
    Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])
        ->name('customers.edit')
        ->middleware('permission:customers.update');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])
        ->name('customers.update')
        ->middleware('permission:customers.update');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])
        ->name('customers.delete')
        ->middleware('permission:customers.delete');
    Route::get('customers/{customer}/disbursements', [CustomerDisbursementController::class, 'customerDisbursements'])
        ->name('customers.disbursements')
        ->middleware('permission:customer-disbursements.view');

    // API route for customer sales (only sales without disbursements, for linking on create disbursement)
    Route::get('api/customers/{customer}/sales', function ($customer) {
        $sales = \App\Models\Sale::where('customer_id', $customer)
            ->whereDoesntHave('customerDisbursements')
            ->latest()
            ->get(['id', 'sale_number', 'total', 'created_at']);
        return response()->json($sales);
    })->middleware('permission:customers.view|customer-disbursements.create');

    // API route for customer devices (that haven't received disbursement)
    Route::get('api/customers/{customer}/devices', function ($customer) {
        $devices = \App\Models\Device::where('customer_id', $customer)
            ->where('has_received_disbursement', false)
            ->with('product:id,name')
            ->latest()
            ->get(['id', 'imei', 'product_id']);
        return response()->json($devices);
    })->middleware('permission:customers.view');

    // API route for sale devices (that haven't received disbursement)
    Route::get('api/sales/{sale}/devices', function (\App\Models\Sale $sale) {
        $devices = \App\Models\Device::where('sale_id', $sale->id)
            ->where('has_received_disbursement', false)
            ->with('product:id,name')
            ->latest()
            ->get(['id', 'imei', 'product_id']);
        return response()->json($devices);
    })->middleware('permission:sales.view|customer-disbursements.create');

    // Customer Disbursements
    Route::get('customer-disbursements', [CustomerDisbursementController::class, 'index'])
        ->name('customer-disbursements.index')
        ->middleware('permission:customer-disbursements.view');
    Route::get('customer-disbursements/export', [CustomerDisbursementController::class, 'export'])
        ->name('customer-disbursements.export')
        ->middleware('permission:customer-disbursements.view');
    Route::get('customer-disbursements/create', [CustomerDisbursementController::class, 'create'])
        ->name('customer-disbursements.create')
        ->middleware('permission:customer-disbursements.create');
    Route::post('customer-disbursements', [CustomerDisbursementController::class, 'store'])
        ->name('customer-disbursements.store')
        ->middleware('permission:customer-disbursements.create');
    Route::get('customer-disbursements/{customerDisbursement}', [CustomerDisbursementController::class, 'show'])
        ->name('customer-disbursements.show')
        ->middleware('permission:customer-disbursements.view');
    Route::post('customer-disbursements/{customerDisbursement}/approve', [CustomerDisbursementController::class, 'approve'])
        ->name('customer-disbursements.approve')
        ->middleware('permission:customer-disbursements.approve');
    Route::post('customer-disbursements/{customerDisbursement}/reject', [CustomerDisbursementController::class, 'reject'])
        ->name('customer-disbursements.reject')
        ->middleware('permission:customer-disbursements.approve');

    // Petty Cash
    Route::get('petty-cash', [PettyCashController::class, 'index'])
        ->name('petty-cash.index')
        ->middleware('permission:petty-cash.view');
    Route::get('petty-cash/export', [PettyCashController::class, 'export'])
        ->name('petty-cash.export')
        ->middleware('permission:petty-cash.view');
    Route::get('petty-cash/request/create', [PettyCashController::class, 'createRequest'])
        ->name('petty-cash.request.create')
        ->middleware('permission:petty-cash.request');
    Route::post('petty-cash/request', [PettyCashController::class, 'storeRequest'])
        ->name('petty-cash.request.store')
        ->middleware('permission:petty-cash.request');
    Route::get('petty-cash/request/{pettyCashRequest}', [PettyCashController::class, 'showRequest'])
        ->name('petty-cash.show-request')
        ->middleware('permission:petty-cash.view');
    Route::get('petty-cash/request/{pettyCashRequest}/attachment', [PettyCashController::class, 'downloadAttachment'])
        ->name('petty-cash.request.attachment')
        ->middleware('permission:petty-cash.view');
    Route::post('petty-cash/request/{pettyCashRequest}/proof-of-expenditure', [PettyCashController::class, 'uploadProofOfExpenditure'])
        ->name('petty-cash.upload-proof')
        ->middleware('permission:petty-cash.view');
    Route::get('petty-cash/request/{pettyCashRequest}/proof-of-expenditure', [PettyCashController::class, 'downloadProofOfExpenditure'])
        ->name('petty-cash.download-proof')
        ->middleware('permission:petty-cash.view');
    Route::post('petty-cash/request/{pettyCashRequest}/approve', [PettyCashController::class, 'approve'])
        ->name('petty-cash.approve')
        ->middleware('permission:petty-cash.approve');
    Route::post('petty-cash/request/{pettyCashRequest}/reject', [PettyCashController::class, 'reject'])
        ->name('petty-cash.reject')
        ->middleware('permission:petty-cash.approve');
    Route::post('petty-cash/request/{pettyCashRequest}/disburse', [PettyCashController::class, 'disburse'])
        ->name('petty-cash.disburse')
        ->middleware('permission:petty-cash.custodian');
    Route::get('petty-cash/fund/{pettyCashFund}/replenish', [PettyCashController::class, 'replenishForm'])
        ->name('petty-cash.replenish.form')
        ->middleware('permission:petty-cash.replenish');
    Route::post('petty-cash/fund/{pettyCashFund}/replenish', [PettyCashController::class, 'replenishStore'])
        ->name('petty-cash.replenish.store')
        ->middleware('permission:petty-cash.replenish');
    Route::get('petty-cash/fund/{pettyCashFund}/reconciliation', [PettyCashController::class, 'reconciliation'])
        ->name('petty-cash.reconciliation')
        ->middleware('permission:petty-cash.view');
    Route::get('petty-cash/funds', [PettyCashController::class, 'fundsIndex'])
        ->name('petty-cash.funds.index')
        ->middleware('permission:petty-cash.manage-funds');
    Route::get('petty-cash/funds/create', [PettyCashController::class, 'fundsCreate'])
        ->name('petty-cash.funds.create')
        ->middleware('permission:petty-cash.manage-funds');
    Route::get('petty-cash/funds/{pettyCashFund}', [PettyCashController::class, 'fundsShow'])
        ->name('petty-cash.funds.show')
        ->middleware('permission:petty-cash.view');
    Route::post('petty-cash/funds', [PettyCashController::class, 'fundsStore'])
        ->name('petty-cash.funds.store')
        ->middleware('permission:petty-cash.manage-funds');
    Route::get('petty-cash/funds/{pettyCashFund}/edit', [PettyCashController::class, 'fundsEdit'])
        ->name('petty-cash.funds.edit')
        ->middleware('permission:petty-cash.manage-funds');
    Route::put('petty-cash/funds/{pettyCashFund}', [PettyCashController::class, 'fundsUpdate'])
        ->name('petty-cash.funds.update')
        ->middleware('permission:petty-cash.manage-funds');
    Route::get('petty-cash/categories', [PettyCashController::class, 'categoriesIndex'])
        ->name('petty-cash.categories.index')
        ->middleware('permission:petty-cash.manage-funds');
    Route::post('petty-cash/categories', [PettyCashController::class, 'categoryStore'])
        ->name('petty-cash.categories.store')
        ->middleware('permission:petty-cash.manage-funds');
    Route::get('petty-cash/categories/{pettyCashCategory}', [PettyCashController::class, 'categoryShow'])
        ->name('petty-cash.categories.show')
        ->middleware('permission:petty-cash.manage-funds');
    Route::get('petty-cash/categories/{pettyCashCategory}/edit', [PettyCashController::class, 'categoryEdit'])
        ->name('petty-cash.categories.edit')
        ->middleware('permission:petty-cash.manage-funds');
    Route::put('petty-cash/categories/{pettyCashCategory}', [PettyCashController::class, 'categoryUpdate'])
        ->name('petty-cash.categories.update')
        ->middleware('permission:petty-cash.manage-funds');

    // Bills (Accounts Payable)
    Route::get('bills', [BillController::class, 'index'])
        ->name('bills.index')
        ->middleware('permission:bills.view');
    Route::get('bills/export', [BillController::class, 'export'])
        ->name('bills.export')
        ->middleware('permission:bills.export');
    // Recurring bills (must be before bills/{bill})
    Route::get('bills/recurring', [RecurringBillController::class, 'index'])
        ->name('bills.recurring.index')
        ->middleware('permission:bills.view');
    Route::get('bills/recurring/create', [RecurringBillController::class, 'create'])
        ->name('bills.recurring.create')
        ->middleware('permission:bills.create');
    Route::post('bills/recurring', [RecurringBillController::class, 'store'])
        ->name('bills.recurring.store')
        ->middleware('permission:bills.create');
    Route::get('bills/recurring/{recurring_bill}/edit', [RecurringBillController::class, 'edit'])
        ->name('bills.recurring.edit')
        ->middleware('permission:bills.create');
    Route::put('bills/recurring/{recurring_bill}', [RecurringBillController::class, 'update'])
        ->name('bills.recurring.update')
        ->middleware('permission:bills.create');
    Route::post('bills/recurring/{recurring_bill}/create-next', [RecurringBillController::class, 'createNextBill'])
        ->name('bills.recurring.create-next')
        ->middleware('permission:bills.create');
    Route::get('bills/create', [BillController::class, 'create'])
        ->name('bills.create')
        ->middleware('permission:bills.create');
    Route::post('bills', [BillController::class, 'store'])
        ->name('bills.store')
        ->middleware('permission:bills.create');
    // Vendors (must be before bills/{bill} so "vendors" is not matched as bill id)
    Route::get('bills/vendors', [VendorController::class, 'index'])
        ->name('bills.vendors.index')
        ->middleware('permission:bills.manage-vendors|bills.view');
    Route::get('bills/vendors/create', [VendorController::class, 'create'])
        ->name('bills.vendors.create')
        ->middleware('permission:bills.manage-vendors');
    Route::post('bills/vendors', [VendorController::class, 'store'])
        ->name('bills.vendors.store')
        ->middleware('permission:bills.manage-vendors');
    Route::get('bills/vendors/{vendor}/edit', [VendorController::class, 'edit'])
        ->name('bills.vendors.edit')
        ->middleware('permission:bills.manage-vendors');
    Route::put('bills/vendors/{vendor}', [VendorController::class, 'update'])
        ->name('bills.vendors.update')
        ->middleware('permission:bills.manage-vendors');
    // Bill categories (must be before bills/{bill})
    Route::get('bills/categories', [BillCategoryController::class, 'index'])
        ->name('bills.categories.index')
        ->middleware('permission:bills.manage-vendors|bills.view');
    Route::get('bills/categories/create', [BillCategoryController::class, 'create'])
        ->name('bills.categories.create')
        ->middleware('permission:bills.manage-vendors');
    Route::post('bills/categories', [BillCategoryController::class, 'store'])
        ->name('bills.categories.store')
        ->middleware('permission:bills.manage-vendors');
    Route::get('bills/categories/{category}/edit', [BillCategoryController::class, 'edit'])
        ->name('bills.categories.edit')
        ->middleware('permission:bills.manage-vendors');
    Route::put('bills/categories/{category}', [BillCategoryController::class, 'update'])
        ->name('bills.categories.update')
        ->middleware('permission:bills.manage-vendors');
    Route::get('bills/{bill}', [BillController::class, 'show'])
        ->name('bills.show')
        ->middleware('permission:bills.view');
    Route::get('bills/{bill}/edit', [BillController::class, 'edit'])
        ->name('bills.edit')
        ->middleware('permission:bills.create');
    Route::put('bills/{bill}', [BillController::class, 'update'])
        ->name('bills.update')
        ->middleware('permission:bills.create');
    Route::post('bills/{bill}/approve', [BillController::class, 'approve'])
        ->name('bills.approve')
        ->middleware('permission:bills.approve');
    Route::post('bills/{bill}/reject', [BillController::class, 'reject'])
        ->name('bills.reject')
        ->middleware('permission:bills.approve');
    Route::post('bills/{bill}/pay', [BillController::class, 'markPaid'])
        ->name('bills.pay')
        ->middleware('permission:bills.pay');
    Route::get('bills/attachments/{attachment}/download', [BillController::class, 'downloadAttachment'])
        ->name('bills.attachments.download')
        ->middleware('permission:bills.view');

    // Devices
    Route::get('devices', [DeviceController::class, 'index'])
        ->name('devices.index')
        ->middleware('permission:devices.view');
    Route::get('devices/overstayed', [DeviceController::class, 'overstayed'])
        ->name('devices.overstayed')
        ->middleware('permission:devices.view');
    Route::get('devices/overstayed/export', [DeviceController::class, 'exportOverstayed'])
        ->name('devices.overstayed.export')
        ->middleware('permission:devices.view');
    Route::get('devices/export', [DeviceController::class, 'export'])
        ->name('devices.export')
        ->middleware('permission:devices.view');
    Route::post('devices/reconcile-imei', [DeviceController::class, 'reconcileImei'])
        ->name('devices.reconcile-imei')
        ->middleware('permission:devices.view');
    Route::get('devices/reconcile-imei/sample', [DeviceController::class, 'downloadReconcileImeiSample'])
        ->name('devices.reconcile-imei.sample')
        ->middleware('permission:devices.view');
    Route::get('devices/import', [DeviceController::class, 'importForm'])
        ->name('devices.import')
        ->middleware('permission:devices.create');
    Route::post('devices/import', [DeviceController::class, 'importSubmit'])
        ->name('devices.import.submit')
        ->middleware('permission:devices.create');
    Route::get('devices/import/sample', [DeviceController::class, 'downloadSampleCsv'])
        ->name('devices.import.sample')
        ->middleware('permission:devices.create');
    Route::get('devices/import/sample-full', [DeviceController::class, 'downloadFullSampleCsv'])
        ->name('devices.import.sample-full')
        ->middleware('permission:devices.create');
    Route::get('devices/create', [DeviceController::class, 'create'])
        ->name('devices.create')
        ->middleware('permission:devices.create');
    Route::post('devices', [DeviceController::class, 'store'])
        ->name('devices.store')
        ->middleware('permission:devices.create');
    Route::get('devices/{device}', [DeviceController::class, 'show'])
        ->name('devices.show')
        ->middleware('permission:devices.view');
    Route::get('devices/{device}/edit', [DeviceController::class, 'edit'])
        ->name('devices.edit')
        ->middleware('permission:devices.update');
    Route::put('devices/{device}', [DeviceController::class, 'update'])
        ->name('devices.update')
        ->middleware('permission:devices.update');
    Route::post('devices/{device}/status', [DeviceController::class, 'updateStatus'])
        ->name('devices.status.update')
        ->middleware('permission:devices.update');
    Route::get('devices/{device}/mark-sold', [DeviceController::class, 'markSoldForm'])
        ->name('devices.mark-sold.form')
        ->middleware('permission:devices.update');
    Route::post('devices/{device}/mark-sold', [DeviceController::class, 'markSoldSubmit'])
        ->name('devices.mark-sold.submit')
        ->middleware('permission:devices.update');
    Route::delete('devices/{device}', [DeviceController::class, 'destroy'])
        ->name('devices.delete')
        ->middleware('permission:devices.delete');

    // Users
    Route::get('users', [UserController::class, 'index'])
        ->name('users.index')
        ->middleware('permission:users.view');
    Route::get('users/import', [UserController::class, 'importForm'])
        ->name('users.import')
        ->middleware('permission:users.create');
    Route::post('users/import', [UserController::class, 'importSubmit'])
        ->name('users.import.submit')
        ->middleware('permission:users.create');
    Route::get('users/import/sample', [UserController::class, 'downloadSampleCsv'])
        ->name('users.import.sample')
        ->middleware('permission:users.create');
    Route::get('users/create', [UserController::class, 'create'])
        ->name('users.create')
        ->middleware('permission:users.create');
    Route::post('users', [UserController::class, 'store'])
        ->name('users.store')
        ->middleware('permission:users.create');
    Route::get('users/{user}', [UserController::class, 'show'])
        ->name('users.show')
        ->middleware('permission:users.view');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])
        ->name('users.edit')
        ->middleware('permission:users.update');
    Route::put('users/{user}', [UserController::class, 'update'])
        ->name('users.update')
        ->middleware('permission:users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->name('users.destroy')
        ->middleware('permission:users.delete');
    Route::post('users/{user}/suspend', [UserController::class, 'suspend'])
        ->name('users.suspend')
        ->middleware('permission:users.update');
    Route::post('users/{user}/unsuspend', [UserController::class, 'unsuspend'])
        ->name('users.unsuspend')
        ->middleware('permission:users.update');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->name('users.reset-password')
        ->middleware('permission:users.update');
    Route::post('users/{user}/set-password', [UserController::class, 'setPassword'])
        ->name('users.set-password')
        ->middleware('permission:users.update');
    Route::post('users/{user}/change-branch', [UserController::class, 'updateBranch'])
        ->name('users.change-branch')
        ->middleware('permission:users.update');
    Route::post('users/{user}/make-field-agent', [UserController::class, 'makeFieldAgent'])
        ->name('users.make-field-agent')
        ->middleware('permission:users.manage-field-agents');
    Route::post('users/{user}/revoke-field-agent', [UserController::class, 'revokeFieldAgent'])
        ->name('users.revoke-field-agent')
        ->middleware('permission:users.manage-field-agents');

    // Field Agents
    Route::get('field-agents', [FieldAgentController::class, 'index'])
        ->name('field-agents.index')
        ->middleware('permission:field-agents.view');
    Route::get('field-agents/import', [FieldAgentController::class, 'importForm'])
        ->name('field-agents.import')
        ->middleware('permission:field-agents.create');
    Route::post('field-agents/import', [FieldAgentController::class, 'importSubmit'])
        ->name('field-agents.import.submit')
        ->middleware('permission:field-agents.create');
    Route::get('field-agents/import/sample', [FieldAgentController::class, 'downloadSampleCsv'])
        ->name('field-agents.import.sample')
        ->middleware('permission:field-agents.create');
    Route::get('field-agents/create', [FieldAgentController::class, 'create'])
        ->name('field-agents.create')
        ->middleware('permission:field-agents.create');
    Route::post('field-agents', [FieldAgentController::class, 'store'])
        ->name('field-agents.store')
        ->middleware('permission:field-agents.create');
    Route::get('field-agents/{fieldAgent}', [FieldAgentController::class, 'show'])
        ->name('field-agents.show')
        ->middleware('permission:field-agents.view');
    Route::get('field-agents/{fieldAgent}/edit', [FieldAgentController::class, 'edit'])
        ->name('field-agents.edit')
        ->middleware('permission:field-agents.update');
    Route::put('field-agents/{fieldAgent}', [FieldAgentController::class, 'update'])
        ->name('field-agents.update')
        ->middleware('permission:field-agents.update');
    Route::delete('field-agents/{fieldAgent}', [FieldAgentController::class, 'destroy'])
        ->name('field-agents.delete')
        ->middleware('permission:field-agents.delete');

    // Commissions - list of commission per sale (no withdrawal feature)
    Route::get('commission-disbursements', [CommissionDisbursementController::class, 'index'])
        ->name('commission-disbursements.index')
        ->middleware('permission:commission-disbursements.view');

    // Commissions - Admin (list users + total commission; details per user)
    Route::get('admin/commission-disbursements', [CommissionDisbursementController::class, 'adminIndex'])
        ->name('commission-disbursements.admin.index')
        ->middleware('permission:commission-disbursements.view');
    Route::get('admin/commission-disbursements/user/{user}', [CommissionDisbursementController::class, 'adminUserShow'])
        ->name('commission-disbursements.admin.user.show')
        ->middleware('permission:commission-disbursements.view');

    // Activity Logs
    Route::get('activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index')
        ->middleware('permission:activity-logs.view');

    // Roles
    Route::get('roles', [RoleController::class, 'index'])
        ->name('roles.index')
        ->middleware('permission:roles.view');
    Route::get('roles/create', [RoleController::class, 'create'])
        ->name('roles.create')
        ->middleware('permission:roles.create');
    Route::post('roles', [RoleController::class, 'store'])
        ->name('roles.store')
        ->middleware('permission:roles.create');
    Route::get('roles/{role}', [RoleController::class, 'show'])
        ->name('roles.show')
        ->middleware('permission:roles.view');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])
        ->name('roles.edit')
        ->middleware('permission:roles.update');
    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->name('roles.update')
        ->middleware('permission:roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->name('roles.destroy')
        ->middleware('permission:roles.delete');
});
