@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.view_production', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-4 md:p-6">
  <div class="w-full max-w-[95%] xl:max-w-[98%] mx-auto">

    <div class="flex flex-col sm:flex-row flex-wrap justify-between items-start sm:items-center gap-4 mb-6">
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
        {{ trans('messages.view_production', [], session('locale')) }}
      </h1>
      <a href="{{ url('production') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-lg bg-[var(--primary-color)] text-white text-sm font-bold shadow hover:opacity-90 transition">
        <span class="material-symbols-outlined me-1">add</span>
        {{ trans('messages.production', [], session('locale')) }}
      </a>
    </div>

    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[1200px]" id="production_drafts_table">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="text-left px-3 py-3 font-bold text-gray-700">#</th>
              <th class="text-left px-3 py-3 font-bold text-gray-700">{{ trans('messages.batch_id', [], session('locale')) }}</th>
              <th class="text-left px-3 py-3 font-bold text-gray-700">{{ trans('messages.stock', [], session('locale')) }}</th>
              <th class="text-center px-3 py-3 font-bold text-gray-700">{{ trans('messages.estimated_output', [], session('locale')) }}</th>
              <th class="text-center px-3 py-3 font-bold text-gray-700">{{ trans('messages.total_materials', [], session('locale')) }}</th>
              <th class="text-center px-3 py-3 font-bold text-gray-700">{{ trans('messages.total_quantity', [], session('locale')) }}</th>
              <th class="text-center px-3 py-3 font-bold text-gray-700">{{ trans('messages.total_cost', [], session('locale')) }}</th>
              <th class="text-center px-3 py-3 font-bold text-gray-700">{{ trans('messages.cost_per_unit', [], session('locale')) }}</th>
              <th class="text-center px-3 py-3 font-bold text-gray-700">{{ trans('messages.status', [], session('locale')) }}</th>
              <th class="text-center px-3 py-3 font-bold text-gray-700">{{ trans('messages.packaging', [], session('locale')) }} {{ trans('messages.status', [], session('locale')) }}</th>
              <th class="text-center px-3 py-3 font-bold text-gray-700">{{ trans('messages.action', [], session('locale')) }}</th>
            </tr>
          </thead>
          <tbody id="production_drafts_body"></tbody>
        </table>
      </div>
      <div class="flex justify-center py-4 border-t">
        <ul id="production_drafts_pagination" class="flex gap-2 list-none"></ul>
      </div>
    </div>
  </div>

  <!-- View materials modal -->
  <div id="viewProductionMaterialsModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden mx-4 flex flex-col">
      <div class="p-4 border-b flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800">{{ trans('messages.materials', [], session('locale')) }}</h2>
        <button type="button" id="closeViewProductionMaterialsModal" class="p-1 text-gray-500 hover:text-gray-700">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <div class="overflow-auto flex-1 p-4">
        <table class="w-full text-sm" id="view_production_materials_table">
          <thead class="bg-gray-100">
            <tr>
              <th class="text-left px-2 py-2 font-semibold">{{ trans('messages.material_name', [], session('locale')) }}</th>
              <th class="text-center px-2 py-2 font-semibold">{{ trans('messages.unit', [], session('locale')) }}</th>
              <th class="text-center px-2 py-2 font-semibold">{{ trans('messages.unit_price', [], session('locale')) }}</th>
              <th class="text-center px-2 py-2 font-semibold">{{ trans('messages.quantity', [], session('locale')) }}</th>
              <th class="text-center px-2 py-2 font-semibold">{{ trans('messages.total', [], session('locale')) }}</th>
            </tr>
          </thead>
          <tbody id="view_production_materials_body"></tbody>
        </table>
        <div class="mt-3 pt-3 border-t flex justify-end">
          <span class="font-semibold text-gray-700">{{ trans('messages.total_cost', [], session('locale')) }}: <span id="view_production_total_cost" class="text-lg font-bold text-gray-900">0.00</span></span>
        </div>
      </div>
    </div>
  </div>
</main>

@include('layouts.footer')
@endsection
