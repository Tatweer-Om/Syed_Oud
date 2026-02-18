@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.production', [], session('locale')) }}</title>
@endpush

<style>
  body { font-family: 'IBM Plex Sans Arabic', sans-serif; }
  .production-stock-dropdown { display: none; position: absolute; left: 0; right: 0; top: 100%; z-index: 1060; max-height: 220px; overflow-y: auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.15); margin-top: 2px; }
  .production-stock-dropdown.show { display: block; }
  .production-stock-option { padding: 0.5rem 0.75rem; cursor: pointer; border-bottom: 1px solid #f3f4f6; }
  .production-stock-option:hover, .production-stock-option.highlight { background: #fef2f2; }
  .production-stock-option:last-child { border-bottom: none; }
  .production-stock-wrap { position: relative; z-index: 1; }
  .production-material-wrap { position: relative; }
  #production_material_dropdown_global { display: none; position: fixed; z-index: 9999; max-height: 240px; overflow-y: auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
  #production_material_dropdown_global.show { display: block; }
  .production-material-option { padding: 0.5rem 0.75rem; cursor: pointer; border-bottom: 1px solid #f3f4f6; font-size: 0.875rem; }
  .production-material-option:hover, .production-material-option.highlight { background: #fef2f2; }
  .production-material-option:last-child { border-bottom: none; }
  #production_materials_table { table-layout: fixed; }
  #production_materials_table th.col-material { width: 35%; min-width: 180px; }
  #production_materials_table th.col-unit { width: 80px; }
  #production_materials_table th.col-price { width: 100px; }
  #production_materials_table th.col-qty { width: 100px; }
  #production_materials_table th.col-total { width: 100px; }
  #production_materials_table td.col-unit input { width: 70px; margin: 0 auto; }
</style>

<main class="flex-1 p-4 md:p-6">
  <div class="max-w-7xl mx-auto">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <a href="{{ isset($is_edit) && $is_edit ? url('view_production') : url('view_material') }}"
           class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors">
          <span class="material-symbols-outlined text-gray-600">arrow_back</span>
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
          {{ isset($is_edit) && $is_edit ? trans('messages.edit_production_draft', [], session('locale')) : trans('messages.production', [], session('locale')) }}
        </h1>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
      @csrf
      @if(isset($is_edit) && $is_edit && isset($draft))
      <input type="hidden" id="production_draft_id" value="{{ $draft->id }}" />
      @endif

      <!-- Top section: Stock selection, Estimated output -->
      <div class="p-6 sm:p-8 border-b border-gray-200">
        <div class="flex items-center gap-2 pb-3 mb-4 border-b border-gray-200">
          <span class="material-symbols-outlined text-primary text-xl">factory</span>
          <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.production_details', [], session('locale')) }}</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Production Date -->
          <label class="flex flex-col">
            <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.production_date', [], session('locale')) }}</span>
            <input type="date"
                   name="production_date"
                   id="production_date"
                   value="{{ date('Y-m-d') }}"
                   class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
          </label>
          <!-- Stock (searchable) -->
          <div class="flex flex-col">
            <label class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.stock', [], session('locale')) }}</label>
            <div class="production-stock-wrap">
              <input type="text"
                     id="stock_search"
                     autocomplete="off"
                     placeholder="{{ trans('messages.search_stock', [], session('locale')) }}"
                     class="h-11 w-full rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
              <input type="hidden" id="stock_id" name="stock_id" value="" />
              <div id="stock_dropdown" class="production-stock-dropdown"></div>
            </div>
          </div>
          <!-- Estimated Output -->
          <label class="flex flex-col">
            <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.estimated_output', [], session('locale')) }}</span>
            <input type="number"
                   step="1"
                   min="0"
                   name="estimated_output"
                   id="estimated_output"
                   placeholder="{{ trans('messages.estimated_output_placeholder', [], session('locale')) }}"
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
          <table class="w-full text-sm min-w-[700px]" id="production_materials_table">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="text-left px-3 py-3 font-bold text-gray-700 col-material">{{ trans('messages.material_name', [], session('locale')) }}</th>
                <th class="text-center px-2 py-3 font-bold text-gray-700 col-unit">{{ trans('messages.unit', [], session('locale')) }}</th>
                <th class="text-center px-2 py-3 font-bold text-gray-700 col-price">{{ trans('messages.unit_price', [], session('locale')) }}</th>
                <th class="text-center px-3 py-3 font-bold text-gray-700 col-qty">{{ trans('messages.quantity', [], session('locale')) }}</th>
                <th class="text-center px-3 py-3 font-bold text-gray-700 col-total">{{ trans('messages.total', [], session('locale')) }}</th>
                <th class="text-center px-2 py-3 font-bold text-gray-700 w-12"></th>
              </tr>
            </thead>
            <tbody id="production_materials_body">
              <!-- Rows added by JS -->
            </tbody>
          </table>
        </div>
        <!-- Summary -->
        <div class="mt-4 flex flex-wrap gap-6 items-center border-t border-gray-200 pt-4">
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-gray-700">{{ trans('messages.total_quantity', [], session('locale')) }}:</span>
            <span id="production_summary_total_qty" class="text-lg font-bold text-gray-900">0</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-gray-700">{{ trans('messages.total_materials', [], session('locale')) }}:</span>
            <span id="production_summary_total_items" class="text-lg font-bold text-gray-900">0</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-gray-700">{{ trans('messages.total_amount', [], session('locale')) }}:</span>
            <span id="production_summary_total_amount" class="text-lg font-bold text-gray-900">0.00</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-gray-700">{{ trans('messages.cost_per_unit', [], session('locale')) }}:</span>
            <span id="production_summary_cost_per_unit" class="text-lg font-bold text-green-600">0.00</span>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div class="p-6 sm:p-8">
        <div class="flex items-center gap-2 pb-3 mb-4 border-b border-gray-200">
          <span class="material-symbols-outlined text-primary text-xl">notes</span>
          <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.notes', [], session('locale')) }}</h2>
        </div>
        <textarea name="production_notes"
                  id="production_notes"
                  rows="4"
                  placeholder="{{ trans('messages.notes_placeholder', [], session('locale')) }}"
                  class="w-full rounded-lg px-4 py-3 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition resize-none"></textarea>
      </div>

      <!-- Footer -->
      <div class="bg-gray-50 px-6 sm:p-8 py-4 border-t border-gray-200 flex justify-end">
        <button type="button" id="production_save_btn" class="px-8 py-3 bg-[var(--primary-color)] text-white font-semibold rounded-xl shadow-md hover:opacity-90 transition flex items-center gap-2">
          <span class="material-symbols-outlined">save</span>
          {{ trans('messages.save', [], session('locale')) }}
        </button>
      </div>
    </div>
  </div>
</main>

<!-- Global material dropdown (appended to body to avoid overflow issues) -->
<div id="production_material_dropdown_global"></div>

@if(isset($is_edit) && $is_edit && isset($draft))
<script>window.PRODUCTION_DRAFT = @json($draft); window.PRODUCTION_EDIT_ID = {{ $draft->id }};</script>
@endif
@include('layouts.footer')
@endsection
