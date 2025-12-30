<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\PaymentGatewayController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PriceAnalyticsController;
use App\Http\Controllers\Api\DebtorController;
use App\Http\Controllers\Api\AppSettingController;
use App\Http\Controllers\Api\ProductUnitController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::get('/app-settings', [AppSettingController::class, 'index']); // Public - for login page branding

// Protected routes
Route::middleware(['auth:sanctum', 'log.api', 'validate.json'])->group(function () {

    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Users
    Route::apiResource('users', UserController::class);
    Route::get('/roles', [UserController::class, 'roles']);

    // Branches
    Route::apiResource('branches', BranchController::class);

    // Stores
    Route::apiResource('stores', StoreController::class);

    // Product Categories
    Route::apiResource('categories', ProductCategoryController::class);

    // Units
    Route::apiResource('units', UnitController::class);

    // Products
    Route::apiResource('products', ProductController::class);
    Route::get('/products/barcode/search', [ProductController::class, 'barcodeSearch']);

    // Product Units (UOM Conversion)
    Route::prefix('products/{product}/units')->group(function () {
        Route::get('/', [ProductUnitController::class, 'index']);
        Route::post('/', [ProductUnitController::class, 'store']);
        Route::put('/{productUnit}', [ProductUnitController::class, 'update']);
        Route::delete('/{productUnit}', [ProductUnitController::class, 'destroy']);
        Route::post('/convert', [ProductUnitController::class, 'convert']);
        Route::post('/price', [ProductUnitController::class, 'getUnitPrice']);
    });

    // Price Analytics
    Route::prefix('price-analytics')->group(function () {
        Route::get('/products/{product}/history', [PriceAnalyticsController::class, 'productPriceHistory']);
        Route::get('/products/{product}/trends', [PriceAnalyticsController::class, 'profitMarginTrends']);
        Route::get('/overall', [PriceAnalyticsController::class, 'overallAnalytics']);
        Route::get('/low-margin', [PriceAnalyticsController::class, 'lowMarginProducts']);
    });

    // Inventory (with store access check)
    Route::prefix('inventory')->middleware('store.access')->group(function () {
        Route::get('/stock-levels', [InventoryController::class, 'stockLevels']);
        Route::get('/stock-ledger', [InventoryController::class, 'stockLedger']);
        Route::get('/low-stock-alert', [InventoryController::class, 'lowStockAlert']);
        Route::get('/stock-balance', [InventoryController::class, 'stockBalance']);
        Route::post('/stock-transfers', [InventoryController::class, 'createTransfer']);
    });

    // Sales (with store access check)
    Route::middleware('store.access')->group(function () {
        Route::apiResource('sales', SaleController::class)->only(['index', 'store', 'show']);
        Route::post('/sales/{sale}/void', [SaleController::class, 'void']);
    });

    // Orders
    Route::apiResource('orders', OrderController::class);
    Route::post('/orders/{order}/confirm', [OrderController::class, 'confirm']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

    // Customers
    Route::apiResource('customers', CustomerController::class);
    Route::get('/customers/{customer}/pricing', [CustomerController::class, 'pricing']);
    Route::post('/customers/{customer}/pricing', [CustomerController::class, 'setPricing']);

    // Suppliers
    Route::apiResource('suppliers', SupplierController::class);

    // Purchase Orders (with store access check)
    Route::middleware('store.access')->group(function () {
        Route::apiResource('purchase-orders', PurchaseOrderController::class)->only(['index', 'store', 'show', 'update']);
        Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve']);
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);
    });

    // Wallet
    Route::prefix('wallet')->group(function () {
        Route::get('/transactions', [WalletController::class, 'transactions']);
        Route::get('/customers/{customer}/balance', [WalletController::class, 'balance']);
        Route::post('/fund', [WalletController::class, 'fund']);
        Route::post('/debit', [WalletController::class, 'debit']);
        Route::post('/transactions/{transaction}/approve', [WalletController::class, 'approve']);
        Route::post('/transactions/{transaction}/reverse', [WalletController::class, 'reverse']);
    });

    // Reports & Dashboard
    Route::prefix('reports')->group(function () {
        Route::get('/sales', [ReportController::class, 'salesReport']);
        Route::get('/inventory', [ReportController::class, 'inventoryReport']);
        Route::get('/profitability', [ReportController::class, 'profitabilityReport']);
        Route::get('/dashboard', [ReportController::class, 'dashboardStats']);

        // PDF Exports
        Route::get('/sales/pdf', [ReportController::class, 'exportSalesPDF']);
        Route::get('/inventory/pdf', [ReportController::class, 'exportInventoryPDF']);
    });

    // Payment Gateways (Super Admin only)
    Route::prefix('payment-gateways')->group(function () {
        Route::get('/', [PaymentGatewayController::class, 'index']);
        Route::get('/active', [PaymentGatewayController::class, 'active']);
        Route::get('/{gateway}', [PaymentGatewayController::class, 'show']);
        Route::post('/', [PaymentGatewayController::class, 'store']);
        Route::put('/{gateway}', [PaymentGatewayController::class, 'update']);
        Route::delete('/{gateway}', [PaymentGatewayController::class, 'destroy']);
        Route::post('/{gateway}/toggle', [PaymentGatewayController::class, 'toggle']);
        Route::post('/{gateway}/set-default', [PaymentGatewayController::class, 'setDefault']);
        Route::post('/{gateway}/test', [PaymentGatewayController::class, 'test']);
    });

    // Payments
    Route::prefix('payments')->group(function () {
        Route::post('/initialize', [PaymentController::class, 'initialize']);
        Route::post('/verify', [PaymentController::class, 'verify']);
    });

    // Debtors Management
    Route::prefix('debtors')->group(function () {
        Route::get('/', [DebtorController::class, 'index']);
        Route::get('/summary', [DebtorController::class, 'summary']);
        Route::get('/aging-report', [DebtorController::class, 'agingReport']);
        Route::get('/{customer}', [DebtorController::class, 'show']);
        Route::post('/sales/{sale}/payment', [DebtorController::class, 'recordPayment']);
    });

    // App Settings (Admin only)
    Route::prefix('app-settings')->group(function () {
        Route::delete('/images/{type}', [AppSettingController::class, 'deleteImage']);
        Route::post('/reset', [AppSettingController::class, 'reset']);
    });
});

// App Settings Update - Separate route without validate.json middleware (needs multipart/form-data)
Route::middleware(['auth:sanctum', 'log.api'])->group(function () {
    Route::post('/app-settings', [AppSettingController::class, 'update']);
});

// Webhook routes (no authentication required)
Route::prefix('webhooks')->group(function () {
    Route::post('/paystack', [PaymentController::class, 'paystackWebhook']);
    Route::post('/monnify', [PaymentController::class, 'monnifyWebhook']);
    Route::post('/palmpay', [PaymentController::class, 'palmpayWebhook']);
});

