@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.manage_units', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-4 md:p-6">
    <div class="w-full max-w-[95%] xl:max-w-[98%] mx-auto">

        <div class="flex flex-col sm:flex-row flex-wrap justify-between items-start sm:items-center gap-4 mb-6">
            <h2 class="text-gray-900 text-2xl sm:text-3xl font-bold">
                {{ trans('messages.manage_units', [], session('locale')) }}
            </h2>
            <button type="button" id="openAddUnitModal"
                class="inline-flex items-center justify-center h-11 px-5 rounded-lg bg-[var(--primary-color)] text-white text-sm sm:text-base font-bold shadow hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
                <span class="material-symbols-outlined me-1">add</span>
                {{ trans('messages.add_unit', [], session('locale')) }}
            </button>
        </div>

        <div class="sticky top-[var(--header-h,64px)] z-10 bg-white/80 backdrop-blur border border-pink-100 rounded-2xl shadow-sm">
            <div class="py-3 px-4">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex-1 min-w-[45%]">
                        <input id="unit_search" type="search"
                            placeholder="{{ trans('messages.search', [], session('locale')) }}"
                            class="w-full h-11 rounded-xl border border-pink-200 focus:border-[var(--primary-color)] focus:ring-[var(--primary-color)] pr-10 text-sm" />
                    </div>
                </div>
            </div>
        </div>

        <section class="hidden xl:block mt-6">
            <div class="rounded-2xl overflow-x-auto border border-pink-100 bg-white shadow-md hover:shadow-lg transition mx-auto">
                <table class="w-full text-sm min-w-[400px] mx-auto">
                    <thead class="bg-gradient-to-l from-pink-50 to-pink-100 text-gray-800">
                        <tr>
                            <th class="text-center px-3 py-3 font-bold">#</th>
                            <th class="text-center px-3 py-3 font-bold">{{ trans('messages.unit', [], session('locale')) }}</th>
                            <th class="text-center px-3 py-3 font-bold">{{ trans('messages.action', [], session('locale')) }}</th>
                        </tr>
                    </thead>
                    <tbody id="units_tbody"></tbody>
                </table>
            </div>
        </section>

        <div class="flex justify-center mt-6">
            <ul id="units_pagination" class="flex gap-2 list-none"></ul>
        </div>
    </div>

    <!-- Add Unit Modal -->
    <div id="addUnitModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800" id="addUnitModalTitle">{{ trans('messages.add_unit', [], session('locale')) }}</h2>
                <button type="button" id="closeAddUnitModal" class="text-gray-400 hover:text-gray-600 p-1">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="addUnitForm">
                <input type="hidden" id="unit_id" name="unit_id">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">{{ trans('messages.unit', [], session('locale')) }}</label>
                    <input type="text" name="unit_name" id="unit_name" required
                        class="w-full h-11 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] px-4"
                        placeholder="{{ trans('messages.unit_name_placeholder', [], session('locale')) }}">
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" id="cancelAddUnitBtn" class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 rounded-xl transition font-semibold">
                        {{ trans('messages.cancel', [], session('locale')) }}
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-[var(--primary-color)] hover:bg-[var(--primary-color)]/90 text-white rounded-xl transition font-semibold">
                        {{ trans('messages.save', [], session('locale')) }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

@include('layouts.footer')
@endsection
