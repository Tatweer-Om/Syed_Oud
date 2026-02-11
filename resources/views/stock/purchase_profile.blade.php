@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.purchase_profile', [], session('locale')) }}</title>
@endpush

<style>
  body { font-family: 'IBM Plex Sans Arabic', sans-serif; }
  .purchase-profile-tab { padding: 0.75rem 1.25rem; font-weight: 600; border-bottom: 2px solid transparent; transition: all 0.2s; }
  .purchase-profile-tab:hover { color: var(--primary-color); }
  .purchase-profile-tab.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
  .purchase-profile-panel { display: none; }
  .purchase-profile-panel.active { display: block; }
</style>

<main class="flex-1 p-4 md:p-6">
  <div class="max-w-7xl mx-auto">

    <!-- Header: Back + Title -->
    <div class="flex items-center gap-4 mb-6">
      <a href="{{ url('view_purchase') }}" class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors">
        <span class="material-symbols-outlined text-gray-600">arrow_back</span>
      </a>
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
        {{ trans('messages.purchase_profile', [], session('locale')) }} #{{ $purchase->id }}
      </h1>
    </div>

    <!-- Top section: General details card -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden mb-6">
      <div class="p-6 sm:p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
          <!-- Details grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 flex-1">
            <div>
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ trans('messages.supplier', [], session('locale')) }}</span>
              <p class="text-base font-semibold text-gray-900 mt-0.5">{{ $purchase->supplier->supplier_name ?? '-' }}</p>
              @if($purchase->supplier && $purchase->supplier->phone)
              <p class="text-sm text-gray-500">{{ $purchase->supplier->phone }}</p>
              @endif
            </div>
            <div>
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ trans('messages.invoice_number', [], session('locale')) }}</span>
              <p class="text-base font-semibold text-gray-900 mt-0.5">{{ $purchase->invoice_no ?: '-' }}</p>
            </div>
            <div>
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ trans('messages.date', [], session('locale')) }}</span>
              <p class="text-base font-semibold text-gray-900 mt-0.5">{{ $purchase->created_at ? $purchase->created_at->format('Y-m-d H:i') : '-' }}</p>
            </div>
            <div>
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ trans('messages.shipping_cost', [], session('locale')) }}</span>
              <p class="text-base font-semibold text-gray-900 mt-0.5">{{ number_format((float) $purchase->total_shipping_price, 2) }}</p>
            </div>
            <div>
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ trans('messages.total_amount', [], session('locale')) }}</span>
              <p class="text-lg font-bold text-gray-900 mt-0.5">{{ number_format((float) $purchase->total_amount, 2) }}</p>
            </div>
            @php $paidAmount = (float) $purchase->payments->sum('amount'); $remainingAmount = (float) $purchase->total_amount - $paidAmount; @endphp
            <div>
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ trans('messages.paid_amount', [], session('locale')) }}</span>
              <p class="text-lg font-bold text-emerald-600 mt-0.5">{{ number_format($paidAmount, 2) }}</p>
            </div>
            <div>
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ trans('messages.remaining', [], session('locale')) }}</span>
              <p class="text-lg font-bold text-[var(--primary-color)] mt-0.5">{{ number_format($remainingAmount, 2) }}</p>
            </div>
          </div>
        </div>

        <!-- Record Payment button -->
        <div class="mt-6 pt-6 border-t border-gray-200">
          <button type="button" id="openPaymentModalBtn" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-bold shadow-lg hover:shadow-xl hover:from-emerald-600 hover:to-emerald-700 transition-all">
            <span class="material-symbols-outlined text-xl">payments</span>
            {{ trans('messages.record_payment', [], session('locale')) }}
          </button>
        </div>
        @if($purchase->notes)
        <div class="mt-4 pt-4 border-t border-gray-100">
          <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ trans('messages.notes', [], session('locale')) }}</span>
          <p class="text-sm text-gray-700 mt-1">{{ $purchase->notes }}</p>
        </div>
        @endif
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
      <div class="border-b border-gray-200 bg-gray-50/50 px-4">
        <div class="flex gap-1">
          <button type="button" class="purchase-profile-tab active" data-tab="materials">
            <span class="material-symbols-outlined align-middle text-lg me-1">inventory_2</span>
            {{ trans('messages.materials', [], session('locale')) }}
          </button>
          <button type="button" class="purchase-profile-tab" data-tab="payments">
            <span class="material-symbols-outlined align-middle text-lg me-1">receipt_long</span>
            {{ trans('messages.payments', [], session('locale')) }}
          </button>
        </div>
      </div>

      <!-- Materials panel -->
      <div id="tab-materials" class="purchase-profile-panel active p-6">
        @php $materials = optional($purchase->details)->materials_json ?? []; @endphp
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="text-left px-3 py-3 font-bold text-gray-700">{{ trans('messages.material_name', [], session('locale')) }}</th>
                <th class="text-center px-2 py-3 font-bold text-gray-700">{{ trans('messages.unit', [], session('locale')) }}</th>
                <th class="text-center px-2 py-3 font-bold text-gray-700">{{ trans('messages.buy_price', [], session('locale')) }}</th>
                <th class="text-center px-2 py-3 font-bold text-gray-700">{{ trans('messages.unit_price_plus_shipping', [], session('locale')) }}</th>
                <th class="text-center px-2 py-3 font-bold text-gray-700">{{ trans('messages.quantity', [], session('locale')) }}</th>
                <th class="text-center px-3 py-3 font-bold text-gray-700">{{ trans('messages.total', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($materials as $m)
              <tr class="border-t border-gray-100 hover:bg-pink-50/30">
                <td class="px-3 py-2 font-semibold">{{ $m['material_name'] ?? '-' }}</td>
                <td class="px-2 py-2 text-center">{{ $m['unit'] ?? '-' }}</td>
                <td class="px-2 py-2 text-center">{{ number_format((float) ($m['price'] ?? 0), 2) }}</td>
                <td class="px-2 py-2 text-center">{{ number_format((float) ($m['unit_price_plus_shipping'] ?? $m['price'] ?? 0), 2) }}</td>
                <td class="px-2 py-2 text-center">{{ $m['quantity'] ?? 0 }}</td>
                <td class="px-3 py-2 text-center font-semibold">{{ number_format((float) ($m['total'] ?? 0), 2) }}</td>
              </tr>
              @empty
              <tr><td colspan="6" class="px-3 py-8 text-center text-gray-500">{{ trans('messages.no_data', [], session('locale')) }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <!-- Payments panel -->
      <div id="tab-payments" class="purchase-profile-panel p-6">
        <div class="flex flex-wrap gap-6 mb-4 pb-4 border-b border-gray-200">
          <div><span class="text-xs font-semibold text-gray-500 uppercase">{{ trans('messages.total_amount', [], session('locale')) }}</span><p class="text-lg font-bold text-gray-900">{{ number_format((float) $purchase->total_amount, 2) }}</p></div>
          <div><span class="text-xs font-semibold text-gray-500 uppercase">{{ trans('messages.paid_amount', [], session('locale')) }}</span><p class="text-lg font-bold text-emerald-600">{{ number_format($paidAmount, 2) }}</p></div>
          <div><span class="text-xs font-semibold text-gray-500 uppercase">{{ trans('messages.remaining', [], session('locale')) }}</span><p class="text-lg font-bold text-[var(--primary-color)]">{{ number_format($remainingAmount, 2) }}</p></div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="text-left px-3 py-3 font-bold text-gray-700">#</th>
                <th class="text-left px-3 py-3 font-bold text-gray-700">{{ trans('messages.payment_amount', [], session('locale')) }}</th>
                <th class="text-left px-3 py-3 font-bold text-gray-700">{{ trans('messages.payment_method', [], session('locale')) }}</th>
                <th class="text-left px-3 py-3 font-bold text-gray-700">{{ trans('messages.payment_date', [], session('locale')) }}</th>
                <th class="text-left px-3 py-3 font-bold text-gray-700">{{ trans('messages.added_by', [], session('locale')) }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($purchase->payments as $i => $pay)
              <tr class="border-t border-gray-100 hover:bg-pink-50/30">
                <td class="px-3 py-2">{{ $i + 1 }}</td>
                <td class="px-3 py-2 font-semibold">{{ number_format((float) $pay->amount, 2) }}</td>
                <td class="px-3 py-2">{{ ucfirst($pay->payment_method ?? '-') }}</td>
                <td class="px-3 py-2">{{ $pay->payment_date ? \Carbon\Carbon::parse($pay->payment_date)->format('Y-m-d') : ($pay->created_at ? $pay->created_at->format('Y-m-d') : '-') }}</td>
                <td class="px-3 py-2">{{ $pay->added_by ?? '-' }}</td>
              </tr>
              @empty
              <tr><td colspan="5" class="px-3 py-8 text-center text-gray-500">{{ trans('messages.no_payment_history', [], session('locale')) }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Record Payment Modal -->
  <div id="paymentModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
      <div class="p-6 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800">{{ trans('messages.record_payment', [], session('locale')) }}</h2>
        <button type="button" id="closePaymentModal" class="p-1 text-gray-500 hover:text-gray-700 rounded hover:bg-gray-100">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <div class="px-6 pt-4 pb-2 bg-gray-50 border-b border-gray-100">
        <div class="flex justify-between text-sm mb-1"><span class="text-gray-600">{{ trans('messages.total_amount', [], session('locale')) }}:</span><span class="font-bold">{{ number_format((float) $purchase->total_amount, 2) }}</span></div>
        <div class="flex justify-between text-sm mb-1"><span class="text-gray-600">{{ trans('messages.paid_amount', [], session('locale')) }}:</span><span class="font-bold text-emerald-600">{{ number_format($paidAmount, 2) }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-gray-600">{{ trans('messages.remaining', [], session('locale')) }}:</span><span class="font-bold text-[var(--primary-color)]">{{ number_format($remainingAmount, 2) }}</span></div>
      </div>
      <form id="paymentForm" class="p-6 space-y-4">
        @csrf
        <label class="block">
          <span class="text-sm font-semibold text-gray-700">{{ trans('messages.payment_amount', [], session('locale')) }}</span>
          <input type="number" name="amount" id="pay_amount" min="0.01" step="0.01" required placeholder="0.00"
                 class="mt-1 block w-full h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary" />
        </label>
        <label class="block">
          <span class="text-sm font-semibold text-gray-700">{{ trans('messages.payment_date', [], session('locale')) }}</span>
          <input type="date" name="payment_date" id="payment_date" required value="{{ date('Y-m-d') }}"
                 class="mt-1 block w-full h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary" />
        </label>
        <div>
          <span class="text-sm font-semibold text-gray-700 block mb-2">{{ trans('messages.payment_method', [], session('locale')) }}</span>
          <div class="flex gap-4">
            <label class="inline-flex items-center gap-2 cursor-pointer">
              <input type="radio" name="payment_method" value="visa" required class="rounded border-gray-300 text-primary focus:ring-primary" />
              <span class="text-sm font-medium">{{ trans('messages.visa', [], session('locale')) }}</span>
            </label>
            <label class="inline-flex items-center gap-2 cursor-pointer">
              <input type="radio" name="payment_method" value="cash" class="rounded border-gray-300 text-primary focus:ring-primary" />
              <span class="text-sm font-medium">{{ trans('messages.cash', [], session('locale')) }}</span>
            </label>
            <label class="inline-flex items-center gap-2 cursor-pointer">
              <input type="radio" name="payment_method" value="bank" class="rounded border-gray-300 text-primary focus:ring-primary" />
              <span class="text-sm font-medium">{{ trans('messages.bank_transfer', [], session('locale')) }}</span>
            </label>
          </div>
        </div>
        <div class="flex gap-3 pt-2">
          <button type="button" id="cancelPaymentBtn" class="flex-1 h-11 rounded-xl border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50">
            {{ trans('messages.cancel', [], session('locale')) }}
          </button>
          <button type="submit" class="flex-1 h-11 rounded-xl bg-emerald-600 text-white font-bold hover:bg-emerald-700 flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-lg">payments</span>
            {{ trans('messages.save', [], session('locale')) }}
          </button>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var purchaseId = {{ $purchase->id }};
  var modal = document.getElementById('paymentModal');
  var form = document.getElementById('paymentForm');
  var openBtn = document.getElementById('openPaymentModalBtn');

  document.querySelectorAll('.purchase-profile-tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var tab = this.getAttribute('data-tab');
      document.querySelectorAll('.purchase-profile-tab').forEach(function(b) { b.classList.remove('active'); });
      document.querySelectorAll('.purchase-profile-panel').forEach(function(p) { p.classList.remove('active'); });
      this.classList.add('active');
      var panel = document.getElementById('tab-' + tab);
      if (panel) panel.classList.add('active');
    });
  });

  if (openBtn && modal) {
    openBtn.addEventListener('click', function() {
      form.reset();
      document.getElementById('payment_date').value = '{{ date("Y-m-d") }}';
      modal.classList.remove('hidden');
    });
  }
  document.getElementById('closePaymentModal').addEventListener('click', function() {
    modal.classList.add('hidden');
  });
  document.getElementById('cancelPaymentBtn').addEventListener('click', function() {
    modal.classList.add('hidden');
  });
  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target.id === 'paymentModal') modal.classList.add('hidden');
    });
  }

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var submitBtn = form.querySelector('button[type="submit"]');
    var origHtml = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '...';
    var formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('amount', document.getElementById('pay_amount').value);
    formData.append('payment_date', document.getElementById('payment_date').value);
    var methodInput = form.querySelector('input[name="payment_method"]:checked');
    formData.append('payment_method', methodInput ? methodInput.value : '');

    fetch('{{ url("purchase") }}/' + purchaseId + '/payment', {
      method: 'POST',
      body: formData,
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json().catch(function() { return {}; }); })
    .then(function(res) {
      if (res.status === 'success') {
        if (typeof show_notification === 'function') show_notification('success', res.message);
        else alert(res.message);
        modal.classList.add('hidden');
        window.location.reload();
      } else {
        var msg = (res.message || (res.errors && Object.values(res.errors).flat().join(' ')) || '{{ __("Something went wrong") }}');
        if (typeof show_notification === 'function') show_notification('error', msg);
        else alert(msg);
      }
      submitBtn.disabled = false;
      submitBtn.innerHTML = origHtml;
    })
    .catch(function(err) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = origHtml;
      if (typeof show_notification === 'function') show_notification('error', '{{ __("Something went wrong") }}');
      else alert('{{ __("Something went wrong") }}');
    });
  });
});
</script>
@include('layouts.footer')
@endsection
