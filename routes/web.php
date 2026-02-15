<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SMSController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\PackagingController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login_page');
});

// Language/Locale change route
Route::post('/change-locale', function (Request $request) {
    $locale = $request->input('locale', 'en');
    if (in_array($locale, ['ar', 'en'])) {
        session(['locale' => $locale]);
        return response()->json(['success' => true, 'locale' => $locale]);
    }
    return response()->json(['success' => false, 'message' => 'Invalid locale'], 400);
})->name('change_locale');

Route::get('dashboard', [HomeController::class, 'index'])->name('dashboard');
Route::get('dashboard/monthly-data', [HomeController::class, 'getMonthlyData'])->name('dashboard.monthly_data');
Route::get('dashboard/low-stock-items', [HomeController::class, 'getLowStockItems'])->name('dashboard.low_stock_items');
Route::get('dashboard/notifications', [HomeController::class, 'getNotifications'])->name('dashboard.notifications');

// Settings Routes
Route::get('settings', [SettingsController::class, 'index'])->name('settings');
Route::get('settings/get', [SettingsController::class, 'getSettings'])->name('settings.get');
Route::post('settings/update', [SettingsController::class, 'update'])->name('settings.update');

// User / Auth Routes
Route::get('user', [UserController::class, 'index'])->name('user');
Route::post('users', [UserController::class, 'store']);
Route::put('users/{user}', [UserController::class, 'update']);
Route::delete('users/{user}', [UserController::class, 'destroy']);
Route::get('users/list', [UserController::class, 'getusers']);
Route::get('users/{user}', [UserController::class, 'show']);
Route::get('login_page', [UserController::class, 'login_page'])->name('login_page');
Route::post('/login-user', [UserController::class, 'login_user'])->name('login_user');
Route::post('/logout', [UserController::class, 'logout'])->name('logout');

// Category, Color, Size (required by Stock)
Route::get('categories', [CategoryController::class, 'index'])->name('category');
Route::post('categories', [CategoryController::class, 'store']);
Route::put('categories/{category}', [CategoryController::class, 'update']);
Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
Route::get('categories/list', [CategoryController::class, 'getCategories']);
Route::get('categories/{category}', [CategoryController::class, 'show']);

Route::get('color', [ColorController::class, 'index'])->name('color');
Route::post('add_color', [ColorController::class, 'add_color'])->name('add_color');
Route::get('show_color', [ColorController::class, 'show_color'])->name('show_color');
Route::post('edit_color', [ColorController::class, 'edit_color'])->name('edit_color');
Route::post('update_color', [ColorController::class, 'update_color'])->name('update_color');
Route::post('delete_color', [ColorController::class, 'delete_color'])->name('delete_color');
Route::get('colors', [ColorController::class, 'index']);
Route::post('colors', [ColorController::class, 'store']);
Route::put('colors/{color}', [ColorController::class, 'update']);
Route::delete('colors/{color}', [ColorController::class, 'destroy']);
Route::get('colors/list', [ColorController::class, 'getcolors']);
Route::get('colors/{color}', [ColorController::class, 'show']);

Route::get('size', [SizeController::class, 'index'])->name('size');
Route::post('sizes', [SizeController::class, 'store']);
Route::put('sizes/{size}', [SizeController::class, 'update']);
Route::delete('sizes/{size}', [SizeController::class, 'destroy']);
Route::get('sizes/list', [SizeController::class, 'getSizes']);
Route::get('sizes/{size}', [SizeController::class, 'show']);

// Units
Route::get('view_units', [UnitController::class, 'index'])->name('view_units');
Route::get('units/list', [UnitController::class, 'getUnits']);
Route::get('units/all', [UnitController::class, 'getAllUnits']);
Route::post('units', [UnitController::class, 'store']);
Route::put('units/{unit}', [UnitController::class, 'update']);
Route::delete('units/{unit}', [UnitController::class, 'destroy']);

// Expense Category
Route::get('expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense_category');
Route::post('expense-categories', [ExpenseCategoryController::class, 'store']);
Route::put('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update']);
Route::delete('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy']);
Route::get('expense-categories/list', [ExpenseCategoryController::class, 'getExpenseCategories']);
Route::get('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'show']);

// Supplier
Route::get('suppliers', [SupplierController::class, 'index'])->name('supplier');
Route::get('suppliers/list', [SupplierController::class, 'getSuppliers']);
Route::get('suppliers/all', [SupplierController::class, 'getAllSuppliers']);
Route::get('suppliers/count', [SupplierController::class, 'getTotalCount']);
Route::post('suppliers', [SupplierController::class, 'store']);
Route::put('suppliers/{supplier}', [SupplierController::class, 'update']);
Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy']);
Route::get('suppliers/{supplier}', [SupplierController::class, 'show']);

// Expense
Route::get('expenses', [ExpenseController::class, 'index'])->name('expense');
Route::post('expenses', [ExpenseController::class, 'store']);
Route::put('expenses/{expense}', [ExpenseController::class, 'update']);
Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy']);
Route::get('expenses/list', [ExpenseController::class, 'getExpenses']);
Route::get('expenses/{expense}', [ExpenseController::class, 'show']);

// SMS
Route::get('sms', [SMSController::class, 'index'])->name('sms');
Route::post('sms', [SMSController::class, 'store']);
Route::get('sms/get', [SMSController::class, 'getSMS']);

// Account
Route::get('accounts', [AccountController::class, 'index'])->name('account');
Route::post('accounts', [AccountController::class, 'store']);
Route::put('accounts/{account}', [AccountController::class, 'update']);
Route::delete('accounts/{account}', [AccountController::class, 'destroy']);
Route::get('accounts/list', [AccountController::class, 'getAccounts']);
Route::get('accounts/all', [AccountController::class, 'all']);
Route::get('accounts/{account}', [AccountController::class, 'show']);

// Material
Route::get('material', [MaterialController::class, 'index'])->name('material');
Route::get('add_material', function () { return redirect()->route('view_material'); })->name('add_material.view');
Route::post('add_material', [MaterialController::class, 'add_material'])->name('add_material');
Route::get('material/list', [MaterialController::class, 'getmaterial']);
Route::get('materials/all', [MaterialController::class, 'getAllMaterials'])->name('materials.all');
Route::get('materials/for-purchase', [MaterialController::class, 'getMaterialsForPurchase']);
Route::get('materials/{id}', [MaterialController::class, 'getMaterial33'])->where('id', '[0-9]+')->name('materials.get');
Route::get('edit_material/{id}', [MaterialController::class, 'edit_material'])->name('edit_material');
Route::post('update_material', [MaterialController::class, 'update_material'])->name('update_material');
Route::delete('/delete_material/{id}', [MaterialController::class, 'delete_material'])->name('delete_material');
Route::get('view_material', [MaterialController::class, 'view_material'])->name('view_material');
Route::post('materials/add-quantity', [MaterialController::class, 'addQuantity'])->name('materials.add_quantity');
Route::get('material-quantity-audit', [MaterialController::class, 'materialQuantityAudit'])->name('material.quantity_audit');
Route::get('material-quantity-audit/data', [MaterialController::class, 'getMaterialQuantityAuditData'])->name('material.quantity_audit.data');

// Stock
Route::get('stock', [StockController::class, 'index'])->name('stock');
Route::get('purchase', [PurchaseController::class, 'index'])->name('purchase');
Route::get('view_purchase', [PurchaseController::class, 'view_purchase'])->name('view_purchase');
Route::post('purchase/draft', [PurchaseController::class, 'storeDraft'])->name('purchase.draft.store');
Route::get('purchase/drafts', [PurchaseController::class, 'getPurchaseDrafts']);
Route::get('purchase/draft/{id}', [PurchaseController::class, 'getDraft'])->where('id', '[0-9]+');
Route::get('purchase/{id}/profile', [PurchaseController::class, 'purchaseProfile'])->name('purchase.profile')->where('id', '[0-9]+');
Route::post('purchase/{id}/payment', [PurchaseController::class, 'storePurchasePayment'])->name('purchase.payment.store')->where('id', '[0-9]+');
Route::get('purchase/{id}', [PurchaseController::class, 'getPurchase'])->where('id', '[0-9]+'); // completed purchase (view materials)
Route::put('purchase/draft/{id}', [PurchaseController::class, 'updateDraft'])->where('id', '[0-9]+');
Route::post('purchase/draft/{id}/complete', [PurchaseController::class, 'completeDraft'])->name('purchase.draft.complete')->where('id', '[0-9]+');
Route::delete('purchase/draft/{id}', [PurchaseController::class, 'deleteDraft'])->where('id', '[0-9]+');
Route::get('purchase/draft/{id}/edit', [PurchaseController::class, 'editDraft'])->name('purchase.draft.edit')->where('id', '[0-9]+');
Route::post('add_stock', [StockController::class, 'add_stock'])->name('add_stock');
Route::get('view_stock', [StockController::class, 'view_stock'])->name('view_stock');
Route::post('update_stock', [StockController::class, 'update_stock'])->name('update_stock');
Route::delete('/delete_stock/{id}', [StockController::class, 'delete_stock'])->name('delete_stock');
Route::get('stock/audit', [StockController::class, 'stockAudit'])->name('stock.audit');
Route::get('stock/audit/list', [StockController::class, 'getStockAuditList'])->name('stock.audit.list');
Route::get('stock/audit/details', [StockController::class, 'getStockAuditDetails'])->name('stock.audit.details');
Route::get('stock/comprehensive-audit', [StockController::class, 'comprehensiveAudit'])->name('stock.comprehensive_audit');
Route::get('stock/comprehensive-audit/list', [StockController::class, 'getComprehensiveAudit'])->name('stock.comprehensive_audit.list');
Route::get('stock/material-audit', [StockController::class, 'materialAudit'])->name('material.audit');
Route::get('stock/material-audit/data', [StockController::class, 'getMaterialAuditData'])->name('material.audit.data');
Route::get('sync-pending-stocks', [StockController::class, 'syncPendingStocks'])->name('sync.pending_stocks');
Route::get('stock/list', [StockController::class, 'getstock']);
Route::get('stock/{id}', [StockController::class, 'show'])->name('stock.show');
Route::get('edit_stock/{id}', [StockController::class, 'edit_stock'])->name('edit_stock');
Route::delete('/stock/image/{id}', [StockController::class, 'deleteImage'])->name('stock.image.delete');
Route::get('/fetch_stock/{id}', [StockController::class, 'fetch_stock']);
Route::delete('stock/{id}', [StockController::class, 'destroy'])->name('stock.destroy');
Route::get('stock_detail', [StockController::class, 'stock_detail'])->name('stock_detail');
Route::get('get_simple_stock_detail', [StockController::class, 'get_simple_stock_detail'])->name('get_simple_stock_detail');
Route::post('stock_push_quantity', [StockController::class, 'stock_push_quantity'])->name('stock_push_quantity');
Route::post('stock_pull_quantity', [StockController::class, 'stock_pull_quantity'])->name('stock_pull_quantity');
Route::get('get_stock_quantity', [StockController::class, 'get_stock_quantity'])->name('get_stock_quantity');
Route::get('get_full_stock_details', [StockController::class, 'get_full_stock_details'])->name('get_full_stock_details');
Route::post('add_quantity', [StockController::class, 'add_quantity'])->name('add_quantity');
Route::get('stock-materials', [StockController::class, 'stockMaterials'])->name('stock_materials');
Route::get('stock-materials/data', [StockController::class, 'getstockMaterials'])->name('stock_materials.data');
Route::get('move_stock_to_system', [StockController::class, 'move_stock_to_system'])->name('move_stock_to_system');

// Production
Route::get('production', [ProductionController::class, 'index'])->name('production');
Route::get('view_production', [ProductionController::class, 'viewProduction'])->name('view_production');
Route::get('stocks/for-production', [ProductionController::class, 'getStocksForProduction']);
Route::get('materials/for-production', [ProductionController::class, 'getMaterialsForProduction']);
Route::post('production/draft', [ProductionController::class, 'storeDraft'])->name('production.draft.store');
Route::get('production/drafts', [ProductionController::class, 'getProductionDrafts']);
Route::get('production/all', [ProductionController::class, 'getAllProductions']);
Route::get('production/draft/{id}', [ProductionController::class, 'getDraft'])->where('id', '[0-9]+');
Route::put('production/draft/{id}', [ProductionController::class, 'updateDraft'])->where('id', '[0-9]+');
Route::post('production/draft/{id}/complete', [ProductionController::class, 'completeDraft'])->name('production.draft.complete')->where('id', '[0-9]+');
Route::delete('production/draft/{id}', [ProductionController::class, 'deleteDraft'])->where('id', '[0-9]+');
Route::get('production/draft/{id}/edit', [ProductionController::class, 'editDraft'])->name('production.draft.edit')->where('id', '[0-9]+');
Route::get('production/{id}/profile', [ProductionController::class, 'productionProfile'])->name('production.profile')->where('id', '[0-9]+');
Route::get('production/{id}/invoice', [ProductionController::class, 'productionInvoice'])->name('production.invoice')->where('id', '[0-9]+');
Route::get('production/{id}', [ProductionController::class, 'getProduction'])->where('id', '[0-9]+');
Route::post('production/{id}/add-material', [ProductionController::class, 'addMaterialToProduction'])->where('id', '[0-9]+');
Route::post('production/{id}/remove-material', [ProductionController::class, 'removeMaterialFromProduction'])->where('id', '[0-9]+');
Route::post('production/{id}/add-wastage', [ProductionController::class, 'addWastage'])->where('id', '[0-9]+');
Route::get('production/{id}/wastages', [ProductionController::class, 'getWastages'])->where('id', '[0-9]+');
Route::get('production/{id}/materials', [ProductionController::class, 'getProductionMaterials'])->where('id', '[0-9]+');
Route::get('production/{id}/history', [ProductionController::class, 'getProductionHistory'])->where('id', '[0-9]+');
Route::post('production/{id}/complete', [ProductionController::class, 'completeProduction'])->where('id', '[0-9]+');

// Packaging
Route::get('materials/for-packaging', [MaterialController::class, 'getMaterialsForPackaging']);
Route::get('production/{id}/packaging', [PackagingController::class, 'create'])->name('packaging.create')->where('id', '[0-9]+');
Route::post('packaging', [PackagingController::class, 'store']);
Route::get('packaging/{id}/profile', [PackagingController::class, 'profile'])->name('packaging.profile')->where('id', '[0-9]+');
Route::post('packaging/{id}/add-material', [PackagingController::class, 'addMaterial'])->where('id', '[0-9]+');
Route::post('packaging/{id}/remove-material', [PackagingController::class, 'removeMaterial'])->where('id', '[0-9]+');
Route::post('packaging/{id}/add-wastage', [PackagingController::class, 'addWastage'])->where('id', '[0-9]+');
Route::post('packaging/{id}/complete', [PackagingController::class, 'complete'])->where('id', '[0-9]+');
Route::get('packaging/{id}/materials', [PackagingController::class, 'getMaterials'])->where('id', '[0-9]+');
Route::get('packaging/{id}/history', [PackagingController::class, 'getHistory'])->where('id', '[0-9]+');
