<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StockController;
use App\Http\Controllers\RundownController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\RundownIncomingController;
use App\Http\Controllers\RundownPressController;
use App\Http\Controllers\ScheduleStampingController;
use App\Http\Controllers\MasterStampingController;
use App\Http\Controllers\PalletMutationController;
use App\Http\Controllers\SmrVendorController;
use App\Http\Controllers\SmrCustomerController;
use App\Http\Controllers\DataGrController;
use App\Http\Controllers\DataScrapController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\StorageLocationController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\GoodsIssueController;
use App\Http\Controllers\StockOverviewController;
use App\Http\Controllers\SummaryKanbanController;

Route::get('/', [StockController::class, 'index'])->name('stock.index');
Route::post('/upload', [StockController::class, 'upload'])->name('stock.upload');
Route::get('/rundown-stock', [RundownController::class, 'index'])->name('rundown.index');
Route::get('/pallet-mutation', [PalletMutationController::class, 'index'])->name('pallet_mutation.index');
Route::get('/smr-vendor', [SmrVendorController::class, 'index'])->name('smr_vendor.index');
Route::get('/smr-customer', [SmrCustomerController::class, 'index'])->name('smr_customer.index');
Route::get('/data-gr', [DataGrController::class, 'index'])->name('data_gr.index');
Route::get('/data-scrap', [DataScrapController::class, 'index'])->name('data_scrap.index');
Route::get('/data-finish-chart', [ChartController::class, 'index'])->name('chart.index');
Route::get('/rundown-incoming', [RundownIncomingController::class, 'index'])->name('rundown_incoming.index');
Route::get('/rundown-incoming/export', [RundownIncomingController::class, 'export'])->name('rundown_incoming.export');
Route::get('/rundown-incoming/template', [RundownIncomingController::class, 'downloadTemplate'])->name('rundown_incoming.template');
Route::delete('/rundown-incoming/delete', [RundownIncomingController::class, 'deleteJob'])->name('rundown_incoming.delete');
Route::post('/rundown-incoming/upload', [RundownIncomingController::class, 'upload'])->name('rundown_incoming.upload');
Route::post('/rundown-incoming/add', [RundownIncomingController::class, 'addJob'])->name('rundown_incoming.add');
Route::post('/rundown-incoming/add-incoming', [RundownIncomingController::class, 'addIncoming'])->name('rundown_incoming.add_incoming');
Route::post('/rundown-incoming/update-inline', [RundownIncomingController::class, 'updateInline'])->name('rundown_incoming.inline');

Route::get('/rundown-press', [RundownPressController::class, 'index'])->name('rundown_press.index');
Route::post('/rundown-press/upload', [RundownPressController::class, 'upload'])->name('rundown_press.upload');
Route::post('/rundown-press/update-inline', [RundownPressController::class, 'updateInline'])->name('rundown_press.inline');
Route::get('/rundown-press/sync-to-stamping', [RundownPressController::class, 'syncAllToScheduleStamping'])->name('rundown_press.sync_stamping');

Route::get('/schedule-stamping', [ScheduleStampingController::class, 'index'])->name('schedule_stamping.index');
Route::get('/schedule_stamping', [ScheduleStampingController::class, 'index']);
Route::get('/schedule-stamping/export', [ScheduleStampingController::class, 'export'])->name('schedule_stamping.export');
Route::post('/schedule-stamping/upload', [ScheduleStampingController::class, 'upload'])->name('schedule_stamping.upload');
Route::post('/schedule-stamping/add-breaks', [ScheduleStampingController::class, 'addStandardBreaks'])->name('schedule_stamping.add_breaks');
Route::post('/schedule_stamping/add-breaks', [ScheduleStampingController::class, 'addStandardBreaks']);
Route::post('/schedule-stamping/reorder', [ScheduleStampingController::class, 'reorder'])->name('schedule_stamping.reorder');
Route::post('/schedule-stamping/update-inline', [ScheduleStampingController::class, 'updateInline'])->name('schedule_stamping.inline');
Route::post('/schedule-stamping/add-job', [ScheduleStampingController::class, 'addJob'])->name('schedule_stamping.add_job');
Route::delete('/schedule-stamping/delete-job/{id}', [ScheduleStampingController::class, 'deleteJob'])->name('schedule_stamping.delete_job');
Route::post('/schedule-stamping/recalibrate-section', [ScheduleStampingController::class, 'recalibrateSection'])->name('schedule_stamping.recalibrate_section');
Route::post('/schedule-stamping/recalibrate-all', [ScheduleStampingController::class, 'recalibrateAll'])->name('schedule_stamping.recalibrate_all');
Route::post('/schedule-stamping/save-sect-head', [ScheduleStampingController::class, 'saveSectHeadPpc'])->name('schedule_stamping.save_sect_head');

Route::get('/master-stamping', [MasterStampingController::class, 'index'])->name('master_stamping.index');
Route::post('/master-stamping/import', [MasterStampingController::class, 'import'])->name('master_stamping.import');
Route::get('/master-stamping/search-ajax', [MasterStampingController::class, 'searchAjax'])->name('master_stamping.search_ajax');

// IRM Vendors routes
Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
Route::post('/vendors/update', [VendorController::class, 'update'])->name('vendors.update');
Route::delete('/vendors/{id}', [VendorController::class, 'destroy'])->name('vendors.destroy');
Route::get('/vendors/export', [VendorController::class, 'exportExcel'])->name('vendors.export');
Route::get('/vendors/template', [VendorController::class, 'downloadTemplate'])->name('vendors.template');
Route::post('/vendors/import', [VendorController::class, 'importExcel'])->name('vendors.import');
Route::get('/vendors/print-pdf', [VendorController::class, 'printPdf'])->name('vendors.print_pdf');

// IRM Materials routes
Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');
Route::get('/materials/create', [MaterialController::class, 'create'])->name('materials.create');
Route::post('/materials', [MaterialController::class, 'store'])->name('materials.store');
Route::post('/materials/update', [MaterialController::class, 'update'])->name('materials.update');
Route::get('/materials/export', [MaterialController::class, 'exportExcel'])->name('materials.export');
Route::get('/materials/template', [MaterialController::class, 'downloadTemplate'])->name('materials.template');
Route::post('/materials/import', [MaterialController::class, 'importExcel'])->name('materials.import');
Route::get('/materials/print-pdf', [MaterialController::class, 'printPdf'])->name('materials.print_pdf');
Route::get('/materials/{id}', [MaterialController::class, 'show'])->name('materials.show');
Route::delete('/materials/{id}', [MaterialController::class, 'destroy'])->name('materials.destroy');

// IRM Customers routes
Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
Route::post('/customers/update', [CustomerController::class, 'update'])->name('customers.update');
Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');
Route::get('/customers/export', [CustomerController::class, 'exportExcel'])->name('customers.export');
Route::get('/customers/template', [CustomerController::class, 'downloadTemplate'])->name('customers.template');
Route::post('/customers/import', [CustomerController::class, 'importExcel'])->name('customers.import');
Route::get('/customers/print-pdf', [CustomerController::class, 'printPdf'])->name('customers.print_pdf');

// IRM Storage Locations routes
Route::get('/storage-locations', [StorageLocationController::class, 'index'])->name('storage_locations.index');
Route::get('/storage-locations/create', [StorageLocationController::class, 'create'])->name('storage_locations.create');
Route::post('/storage-locations', [StorageLocationController::class, 'store'])->name('storage_locations.store');
Route::post('/storage-locations/update', [StorageLocationController::class, 'update'])->name('storage_locations.update');
Route::delete('/storage-locations/{id}', [StorageLocationController::class, 'destroy'])->name('storage_locations.destroy');
Route::get('/storage-locations/export', [StorageLocationController::class, 'exportExcel'])->name('storage_locations.export');
Route::get('/storage-locations/template', [StorageLocationController::class, 'downloadTemplate'])->name('storage_locations.template');
Route::post('/storage-locations/import', [StorageLocationController::class, 'importExcel'])->name('storage_locations.import');
Route::get('/storage-locations/print-pdf', [StorageLocationController::class, 'printPdf'])->name('storage_locations.print_pdf');

// IRM Purchase Orders routes
Route::get('/purchase-orders/import-template', [PurchaseOrderController::class, 'downloadTemplate'])->name('purchase_orders.import-template');
Route::post('/purchase-orders/import-excel', [PurchaseOrderController::class, 'importExcel'])->name('purchase_orders.import-excel');
Route::post('/purchase-orders/import-create', [PurchaseOrderController::class, 'importCreate'])->name('purchase_orders.import-create');
Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase_orders.index');
Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase_orders.create');
Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase_orders.store');
Route::get('/purchase-orders/{id}', [PurchaseOrderController::class, 'show'])->name('purchase_orders.show');
Route::get('/purchase-orders/{id}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase_orders.edit');
Route::post('/purchase-orders/{id}/update', [PurchaseOrderController::class, 'update'])->name('purchase_orders.update');
Route::delete('/purchase-orders/{id}', [PurchaseOrderController::class, 'destroy'])->name('purchase_orders.destroy');
Route::get('/purchase-orders/export/excel', [PurchaseOrderController::class, 'exportExcel'])->name('purchase_orders.export');
Route::get('/purchase-orders/print-pdf/print', [PurchaseOrderController::class, 'printPdf'])->name('purchase_orders.print_pdf');
Route::post('/purchase-orders/{id}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase_orders.approve');
Route::post('/purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase_orders.cancel');
Route::get('/purchase-orders/{id}/pdf', [PurchaseOrderController::class, 'printDetailPdf'])->name('purchase_orders.detail_pdf');

// IRM Goods Receipts routes
Route::get('/goods-receipts', [GoodsReceiptController::class, 'index'])->name('goods_receipts.index');
Route::post('/goods-receipts', [GoodsReceiptController::class, 'store'])->name('goods_receipts.store');
Route::get('/goods-receipts/{id}', [GoodsReceiptController::class, 'show'])->name('goods_receipts.show');
Route::post('/goods-receipts/update', [GoodsReceiptController::class, 'update'])->name('goods_receipts.update');
Route::delete('/goods-receipts/{id}', [GoodsReceiptController::class, 'destroy'])->name('goods_receipts.destroy');
Route::get('/goods-receipts/export/excel', [GoodsReceiptController::class, 'exportExcel'])->name('goods_receipts.export');
Route::get('/goods-receipts/print-pdf/print', [GoodsReceiptController::class, 'printPdf'])->name('goods_receipts.print_pdf');

// IRM Goods Issues routes
Route::get('/goods-issues', [GoodsIssueController::class, 'index'])->name('goods_issues.index');
Route::get('/goods-issues/create', [GoodsIssueController::class, 'create'])->name('goods_issues.create');
Route::post('/goods-issues', [GoodsIssueController::class, 'store'])->name('goods_issues.store');
Route::get('/goods-issues/{id}', [GoodsIssueController::class, 'show'])->name('goods_issues.show');
Route::post('/goods-issues/update', [GoodsIssueController::class, 'update'])->name('goods_issues.update');
Route::delete('/goods-issues/{id}', [GoodsIssueController::class, 'destroy'])->name('goods_issues.destroy');
Route::get('/goods-issues/export/excel', [GoodsIssueController::class, 'exportExcel'])->name('goods_issues.export');
Route::get('/goods-issues/print-pdf/print', [GoodsIssueController::class, 'printPdf'])->name('goods_issues.print_pdf');

// IRM Stock Overview routes
Route::get('/stock-overviews', [StockOverviewController::class, 'index'])->name('stock_overviews.index');
Route::get('/stock-overviews/export/excel', [StockOverviewController::class, 'exportExcel'])->name('stock_overviews.export');

// IRM Summary Kanban (SKM) routes
Route::get('/summary-kanban', [SummaryKanbanController::class, 'index'])->name('summary_kanban.index');
Route::get('/summary-kanban/create', [SummaryKanbanController::class, 'create'])->name('summary_kanban.create');
Route::post('/summary-kanban', [SummaryKanbanController::class, 'store'])->name('summary_kanban.store');
Route::get('/summary-kanban/demands/template', [SummaryKanbanController::class, 'demandTemplate'])->name('summary_kanban.demands.template');
Route::post('/summary-kanban/demands/import', [SummaryKanbanController::class, 'importDemands'])->name('summary_kanban.demands.import');
Route::delete('/summary-kanban/demands/clear', [SummaryKanbanController::class, 'clearDemands'])->name('summary_kanban.demands.clear');
Route::get('/summary-kanban/{skm}/excel', [SummaryKanbanController::class, 'exportExcel'])->name('summary_kanban.excel');
Route::get('/summary-kanban/{skm}/pdf', [SummaryKanbanController::class, 'exportPdf'])->name('summary_kanban.pdf');
Route::patch('/summary-kanban/{skm}/status', [SummaryKanbanController::class, 'updateStatus'])->name('summary_kanban.status');
Route::post('/summary-kanban/{skm}/generate-po', [SummaryKanbanController::class, 'generatePo'])->name('summary_kanban.generate-po');
Route::get('/summary-kanban/{skm}', [SummaryKanbanController::class, 'show'])->name('summary_kanban.show');
Route::delete('/summary-kanban/{skm}', [SummaryKanbanController::class, 'destroy'])->name('summary_kanban.destroy');

// IRM Business Event Logs routes
Route::get('/business-event-logs', [\App\Http\Controllers\BusinessEventLogController::class, 'index'])->name('business_logs.index');
Route::get('/business-event-logs/export', [\App\Http\Controllers\BusinessEventLogController::class, 'exportExcel'])->name('business_logs.export');

// SAP PP - Bill of Materials
Route::get('/boms/export', [\App\Http\Controllers\BomController::class, 'exportExcel'])->name('boms.export');
Route::get('/boms/template', [\App\Http\Controllers\BomController::class, 'downloadTemplate'])->name('boms.template');
Route::post('/boms/import', [\App\Http\Controllers\BomController::class, 'importExcel'])->name('boms.import');
Route::get('/boms/print-pdf', [\App\Http\Controllers\BomController::class, 'printPdf'])->name('boms.print_pdf');
Route::get('/boms', [\App\Http\Controllers\BomController::class, 'index'])->name('boms.index');
Route::get('/boms/create', [\App\Http\Controllers\BomController::class, 'create'])->name('boms.create');
Route::post('/boms', [\App\Http\Controllers\BomController::class, 'store'])->name('boms.store');
Route::get('/boms/{id}', [\App\Http\Controllers\BomController::class, 'show'])->name('boms.show');
Route::get('/boms/{id}/edit', [\App\Http\Controllers\BomController::class, 'edit'])->name('boms.edit');
Route::put('/boms/{id}', [\App\Http\Controllers\BomController::class, 'update'])->name('boms.update');
Route::delete('/boms/{id}', [\App\Http\Controllers\BomController::class, 'destroy'])->name('boms.destroy');

// SAP PP - Production Orders
Route::get('/production-orders', [\App\Http\Controllers\ProductionOrderController::class, 'index'])->name('production_orders.index');
Route::get('/production-orders/create', [\App\Http\Controllers\ProductionOrderController::class, 'create'])->name('production_orders.create');
Route::post('/production-orders', [\App\Http\Controllers\ProductionOrderController::class, 'store'])->name('production_orders.store');
Route::get('/production-orders/print-all', [\App\Http\Controllers\ProductionOrderController::class, 'printAll'])->name('production_orders.print_all');
Route::post('/production-orders/bulk-release', [\App\Http\Controllers\ProductionOrderController::class, 'bulkRelease'])->name('production_orders.bulk_release');
Route::get('/production-orders/{id}', [\App\Http\Controllers\ProductionOrderController::class, 'show'])->name('production_orders.show');
Route::get('/production-orders/{id}/edit', [\App\Http\Controllers\ProductionOrderController::class, 'edit'])->name('production_orders.edit');
Route::put('/production-orders/{id}', [\App\Http\Controllers\ProductionOrderController::class, 'update'])->name('production_orders.update');
Route::delete('/production-orders/{id}', [\App\Http\Controllers\ProductionOrderController::class, 'destroy'])->name('production_orders.destroy');
Route::get('/production-orders/{id}/print', [\App\Http\Controllers\ProductionOrderController::class, 'print'])->name('production_orders.print');
Route::post('/production-orders/{id}/release', [\App\Http\Controllers\ProductionOrderController::class, 'release'])->name('production_orders.release');
Route::post('/production-orders/{id}/issue', [\App\Http\Controllers\ProductionOrderController::class, 'goodsIssue'])->name('production_orders.issue');
Route::post('/production-orders/{id}/confirm', [\App\Http\Controllers\ProductionOrderController::class, 'confirm'])->name('production_orders.confirm');
Route::post('/production-orders/{id}/cancel', [\App\Http\Controllers\ProductionOrderController::class, 'cancel'])->name('production_orders.cancel');

// SAP PP - MRP
Route::get('/mrp/export-pdf', [\App\Http\Controllers\MrpController::class, 'exportListPdf'])->name('mrp.export-pdf');
Route::get('/mrp', [\App\Http\Controllers\MrpController::class, 'index'])->name('mrp.index');
Route::post('/mrp/run', [\App\Http\Controllers\MrpController::class, 'run'])->name('mrp.run');
Route::get('/mrp/demands/template', [\App\Http\Controllers\MrpController::class, 'downloadDemandTemplate'])->name('mrp.demands.template');
Route::post('/mrp/demands/import', [\App\Http\Controllers\MrpController::class, 'importDemands'])->name('mrp.demands.import');
Route::delete('/mrp/demands/clear', [\App\Http\Controllers\MrpController::class, 'clearDemands'])->name('mrp.demands.clear');
Route::delete('/mrp/demands/{mrpDemand}', [\App\Http\Controllers\MrpController::class, 'destroyDemand'])->name('mrp.demands.destroy');
Route::get('/mrp/{mrpRun}', [\App\Http\Controllers\MrpController::class, 'show'])->name('mrp.show');
Route::delete('/mrp/{mrpRun}', [\App\Http\Controllers\MrpController::class, 'destroy'])->name('mrp.destroy');
Route::get('/mrp/{mrpRun}/excel', [\App\Http\Controllers\MrpController::class, 'exportExcel'])->name('mrp.excel');
Route::get('/mrp/{mrpRun}/pdf', [\App\Http\Controllers\MrpController::class, 'exportPdf'])->name('mrp.pdf');