@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.view_stock_lang', [], session('locale')) }}</title>
@endpush

<style>
    [x-cloak] {
        display: none !important;
    }
</style>
<main class="flex-1 p-4 md:p-6"
    x-data="{ 
        showDetails: false, 
        loading: false, 
        showQuantity: false, 
        actionType: 'add',
        availableQuantity: 0
    }"
    >
    <div class="w-full max-w-[1920px] mx-auto">

        <!-- Page title and add button -->
        <div class="flex flex-col sm:flex-row flex-wrap justify-between items-start sm:items-center gap-4 mb-6">
            <h2 class="text-gray-900 text-2xl sm:text-3xl font-bold">
                {{ trans('messages.manage_stocks', [], session('locale')) }}
            </h2>
            <a href="{{url('stock')}}"
                class="inline-flex items-center justify-center h-11 px-5 rounded-lg bg-[var(--primary-color)] text-white text-sm sm:text-base font-bold shadow hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
                <span class="material-symbols-outlined me-1">add</span>
                {{ trans('messages.add_stock', [], session('locale')) }}
            </a>
        </div>


        <!-- Search and filters -->
        <div class="sticky top-[var(--header-h,64px)] z-10 bg-white/80 backdrop-blur border border-pink-100 rounded-2xl shadow-sm">
            <div class="py-3 px-4">
                <div class="flex flex-wrap items-center gap-2 overflow-x-auto no-scrollbar">
                    <div class="flex-1 min-w-[45%]">
                        <input id="stock_search" type="search"
                            placeholder="{{ trans('messages.search_placeholder', [], session('locale')) }}"
                            class="w-full h-11 rounded-xl border border-pink-200 focus:border-[var(--primary-color)] focus:ring-[var(--primary-color)] pr-10 text-sm" />
                    </div>
                    <select id="stock_filter" class="shrink-0 rounded-xl border border-pink-200 h-11 text-sm">
                        <option value="all">{{ trans('messages.all', [], session('locale')) }}</option>
                        <option value="available">{{ trans('messages.available', [], session('locale')) }}</option>
                        <option value="low">{{ trans('messages.low', [], session('locale')) }}</option>
                        <option value="out_of_stock">{{ trans('messages.out_of_stock', [], session('locale')) }}</option>
                    </select>
                </div>
            </div>
        </div>


        <!-- Mobile cards -->
        <section class="mt-4 xl:hidden">
            <div id="mobile_stock_cards" class="grid grid-cols-1 sm:grid-cols-2 gap-4"></div>
        </section>

        <!-- Desktop table -->
        <section class="hidden xl:block mt-6">
            <div class="rounded-2xl overflow-x-auto border border-pink-100 bg-white shadow-md hover:shadow-lg transition mx-auto">
                <table class="w-full text-sm min-w-full">
                    <thead class="bg-gradient-to-l from-pink-50 to-pink-100 text-gray-800 sticky top-0 z-10">
                        <tr>
                            <th class="text-center px-3 sm:px-4 md:px-6 py-3 font-bold whitespace-nowrap min-w-[200px]">{{ trans('messages.stock_name', [], session('locale')) ?: 'Stock Name' }}</th>
                            <th class="text-center px-3 sm:px-4 md:px-6 py-3 font-bold whitespace-nowrap min-w-[120px]">{{ trans('messages.barcode', [], session('locale')) }}</th>
                            <th class="text-center px-3 sm:px-4 md:px-6 py-3 font-bold whitespace-nowrap min-w-[100px]">{{ trans('messages.quantity', [], session('locale')) }}</th>
                            <th class="text-center px-3 sm:px-4 md:px-6 py-3 font-bold whitespace-nowrap min-w-[120px]">{{ trans('messages.sales_price', [], session('locale')) }}</th>
                            <th class="text-center px-3 sm:px-4 md:px-6 py-3 font-bold whitespace-nowrap min-w-[200px]">{{ trans('messages.actions', [], session('locale')) }}</th>
                        </tr>
                    </thead>

                    <tbody id="desktop_stock_body"></tbody>
                </table>

                <!-- Pagination -->
            </div>
        </section>
    <ul id="stock_pagination" class="flex flex-wrap justify-center items-center gap-1.5 mt-4 list-none pl-0 max-w-full"></ul>

    <!-- Pagination loader - shown when changing page via pagination buttons -->
    <div id="stock_pagination_loader" class="fixed inset-0 flex flex-col items-center justify-center bg-black/70 z-[9998]" style="display: none;">
        <div class="loader border-4 border-pink-200 border-t-[var(--primary-color)] rounded-full w-16 h-16 animate-spin mb-4"></div>
        <p class="text-white text-lg font-bold">{{ trans('messages.loading_details', [], session('locale')) }}</p>
    </div>

    </div>

    <!-- View Stock Detail Modal -->
        <div x-show="showDetails"
            x-transition
            x-cloak
            class="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4"
            @keydown.escape.window="showDetails = false">

            <div @click.away="showDetails = false" @click.stop
                class="bg-white w-full max-w-4xl max-h-[90vh] rounded-3xl shadow-2xl overflow-hidden flex flex-col">

                <!-- Header -->
                <div class="flex justify-between items-center p-5 border-b bg-gradient-to-r from-pink-50 to-purple-50">
                    <h2 class="text-xl font-bold text-[var(--primary-color)]">{{ trans('messages.stock_details', [], session('locale')) }}</h2>
                    <button @click="showDetails = false" class="text-gray-500 hover:text-gray-800">
                        <span class="material-symbols-outlined text-3xl">close</span>
                    </button>
                </div>

                <div x-show="loading" class="flex flex-col items-center justify-center py-12">
                    <div class="loader border-4 border-pink-200 border-t-[var(--primary-color)] rounded-full w-12 h-12 animate-spin"></div>
                    <p class="text-gray-600 mt-2">{{ trans('messages.loading_details', [], session('locale')) }}</p>
                </div>
                <div x-show="!loading" class="p-6 overflow-y-auto flex-1" id="stock_detail_body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div id="detail_image_wrap" class="rounded-xl overflow-hidden bg-gray-100">
                            <img id="stock_detail_image" src="" alt="" class="w-full h-64 object-cover" onerror="this.parentElement.style.display='none'" />
                        </div>
                        <div class="space-y-3 text-sm">
                            <p><strong>{{ trans('messages.stock_name', [], session('locale')) ?: 'Stock Name' }}:</strong> <span id="detail_stock_name">-</span></p>
                            <p><strong>{{ trans('messages.category', [], session('locale')) }}:</strong> <span id="detail_category">-</span></p>
                            <p><strong>{{ trans('messages.barcode', [], session('locale')) }}:</strong> <span id="detail_barcode">-</span></p>
                            <p><strong>{{ trans('messages.production_unit', [], session('locale')) ?: 'Production Unit' }}:</strong> <span id="detail_production_unit">-</span></p>
                            <p><strong>{{ trans('messages.quantity', [], session('locale')) }}:</strong> <span id="detail_quantity" class="font-bold text-[var(--primary-color)]">-</span></p>
                            <p><strong>{{ trans('messages.cost_price', [], session('locale')) }}:</strong> <span id="detail_cost_price">-</span></p>
                            <p><strong>{{ trans('messages.sales_price', [], session('locale')) }}:</strong> <span id="detail_sales_price">-</span></p>
                            <p><strong>{{ trans('messages.discount', [], session('locale')) }}:</strong> <span id="detail_discount">-</span></p>
                            <p><strong>{{ trans('messages.tax', [], session('locale')) }}:</strong> <span id="detail_tax">-</span></p>
                            <p><strong>{{ trans('messages.general_minimum_stock', [], session('locale')) }}:</strong> <span id="detail_notification_limit">-</span></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <strong>{{ trans('messages.stock_notes', [], session('locale')) ?: 'Notes' }}:</strong>
                        <p id="detail_notes" class="text-gray-700 mt-1">-</p>
                    </div>
                </div>






            </div>
        </div>

    <!-- Pull/Push Quantity Modal -->
    <div x-show="showQuantity" x-transition x-cloak
        class="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4"
        @keydown.escape.window="showQuantity = false">
        <div @click.away="showQuantity = false" @click.stop
            class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden flex flex-col">
            <form id="save_qty" class="flex flex-col">
                @csrf
                <input type="hidden" name="stock_id" id="qty_stock_id" value="" />
                <div class="flex justify-between items-center p-4 border-b bg-gradient-to-r from-[var(--primary-color)] to-[#5e4a9e]">
                    <h5 class="text-white text-lg font-bold flex items-center">
                        <span class="material-symbols-outlined me-2">inventory_2</span>
                        {{ trans('messages.manage_quantities', [], session('locale')) }}
                    </h5>
                    <button type="button" @click="showQuantity = false" class="text-white hover:text-gray-200">
                        <span class="material-symbols-outlined text-2xl">close</span>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-gray-600">{{ trans('messages.available', [], session('locale')) }}: <strong class="text-[var(--primary-color)]" id="qty_available_display">0</strong></p>
                    <div class="flex justify-center gap-2">
                        <input type="radio" class="hidden" name="qtyType" id="qty_add" value="add" x-model="actionType" @change="document.getElementById('qty_amount') && document.getElementById('qty_amount').removeAttribute('max')">
                        <label for="qty_add" :class="actionType === 'add' ? 'bg-[var(--primary-color)] text-white' : 'bg-gray-100 text-gray-700'"
                            class="px-4 py-2 rounded-lg text-sm font-semibold cursor-pointer flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">add_circle</span>
                            {{ trans('messages.add_new', [], session('locale')) }}
                        </label>
                        <input type="radio" class="hidden" name="qtyType" id="qty_pull" value="pull" x-model="actionType" @change="document.getElementById('qty_amount') && document.getElementById('qty_amount').setAttribute('max', availableQuantity)">
                        <label for="qty_pull" :class="actionType === 'pull' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700'"
                            class="px-4 py-2 rounded-lg text-sm font-semibold cursor-pointer flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">remove_circle</span>
                            {{ trans('messages.pull_quantity', [], session('locale')) }}
                        </label>
                    </div>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">{{ trans('messages.quantity', [], session('locale')) }}</span>
                        <input type="number" step="1" min="0" name="quantity" id="qty_amount" placeholder="0"
                            class="mt-1 w-full h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary" />
                    </label>
                    <div x-show="actionType === 'pull'" x-transition class="p-4 bg-red-50 border-2 border-red-200 rounded-xl">
                        <label class="block font-bold text-red-600 mb-2">
                            <span class="material-symbols-outlined align-middle text-lg">warning</span>
                            {{ trans('messages.pull_reason_required', [], session('locale')) ?: 'Pull reason is required' }}
                        </label>
                        <textarea name="pull_reason" id="pull_reason" rows="3"
                            placeholder="{{ trans('messages.pull_reason_placeholder', [], session('locale')) ?: 'Enter reason for pulling quantity...' }}"
                            class="w-full border-2 border-red-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 p-4 border-t bg-gray-50">
                    <button type="button" @click="showQuantity = false"
                        class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 font-semibold hover:bg-gray-50">
                        {{ trans('messages.cancel', [], session('locale')) }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg text-white font-semibold shadow-md hover:shadow-lg"
                        style="background: linear-gradient(135deg, var(--primary-color), #5e4a9e);">
                        <span class="material-symbols-outlined align-middle me-1 text-sm">check</span>
                        {{ trans('messages.save_operation', [], session('locale')) }}
                    </button>
                </div>
            </form>
        </div>
    </div>





    <!-- Legacy Full Stock Details Modal (hidden, kept for compatibility) -->
   <div x-show="false"
    x-transition
    x-cloak
    class="fixed inset-0 bg-black/60 z-[9998] flex items-center justify-center p-4"
    @keydown.escape.window="showFullDetails = false">

    <div @click.away="showFullDetails = false" @click.stop
        class="bg-white w-full max-w-6xl max-h-[90vh] rounded-3xl shadow-2xl overflow-hidden flex flex-col">

        <!-- Header -->
        <div class="flex justify-between items-center p-5 border-b bg-gradient-to-r from-pink-50 to-purple-50 flex-shrink-0">
            <h5 class="text-[var(--primary-color)] text-xl font-bold">
                {{ trans('messages.stock_details', [], session('locale')) }}:
                <span id="full_modal_stock_code">...</span>
            </h5>
            <button type="button" @click="showFullDetails = false"
                class="text-gray-500 hover:text-gray-800 transition">
                <span class="material-symbols-outlined text-3xl">close</span>
            </button>
        </div>

        <!-- Loader -->
        <div x-show="fullDetailsLoading" class="flex flex-col items-center justify-center p-12">
            <div class="border-4 border-pink-200 border-t-[var(--primary-color)] rounded-full w-16 h-16 animate-spin mb-4"></div>
            <p class="text-gray-600 font-semibold">
                {{ trans('messages.loading_details', [], session('locale')) }}
            </p>
        </div>

        <!-- Body -->
        <div x-show="!fullDetailsLoading"
            class="p-4 md:p-6 overflow-y-auto flex-1"
            id="fullStockDetailsBody">

            <!-- Total Quantity -->
            <div class="text-center mb-6">
                <div class="inline-block p-4 rounded-2xl shadow-md"
                    style="background: linear-gradient(135deg, var(--primary-color), #5e4a9e);">
                    <h6 class="text-white mb-1 font-semibold text-sm">
                        {{ trans('messages.total_quantity', [], session('locale')) }}
                    </h6>
                    <h3 class="text-white mb-0 font-bold text-3xl" id="full_total_quantity">0</h3>
                </div>
            </div>

            <!-- Images -->
            <div class="mb-6">
                <h6 class="font-bold text-[var(--primary-color)] mb-3 flex items-center">
                    <span class="material-symbols-outlined me-2">images</span>
                    {{ trans('messages.images', [], session('locale')) }}
                </h6>
                <div id="full_stock_images_container"
                    class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3"></div>
            </div>

            <hr class="my-6 border-dashed border-gray-300">

            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <h6 class="font-bold text-[var(--primary-color)] mb-3">
                        {{ trans('messages.basic_info', [], session('locale')) }}
                    </h6>

                    <p><strong>{{ trans('messages.code', [], session('locale')) }}:</strong>
                        <span id="full_stock_code">-</span></p>

                    <p><strong>{{ trans('messages.design', [], session('locale')) }}:</strong>
                        <span id="full_design_name">-</span></p>

                    <p><strong>{{ trans('messages.description', [], session('locale')) }}:</strong>
                        <span id="full_description">-</span></p>

                    <p><strong>{{ trans('messages.barcode', [], session('locale')) }}:</strong>
                        <span id="full_barcode">-</span></p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-4">
                    <h6 class="font-bold text-[var(--primary-color)] mb-3">
                        {{ trans('messages.price_info', [], session('locale')) }}
                    </h6>

                    <p><strong>{{ trans('messages.cost_price', [], session('locale')) }}:</strong>
                        <span id="full_cost_price">-</span></p>

                    <p><strong>{{ trans('messages.sales_price', [], session('locale')) }}:</strong>
                        <span id="full_sales_price">-</span></p>

                    <p><strong>{{ trans('messages.tailor_charges', [], session('locale')) }}:</strong>
                        <span id="full_tailor_charges">-</span></p>

                    <p><strong>{{ trans('messages.tailors', [], session('locale')) }}:</strong>
                        <span id="full_tailor_names">-</span></p>
                </div>
            </div>

            <hr class="my-6 border-dashed border-gray-300">

            <!-- Color / Size -->
            <div>
                <h6 class="font-bold text-[var(--primary-color)] mb-3 flex items-center">
                    <span class="material-symbols-outlined me-2">palette</span>
                    {{ trans('messages.by_color_and_size', [], session('locale')) }}
                </h6>

                <div id="full_size_color_container"
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end p-5 border-t bg-gray-50">
            <button @click="showFullDetails = false"
                class="px-5 py-3 rounded-lg border bg-white text-gray-700 font-semibold hover:bg-gray-50">
                {{ trans('messages.close', [], session('locale')) }}
            </button>
        </div>
    </div>
</div>


</main>

@include('layouts.footer')
@endsection