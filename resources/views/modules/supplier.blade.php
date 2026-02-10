@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.supplier', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto" 
      x-data="{ open: false, edit: false }"
      @close-modal.window="open = false"
      @open-modal.window="open = true">

    <div class="max-w-6xl mx-auto w-full">
        <!-- Page title and Add button -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">
                {{ trans('messages.manage_suppliers', [], session('locale')) }}
            </h2>
            <button id="add_supplier_btn" @click="open = true"
                class="flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-[var(--primary-color)] rounded-full shadow-lg hover:bg-[var(--primary-darker)] transition-transform hover:scale-105">
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>{{ trans('messages.add_new_supplier', [], session('locale')) }}</span>
            </button>
        </div>

        <!-- Total Suppliers Count Card -->
        <div class="mb-6">
            <div class="inline-flex items-center gap-3 px-5 py-3 bg-white rounded-xl shadow-md border border-[var(--border-color)]">
                <span class="material-symbols-outlined text-2xl text-[var(--primary-color)]">local_shipping</span>
                <div>
                    <p class="text-sm text-gray-500">{{ trans('messages.total_suppliers', [], session('locale')) }}</p>
                    <p id="total_suppliers_count" class="text-2xl font-bold text-[var(--text-primary)]">0</p>
                </div>
            </div>
        </div>

        <!-- Search bar -->
        <div class="w-full mt-6 mb-8">
            <div class="relative flex items-center bg-white/90 backdrop-blur-md rounded-2xl shadow-md border border-[var(--accent-color)] max-w-md mx-auto sm:mx-0 px-3 py-2 transition-all duration-300 hover:shadow-lg hover:bg-white">
              <input
                id="search_supplier"
                type="text"
                placeholder="{{ trans('messages.search_supplier', [], session('locale')) }}"
                class="flex-1 bg-transparent border-none focus:ring-0 focus:outline-none text-[var(--text-primary)] placeholder-gray-400 text-sm px-3" />
                <button
                    class="flex items-center justify-center rounded-xl bg-[var(--primary-color)] text-white w-10 h-10 hover:bg-[var(--primary-darker)] transition-all duration-200 shadow-sm"
                    title="{{ trans('messages.search', [], session('locale')) }}">
                    <span class="material-symbols-outlined text-[22px]">search</span>
                </button>
            </div>
        </div>

        <!-- Suppliers table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)]">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 border-b border-[var(--border-color)]">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">
                            {{ trans('messages.supplier_name', [], session('locale')) }}
                        </th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">
                            {{ trans('messages.phone', [], session('locale')) }}
                        </th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)]">
                            {{ trans('messages.notes', [], session('locale')) }}
                        </th>
                        <th class="px-4 sm:px-6 py-4 font-semibold text-[var(--text-secondary)] text-center">
                            {{ trans('messages.actions', [], session('locale')) }}
                        </th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
    <div class="flex justify-center mt-6 max-w-6xl mx-auto">
        <ul id="pagination" class="supplier-pagination dress_pagination flex gap-2 items-center"></ul>
    </div>

    <!-- Add/Edit Supplier Modal -->
    <div x-show="open" x-cloak 
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" id="add_supplier_modal">
        <div @click.away="open = false"
            class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 sm:p-8">
            <div class="flex justify-between items-start mb-6">
                <h1 class="text-xl sm:text-2xl font-bold" id="supplier_modal_title">
                    {{ trans('messages.add_new_supplier', [], session('locale')) }}
                </h1>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600" id="close_modal">
                    <span class="material-symbols-outlined text-3xl">close</span>
                </button>
            </div>
            <form id="supplier_form">
                @csrf
                <div class="space-y-6">
                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.supplier_name', [], session('locale')) }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                            placeholder="{{ trans('messages.supplier_name_placeholder', [], session('locale')) }}"
                            name="supplier_name" id="supplier_name" required
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.phone', [], session('locale')) }}
                        </label>
                        <input type="text"
                            placeholder="{{ trans('messages.phone_placeholder', [], session('locale')) }}"
                            name="phone" id="supplier_phone"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                    </div>

                    <input type="hidden" id="supplier_id" name="supplier_id">

                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.notes', [], session('locale')) }}
                        </label>
                        <textarea
                            placeholder="{{ trans('messages.notes_placeholder', [], session('locale')) }}"
                            name="notes" id="supplier_notes" rows="4"
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]"></textarea>
                    </div>
                </div>

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
@include('custom_js.supplier_js')
@endsection
