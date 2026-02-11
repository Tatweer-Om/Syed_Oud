@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.purchase', [], session('locale')) }}</title>
@endpush

<style>
  body { font-family: 'IBM Plex Sans Arabic', sans-serif; }
  .purchase-supplier-dropdown { display: none; position: absolute; left: 0; right: 0; top: 100%; z-index: 1060; max-height: 220px; overflow-y: auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.15); margin-top: 2px; }
  .purchase-supplier-dropdown.show { display: block; }
  .purchase-supplier-option { padding: 0.5rem 0.75rem; cursor: pointer; border-bottom: 1px solid #f3f4f6; }
  .purchase-supplier-option:hover, .purchase-supplier-option.highlight { background: #fef2f2; }
  .purchase-supplier-option:last-child { border-bottom: none; }
  .purchase-supplier-wrap { position: relative; z-index: 1; }
  .purchase-material-wrap { position: relative; z-index: 1; }
  .purchase-material-dropdown { display: none; position: fixed; z-index: 1060; max-height: 240px; overflow-y: auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
  .purchase-material-dropdown.show { display: block; }
  .purchase-material-option { padding: 0.4rem 0.75rem; cursor: pointer; border-bottom: 1px solid #f3f4f6; font-size: 0.875rem; }
  .purchase-material-option:hover, .purchase-material-option.highlight { background: #fef2f2; }
  .purchase-material-option:last-child { border-bottom: none; }
  #purchase_materials_table { table-layout: fixed; }
  #purchase_materials_table th.col-material { width: 40%; min-width: 180px; }
  #purchase_materials_table th.col-unit { width: 80px; }
  #purchase_materials_table th.col-price { width: 90px; }
  #purchase_materials_table td.col-unit input { width: 70px; margin: 0 auto; }
  #purchase_materials_table td.col-price input { width: 80px; margin: 0 auto; }
  #purchase_materials_table th.col-price-shipping { width: 100px; }
  #purchase_materials_table td.col-price-shipping { width: 100px; }
</style>

<main class="flex-1 p-4 md:p-6">
  <div class="max-w-7xl mx-auto">

    <!-- Header (same style as stock page) -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <a href="{{ isset($is_edit) && $is_edit ? url('view_purchase') : url('view_material') }}"
           class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors">
          <span class="material-symbols-outlined text-gray-600">arrow_back</span>
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
          {{ isset($is_edit) && $is_edit ? trans('messages.edit_purchase_draft', [], session('locale')) : trans('messages.purchase', [], session('locale')) }}
        </h1>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
      @csrf
      @if(isset($is_edit) && $is_edit && isset($draft))
      <input type="hidden" id="purchase_draft_id" value="{{ $draft->id }}" />
      @endif

      <!-- Top section: Supplier, Invoice number, Shipping cost -->
      <div class="p-6 sm:p-8 border-b border-gray-200">
        <div class="flex items-center gap-2 pb-3 mb-4 border-b border-gray-200">
          <span class="material-symbols-outlined text-primary text-xl">receipt</span>
          <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.purchase_details', [], session('locale')) }}</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Supplier (searchable) -->
          <div class="flex flex-col">
            <label class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.supplier', [], session('locale')) }}</label>
            <div class="purchase-supplier-wrap">
              <input type="text"
                     id="supplier_search"
                     autocomplete="off"
                     placeholder="{{ trans('messages.search_supplier', [], session('locale')) }}"
                     class="h-11 w-full rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
              <input type="hidden" id="supplier_id" name="supplier_id" value="" />
              <div id="supplier_dropdown" class="purchase-supplier-dropdown"></div>
            </div>
          </div>
          <!-- Invoice number -->
          <label class="flex flex-col">
            <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.invoice_number', [], session('locale')) }}</span>
            <input type="text"
                   name="invoice_number"
                   id="invoice_number"
                   placeholder="{{ trans('messages.invoice_number_placeholder', [], session('locale')) }}"
                   class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
          </label>
          <!-- Invoice amount (editable - user can enter amount) -->
          <label class="flex flex-col">
            <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.invoice_amount', [], session('locale')) }}</span>
            <input type="number"
                   name="invoice_amount"
                   id="invoice_amount"
                   min="0"
                   step="0.01"
                   value="0"
                   placeholder="0.00"
                   class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
          </label>
          <!-- Shipping cost -->
          <label class="flex flex-col">
            <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.shipping_cost', [], session('locale')) }}</span>
            <input type="number"
                   step="0.01"
                   min="0"
                   name="shipping_cost"
                   id="shipping_cost"
                   value="0"
                   placeholder="0.00"
                   class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
          </label>
        </div>
      </div>

      <!-- Materials section: dynamic rows -->
      <div class="p-6 sm:p-8 border-b border-gray-200">
        <div class="flex items-center justify-between gap-2 pb-3 mb-4 border-b border-gray-200">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
            <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.materials', [], session('locale')) }}</h2>
          </div>
          <button type="button" id="add_material_row" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[var(--primary-color)] text-white text-sm font-semibold hover:opacity-90 transition">
            <span class="material-symbols-outlined text-lg">add</span>
            {{ trans('messages.add_new_row', [], session('locale')) }}
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[600px]" id="purchase_materials_table">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="text-left px-3 py-3 font-bold text-gray-700 col-material">{{ trans('messages.material_name', [], session('locale')) }}</th>
                <th class="text-center px-2 py-3 font-bold text-gray-700 col-unit">{{ trans('messages.unit', [], session('locale')) }}</th>
                <th class="text-center px-2 py-3 font-bold text-gray-700 col-price">{{ trans('messages.buy_price', [], session('locale')) }}</th>
                <th class="text-center px-2 py-3 font-bold text-gray-700 col-price-shipping">{{ trans('messages.unit_price_plus_shipping', [], session('locale')) }}</th>
                <th class="text-center px-3 py-3 font-bold text-gray-700">{{ trans('messages.quantity', [], session('locale')) }}</th>
                <th class="text-center px-3 py-3 font-bold text-gray-700">{{ trans('messages.total', [], session('locale')) }}</th>
                <th class="text-center px-2 py-3 font-bold text-gray-700 w-12"></th>
              </tr>
            </thead>
            <tbody id="purchase_materials_body">
              <!-- Rows added by JS -->
            </tbody>
          </table>
        </div>
        <!-- Summary -->
        <div class="mt-4 flex flex-wrap gap-6 items-center border-t border-gray-200 pt-4">
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-gray-700">{{ trans('messages.total_quantity', [], session('locale')) }}:</span>
            <span id="purchase_summary_total_qty" class="text-lg font-bold text-gray-900">0</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-gray-700">{{ trans('messages.total_amount', [], session('locale')) }}:</span>
            <span id="purchase_summary_total_amount" class="text-lg font-bold text-gray-900">0.00</span>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div class="p-6 sm:p-8">
        <div class="flex items-center gap-2 pb-3 mb-4 border-b border-gray-200">
          <span class="material-symbols-outlined text-primary text-xl">notes</span>
          <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.notes', [], session('locale')) }}</h2>
        </div>
        <textarea name="purchase_notes"
                  id="purchase_notes"
                  rows="4"
                  placeholder="{{ trans('messages.notes_placeholder', [], session('locale')) }}"
                  class="w-full rounded-lg px-4 py-3 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition resize-none"></textarea>
      </div>

      <!-- Footer -->
      <div class="bg-gray-50 px-6 sm:p-8 py-4 border-t border-gray-200 flex justify-end">
        <button type="button" id="purchase_save_btn" class="px-8 py-3 bg-[var(--primary-color)] text-white font-semibold rounded-xl shadow-md hover:opacity-90 transition flex items-center gap-2">
          <span class="material-symbols-outlined">save</span>
          {{ trans('messages.save', [], session('locale')) }}
        </button>
      </div>
    </div>
  </div>
</main>

@if(isset($is_edit) && $is_edit && isset($draft))
<script>window.PURCHASE_DRAFT = @json($draft); window.PURCHASE_EDIT_ID = {{ $draft->id }};</script>
@endif
@include('layouts.footer')
@endsection
