@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.assetmaintenance_lang', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto" 
      x-data="{ open: false, edit: false, del: false }"
      @close-modal.window="open = false"
      @open-modal.window="open = true">

    <div class="max-w-7xl mx-auto">
        <!-- Page title and Add button -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-10">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">
                {{ trans('messages.manage_assetmaintenances', [], session('locale')) }}
            </h2>
            <button @click="open = true"
                class="flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-[var(--primary-color)] rounded-full shadow-lg hover:bg-[var(--primary-darker)] transition-transform hover:scale-105">
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>{{ trans('messages.add_new_assetmaintenance', [], session('locale')) }}</span>
            </button>
        </div>

        <!-- Search bar -->
        <div class="w-full mt-6 mb-8">
            <div class="relative flex items-center bg-white/90 backdrop-blur-md rounded-2xl shadow-md border border-[var(--accent-color)] max-w-md mx-auto sm:mx-0 px-3 py-2 transition-all duration-300 hover:shadow-lg hover:bg-white">
              <input
                id="search_assetmaintenance"
                type="text"
                placeholder="{{ trans('messages.search_assetmaintenance', [], session('locale')) }}"
                class="flex-1 bg-transparent border-none focus:ring-0 focus:outline-none text-[var(--text-primary)] placeholder-gray-400 text-sm px-3" />
                <button
                    class="flex items-center justify-center rounded-xl bg-[var(--primary-color)] text-white w-10 h-10 hover:bg-[var(--primary-darker)] transition-all duration-200 shadow-sm"
                    title="{{ trans('messages.search', [], session('locale')) }}">
                    <span class="material-symbols-outlined text-[22px]">search</span>
                </button>
            </div>
        </div>

        <!-- assetmaintenances table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)] overflow-x-auto">
            <table class="w-full text-sm text-right min-w-full">
                <thead class="bg-gray-50 border-b border-[var(--border-color)]">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.asset_name', [], session('locale')) }}</th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.maintenance_type', [], session('locale')) }}</th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.maintenance_date', [], session('locale')) }}</th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.next_maintenance_date', [], session('locale')) }}</th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.maintenance_cost', [], session('locale')) }}</th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.description', [], session('locale')) }}</th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">{{ trans('messages.performed_by', [], session('locale')) }}</th>
                        
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)] text-center">{{ trans('messages.actions', [], session('locale')) }}</th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
    <div class="flex justify-center mt-6">
        <ul id="pagination" class="dress_pagination flex gap-2"></ul>
    </div>

    <!-- Add assetmaintenance Modal -->
    <div x-show="open" x-cloak 
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" id="add_assetmaintenance_modal" x-ref="assetmaintenanceModal">
        <div @click.away="open = false"
            class="bg-white rounded-2xl shadow-xl w-full max-w-4xl p-6 sm:p-8 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-start mb-6">
                <h1 class="text-xl sm:text-2xl font-bold">
                    <span x-text="edit ? '{{ trans('messages.edit_assetmaintenance', [], session('locale')) }}' : '{{ trans('messages.add_assetmaintenance', [], session('locale')) }}'"></span>
                </h1>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600" id="close_modal">
                    <span class="material-symbols-outlined text-3xl">close</span>
                </button>
            </div>
            <form id="assetmaintenance_form">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.asset_name', [], session('locale')) }}
                        </label>
                        <select name="asset_id" id="asset_id"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                            @foreach ($asset as $ass)
                                <option value="{{ $ass->id }}">{{ $ass->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.maintenance_type', [], session('locale')) }}
                        </label>
                        <select name="maintenance_type" id="maintenance_type"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                            <option value="1">{{ trans('messages.asset_maintenance_emergency', [], session('locale')) }}</option>
                            <option value="2">{{ trans('messages.asset_maintenance_schedule', [], session('locale')) }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.maintenance_date', [], session('locale')) }}
                        </label>
                        <input type="date"
                            placeholder="{{ trans('messages.maintenance_date', [], session('locale')) }}"
                            name="maintenance_date" id="maintenance_date"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.next_maintenance_date', [], session('locale')) }}
                        </label>
                        <input type="date"  
                            placeholder="{{ trans('messages.next_maintenance_date', [], session('locale')) }}"
                            name="next_maintenance_date" id="next_maintenance_date"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.maintenance_cost', [], session('locale')) }}
                        </label>
                        <input type="number" step="0.01"
                            placeholder="{{ trans('messages.maintenance_cost', [], session('locale')) }}"
                            name="cost" id="cost"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.performed_by', [], session('locale')) }}
                        </label>
                        <select name="performed_by" id="performed_by"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                            <option value="1">{{ trans('messages.asset_internal', [], session('locale')) }}</option>
                            <option value="2">{{ trans('messages.asset_external', [], session('locale')) }}</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.description', [], session('locale')) }}
                        </label>
                        <textarea
                            placeholder="{{ trans('messages.description', [], session('locale')) }}"
                            name="description" id="description" rows="4"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]"></textarea>
                    </div>
                </div>

                <input type="hidden" id="asset_edit_id" name="asset_edit_id">

                <div class="mt-8 pt-6 border-t">
                    <button type="submit"
                        class="w-full bg-[var(--primary-color)] text-white font-bold py-3 rounded-lg hover:bg-[var(--primary-darker)]">
                        {{ trans('messages.save', [], session('locale')) }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</main>

@include('layouts.footer')
@endsection



