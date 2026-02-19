@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.production_profile', [], session('locale')) }}</title>
@endpush

<style>
  body { font-family: 'IBM Plex Sans Arabic', sans-serif; }
  
  .profile-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
  }
  
  .material-badge {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
  }
  
  .material-badge:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  }
  
  .fade-in {
    animation: fadeIn 0.4s ease-out forwards;
  }
  
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .stat-box {
    border-left: 3px solid #e5e7eb;
    transition: all 0.2s ease;
  }
  
  .stat-box:hover {
    border-left-color: #6b7280;
    background: #f9fafb;
  }
  
  .stat-box.primary { border-left-color: #3b82f6; }
  .stat-box.success { border-left-color: #10b981; }
  .stat-box.warning { border-left-color: #f59e0b; }
  
  /* Modal styles */
  .modal-overlay {
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
  }
  
  /* Searchable select dropdown */
  .material-select-dropdown {
    display: none;
    position: absolute;
    left: 0;
    right: 0;
    top: 100%;
    z-index: 50;
    max-height: 200px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    margin-top: 2px;
  }
  
  .material-select-dropdown.show { display: block; }
  
  .material-select-option {
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
    font-size: 0.875rem;
  }
  
  .material-select-option:hover, .material-select-option.highlight {
    background: #f3f4f6;
  }
  
  .material-select-option:last-child { border-bottom: none; }
  
  /* Tabs */
  .profile-tabs { display: flex; gap: 0; border-bottom: 1px solid #e5e7eb; }
  .profile-tab { padding: 0.75rem 1.25rem; font-weight: 500; color: #6b7280; cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.2s; }
  .profile-tab:hover { color: #374151; }
  .profile-tab.active { color: #3b82f6; border-bottom-color: #3b82f6; }
  .profile-tab-content { display: none; }
  .profile-tab-content.active { display: block; }
</style>

<main class="flex-1 p-4 md:p-6 bg-gray-50 min-h-screen">
  <div class="max-w-full xl:max-w-[1400px] mx-auto">

    <!-- Header -->
    <div class="mb-4">
      <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-100 text-blue-800 text-sm font-bold">
        <span class="material-symbols-outlined text-base">precision_manufacturing</span>
        {{ trans('messages.production', [], session('locale')) }}
      </span>
    </div>
    <div class="flex items-center justify-between mb-6 fade-in flex-wrap gap-4">
      <div class="flex items-center gap-4">
        <a href="{{ url('view_production') }}" class="flex items-center justify-center w-10 h-10 rounded-lg bg-white border border-gray-200 hover:bg-gray-50 transition">
          <span class="material-symbols-outlined text-gray-600">arrow_back</span>
        </a>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ $production->stock->stock_name ?? 'Production' }}</h1>
          <p class="text-sm text-gray-500">{{ $production->batch_id ?? '-' }}</p>
        </div>
      </div>
      
      <!-- Action Buttons -->
      <div class="flex items-center gap-2 flex-wrap">
        @if($production->status === 'completed')
        @php $packaging = $production->packagings->first(); @endphp
        @if($packaging)
        <a href="{{ url('packaging/' . $packaging->id . '/profile') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
          <span class="material-symbols-outlined text-lg">inventory_2</span>
          {{ trans('messages.packaging', [], session('locale')) }}
        </a>
        @if($packaging->status === 'completed')
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-purple-50 text-purple-700 text-sm font-semibold border border-purple-200">
          <span class="material-symbols-outlined text-lg">inventory_2</span>
          {{ trans('messages.packaging', [], session('locale')) }} {{ trans('messages.completed', [], session('locale')) }}
        </span>
        @endif
        @else
        <a href="{{ url('production/' . $production->id . '/packaging') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
          <span class="material-symbols-outlined text-lg">inventory_2</span>
          {{ trans('messages.packaging', [], session('locale')) }}
        </a>
        @endif
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-50 text-green-700 text-sm font-semibold border border-green-200">
          <span class="material-symbols-outlined text-lg">verified</span>
          {{ trans('messages.completed', [], session('locale')) }}
        </span>
        @else
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-50 text-blue-700 text-sm font-semibold border border-blue-200">
          <span class="material-symbols-outlined text-lg">schedule</span>
          {{ trans('messages.under_process', [], session('locale')) }}
        </span>
        @endif
        @if($production->status !== 'completed')
        <button type="button" id="btn_add_material" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">
          <span class="material-symbols-outlined text-lg">add_circle</span>
          {{ trans('messages.add_material', [], session('locale')) }}
        </button>
        <button type="button" id="btn_remove_material" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:bg-red-700 transition">
          <span class="material-symbols-outlined text-lg">remove_circle</span>
          {{ trans('messages.remove_material', [], session('locale')) }}
        </button>
        <button type="button" id="btn_add_wastage" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 transition">
          <span class="material-symbols-outlined text-lg">delete_sweep</span>
          {{ trans('messages.add_wastage', [], session('locale')) }}
        </button>
        <button type="button" id="btn_complete_production" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-semibold hover:bg-green-700 transition">
          <span class="material-symbols-outlined text-lg">check_circle</span>
          {{ trans('messages.production_completion', [], session('locale')) }}
        </button>
        @else
        <button type="button" disabled class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-300 text-gray-500 text-sm font-semibold cursor-not-allowed">
          <span class="material-symbols-outlined text-lg">add_circle</span>
          {{ trans('messages.add_material', [], session('locale')) }}
        </button>
        <button type="button" disabled class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-300 text-gray-500 text-sm font-semibold cursor-not-allowed">
          <span class="material-symbols-outlined text-lg">remove_circle</span>
          {{ trans('messages.remove_material', [], session('locale')) }}
        </button>
        <button type="button" disabled class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-300 text-gray-500 text-sm font-semibold cursor-not-allowed">
          <span class="material-symbols-outlined text-lg">delete_sweep</span>
          {{ trans('messages.add_wastage', [], session('locale')) }}
        </button>
        @endif
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
      
      <!-- Left Column - Details (reduced width) -->
      <div class="xl:col-span-2 space-y-6 max-w-[300px] xl:max-w-none">
        
        <!-- Key Metrics Card -->
        <div class="profile-card rounded-xl shadow-sm fade-in">
          <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-1.5">
              <span class="material-symbols-outlined text-gray-500 text-lg">analytics</span>
              Key Metrics
            </h2>
          </div>
          <div class="p-4 space-y-3">
            @php
              $costPerUnit = ((float) $production->estimated_output > 0)
                  ? ((float) $production->total_amount / (float) $production->estimated_output)
                  : 0;
            @endphp

            <div class="stat-box primary pl-3 py-2">
              <p class="text-[10px] text-gray-500 uppercase tracking-wider mb-0.5">{{ trans('messages.estimated_output', [], session('locale')) }}</p>
              <p class="text-base font-bold text-gray-900">{{ number_format((float) $production->estimated_output, 0) }}</p>
            </div>

            @php
              $expectedOutputUnit = optional(optional($production->stock)->productionUnit)->unit_name ?? '';
            @endphp
            <div class="stat-box primary pl-3 py-2">
              <p class="text-[10px] text-gray-500 uppercase tracking-wider mb-0.5">{{ trans('messages.expected_output', [], session('locale')) }}</p>
              <p class="text-base font-bold text-gray-900">{{ $production->expected_output !== null ? number_format((float) $production->expected_output, 0) : '-' }}{{ $expectedOutputUnit ? ' ' . $expectedOutputUnit : '' }}</p>
            </div>
            
            @if($production->actual_output)
            <div class="stat-box pl-3 py-2" style="border-left-color: #8b5cf6;">
              <p class="text-[10px] text-gray-500 uppercase tracking-wider mb-0.5">{{ trans('messages.actual_output', [], session('locale')) }}</p>
              <p class="text-base font-bold text-purple-600">{{ number_format((float) $production->actual_output, 0) }}</p>
            </div>
            @endif
            
            <div class="stat-box success pl-3 py-2">
              <p class="text-[10px] text-gray-500 uppercase tracking-wider mb-0.5">{{ trans('messages.total_cost', [], session('locale')) }}</p>
              <p class="text-base font-bold text-emerald-600" id="display_total_cost">{{ number_format((float) $production->total_amount, 2) }}</p>
            </div>
            
            <div class="stat-box warning pl-3 py-2">
              <p class="text-[10px] text-gray-500 uppercase tracking-wider mb-0.5">{{ trans('messages.cost_per_unit', [], session('locale')) }}</p>
              <p class="text-base font-bold text-amber-600" id="display_cost_per_unit">{{ number_format($costPerUnit, 2) }}</p>
            </div>
          </div>
        </div>

        <!-- Production Info Card -->
        <div class="profile-card rounded-xl shadow-sm fade-in" style="animation-delay: 0.1s;">
          <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-1.5">
              <span class="material-symbols-outlined text-gray-500 text-lg">info</span>
              Production Info
            </h2>
          </div>
          <div class="p-4">
            <table class="w-full text-xs">
              <tbody class="divide-y divide-gray-100">
                <tr>
                  <td class="py-1.5 text-gray-500">{{ trans('messages.production_id', [], session('locale')) }}</td>
                  <td class="py-1.5 text-right font-medium text-gray-900">{{ $production->production_id ?? '-' }}</td>
                </tr>
                <tr>
                  <td class="py-1.5 text-gray-500">{{ trans('messages.filling_id', [], session('locale')) }}</td>
                  <td class="py-1.5 text-right font-medium text-gray-900">{{ $production->filling_id ?? '-' }}</td>
                </tr>
                <tr>
                  <td class="py-1.5 text-gray-500">{{ trans('messages.batch_id', [], session('locale')) }}</td>
                  <td class="py-1.5 text-right font-medium text-gray-900">{{ $production->batch_id ?? '-' }}</td>
                </tr>
                <tr>
                  <td class="py-1.5 text-gray-500">{{ trans('messages.production_date', [], session('locale')) }}</td>
                  <td class="py-1.5 text-right font-medium text-gray-900">{{ $production->production_date ? \Carbon\Carbon::parse($production->production_date)->format('d M Y') : '-' }}</td>
                </tr>
                <tr>
                  <td class="py-1.5 text-gray-500">{{ trans('messages.total_materials', [], session('locale')) }}</td>
                  <td class="py-1.5 text-right font-medium text-gray-900" id="display_total_materials">{{ $production->total_items ?? 0 }}</td>
                </tr>
                <tr>
                  <td class="py-1.5 text-gray-500">{{ trans('messages.total_quantity', [], session('locale')) }}</td>
                  <td class="py-1.5 text-right font-medium text-gray-900" id="display_total_quantity">{{ number_format((float) $production->total_quantity, 2) }}</td>
                </tr>
                <tr>
                  <td class="py-1.5 text-gray-500">{{ trans('messages.added_by', [], session('locale')) }}</td>
                  <td class="py-1.5 text-right font-medium text-gray-900">{{ $production->added_by ?? '-' }}</td>
                </tr>
                <tr>
                  <td class="py-1.5 text-gray-500">{{ trans('messages.created_at', [], session('locale')) }}</td>
                  <td class="py-1.5 text-right font-medium text-gray-900">{{ $production->created_at ? $production->created_at->format('d M Y, H:i') : '-' }}</td>
                </tr>
                @if($production->completed_at)
                <tr>
                  <td class="py-1.5 text-gray-500">{{ trans('messages.completed_at', [], session('locale')) }}</td>
                  <td class="py-1.5 text-right font-medium text-green-600">{{ $production->completed_at->format('d M Y, H:i') }}</td>
                </tr>
                @endif
              </tbody>
            </table>
          </div>
        </div>

        <!-- Notes Card (if exists) -->
        @if($production->notes)
        <div class="profile-card rounded-xl shadow-sm fade-in" style="animation-delay: 0.2s;">
          <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800 flex items-center gap-2">
              <span class="material-symbols-outlined text-gray-500">notes</span>
              {{ trans('messages.notes', [], session('locale')) }}
            </h2>
          </div>
          <div class="p-5">
            <p class="text-sm text-gray-600 leading-relaxed">{{ $production->notes }}</p>
          </div>
        </div>
        @endif
      </div>

      <!-- Right Column - Materials and Tabs -->
      <div class="xl:col-span-10">
        <!-- Materials overview card (compact) -->
        <div class="profile-card rounded-xl shadow-sm fade-in mb-6" style="animation-delay: 0.15s;">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
            <h2 class="font-semibold text-gray-800 flex items-center gap-2">
              <span class="material-symbols-outlined text-gray-500">science</span>
              {{ trans('messages.materials', [], session('locale')) }}
            </h2>
            <span class="text-sm text-gray-500" id="materials_count">{{ $production->total_items ?? 0 }} items</span>
          </div>
          <div class="p-6" id="materials_container">
            @php $materials = optional($production->details)->materials_json ?? []; @endphp
            @if(count($materials) > 0)
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3" id="materials_grid">
              @foreach($materials as $i => $m)
              @php
                $qty = (float) ($m['quantity'] ?? 0);
                $unit = $m['unit'] ?? '';
                $name = $m['material_name'] ?? 'Material';
              @endphp
              <div class="material-badge rounded-xl p-3 text-center">
                <div class="w-8 h-8 mx-auto mb-2 rounded-full bg-gray-100 flex items-center justify-center">
                  <span class="material-symbols-outlined text-gray-500 text-lg">water_drop</span>
                </div>
                <h3 class="font-medium text-gray-800 text-xs mb-1 truncate" title="{{ $name }}">{{ $name }}</h3>
                <div class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-gray-800 text-white text-xs font-medium">
                  {{ number_format($qty, 2) }} {{ $unit }}
                </div>
              </div>
              @endforeach
            </div>
            @else
            <div class="text-center py-10">
              <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-gray-400">inventory_2</span>
              </div>
              <p class="text-gray-500 text-sm">{{ trans('messages.no_data', [], session('locale')) }}</p>
            </div>
            @endif
          </div>
        </div>

        <!-- Tabs: Production History | Materials & Cost -->
        <div class="profile-card rounded-xl shadow-sm fade-in" style="animation-delay: 0.2s;">
          <div class="profile-tabs px-5 pt-4">
            <div class="profile-tab active" data-tab="history">
              <span class="material-symbols-outlined align-middle text-base mr-1">history</span>
              {{ trans('messages.production_history', [], session('locale')) }}
            </div>
            <div class="profile-tab" data-tab="materials-cost">
              <span class="material-symbols-outlined align-middle text-base mr-1">table_chart</span>
              {{ trans('messages.materials', [], session('locale')) }} & {{ trans('messages.total_cost', [], session('locale')) }}
            </div>
          </div>
          <div class="p-5">
            <!-- Tab: Production History -->
            <div class="profile-tab-content active" id="tab-history">
              <div class="overflow-x-auto">
                <table class="w-full text-sm" id="production_history_table">
                  <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                      <th class="text-left px-3 py-2 font-semibold text-gray-700">{{ trans('messages.date_time', [], session('locale')) }}</th>
                      <th class="text-left px-3 py-2 font-semibold text-gray-700">{{ trans('messages.action', [], session('locale')) }}</th>
                      <th class="text-left px-3 py-2 font-semibold text-gray-700">{{ trans('messages.material_name', [], session('locale')) }}</th>
                      <th class="text-center px-3 py-2 font-semibold text-gray-700">{{ trans('messages.quantity', [], session('locale')) }}</th>
                      <th class="text-left px-3 py-2 font-semibold text-gray-700">{{ trans('messages.added_by', [], session('locale')) }}</th>
                      <th class="text-left px-3 py-2 font-semibold text-gray-700">{{ trans('messages.notes', [], session('locale')) }}</th>
                    </tr>
                  </thead>
                  <tbody id="production_history_body">
                    <tr><td colspan="6" class="px-3 py-4 text-center text-gray-500">{{ trans('messages.loading', [], session('locale')) }}</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
            <!-- Tab: Materials & Cost -->
            <div class="profile-tab-content" id="tab-materials-cost">
              @php $materials = optional($production->details)->materials_json ?? []; @endphp
              @if(count($materials) > 0)
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                      <th class="text-left px-3 py-2 font-semibold text-gray-700">{{ trans('messages.material_name', [], session('locale')) }}</th>
                      <th class="text-center px-3 py-2 font-semibold text-gray-700">{{ trans('messages.quantity', [], session('locale')) }}</th>
                      <th class="text-center px-3 py-2 font-semibold text-gray-700">{{ trans('messages.unit', [], session('locale')) }}</th>
                      <th class="text-right px-3 py-2 font-semibold text-gray-700">{{ trans('messages.unit_price', [], session('locale')) }}</th>
                      <th class="text-right px-3 py-2 font-semibold text-gray-700">{{ trans('messages.total', [], session('locale')) }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($materials as $m)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                      <td class="px-3 py-2 font-medium">{{ $m['material_name'] ?? '-' }}</td>
                      <td class="px-3 py-2 text-center">{{ number_format((float)($m['quantity'] ?? 0), 2) }}</td>
                      <td class="px-3 py-2 text-center">{{ $m['unit'] ?? '-' }}</td>
                      <td class="px-3 py-2 text-right">{{ number_format((float)($m['unit_price'] ?? 0), 2) }}</td>
                      <td class="px-3 py-2 text-right font-semibold">{{ number_format((float)($m['total'] ?? 0), 2) }}</td>
                    </tr>
                    @endforeach
                  </tbody>
                  <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                      <td colspan="4" class="px-3 py-3 font-bold text-gray-800">{{ trans('messages.total_cost', [], session('locale')) }} ({{ trans('messages.materials', [], session('locale')) }})</td>
                      <td class="px-3 py-3 text-right font-bold text-emerald-600 text-base">{{ number_format((float) $production->total_amount, 2) }}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
              @else
              <div class="text-center py-12 text-gray-500">{{ trans('messages.no_data', [], session('locale')) }}</div>
              @endif
            </div>
          </div>
        </div>
      </div>
      
    </div>
  </div>
</main>

<!-- Add Material Modal -->
<div id="addMaterialModal" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
      <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
        <span class="material-symbols-outlined text-blue-600">add_circle</span>
        {{ trans('messages.add_material', [], session('locale')) }}
      </h3>
      <button type="button" class="close-modal p-1 text-gray-400 hover:text-gray-600">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <div class="relative">
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('messages.material_name', [], session('locale')) }}</label>
        <input type="text" id="add_material_search" autocomplete="off" placeholder="{{ trans('messages.search_material_placeholder', [], session('locale')) }}" class="w-full h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
        <input type="hidden" id="add_material_id" />
        <div id="add_material_dropdown" class="material-select-dropdown"></div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('messages.quantity', [], session('locale')) }}</label>
        <input type="number" id="add_material_qty" min="0" step="any" placeholder="0.00" class="w-full h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('messages.notes', [], session('locale')) }}</label>
        <textarea id="add_material_notes" rows="3" placeholder="{{ trans('messages.notes_placeholder', [], session('locale')) }}" class="w-full rounded-lg px-4 py-2 border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
      </div>
    </div>
    <div class="p-5 border-t border-gray-100 flex justify-end gap-3">
      <button type="button" class="close-modal px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">{{ trans('messages.cancel', [], session('locale')) }}</button>
      <button type="button" id="confirm_add_material" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700">{{ trans('messages.add', [], session('locale')) }}</button>
    </div>
  </div>
</div>

<!-- Remove Material Modal -->
<div id="removeMaterialModal" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
      <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
        <span class="material-symbols-outlined text-red-600">remove_circle</span>
        {{ trans('messages.remove_material', [], session('locale')) }}
      </h3>
      <button type="button" class="close-modal p-1 text-gray-400 hover:text-gray-600">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <div class="relative">
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('messages.material_name', [], session('locale')) }}</label>
        <input type="text" id="remove_material_search" autocomplete="off" placeholder="{{ trans('messages.search_material_placeholder', [], session('locale')) }}" class="w-full h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-red-500 focus:border-red-500" />
        <input type="hidden" id="remove_material_id" />
        <div id="remove_material_dropdown" class="material-select-dropdown"></div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('messages.quantity', [], session('locale')) }}</label>
        <input type="number" id="remove_material_qty" min="0" step="any" placeholder="0.00" class="w-full h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-red-500 focus:border-red-500" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('messages.notes', [], session('locale')) }}</label>
        <textarea id="remove_material_notes" rows="3" placeholder="{{ trans('messages.notes_placeholder', [], session('locale')) }}" class="w-full rounded-lg px-4 py-2 border border-gray-300 focus:ring-2 focus:ring-red-500 focus:border-red-500"></textarea>
      </div>
    </div>
    <div class="p-5 border-t border-gray-100 flex justify-end gap-3">
      <button type="button" class="close-modal px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">{{ trans('messages.cancel', [], session('locale')) }}</button>
      <button type="button" id="confirm_remove_material" class="px-5 py-2.5 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700">{{ trans('messages.remove', [], session('locale')) }}</button>
    </div>
  </div>
</div>

<!-- Add Wastage Modal -->
<div id="addWastageModal" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
      <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
        <span class="material-symbols-outlined text-amber-600">delete_sweep</span>
        {{ trans('messages.add_wastage', [], session('locale')) }}
      </h3>
      <button type="button" class="close-modal p-1 text-gray-400 hover:text-gray-600">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ trans('messages.wastage_type', [], session('locale')) }}</label>
        <div class="space-y-2">
          <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
            <input type="radio" name="wastage_type" value="evaporation" class="w-4 h-4 text-amber-600 border-gray-300 focus:ring-amber-500" />
            <span class="text-sm text-gray-700">{{ trans('messages.evaporation', [], session('locale')) }}</span>
          </label>
          <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
            <input type="radio" name="wastage_type" value="spillage" class="w-4 h-4 text-amber-600 border-gray-300 focus:ring-amber-500" />
            <span class="text-sm text-gray-700">{{ trans('messages.spillage', [], session('locale')) }}</span>
          </label>
          <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
            <input type="radio" name="wastage_type" value="batch_failure" class="w-4 h-4 text-amber-600 border-gray-300 focus:ring-amber-500" />
            <span class="text-sm text-gray-700">{{ trans('messages.batch_failure', [], session('locale')) }}</span>
          </label>
        </div>
      </div>
      <div class="relative">
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('messages.material_name', [], session('locale')) }}</label>
        <input type="text" id="wastage_material_search" autocomplete="off" placeholder="{{ trans('messages.search_material_placeholder', [], session('locale')) }}" class="w-full h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500" />
        <input type="hidden" id="wastage_material_id" />
        <div id="wastage_material_dropdown" class="material-select-dropdown"></div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('messages.quantity', [], session('locale')) }}</label>
        <input type="number" id="wastage_qty" min="0.01" step="0.01" placeholder="0.00" class="w-full h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('messages.notes', [], session('locale')) }}</label>
        <textarea id="wastage_notes" rows="3" placeholder="{{ trans('messages.notes_placeholder', [], session('locale')) }}" class="w-full rounded-lg px-4 py-2 border border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500"></textarea>
      </div>
    </div>
    <div class="p-5 border-t border-gray-100 flex justify-end gap-3">
      <button type="button" class="close-modal px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">{{ trans('messages.cancel', [], session('locale')) }}</button>
      <button type="button" id="confirm_add_wastage" class="px-5 py-2.5 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700">{{ trans('messages.add', [], session('locale')) }}</button>
    </div>
  </div>
</div>

<!-- Materials Table Modal -->
<div id="materialsTableModal" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden mx-4 flex flex-col">
    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
      <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
        <span class="material-symbols-outlined text-gray-600">table_chart</span>
        {{ trans('messages.view_materials_table', [], session('locale')) }}
      </h3>
      <button type="button" class="close-modal p-1 text-gray-400 hover:text-gray-600">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div class="overflow-auto flex-1 p-5">
      @php $materials = optional($production->details)->materials_json ?? []; @endphp
      @if(count($materials) > 0)
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-3 py-2 font-semibold text-gray-700">{{ trans('messages.material_name', [], session('locale')) }}</th>
            <th class="text-center px-3 py-2 font-semibold text-gray-700">{{ trans('messages.quantity', [], session('locale')) }}</th>
            <th class="text-center px-3 py-2 font-semibold text-gray-700">{{ trans('messages.unit', [], session('locale')) }}</th>
            <th class="text-right px-3 py-2 font-semibold text-gray-700">{{ trans('messages.unit_price', [], session('locale')) }}</th>
            <th class="text-right px-3 py-2 font-semibold text-gray-700">{{ trans('messages.total', [], session('locale')) }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($materials as $m)
          <tr class="border-b border-gray-100 hover:bg-gray-50">
            <td class="px-3 py-2 font-medium">{{ $m['material_name'] ?? '-' }}</td>
            <td class="px-3 py-2 text-center">{{ number_format((float)($m['quantity'] ?? 0), 2) }}</td>
            <td class="px-3 py-2 text-center">{{ $m['unit'] ?? '-' }}</td>
            <td class="px-3 py-2 text-right">{{ number_format((float)($m['unit_price'] ?? 0), 2) }}</td>
            <td class="px-3 py-2 text-right font-semibold">{{ number_format((float)($m['total'] ?? 0), 2) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      @else
      <p class="text-center text-gray-500 py-8">{{ trans('messages.no_data', [], session('locale')) }}</p>
      @endif
    </div>
  </div>
</div>

<!-- Complete Production Modal -->
<div id="completeProductionModal" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
      <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
        <span class="material-symbols-outlined text-green-600">check_circle</span>
        {{ trans('messages.production_completion', [], session('locale')) }}
      </h3>
      <button type="button" class="close-complete-modal p-1 text-gray-400 hover:text-gray-600">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('messages.expected_output', [], session('locale')) ?: 'Expected output' }}</label>
        <div class="h-11 flex items-center rounded-lg px-4 bg-gray-100 border border-gray-200 text-gray-900 font-semibold" id="complete_expected_output_display">
          @php
            $displayExpected = $production->expected_output ?? $production->estimated_output;
            $unitSuffix = optional($production->stock->productionUnit)->unit_name ? ' ' . $production->stock->productionUnit->unit_name : '';
          @endphp
          {{ number_format((float) $displayExpected, 2) }}{{ $unitSuffix }}
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('messages.actual_output', [], session('locale')) }}</label>
        <input type="number" id="complete_actual_output" min="0" step="0.01" placeholder="0.00" class="w-full h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('messages.notes', [], session('locale')) }}</label>
        <textarea id="complete_production_notes" rows="3" placeholder="{{ trans('messages.notes_placeholder', [], session('locale')) }}" class="w-full rounded-lg px-4 py-2 border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
      </div>
    </div>
    <div class="p-5 border-t border-gray-100 flex justify-end gap-3">
      <button type="button" class="close-complete-modal px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50">{{ trans('messages.cancel', [], session('locale')) }}</button>
      <button type="button" id="confirm_complete_production" class="px-5 py-2.5 rounded-lg bg-green-600 text-white font-medium hover:bg-green-700">{{ trans('messages.yes_complete', [], session('locale')) }}</button>
    </div>
  </div>
</div>

<script>
var PRODUCTION_ID = {{ $production->id }};
var ESTIMATED_OUTPUT = {{ (float) $production->estimated_output }};
</script>

@include('layouts.footer')
@endsection
