@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.edit_stock', [], session('locale')) ?: 'Edit Stock' }}</title>
@endpush

<style>
  body {
    font-family: 'IBM Plex Sans Arabic', sans-serif;
  }
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
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.stock_name', [], session('locale')) ?: 'Stock Name' }}</span>
              <input type="text" name="stock_name" id="stock_name"
                     placeholder="{{ trans('messages.stock_name_placeholder', [], session('locale')) ?: 'Enter stock name' }}"
                     value="{{ $stock->stock_name ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
            </label>

            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.category', [], session('locale')) }}</span>
              <select class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" 
                      name="category_id" id="category_id">
                <option value="">{{ trans('messages.choose', [], session('locale')) }}</option>
                @foreach($categories as $category)
                  <option value="{{ $category->id }}" {{ $stock->category_id == $category->id ? 'selected' : '' }}>{{ $category->category_name }}</option>
                @endforeach
              </select>
            </label>

            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.barcode', [], session('locale')) }}</span>
              <input type="text" name="barcode" id="barcode"
                     value="{{ $stock->barcode ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 transition-all" />
            </label>

            <label class="flex flex-col md:col-span-3">
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
