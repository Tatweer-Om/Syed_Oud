@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.packaging_profile', [], session('locale')) }}</title>
@endpush

<style>
  .profile-card { background: #fff; border: 1px solid #e5e7eb; }
  .material-badge { background: #f9fafb; border: 1px solid #e5e7eb; transition: all 0.3s ease; }
  .material-badge:hover { background: #f3f4f6; border-color: #d1d5db; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
  .fade-in { animation: fadeIn 0.4s ease-out forwards; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  .stat-box { border-left: 3px solid #e5e7eb; }
  .stat-box.primary { border-left-color: #3b82f6; }
  .stat-box.success { border-left-color: #10b981; }
  .stat-box.warning { border-left-color: #f59e0b; }
  .modal-overlay { background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
  .material-select-dropdown { display: none; position: absolute; left: 0; right: 0; top: 100%; z-index: 50; max-height: 200px; overflow-y: auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.15); margin-top: 2px; }
  .material-select-dropdown.show { display: block; }
  .material-select-option { padding: 0.5rem 0.75rem; cursor: pointer; border-bottom: 1px solid #f3f4f6; font-size: 0.875rem; }
  .material-select-option:hover, .material-select-option.highlight { background: #f3f4f6; }
  .material-select-option:last-child { border-bottom: none; }
</style>

<main class="flex-1 p-4 md:p-6 bg-gray-50 min-h-screen">
  <div class="max-w-full xl:max-w-[1400px] mx-auto">

    <div class="mb-4">
      <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-purple-100 text-purple-800 text-sm font-bold">
        <span class="material-symbols-outlined text-base">inventory_2</span>
        {{ trans('messages.packaging', [], session('locale')) }}
      </span>
    </div>
    <div class="flex items-center justify-between mb-6 fade-in flex-wrap gap-4">
      <div class="flex items-center gap-4">
        <a href="{{ url('production/' . $packaging->production_id . '/profile') }}" class="flex items-center justify-center w-10 h-10 rounded-lg bg-white border border-gray-200 hover:bg-gray-50 transition">
          <span class="material-symbols-outlined text-gray-600">arrow_back</span>
        </a>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $packaging->stock->stock_name ?? 'Packaging' }}</h1>
          <p class="text-sm text-gray-500">{{ $packaging->batch_id ?? '-' }}</p>
        </div>
      </div>

      <div class="flex items-center gap-2 flex-wrap">
        @if($packaging->status === 'completed')
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-50 text-green-700 text-sm font-semibold border border-green-200">{{ trans('messages.completed', [], session('locale')) }}</span>
        <button type="button" disabled class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-300 text-gray-500 text-sm font-semibold cursor-not-allowed">{{ trans('messages.add_material', [], session('locale')) }}</button>
        <button type="button" disabled class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-300 text-gray-500 text-sm font-semibold cursor-not-allowed">{{ trans('messages.remove_material', [], session('locale')) }}</button>
        <button type="button" disabled class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-300 text-gray-500 text-sm font-semibold cursor-not-allowed">{{ trans('messages.add_wastage', [], session('locale')) }}</button>
        @else
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-50 text-blue-700 text-sm font-semibold border border-blue-200">{{ trans('messages.under_process', [], session('locale')) }}</span>
        <button type="button" id="btn_add_material" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">{{ trans('messages.add_material', [], session('locale')) }}</button>
        <button type="button" id="btn_remove_material" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:bg-red-700">{{ trans('messages.remove_material', [], session('locale')) }}</button>
        <button type="button" id="btn_add_wastage" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700">{{ trans('messages.add_wastage', [], session('locale')) }}</button>
        <button type="button" id="btn_complete_packaging" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-semibold hover:bg-green-700">{{ trans('messages.complete_packaging', [], session('locale')) }}</button>
        @endif
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
      <div class="xl:col-span-1 space-y-6">
        @php
          $costPerUnit = ((float)$packaging->estimated_output > 0) ? ((float)$packaging->total_amount / (float)$packaging->estimated_output) : 0;
        @endphp
        <div class="profile-card rounded-xl shadow-sm fade-in">
          <div class="px-5 py-4 border-b border-gray-100"><h2 class="font-semibold text-gray-800">Key Metrics</h2></div>
          <div class="p-5 space-y-4">
            <div class="stat-box primary pl-4 py-3">
              <p class="text-xs text-gray-500 uppercase mb-1">{{ trans('messages.estimated_output', [], session('locale')) }}</p>
              <p class="text-2xl font-bold">{{ number_format((float)$packaging->estimated_output, 0) }}</p>
            </div>
            @if($packaging->actual_output)
            <div class="stat-box pl-4 py-3" style="border-left-color:#8b5cf6;">
              <p class="text-xs text-gray-500 uppercase mb-1">{{ trans('messages.actual_output', [], session('locale')) }}</p>
              <p class="text-2xl font-bold text-purple-600">{{ number_format((float)$packaging->actual_output, 0) }}</p>
            </div>
            @endif
            <div class="stat-box success pl-4 py-3">
              <p class="text-xs text-gray-500 uppercase mb-1">{{ trans('messages.total_cost', [], session('locale')) }}</p>
              <p class="text-2xl font-bold text-emerald-600">{{ number_format((float)$packaging->total_amount, 2) }}</p>
            </div>
            <div class="stat-box warning pl-4 py-3">
              <p class="text-xs text-gray-500 uppercase mb-1">{{ trans('messages.cost_per_unit', [], session('locale')) }}</p>
              <p class="text-2xl font-bold text-amber-600">{{ number_format($costPerUnit, 2) }}</p>
            </div>
          </div>
        </div>

        <div class="profile-card rounded-xl shadow-sm fade-in">
          <div class="px-5 py-4 border-b border-gray-100"><h2 class="font-semibold text-gray-800">{{ trans('messages.packaging', [], session('locale')) }} Info</h2></div>
          <div class="p-5">
            <table class="w-full text-sm">
              <tbody class="divide-y divide-gray-100">
                <tr><td class="py-2.5 text-gray-500">{{ trans('messages.batch_id', [], session('locale')) }}</td><td class="py-2.5 text-right font-medium">{{ $packaging->batch_id ?? '-' }}</td></tr>
                <tr><td class="py-2.5 text-gray-500">{{ trans('messages.total_materials', [], session('locale')) }}</td><td class="py-2.5 text-right font-medium">{{ $packaging->total_items ?? 0 }}</td></tr>
                <tr><td class="py-2.5 text-gray-500">{{ trans('messages.total_quantity', [], session('locale')) }}</td><td class="py-2.5 text-right font-medium">{{ number_format((float)$packaging->total_quantity, 2) }}</td></tr>
                <tr><td class="py-2.5 text-gray-500">{{ trans('messages.added_by', [], session('locale')) }}</td><td class="py-2.5 text-right font-medium">{{ $packaging->added_by ?? '-' }}</td></tr>
                @if($packaging->completed_at)
                <tr><td class="py-2.5 text-gray-500">{{ trans('messages.completed_at', [], session('locale')) }}</td><td class="py-2.5 text-right font-medium text-green-600">{{ $packaging->completed_at->format('d M Y, H:i') }}</td></tr>
                @endif
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="xl:col-span-2">
        <div class="profile-card rounded-xl shadow-sm fade-in">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">{{ trans('messages.materials', [], session('locale')) }}</h2>
            @if(count(optional($packaging->details)->materials_json ?? []) > 0)
            <button type="button" id="btn_view_materials_table" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-600 text-white text-sm font-medium hover:bg-gray-700">{{ trans('messages.view_materials_table', [], session('locale')) }}</button>
            @endif
          </div>
          <div class="p-6">
            @php $materials = optional($packaging->details)->materials_json ?? []; @endphp
            @if(count($materials) > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
              @foreach($materials as $m)
              <div class="material-badge rounded-xl p-4 text-center">
                <h3 class="font-semibold text-gray-800 text-sm mb-2">{{ $m['material_name'] ?? 'Material' }}</h3>
                <div class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-gray-800 text-white text-xs font-medium">{{ number_format((float)($m['quantity'] ?? 0), 2) }} {{ $m['unit'] ?? '' }}</div>
              </div>
              @endforeach
            </div>
            <div class="mt-6 pt-5 border-t flex flex-wrap gap-6 text-sm">
              <span>{{ trans('messages.total_materials', [], session('locale')) }}: <strong>{{ count($materials) }}</strong></span>
              <span>{{ trans('messages.total_quantity', [], session('locale')) }}: <strong>{{ number_format((float)$packaging->total_quantity, 2) }}</strong></span>
              <span>{{ trans('messages.total_cost', [], session('locale')) }}: <strong class="text-emerald-600">{{ number_format((float)$packaging->total_amount, 2) }}</strong></span>
            </div>
            @else
            <div class="text-center py-16 text-gray-500">{{ trans('messages.no_data', [], session('locale')) }}</div>
            @endif
          </div>
        </div>

        <div class="profile-card rounded-xl shadow-sm fade-in mt-6">
          <div class="px-5 py-4 border-b border-gray-100"><h2 class="font-semibold text-gray-800">{{ trans('messages.packaging_history', [], session('locale')) }}</h2></div>
          <div class="p-5 overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b"><tr><th class="text-left px-3 py-2 font-semibold">{{ trans('messages.date_time', [], session('locale')) }}</th><th class="text-left px-3 py-2 font-semibold">{{ trans('messages.action', [], session('locale')) }}</th><th class="text-left px-3 py-2 font-semibold">{{ trans('messages.material_name', [], session('locale')) }}</th><th class="text-center px-3 py-2 font-semibold">{{ trans('messages.quantity', [], session('locale')) }}</th><th class="text-left px-3 py-2 font-semibold">{{ trans('messages.added_by', [], session('locale')) }}</th><th class="text-left px-3 py-2 font-semibold">{{ trans('messages.notes', [], session('locale')) }}</th></tr></thead>
              <tbody id="packaging_history_body"><tr><td colspan="6" class="px-3 py-4 text-center text-gray-500">{{ trans('messages.loading', [], session('locale')) }}</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Add Material Modal -->
<div id="addMaterialModal" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
    <div class="p-5 border-b flex items-center justify-between">
      <h3 class="text-lg font-bold">{{ trans('messages.add_material', [], session('locale')) }}</h3>
      <button type="button" class="close-modal p-1 text-gray-400"><span class="material-symbols-outlined">close</span></button>
    </div>
    <div class="p-5 space-y-4">
      <div class="relative">
        <label class="block text-sm font-medium mb-1">{{ trans('messages.material_name', [], session('locale')) }}</label>
        <input type="text" id="add_material_search" autocomplete="off" placeholder="{{ trans('messages.search_material_placeholder', [], session('locale')) }}" class="w-full h-11 rounded-lg px-4 border border-gray-300" />
        <input type="hidden" id="add_material_id" />
        <div id="add_material_dropdown" class="material-select-dropdown"></div>
      </div>
      <div><label class="block text-sm font-medium mb-1">{{ trans('messages.quantity', [], session('locale')) }}</label><input type="number" id="add_material_qty" min="0.01" step="0.01" class="w-full h-11 rounded-lg px-4 border border-gray-300" /></div>
      <div><label class="block text-sm font-medium mb-1">{{ trans('messages.notes', [], session('locale')) }}</label><textarea id="add_material_notes" rows="3" class="w-full rounded-lg px-4 py-2 border border-gray-300"></textarea></div>
    </div>
    <div class="p-5 border-t flex justify-end gap-3">
      <button type="button" class="close-modal px-5 py-2.5 rounded-lg border border-gray-300">{{ trans('messages.cancel', [], session('locale')) }}</button>
      <button type="button" id="confirm_add_material" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white">{{ trans('messages.add', [], session('locale')) }}</button>
    </div>
  </div>
</div>

<!-- Remove Material Modal -->
<div id="removeMaterialModal" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
    <div class="p-5 border-b flex items-center justify-between">
      <h3 class="text-lg font-bold">{{ trans('messages.remove_material', [], session('locale')) }}</h3>
      <button type="button" class="close-modal p-1 text-gray-400"><span class="material-symbols-outlined">close</span></button>
    </div>
    <div class="p-5 space-y-4">
      <div class="relative">
        <label class="block text-sm font-medium mb-1">{{ trans('messages.material_name', [], session('locale')) }}</label>
        <input type="text" id="remove_material_search" autocomplete="off" placeholder="{{ trans('messages.search_material_placeholder', [], session('locale')) }}" class="w-full h-11 rounded-lg px-4 border border-gray-300" />
        <input type="hidden" id="remove_material_id" />
        <div id="remove_material_dropdown" class="material-select-dropdown"></div>
      </div>
      <div><label class="block text-sm font-medium mb-1">{{ trans('messages.quantity', [], session('locale')) }}</label><input type="number" id="remove_material_qty" min="0.01" step="0.01" class="w-full h-11 rounded-lg px-4 border border-gray-300" /></div>
      <div><label class="block text-sm font-medium mb-1">{{ trans('messages.notes', [], session('locale')) }}</label><textarea id="remove_material_notes" rows="3" class="w-full rounded-lg px-4 py-2 border border-gray-300"></textarea></div>
    </div>
    <div class="p-5 border-t flex justify-end gap-3">
      <button type="button" class="close-modal px-5 py-2.5 rounded-lg border border-gray-300">{{ trans('messages.cancel', [], session('locale')) }}</button>
      <button type="button" id="confirm_remove_material" class="px-5 py-2.5 rounded-lg bg-red-600 text-white">{{ trans('messages.remove', [], session('locale')) }}</button>
    </div>
  </div>
</div>

<!-- Add Wastage Modal -->
<div id="addWastageModal" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
    <div class="p-5 border-b flex items-center justify-between">
      <h3 class="text-lg font-bold">{{ trans('messages.add_wastage', [], session('locale')) }}</h3>
      <button type="button" class="close-modal p-1 text-gray-400"><span class="material-symbols-outlined">close</span></button>
    </div>
    <div class="p-5 space-y-4">
      <div class="relative">
        <label class="block text-sm font-medium mb-1">{{ trans('messages.material_name', [], session('locale')) }}</label>
        <input type="text" id="wastage_material_search" autocomplete="off" placeholder="{{ trans('messages.search_material_placeholder', [], session('locale')) }}" class="w-full h-11 rounded-lg px-4 border border-gray-300" />
        <input type="hidden" id="wastage_material_id" />
        <div id="wastage_material_dropdown" class="material-select-dropdown"></div>
      </div>
      <div><label class="block text-sm font-medium mb-1">{{ trans('messages.quantity', [], session('locale')) }}</label><input type="number" id="wastage_qty" min="0.01" step="0.01" class="w-full h-11 rounded-lg px-4 border border-gray-300" /></div>
      <div><label class="block text-sm font-medium mb-1">{{ trans('messages.notes', [], session('locale')) }}</label><textarea id="wastage_notes" rows="3" class="w-full rounded-lg px-4 py-2 border border-gray-300"></textarea></div>
    </div>
    <div class="p-5 border-t flex justify-end gap-3">
      <button type="button" class="close-modal px-5 py-2.5 rounded-lg border border-gray-300">{{ trans('messages.cancel', [], session('locale')) }}</button>
      <button type="button" id="confirm_add_wastage" class="px-5 py-2.5 rounded-lg bg-amber-600 text-white">{{ trans('messages.add', [], session('locale')) }}</button>
    </div>
  </div>
</div>

<!-- Complete Packaging Modal -->
<div id="completePackagingModal" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
    <div class="p-5 border-b flex items-center justify-between">
      <h3 class="text-lg font-bold">{{ trans('messages.complete_packaging', [], session('locale')) }}</h3>
      <button type="button" class="close-modal p-1 text-gray-400"><span class="material-symbols-outlined">close</span></button>
    </div>
    <div class="p-5 space-y-4">
      <div>
        <label class="block text-sm font-medium mb-1">{{ trans('messages.estimated_output', [], session('locale')) }}</label>
        <input type="text" id="complete_estimated_output" value="{{ number_format((float)$packaging->estimated_output, 2) }}" readonly class="w-full h-11 rounded-lg px-4 border border-gray-200 bg-gray-50" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">{{ trans('messages.actual_output', [], session('locale')) }}</label>
        <input type="number" id="complete_actual_output" min="0.01" step="0.01" placeholder="0.00" class="w-full h-11 rounded-lg px-4 border border-gray-300" />
      </div>
      <div class="p-4 bg-yellow-50 rounded-lg">
        <p class="text-sm text-yellow-700">{{ trans('messages.actual_output_info', [], session('locale')) }}</p>
      </div>
    </div>
    <div class="p-5 border-t flex justify-end gap-3">
      <button type="button" class="close-modal px-5 py-2.5 rounded-lg border border-gray-300">{{ trans('messages.cancel', [], session('locale')) }}</button>
      <button type="button" id="confirm_complete_packaging" class="px-5 py-2.5 rounded-lg bg-green-600 text-white">{{ trans('messages.complete', [], session('locale')) }}</button>
    </div>
  </div>
</div>

<!-- Materials Table Modal -->
<div id="materialsTableModal" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden mx-4 flex flex-col">
    <div class="p-5 border-b flex items-center justify-between">
      <h3 class="text-lg font-bold">{{ trans('messages.view_materials_table', [], session('locale')) }}</h3>
      <button type="button" class="close-modal p-1 text-gray-400"><span class="material-symbols-outlined">close</span></button>
    </div>
    <div class="overflow-auto flex-1 p-5">
      @php $matList = optional($packaging->details)->materials_json ?? []; @endphp
      @if(count($matList) > 0)
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b"><tr><th class="text-left px-3 py-2 font-semibold">{{ trans('messages.material_name', [], session('locale')) }}</th><th class="text-center px-3 py-2 font-semibold">{{ trans('messages.quantity', [], session('locale')) }}</th><th class="text-center px-3 py-2 font-semibold">{{ trans('messages.unit', [], session('locale')) }}</th><th class="text-right px-3 py-2 font-semibold">{{ trans('messages.unit_price', [], session('locale')) }}</th><th class="text-right px-3 py-2 font-semibold">{{ trans('messages.total', [], session('locale')) }}</th></tr></thead>
        <tbody>
          @foreach($matList as $m)
          <tr class="border-b"><td class="px-3 py-2 font-medium">{{ $m['material_name'] ?? '-' }}</td><td class="px-3 py-2 text-center">{{ number_format((float)($m['quantity'] ?? 0), 2) }}</td><td class="px-3 py-2 text-center">{{ $m['unit'] ?? '-' }}</td><td class="px-3 py-2 text-right">{{ number_format((float)($m['unit_price'] ?? 0), 2) }}</td><td class="px-3 py-2 text-right font-semibold">{{ number_format((float)($m['total'] ?? 0), 2) }}</td></tr>
          @endforeach
        </tbody>
      </table>
      @else
      <p class="text-center text-gray-500 py-8">{{ trans('messages.no_data', [], session('locale')) }}</p>
      @endif
    </div>
  </div>
</div>

<script>
var PACKAGING_ID = {{ $packaging->id }};
var PRODUCTION_ID = {{ $packaging->production_id }};
</script>
@include('layouts.footer')
@endsection
