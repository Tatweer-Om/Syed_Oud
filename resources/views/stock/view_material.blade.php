@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.view_material_lang', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-4 md:p-6"
    x-data="{ showDetails: false, loading: false, showQuantity: false, actionType: 'add' }">
    <div class="w-full max-w-[95%] xl:max-w-[98%] mx-auto">

        <!-- Page title and add button -->
        <div class="flex flex-col sm:flex-row flex-wrap justify-between items-start sm:items-center gap-4 mb-6">
            <h2 class="text-gray-900 text-2xl sm:text-3xl font-bold">
                {{ trans('messages.manage_material', [], session('locale')) }}
            </h2>
            <button type="button" id="openAddMaterialModal"
                class="inline-flex items-center justify-center h-11 px-5 rounded-lg bg-[var(--primary-color)] text-white text-sm sm:text-base font-bold shadow hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
                <span class="material-symbols-outlined me-1">add</span>
                {{ trans('messages.add_material', [], session('locale')) }}
            </button>
        </div>

        <!-- Search and filters -->
        <div class="sticky top-[var(--header-h,64px)] z-10 bg-white/80 backdrop-blur border border-pink-100 rounded-2xl shadow-sm">
            <div class="py-3 px-4">
                <div class="flex flex-wrap items-center gap-2 overflow-x-auto no-scrollbar">
                    <div class="flex-1 min-w-[45%]">
                        <input id="q" type="search"
                            placeholder="{{ trans('messages.search_material_placeholder', [], session('locale')) }}"
                            class="w-full h-11 rounded-xl border border-pink-200 focus:border-[var(--primary-color)] focus:ring-[var(--primary-color)] pr-10 text-sm" />
                    </div>

                </div>
            </div>
        </div>


   

    <!-- Desktop table -->
    <section class="hidden xl:block mt-6">
        <div class="rounded-2xl overflow-x-auto border border-pink-100 bg-white shadow-md hover:shadow-lg transition mx-auto" style="max-width: 100%;">
            <table class="w-full text-sm min-w-[600px] mx-auto">
                <thead class="bg-gradient-to-l from-pink-50 to-pink-100 text-gray-800">
                    <tr>
                        <th class="text-center px-3 py-3 font-bold">{{ trans('messages.material_name', [], session('locale')) }}</th>
                        <th class="text-center px-3 py-3 font-bold">{{ trans('messages.unit', [], session('locale')) }}</th>
                        <th class="text-center px-3 py-3 font-bold">{{ trans('messages.quantity', [], session('locale')) }}</th>
                        <th class="text-center px-3 py-3 font-bold">{{ trans('messages.buy_price', [], session('locale')) }}</th>
                        <th class="text-center px-3 py-3 font-bold">{{ trans('messages.notes', [], session('locale')) }}</th>
                        <th class="text-center px-3 py-3 font-bold">{{ trans('messages.action', [], session('locale')) }}</th>
                    </tr>
                </thead>

                <tbody id="desktop_material_body"></tbody>
            </table>
            <div id="mobile_material_cards" class="md:hidden"></div>


        </div>
     
    </section>



    </div>
    <div class="flex justify-center mt-6">
    <ul id="material_pagination" class="material_pagination flex gap-2 list-none"></ul>
</div>

    <!-- Add Material Modal (popup) -->
    <div id="addMaterialModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">{{ trans('messages.add_material', [], session('locale')) }}</h2>
                <button type="button" id="closeAddMaterialModal" class="text-gray-400 hover:text-gray-600 p-1">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="addMaterialModalForm">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ trans('messages.material_name', [], session('locale')) }}</label>
                        <input type="text" name="material_name" id="popup_material_name" required
                            class="w-full h-11 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] px-4"
                            placeholder="{{ trans('messages.material_name_placeholder', [], session('locale')) }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ trans('messages.material_type', [], session('locale')) }}</label>
                        <div class="flex gap-6">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="material_type" value="production" checked
                                    class="w-4 h-4 text-[var(--primary-color)] focus:ring-[var(--primary-color)]">
                                <span class="text-sm font-medium">{{ trans('messages.production', [], session('locale')) }}</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="material_type" value="packaging"
                                    class="w-4 h-4 text-[var(--primary-color)] focus:ring-[var(--primary-color)]">
                                <span class="text-sm font-medium">{{ trans('messages.packaging', [], session('locale')) }}</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ trans('messages.unit', [], session('locale')) }}</label>
                        <select name="material_unit" id="popup_material_unit" required
                            class="w-full h-11 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] px-4">
                            <option value="">{{ trans('messages.choose', [], session('locale')) }}</option>
                            <!-- Options loaded from units module via JS -->
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ trans('messages.quantity', [], session('locale')) }}</label>
                        <input type="number" step="0.01" min="0" name="quantity" id="popup_material_quantity"
                            class="w-full h-11 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] px-4"
                            placeholder="0">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ trans('messages.buy_price', [], session('locale')) }} ({{ trans('messages.unit', [], session('locale')) }})</label>
                        <input type="number" step="0.01" min="0" name="purchase_price" id="popup_purchase_price"
                            class="w-full h-11 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] px-4"
                            placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ trans('messages.description', [], session('locale')) }} / {{ trans('messages.notes', [], session('locale')) ?: 'Notes' }}</label>
                        <textarea name="material_notes" id="popup_material_notes" rows="3"
                            class="w-full rounded-xl border border-gray-300 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] px-4 py-2 resize-none"
                            placeholder="{{ trans('messages.description_placeholder', [], session('locale')) }}"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <button type="button" id="cancelAddMaterialBtn" class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 rounded-xl transition font-semibold">
                        {{ trans('messages.cancel', [], session('locale')) }}
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-[var(--primary-color)] hover:bg-[var(--primary-color)]/90 text-white rounded-xl transition font-semibold">
                        {{ trans('messages.save', [], session('locale')) }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Material Modal (popup) -->
    <div id="editMaterialModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">{{ trans('messages.edit_material', [], session('locale')) }}</h2>
                <button type="button" id="closeEditMaterialModal" class="text-gray-400 hover:text-gray-600 p-1">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="editMaterialModalForm">
                @csrf
                <input type="hidden" id="edit_material_id" name="material_id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ trans('messages.material_name', [], session('locale')) }}</label>
                        <input type="text" name="material_name" id="edit_popup_material_name" required
                            class="w-full h-11 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] px-4"
                            placeholder="{{ trans('messages.material_name_placeholder', [], session('locale')) }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ trans('messages.material_type', [], session('locale')) }}</label>
                        <div class="flex gap-6">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="edit_material_type" value="production"
                                    class="w-4 h-4 text-[var(--primary-color)] focus:ring-[var(--primary-color)]">
                                <span class="text-sm font-medium">{{ trans('messages.production', [], session('locale')) }}</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="edit_material_type" value="packaging"
                                    class="w-4 h-4 text-[var(--primary-color)] focus:ring-[var(--primary-color)]">
                                <span class="text-sm font-medium">{{ trans('messages.packaging', [], session('locale')) }}</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ trans('messages.unit', [], session('locale')) }}</label>
                        <select name="material_unit" id="edit_popup_material_unit" required
                            class="w-full h-11 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] px-4">
                            <option value="">{{ trans('messages.choose', [], session('locale')) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ trans('messages.quantity', [], session('locale')) }}</label>
                        <input type="number" step="0.01" min="0" name="quantity" id="edit_popup_material_quantity"
                            class="w-full h-11 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] px-4"
                            placeholder="0">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ trans('messages.buy_price', [], session('locale')) }} ({{ trans('messages.unit', [], session('locale')) }})</label>
                        <input type="number" step="0.01" min="0" name="purchase_price" id="edit_popup_purchase_price"
                            class="w-full h-11 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] px-4"
                            placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ trans('messages.description', [], session('locale')) }} / {{ trans('messages.notes', [], session('locale')) ?: 'Notes' }}</label>
                        <textarea name="material_notes" id="edit_popup_material_notes" rows="3"
                            class="w-full rounded-xl border border-gray-300 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] px-4 py-2 resize-none"
                            placeholder="{{ trans('messages.description_placeholder', [], session('locale')) }}"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <button type="button" id="cancelEditMaterialBtn" class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 rounded-xl transition font-semibold">
                        {{ trans('messages.cancel', [], session('locale')) }}
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-[var(--primary-color)] hover:bg-[var(--primary-color)]/90 text-white rounded-xl transition font-semibold">
                        {{ trans('messages.save', [], session('locale')) }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</main>

@include('layouts.footer')
@endsection