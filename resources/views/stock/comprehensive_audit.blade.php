@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.stock_audit', [], session('locale')) }} - {{ trans('messages.audit_details', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto">
    <div class="max-w-[98%] mx-auto">
        <!-- Page title -->
        <div class="mb-6">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">
                {{ trans('messages.stock_audit', [], session('locale')) }} - {{ trans('messages.audit_details', [], session('locale')) }}
            </h2>
        </div>

        <!-- Search and Filter Section -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700">
                        {{ trans('messages.search', [], session('locale')) ?: 'Search' }} ({{ trans('messages.barcode', [], session('locale')) }}, {{ trans('messages.code', [], session('locale')) }}, {{ trans('messages.stock_name', [], session('locale')) ?: 'Stock' }})
                    </label>
                    <div class="relative">
                        <input
                            id="searchInput"
                            type="text"
                            placeholder="{{ trans('messages.search_stock_audit', [], session('locale')) }}"
                            class="w-full h-10 rounded-lg border border-gray-300 px-4 pr-10 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
                        <span class="material-symbols-outlined absolute right-3 top-2.5 text-gray-400">search</span>
                    </div>
                </div>

                <!-- From Date -->
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700">
                        {{ trans('messages.from_date', [], session('locale')) ?: 'From Date' }}
                    </label>
                    <input
                        id="fromDate"
                        type="date"
                        class="w-full h-10 rounded-lg border border-gray-300 px-4 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
                </div>

                <!-- To Date -->
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700">
                        {{ trans('messages.to_date', [], session('locale')) ?: 'To Date' }}
                    </label>
                    <input
                        id="toDate"
                        type="date"
                        class="w-full h-10 rounded-lg border border-gray-300 px-4 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <button onclick="searchAudit()" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary/90 flex items-center gap-2 transition">
                    <span class="material-symbols-outlined">search</span>
                    {{ trans('messages.search', [], session('locale')) }}
                </button>
                <button onclick="clearFilters()" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 flex items-center gap-2 transition">
                    <span class="material-symbols-outlined">clear</span>
                    {{ trans('messages.clear', [], session('locale')) ?: 'Clear' }}
                </button>
            </div>
        </div>

        <!-- Remaining Quantity Display (shown when searching) -->
        <div id="remainingQtySection" class="hidden bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-blue-600 text-2xl">inventory</span>
                <div>
                    <p class="text-sm font-semibold text-blue-800">
                        {{ trans('messages.remaining_quantity', [], session('locale')) ?: 'Remaining Quantity' }}: 
                        <span id="remainingQtyValue" class="text-xl font-bold">0</span>
                    </p>
                    <div id="remainingBySize" class="mt-1 text-xs text-blue-700"></div>
                </div>
            </div>
        </div>

        <!-- Audit Logs Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 border text-right font-semibold text-xs">{{ trans('messages.date', [], session('locale')) }}</th>
                            <th class="px-4 py-3 border text-right font-semibold text-xs">{{ trans('messages.time', [], session('locale')) }}</th>
                            <th class="px-4 py-3 border text-right font-semibold text-xs">{{ trans('messages.barcode', [], session('locale')) }}</th>
                            <th class="px-4 py-3 border text-right font-semibold text-xs">{{ trans('messages.stock_name', [], session('locale')) ?: 'Stock' }}</th>
                            <th class="px-4 py-3 border text-right font-semibold text-xs">{{ trans('messages.operation_type', [], session('locale')) ?: 'Operation' }}</th>
                            <th class="px-4 py-3 border text-right font-semibold text-xs">{{ trans('messages.previous_quantity', [], session('locale')) ?: 'Previous Qty' }}</th>
                            <th class="px-4 py-3 border text-right font-semibold text-xs">{{ trans('messages.quantity_change', [], session('locale')) ?: 'Change' }}</th>
                            <th class="px-4 py-3 border text-right font-semibold text-xs">{{ trans('messages.new_quantity', [], session('locale')) ?: 'New Qty' }}</th>
                            <th class="px-4 py-3 border text-right font-semibold text-xs">{{ trans('messages.added_by', [], session('locale')) ?: 'Added By' }}</th>
                        </tr>
                    </thead>
                    <tbody id="auditTableBody">
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                {{ trans('messages.loading', [], session('locale')) }}...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-6">
            <ul id="pagination" class="flex gap-2 items-center"></ul>
        </div>
    </div>
</main>

@include('layouts.footer')
@include('custom_js.comprehensive_audit_js')
@endsection
