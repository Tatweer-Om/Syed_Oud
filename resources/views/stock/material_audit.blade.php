@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.material_audit', [], session('locale')) ?: 'Material Audit' }}</title>
@endpush

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto">
    <div class="max-w-[95%] mx-auto">
        <!-- Page title -->
        <div class="mb-6">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">
                {{ trans('messages.material_audit', [], session('locale')) ?: 'Material Audit' }}
            </h2>
        </div>

        <!-- Search bar -->
        <div class="w-full mb-6">
            <div class="relative flex items-center bg-white/90 backdrop-blur-md rounded-2xl shadow-md border border-[var(--accent-color)] max-w-md px-3 py-2 transition-all duration-300 hover:shadow-lg hover:bg-white">
                <input
                    id="search_material_audit"
                    type="text"
                    placeholder="{{ trans('messages.search_stock', [], session('locale')) ?: 'Search by stock code, name, or barcode' }}"
                    class="flex-1 bg-transparent border-none focus:ring-0 focus:outline-none text-[var(--text-primary)] placeholder-gray-400 text-sm px-3" />
                <button
                    id="search_btn"
                    class="flex items-center justify-center rounded-xl bg-[var(--primary-color)] text-white w-10 h-10 hover:bg-[var(--primary-darker)] transition-all duration-200 shadow-sm"
                    title="{{ trans('messages.search', [], session('locale')) }}">
                    <span class="material-symbols-outlined text-[22px]">search</span>
                </button>
            </div>
        </div>

        <!-- Audit table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)]">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 border-b border-[var(--border-color)]">
                        <tr>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.barcode', [], session('locale')) }} / {{ trans('messages.code', [], session('locale')) }} / {{ trans('messages.design_name', [], session('locale')) }}</th>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.tailor_name', [], session('locale')) }}</th>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.source', [], session('locale')) ?: 'Source' }}</th>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.quantity_added', [], session('locale')) }} / {{ trans('messages.added_by', [], session('locale')) ?: 'Added By' }} / {{ trans('messages.date', [], session('locale')) }}</th>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.total_material_required', [], session('locale')) ?: 'Total Material Required' }}</th>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.material_available_in_stock', [], session('locale')) ?: 'Material Available in Stock' }}</th>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.materials_with_tailor', [], session('locale')) ?: 'Materials with Tailor' }}</th>
                            <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.material_status', [], session('locale')) ?: 'Material Status' }}</th>
                        </tr>
                    </thead>
                    <tbody id="materialAuditTableBody">
                        <tr>
                            <td colspan="8" class="px-4 sm:px-6 py-8 text-center text-gray-500">
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
@include('custom_js.material_audit_js')
@endsection
