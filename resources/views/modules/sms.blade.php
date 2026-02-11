@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.sms_panel', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto">
    <div class="max-w-6xl mx-auto">
        <!-- Page title -->
        <div class="mb-10">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">
                {{ trans('messages.sms_panel', [], session('locale')) }}
            </h2>
            <p class="text-gray-600 mt-2">{{ trans('messages.sms_panel_description', [], session('locale')) }}</p>
        </div>

        <!-- SMS Form -->
        <div class="bg-white rounded-2xl shadow-lg border border-[var(--border-color)] p-6 sm:p-8">
            <form id="sms_form">
                @csrf
                <div class="mb-6">
                    <!-- Message Type -->
                    <div>
                        <label class="block text-base font-medium mb-2">
                            {{ trans('messages.message_type', [], session('locale')) }} <span class="text-red-500">*</span>
                        </label>
                        <select name="message_type" id="message_type" required
                            class="w-full border rounded-lg p-3 focus:ring focus:ring-[var(--primary-color)]">
                            <option value="">{{ trans('messages.select_message_type', [], session('locale')) }}</option>
                            <option value="1">{{ trans('messages.pos_order', [], session('locale')) }}</option>
                            <option value="2">{{ trans('messages.special_order', [], session('locale')) }}</option>
                            <option value="3">{{ trans('messages.repairing_customer', [], session('locale')) }}</option>
                            <option value="4">{{ trans('messages.repairing_tailor', [], session('locale')) }}</option>
                            <option value="5">{{ trans('messages.order_delivery_customer', [], session('locale')) }}</option>
                            <option value="6">{{ trans('messages.special_order_in_progress', [], session('locale')) }}</option>
                            <option value="7">{{ trans('messages.special_order_ready', [], session('locale')) }}</option>
                            <option value="8">{{ trans('messages.special_order_delivered', [], session('locale')) }}</option>
                        </select>
                    </div>
                </div>

                <!-- Variables Section -->
                <div class="mb-6">
                    <label class="block text-base font-medium mb-3">
                        {{ trans('messages.available_variables', [], session('locale')) }}
                    </label>
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                            <button type="button" onclick="insertVariable('{customer_name}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {customer_name}
                            </button>
                            <button type="button" onclick="insertVariable('{pos_order_no}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {pos_order_no}
                            </button>
                            <button type="button" onclick="insertVariable('{special_order_no}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {special_order_no}
                            </button>
                            <button type="button" onclick="insertVariable('{customer_phone_number}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {customer_phone_number}
                            </button>
                            <button type="button" onclick="insertVariable('{stock_name}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {stock_name}
                            </button>
                            <button type="button" onclick="insertVariable('{stock_code}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {stock_code}
                            </button>
                            <button type="button" onclick="insertVariable('{stock_category}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {stock_category}
                            </button>
                            <button type="button" onclick="insertVariable('{color}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {color}
                            </button>
                            <button type="button" onclick="insertVariable('{size}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {size}
                            </button>
                            <button type="button" onclick="insertVariable('{stock_length}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {stock_length}
                            </button>
                            <button type="button" onclick="insertVariable('{bust}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {bust}
                            </button>
                            <button type="button" onclick="insertVariable('{sleeves_length}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {sleeves_length}
                            </button>
                            <button type="button" onclick="insertVariable('{buttons}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {buttons}
                            </button>
                            <button type="button" onclick="insertVariable('{special_order_number}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {special_order_number}
                            </button>
                            <button type="button" onclick="insertVariable('{quantity}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {quantity}
                            </button>
                            <button type="button" onclick="insertVariable('{total_amount}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {total_amount}
                            </button>
                            <button type="button" onclick="insertVariable('{remaining_amount}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {remaining_amount}
                            </button>
                            <button type="button" onclick="insertVariable('{paid_amount}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {paid_amount}
                            </button>
                            <button type="button" onclick="insertVariable('{discount}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {discount}
                            </button>
                            <button type="button" onclick="insertVariable('{delivery_charges}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {delivery_charges}
                            </button>
                            <button type="button" onclick="insertVariable('{tailor_name}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {tailor_name}
                            </button>
                            <button type="button" onclick="insertVariable('{delivery_date}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {delivery_date}
                            </button>
                            <button type="button" onclick="insertVariable('{pos_order_status}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {pos_order_status}
                            </button>
                            <button type="button" onclick="insertVariable('{special_order_status}')" 
                                class="px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg hover:bg-[var(--primary-color)] hover:text-white hover:border-[var(--primary-color)] transition-colors">
                                {special_order_status}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Message Textarea -->
                <div class="mb-6">
                    <label class="block text-base font-medium mb-2">
                        {{ trans('messages.sms_message', [], session('locale')) }} <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        name="sms" id="sms_text" rows="8" required
                        placeholder="{{ trans('messages.sms_message_placeholder', [], session('locale')) }}"
                        class="w-full border rounded-lg p-4 focus:ring focus:ring-[var(--primary-color)] font-mono text-sm"></textarea>
                    <p class="text-xs text-gray-500 mt-2">{{ trans('messages.click_variable_to_insert', [], session('locale')) }}</p>
                </div>

                <!-- Preview Section -->
                <div class="mb-6 bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <label class="block text-base font-medium mb-2 text-blue-800">
                        {{ trans('messages.message_preview', [], session('locale')) }}
                    </label>
                    <div id="message_preview" class="bg-white rounded p-3 border border-blue-300 min-h-[100px] text-sm whitespace-pre-wrap">
                        {{ trans('messages.select_message_type', [], session('locale')) }}
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="submit"
                        class="px-8 py-3 bg-[var(--primary-color)] text-white font-bold rounded-lg hover:bg-[var(--primary-darker)] transition-colors">
                        {{ trans('messages.save_message', [], session('locale')) }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

@include('layouts.footer')
@include('custom_js.sms_js')
@endsection

