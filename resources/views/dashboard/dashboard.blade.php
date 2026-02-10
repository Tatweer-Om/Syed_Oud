@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.dashboard', [], session('locale')) }}</title>
@endpush
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    arabic: ["IBM Plex Sans Arabic", "system-ui", "sans-serif"],
                },
                boxShadow: {
                    soft: "0 10px 30px rgba(0,0,0,.06)",
                }
            }
        }
    }
</script>

<style>
    @media (prefers-color-scheme: dark) {
        .dark\:text-gray-200 {
            --tw-text-opacity: 1;
            color: #1F2937!important;
        }
    }
    :root {
        --bg: #f7f7fb;
        --card: #ffffff;
        --text: #333;
        --muted: #6b7280;
        --border: rgba(0, 0, 0, .06);
        --primary: #b34b8a;
        --primary2: #6d5bd0;
        --gold: #b68a2c;
        --danger: #ef4444;
        --dangerSoft: rgba(239, 68, 68, .12);
        --ok: #10b981;
        --okSoft: rgba(16, 185, 129, .12);
        --warn: #f59e0b;
        --warnSoft: rgba(245, 158, 11, .14);
    }
    body {
        font-family: var(--tw-fontFamily-arabic);
        background: var(--bg);
        color: var(--text);
    }
    @media print {
        .no-print { display: none !important; }
        .print-card { box-shadow: none !important; border: 1px solid #ddd !important; }
    }
</style>

<main class="flex-1 p-6 space-y-6">
    <!-- Top row: KPI boxes -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">{{ trans('messages.net_profit_today', [], session('locale')) ?: 'Net Profit Today' }}</p>
                    <p class="mt-2 text-2xl font-extrabold">{{ number_format($todayNetProfit ?? 0, 3) }} {{ trans('messages.currency', [], session('locale')) }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ trans('messages.revenue', [], session('locale')) ?: 'Revenue' }}: {{ number_format($todayRevenue ?? 0, 3) }} •
                        {{ trans('messages.expenses', [], session('locale')) ?: 'Expenses' }}: {{ number_format($todayExpenses ?? 0, 3) }}
                    </p>
                </div>
                <div class="w-11 h-11 rounded-2xl grid place-items-center" style="background: rgba(16,185,129,.12);">
                    <span class="material-symbols-outlined text-[22px]" style="color: var(--ok);">trending_up</span>
                </div>
            </div>
        </div>

        <div class="bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">{{ trans('messages.net_profit_this_month', [], session('locale')) ?: 'Net Profit This Month' }}</p>
                    <p class="mt-2 text-2xl font-extrabold">{{ number_format($monthNetProfit ?? 0, 3) }} {{ trans('messages.currency', [], session('locale')) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ trans('messages.current_month', [], session('locale')) ?: 'Current Month' }}</p>
                </div>
                <div class="w-11 h-11 rounded-2xl grid place-items-center" style="background: rgba(109,91,208,.12);">
                    <span class="material-symbols-outlined text-[22px]" style="color: var(--primary2);">account_balance</span>
                </div>
            </div>
        </div>

        <div class="bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">{{ trans('messages.revenue_minus_expense', [], session('locale')) ?: 'Revenue - Expense' }}</p>
                    <p class="mt-2 text-2xl font-extrabold">{{ number_format($revenueMinusExpense ?? 0, 3) }} {{ trans('messages.currency', [], session('locale')) }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ trans('messages.total_revenue', [], session('locale')) ?: 'Total Revenue' }}: {{ number_format($totalRevenue ?? 0, 3) }} •
                        {{ trans('messages.total_expenses', [], session('locale')) ?: 'Total Expenses' }}: {{ number_format($totalExpenses ?? 0, 3) }}
                    </p>
                </div>
                <div class="w-11 h-11 rounded-2xl grid place-items-center" style="background: rgba(179,75,138,.12);">
                    <span class="material-symbols-outlined text-[22px]" style="color: var(--primary);">payments</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Chart + Low stock -->
    <section class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="xl:col-span-2 bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
            <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[22px]" style="color: var(--primary2);">bar_chart</span>
                    <div>
                        <h2 class="font-bold text-base sm:text-lg">{{ trans('messages.annual_revenue_expenses', [], session('locale')) }}</h2>
                        <p class="text-xs text-gray-500">{{ trans('messages.monthly_financial_performance', [], session('locale')) }}</p>
                    </div>
                </div>
                <div class="no-print flex items-center gap-2">
                    <select id="yearSelector" class="rounded-xl border border-[var(--border)] bg-white px-3 py-2 text-sm" onchange="updateChart()">
                        <option value="{{ $currentYear }}" selected>{{ $currentYear }}</option>
                        <option value="{{ $currentYear - 1 }}">{{ $currentYear - 1 }}</option>
                        @if($currentYear > 2023)
                        <option value="{{ $currentYear - 2 }}">{{ $currentYear - 2 }}</option>
                        @endif
                    </select>
                </div>
            </div>
            <div class="relative h-[360px]">
                <canvas id="yearlyBarChart"></canvas>
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-[var(--card)] border border-[var(--border)] rounded-2xl p-4 shadow-soft print-card">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]" style="color: var(--warn);">inventory_2</span>
                        <h3 class="font-bold">{{ trans('messages.low_stock_alert', [], session('locale')) }}</h3>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full" style="background: var(--warnSoft); color: var(--warn);">{{ trans('messages.important', [], session('locale')) }}</span>
                </div>
                <div class="mt-3 space-y-2 text-sm" id="lowStockItemsList">
                    <div class="text-center text-gray-500 py-4">{{ trans('messages.loading', [], session('locale')) }}...</div>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctxYearly = document.getElementById('yearlyBarChart');
    if (!ctxYearly) return;
    
    @php
        $defaultMonthlyData = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $monthlyRevenueData = $monthlyData['revenue'] ?? $defaultMonthlyData;
        $monthlyExpensesData = $monthlyData['expenses'] ?? $defaultMonthlyData;
    @endphp
    const monthlyRevenue = @json($monthlyRevenueData);
    const monthlyExpenses = @json($monthlyExpensesData);
    const monthLabels = ['{{ trans("messages.january", [], session("locale")) }}','{{ trans("messages.february", [], session("locale")) }}','{{ trans("messages.march", [], session("locale")) }}','{{ trans("messages.april", [], session("locale")) }}','{{ trans("messages.may", [], session("locale")) }}','{{ trans("messages.june", [], session("locale")) }}','{{ trans("messages.july", [], session("locale")) }}','{{ trans("messages.august", [], session("locale")) }}','{{ trans("messages.september", [], session("locale")) }}','{{ trans("messages.october", [], session("locale")) }}','{{ trans("messages.november", [], session("locale")) }}','{{ trans("messages.december", [], session("locale")) }}'];
    
    window.yearlyChart = new Chart(ctxYearly, {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [{
                label: '{{ trans("messages.revenue", [], session("locale")) }}',
                data: monthlyRevenue,
                backgroundColor: 'rgba(109, 91, 208, 0.75)',
                borderRadius: 8,
                barThickness: 14
            }, {
                label: '{{ trans("messages.expenses", [], session("locale")) }}',
                data: monthlyExpenses,
                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                borderRadius: 8,
                barThickness: 14
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } },
                tooltip: { rtl: true, callbacks: { label: function(context) { return context.dataset.label + ': ' + parseFloat(context.raw).toFixed(3) + ' ر.ع'; } } }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { callback: (value) => parseFloat(value).toFixed(3) + ' ر.ع' } }
            }
        }
    });
});

function updateChart() {
    if (!window.yearlyChart) return;
    const selectedYear = document.getElementById('yearSelector').value;
    window.yearlyChart.data.datasets[0].data = [0,0,0,0,0,0,0,0,0,0,0,0];
    window.yearlyChart.data.datasets[1].data = [0,0,0,0,0,0,0,0,0,0,0,0];
    window.yearlyChart.update();
    fetch(`/dashboard/monthly-data?year=${selectedYear}`, {
        method: 'GET',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.yearlyChart.data.datasets[0].data = data.revenue;
            window.yearlyChart.data.datasets[1].data = data.expenses;
            window.yearlyChart.update();
        }
    })
    .catch(err => console.error(err));
}

async function loadLowStockItems() {
    try {
        const response = await fetch('/dashboard/low-stock-items', { method: 'GET', headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        const container = document.getElementById('lowStockItemsList');
        if (data.success && data.items && data.items.length > 0) {
            container.innerHTML = data.items.map(item => {
                const isCritical = item.remaining <= 1;
                const badgeClass = isCritical ? 'background: var(--dangerSoft); color: var(--danger);' : 'background: var(--warnSoft); color: var(--warn);';
                const barColor = isCritical ? 'var(--danger)' : 'var(--warn)';
                const percentage = Math.max(item.percentage, 5);
                return `
                    <div class="rounded-xl border border-[var(--border)] bg-white p-3">
                        <div class="flex items-center justify-between">
                            <p class="font-semibold">${item.design_name}</p>
                            <span class="text-xs px-2 py-1 rounded-full" style="${badgeClass}">{{ trans("messages.remaining", [], session("locale")) }} ${item.remaining}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full" style="width: ${percentage}%; background: ${barColor};"></div>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = `<div class="text-center text-gray-500 py-4">{{ trans("messages.no_low_stock_items", [], session("locale")) ?: "No low stock items" }}</div>`;
        }
    } catch (error) {
        const container = document.getElementById('lowStockItemsList');
        if (container) container.innerHTML = `<div class="text-center text-red-500 py-4">{{ trans("messages.error_loading_data", [], session("locale")) }}</div>`;
    }
}
loadLowStockItems();
setInterval(loadLowStockItems, 5 * 60 * 1000);
</script>
@include('layouts.footer')
@endsection
