@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.packaging', [], session('locale')) }}</title>
@endpush

<style>
  .packaging-material-wrap { position: relative; }
  #packaging_material_dropdown_global { display: none; position: fixed; z-index: 9999; max-height: 240px; overflow-y: auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
  #packaging_material_dropdown_global.show { display: block; }
  .packaging-material-option { padding: 0.5rem 0.75rem; cursor: pointer; border-bottom: 1px solid #f3f4f6; font-size: 0.875rem; }
  .packaging-material-option:hover, .packaging-material-option.highlight { background: #fef2f2; }
  .packaging-material-option:last-child { border-bottom: none; }
</style>

<main class="flex-1 p-4 md:p-6">
  <div class="max-w-7xl mx-auto">

    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <a href="{{ url('production/' . $production->id . '/profile') }}" class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors">
          <span class="material-symbols-outlined text-gray-600">arrow_back</span>
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ trans('messages.packaging', [], session('locale')) }}</h1>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
      <!-- Production info (readonly) -->
      <div class="p-6 sm:p-8 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-bold text-gray-800 mb-4">{{ trans('messages.production_info', [], session('locale')) }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <span class="text-sm text-gray-500">{{ trans('messages.stock', [], session('locale')) }}:</span>
            <p class="font-semibold">{{ $production->stock->stock_name ?? '-' }}</p>
          </div>
          <div>
            <span class="text-sm text-gray-500">{{ trans('messages.batch_id', [], session('locale')) }}:</span>
            <p class="font-semibold">{{ $production->batch_id ?? '-' }}</p>
          </div>
          <div>
            <span class="text-sm text-gray-500">{{ trans('messages.estimated_output', [], session('locale')) }}:</span>
            <p class="font-semibold">{{ number_format((float) $production->estimated_output, 0) }}</p>
          </div>
        </div>
      </div>

      <!-- Materials section (packaging type only) -->
      <div class="p-6 sm:p-8 border-b border-gray-200">
        <div class="flex items-center justify-between gap-2 pb-3 mb-4 border-b border-gray-200">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
            <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.materials', [], session('locale')) }} ({{ trans('messages.packaging', [], session('locale')) }})</h2>
          </div>
          <button type="button" id="add_material_row" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[var(--primary-color)] text-white text-sm font-semibold hover:opacity-90 transition">
            <span class="material-symbols-outlined text-lg">add</span>
            {{ trans('messages.add_new_row', [], session('locale')) }}
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[700px]" id="packaging_materials_table">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="text-left px-3 py-3 font-bold text-gray-700">{{ trans('messages.material_name', [], session('locale')) }}</th>
                <th class="text-center px-2 py-3 font-bold text-gray-700">{{ trans('messages.unit', [], session('locale')) }}</th>
                <th class="text-center px-2 py-3 font-bold text-gray-700">{{ trans('messages.unit_price', [], session('locale')) }}</th>
                <th class="text-center px-3 py-3 font-bold text-gray-700">{{ trans('messages.quantity', [], session('locale')) }}</th>
                <th class="text-center px-3 py-3 font-bold text-gray-700">{{ trans('messages.total', [], session('locale')) }}</th>
                <th class="text-center px-2 py-3 font-bold text-gray-700 w-12"></th>
              </tr>
            </thead>
            <tbody id="packaging_materials_body"></tbody>
          </table>
        </div>
        <div class="mt-4 flex flex-wrap gap-6 items-center border-t border-gray-200 pt-4">
          <div><span class="text-sm font-semibold text-gray-700">{{ trans('messages.total_quantity', [], session('locale')) }}:</span> <span id="packaging_summary_total_qty" class="font-bold">0</span></div>
          <div><span class="text-sm font-semibold text-gray-700">{{ trans('messages.total_materials', [], session('locale')) }}:</span> <span id="packaging_summary_total_items" class="font-bold">0</span></div>
          <div><span class="text-sm font-semibold text-gray-700">{{ trans('messages.total_amount', [], session('locale')) }}:</span> <span id="packaging_summary_total_amount" class="font-bold">0.00</span></div>
        </div>
      </div>

      <div class="p-6 sm:p-8 border-t flex justify-end">
        <button type="button" id="packaging_save_btn" class="px-8 py-3 bg-[var(--primary-color)] text-white font-semibold rounded-xl shadow-md hover:opacity-90 transition flex items-center gap-2">
          <span class="material-symbols-outlined">save</span>
          {{ trans('messages.save', [], session('locale')) }}
        </button>
      </div>
    </div>
  </div>
</main>

<div id="packaging_material_dropdown_global"></div>

<input type="hidden" id="production_id" value="{{ $production->id }}" />
@include('layouts.footer')
@endsection
