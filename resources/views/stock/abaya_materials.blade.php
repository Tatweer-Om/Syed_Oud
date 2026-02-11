@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.stock_materials', [], session('locale')) }}</title>
@endpush

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

<main class="flex-1 p-4 md:p-6" x-data="{ loading: false, search: '' }">
    <div class="w-full max-w-[1920px] mx-auto">
        <!-- Page title -->
        <div class="mb-6">
            <h2 class="text-gray-900 text-2xl sm:text-3xl font-bold">
                {{ trans('messages.stock_materials', [], session('locale')) }}
            </h2>
            <p class="text-gray-600 mt-2">{{ trans('messages.stock_materials_description', [], session('locale')) }}</p>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-pink-100 p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Search
                    </label>
                    <input type="text" 
                           x-model="search"
                           @input.debounce.300ms="loadData()"
                           placeholder="Search by stock code, design name, or barcode..."
                           class="w-full h-11 px-4 rounded-lg border-2 border-gray-300 focus:border-[var(--primary-color)] focus:ring-2 focus:ring-[var(--primary-color)]/20 text-sm">
                </div>
                <div class="flex items-end">
                    <button @click="loadData()" 
                            class="w-full bg-[var(--primary-color)] hover:bg-opacity-90 text-white px-4 py-3 rounded-lg font-semibold flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">refresh</span>
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-pink-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gradient-to-l from-pink-50 to-pink-100 text-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-center font-bold">{{ trans('messages.stock_code', [], session('locale')) }}</th>
                            <th class="px-4 py-3 text-center font-bold">{{ trans('messages.design_name', [], session('locale')) }}</th>
                            <th class="px-4 py-3 text-center font-bold">{{ trans('messages.category', [], session('locale')) }}</th>
                            <th class="px-4 py-3 text-center font-bold">{{ trans('messages.required_materials', [], session('locale')) }}</th>
                        </tr>
                    </thead>
                    <tbody id="stock_materials_body">
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <div class="border-4 border-pink-200 border-t-[var(--primary-color)] rounded-full w-12 h-12 animate-spin mb-3"></div>
                                    Loading...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div id="stock_materials_pagination" class="p-4 border-t border-gray-200"></div>
        </div>
    </div>

    <!-- Materials Modal -->
    <div id="materials_modal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-[100] hidden">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-7 max-h-[90vh] overflow-y-auto mx-4">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">{{ trans('messages.required_materials', [], session('locale')) }}</h2>
                <button onclick="closeMaterialsModal()" class="text-gray-500 hover:text-gray-700">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- stock Info -->
            <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                <div class="flex items-center gap-4">
                    <img id="modal_stock_image" src="" class="w-20 h-20 rounded-lg border object-cover shadow-sm" alt="stock" onerror="this.src='/images/placeholder.png'">
                    <div>
                        <div class="font-semibold text-lg text-indigo-600" id="modal_stock_code"></div>
                        <div class="font-medium text-gray-800" id="modal_design_name"></div>
                        <div class="text-sm text-gray-600" id="modal_category"></div>
                    </div>
                </div>
            </div>

            <!-- Materials List -->
            <div id="materials_list" class="space-y-3">
                <!-- Materials will be populated here -->
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button onclick="closeMaterialsModal()" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-xl">
                    {{ trans('messages.close', [], session('locale')) }}
                </button>
            </div>
        </div>
    </div>
</main>

@include('layouts.footer')
@include('custom_js.stock_materials_js')
@endsection
