@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.material_quantity_audit', [], session('locale')) ?: 'Material Quantity Audit' }}</title>
@endpush

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto">
    <div class="max-w-[95%] mx-auto">
        <!-- Page title -->
        <div class="mb-6">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">
                {{ trans('messages.material_quantity_audit', [], session('locale')) ?: 'Material Quantity Audit' }}
            </h2>
        </div>

        <!-- Search and Date Filters -->
        <div class="mb-6 bg-white rounded-2xl shadow-lg p-6 border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2 text-gray-700">
                        {{ trans('messages.search_material_placeholder', [], session('locale')) ?: 'Search by material name' }}
                    </label>
                    <div class="relative flex items-center bg-white/90 backdrop-blur-md rounded-2xl shadow-md border border-[var(--accent-color)] px-3 py-2">
                        <input
                            id="search_material"
                            type="text"
                            placeholder="{{ trans('messages.search_material_placeholder', [], session('locale')) ?: 'Search by material name' }}"
                            class="flex-1 bg-transparent border-none focus:ring-0 focus:outline-none text-[var(--text-primary)] placeholder-gray-400 text-sm px-3" />
                        <button
                            id="search_btn"
                            class="flex items-center justify-center rounded-xl bg-[var(--primary-color)] text-white w-10 h-10 hover:bg-[var(--primary-darker)] transition-all duration-200 shadow-sm ml-2"
                            title="{{ trans('messages.search', [], session('locale')) }}">
                            <span class="material-symbols-outlined text-[22px]">search</span>
                        </button>
                    </div>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700">
                        {{ trans('messages.date_from', [], session('locale')) ?: 'From Date' }}
                    </label>
                    <input
                        id="date_from"
                        type="date"
                        class="w-full h-10 rounded-lg border border-gray-300 px-4 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700">
                        {{ trans('messages.date_to', [], session('locale')) ?: 'To Date' }}
                    </label>
                    <input
                        id="date_to"
                        type="date"
                        class="w-full h-10 rounded-lg border border-gray-300 px-4 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
                </div>
            </div>

            <!-- Remaining Quantity Display -->
            <div id="remaining_quantity_display" class="hidden mt-4 flex items-center gap-2 px-4 py-2 bg-green-50 border-2 border-green-200 rounded-xl">
                <span class="material-symbols-outlined text-green-600">inventory_2</span>
                <span class="text-sm font-semibold text-gray-700">
                    {{ trans('messages.remaining_quantity', [], session('locale')) ?: 'Remaining Quantity' }}:
                </span>
                <span id="remaining_quantity_value" class="text-lg font-bold text-green-600"></span>
            </div>
        </div>

        <!-- Audit table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)]">
            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-xs font-semibold text-[var(--text-secondary)] border border-gray-300">{{ trans('messages.date', [], session('locale')) }}</th>
                            <th class="px-2 py-2 text-xs font-semibold text-[var(--text-secondary)] border border-gray-300">{{ trans('messages.material_name', [], session('locale')) }}</th>
                            <th class="px-2 py-2 text-xs font-semibold text-[var(--text-secondary)] border border-gray-300">{{ trans('messages.source', [], session('locale')) ?: 'Source' }}</th>
                            <th class="px-2 py-2 text-xs font-semibold text-[var(--text-secondary)] border border-gray-300">{{ trans('messages.operation_type', [], session('locale')) ?: 'Operation Type' }}</th>
                            <th class="px-2 py-2 text-xs font-semibold text-[var(--text-secondary)] border border-gray-300">{{ trans('messages.previous_stock', [], session('locale')) ?: 'Previous Stock' }}</th>
                            <th class="px-2 py-2 text-xs font-semibold text-[var(--text-secondary)] border border-gray-300">{{ trans('messages.change', [], session('locale')) ?: 'Change' }}</th>
                            <th class="px-2 py-2 text-xs font-semibold text-[var(--text-secondary)] border border-gray-300">{{ trans('messages.remaining_quantity', [], session('locale')) ?: 'Remaining Quantity' }}</th>
                            <th class="px-2 py-2 text-xs font-semibold text-[var(--text-secondary)] border border-gray-300">{{ trans('messages.added_by', [], session('locale')) ?: 'Added By' }}</th>
                        </tr>
                    </thead>
                    <tbody id="auditTableBody">
                        <tr>
                            <td colspan="8" class="px-2 py-4 text-xs text-center text-gray-500 border border-gray-300">
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
@include('custom_js.material_quantity_audit_js')
@endsection
