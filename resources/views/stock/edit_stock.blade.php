@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.edit_stock', [], session('locale')) ?: 'Edit Stock' }}</title>
@endpush

<style>
  body { font-family: 'IBM Plex Sans Arabic', sans-serif; }
  .stock-unit-dropdown { display: none; position: absolute; left: 0; right: 0; top: 100%; z-index: 1060; max-height: 200px; overflow-y: auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.15); margin-top: 2px; }
  .stock-unit-dropdown.show { display: block; }
  .stock-unit-option { padding: 0.4rem 0.75rem; cursor: pointer; border-bottom: 1px solid #f3f4f6; font-size: 0.875rem; }
  .stock-unit-option:hover, .stock-unit-option.highlight { background: #fef2f2; }
  .stock-unit-wrap { position: relative; }
</style>
<main class="flex-1 p-4 md:p-6">
  <div class="max-w-7xl mx-auto">
    
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <a href="{{ url('view_stock') }}{{ isset($returnPage) && $returnPage > 1 ? '?page=' . $returnPage : '' }}" 
           class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors">
          <span class="material-symbols-outlined text-gray-600">arrow_back</span>
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
          {{ trans('messages.edit_stock', [], session('locale')) ?: 'Edit Stock' }}
        </h1>
      </div>
    </div>

    <form id="update_stock" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
      @csrf
      <input type="hidden" value="{{ $stock->id }}" name="stock_id" id="stock_id"/>
      <input type="hidden" name="return_page" value="{{ $returnPage ?? 1 }}" />
      
      <div class="p-6 sm:p-8 space-y-6">
        
        <div class="space-y-4">
          <div class="flex items-center gap-2 pb-3 border-b border-gray-200">
            <span class="material-symbols-outlined text-primary text-xl">info</span>
            <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.basic_info', [], session('locale')) }}</h2>
          </div>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <label class="flex flex-col">
              <span class="text-xs font-semibold text-gray-700 mb-1">{{ trans('messages.stock_name', [], session('locale')) ?: 'Stock Name' }}</span>
              <input type="text" name="stock_name" id="stock_name"
                     placeholder="{{ trans('messages.stock_name_placeholder', [], session('locale')) ?: 'Enter stock name' }}"
                     value="{{ $stock->stock_name ?? '' }}"
                     class="h-10 rounded-lg px-3 text-sm border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
            </label>
            <label class="flex flex-col">
              <span class="text-xs font-semibold text-gray-700 mb-1">{{ trans('messages.barcode', [], session('locale')) }}</span>
              <div class="flex gap-1" x-data="{ generating: false, generated: false }">
                <input type="text" name="barcode" id="barcode" value="{{ $stock->barcode ?? '' }}"
                       class="h-10 flex-1 rounded-lg px-3 text-sm border border-gray-300 focus:ring-2 focus:ring-primary/50 transition-all min-w-0"
                       :class="generated ? 'border-green-500 bg-green-50' : ''" />
                <button type="button" @click="generating = true; setTimeout(() => { var v = Math.floor(100000000000 + Math.random() * 900000000000); document.getElementById('barcode').value = v; generating = false; generated = true; setTimeout(() => generated = false, 2000); }, 500);" :disabled="generating"
                        class="h-10 px-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 flex items-center justify-center shrink-0" title="{{ trans('messages.generate', [], session('locale')) }}">
                  <span class="material-symbols-outlined text-lg" :class="generating ? 'animate-spin' : ''">qr_code_2</span>
                </button>
              </div>
            </label>
            <label class="flex flex-col">
              <span class="text-xs font-semibold text-gray-700 mb-1">{{ trans('messages.category', [], session('locale')) }}</span>
              <select class="h-10 rounded-lg px-3 text-sm border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" name="category_id" id="category_id">
                <option value="">{{ trans('messages.choose', [], session('locale')) }}</option>
                @foreach($categories as $category)
                  <option value="{{ $category->id }}" {{ $stock->category_id == $category->id ? 'selected' : '' }}>{{ $category->category_name }}</option>
                @endforeach
              </select>
            </label>
            <label class="flex flex-col">
              <span class="text-xs font-semibold text-gray-700 mb-1">{{ trans('messages.production_unit', [], session('locale')) ?: 'Production Unit' }}</span>
              <div class="stock-unit-wrap">
                <input type="text" id="production_unit_search" autocomplete="off"
                       placeholder="{{ trans('messages.search_unit_placeholder', [], session('locale')) ?: 'Search unit...' }}"
                       value="{{ $stock->productionUnit->unit_name ?? '' }}"
                       class="h-10 w-full rounded-lg px-3 text-sm border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
                <input type="hidden" name="production_unit_id" id="production_unit_id" value="{{ $stock->production_unit_id ?? '' }}" />
                <div id="production_unit_dropdown" class="stock-unit-dropdown"></div>
              </div>
            </label>
            <label class="flex flex-col sm:col-span-2 lg:col-span-4">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.stock_notes', [], session('locale')) ?: 'Notes' }}</span>
              <textarea name="stock_notes" id="stock_notes" rows="3"
                        placeholder="{{ trans('messages.stock_notes_placeholder', [], session('locale')) ?: 'Optional notes' }}"
                        class="rounded-lg px-4 py-3 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition">{{ $stock->stock_notes ?? '' }}</textarea>
            </label>

            <label class="flex flex-col md:col-span-3">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.image', [], session('locale')) ?: 'Stock Image' }}</span>
              @if($stock->image ?? null)
                <div class="mb-2">
                  <img src="{{ asset($stock->image) }}" alt="Current" class="w-24 h-24 object-cover rounded-lg border border-gray-200" />
                  <p class="text-xs text-gray-500 mt-1">{{ trans('messages.current_image', [], session('locale')) ?: 'Current image - upload new to replace' }}</p>
                </div>
              @endif
              <input type="file" name="image" id="image" accept="image/*"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20" />
              <div id="image_preview" class="mt-2 hidden">
                <img id="image_preview_img" src="" alt="Preview" class="w-24 h-24 object-cover rounded-lg border border-gray-200" />
              </div>
            </label>
          </div>
        </div>

        <div class="space-y-4 pt-4 border-t border-gray-200">
          <div class="flex items-center gap-2 pb-3 border-b border-gray-200">
            <span class="material-symbols-outlined text-primary text-xl">attach_money</span>
            <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.pricing', [], session('locale')) }}</h2>
          </div>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.cost_price', [], session('locale')) }}</span>
              <input type="number" step="0.001" min="0"
                     placeholder="{{ trans('messages.cost_price_placeholder', [], session('locale')) }}"
                     value="{{ $stock->cost_price ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" 
                     name="cost_price" id="cost_price" />
            </label>

            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.sale_price', [], session('locale')) }}</span>
              <input type="number" step="0.001" min="0"
                     name="sales_price" id="sales_price"
                     placeholder="{{ trans('messages.sale_price_placeholder', [], session('locale')) }}"
                     value="{{ $stock->sales_price ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
            </label>

            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.discount', [], session('locale')) }}</span>
              <input type="number" step="0.001" min="0"
                     name="discount" id="discount"
                     placeholder="{{ trans('messages.discount_placeholder', [], session('locale')) }}"
                     value="{{ $stock->discount ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
            </label>

            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.tax', [], session('locale')) }}</span>
              <input type="number" step="0.001" min="0"
                     name="tax" id="tax"
                     placeholder="{{ trans('messages.tax_placeholder', [], session('locale')) }}"
                     value="{{ $stock->tax ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
            </label>

            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.general_minimum_stock', [], session('locale')) }}</span>
              <input type="number" step="1" min="0" placeholder="0"
                     name="notification_limit" id="notification_limit"
                     value="{{ $stock->notification_limit ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
            </label>
          </div>
        </div>

      </div>

      <div class="bg-gray-50 px-6 sm:px-8 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
        <a href="{{ url('view_stock') }}{{ isset($returnPage) && $returnPage > 1 ? '?page=' . $returnPage : '' }}" 
           class="text-sm text-gray-600 hover:text-gray-800 transition-colors">
          {{ trans('messages.cancel', [], session('locale')) }}
        </a>
        <button type="submit"
                class="w-full sm:w-auto px-8 py-3 bg-primary text-white font-semibold rounded-xl shadow-md hover:bg-primary/90 hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2">
          <span class="material-symbols-outlined text-lg">save</span>
          <span>{{ trans('messages.save', [], session('locale')) }}</span>
        </button>
      </div>

    </form>

  </div>
</main>

@include('layouts.footer')
@endsection
