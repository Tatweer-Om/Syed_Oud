<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ trans('messages.production_invoice', [], session('locale')) }} - {{ $production->batch_id ?? '' }}</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; line-height: 1.5; color: #1f2937; margin: 0; padding: 24px; background: #f8fafc; }
    .container { max-width: 850px; margin: 0 auto; background: #fff; padding: 32px; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
    .header { text-align: center; border-bottom: 3px solid #4f46e5; padding-bottom: 20px; margin-bottom: 24px; }
    .header h1 { font-size: 22px; font-weight: 700; color: #4f46e5; margin: 0 0 6px 0; letter-spacing: -0.5px; }
    .header .sub { font-size: 13px; color: #6b7280; font-weight: 500; }
    .details-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px 32px; margin-bottom: 28px; padding: 20px; background: #f9fafb; border-radius: 8px; font-size: 13px; }
    .details-grid div { display: flex; justify-content: space-between; align-items: center; }
    .details-grid .label { color: #6b7280; font-weight: 500; }
    .details-grid .val { font-weight: 600; color: #111827; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
    th { background: #f3f4f6; padding: 10px 12px; text-align: left; font-weight: 600; border: 1px solid #e5e7eb; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
    td { padding: 10px 12px; border: 1px solid #e5e7eb; }
    .section-title { background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; font-size: 12px; font-weight: 700; padding: 10px 14px; margin-top: 20px; border-radius: 6px 6px 0 0; letter-spacing: 0.5px; }
    .section-title.packaging { background: linear-gradient(135deg, #7c3aed, #8b5cf6); }
    .section-title.wastage { background: linear-gradient(135deg, #b45309, #d97706); }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .summary { margin-top: 28px; padding: 24px; background: linear-gradient(135deg, #f9fafb, #f3f4f6); border: 1px solid #e5e7eb; border-radius: 10px; font-size: 13px; }
    .summary-row { display: flex; justify-content: space-between; padding: 8px 0; align-items: center; }
    .summary-row.total { font-weight: 700; font-size: 15px; border-top: 2px solid #4f46e5; margin-top: 12px; padding-top: 12px; color: #4f46e5; }
    .print-btn { position: fixed; top: 16px; right: 16px; padding: 10px 20px; background: #4f46e5; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; box-shadow: 0 4px 12px rgba(79,70,229,0.35); transition: all 0.2s; }
    .print-btn:hover { background: #4338ca; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(79,70,229,0.4); }
    .back-link { position: fixed; top: 16px; left: 16px; padding: 10px 20px; background: #64748b; color: #fff; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 600; transition: all 0.2s; }
    .back-link:hover { background: #475569; color: #fff; transform: translateY(-1px); }
    .no-data { color: #9ca3af; font-style: italic; }
    @media print {
      body { padding: 0; background: #fff; }
      .print-btn, .back-link { display: none !important; }
      .container { max-width: 100%; box-shadow: none; padding: 20px; }
    }
  </style>
</head>
<body>
  <a href="{{ url('view_production') }}" class="back-link">&larr; {{ trans('messages.back', [], session('locale')) }}</a>
  <button type="button" class="print-btn" onclick="window.print()">{{ trans('messages.print', [], session('locale')) }}</button>

  <div class="container">
    <div class="header">
      <h1>{{ trans('messages.production_invoice', [], session('locale')) }}</h1>
      <p class="sub">{{ $production->batch_id ?? '-' }} &bull; {{ $production->production_id ?? '-' }}</p>
    </div>

    <div class="details-grid">
      <div><span class="label">{{ trans('messages.stock', [], session('locale')) }}:</span> <span class="val">{{ $production->stock->stock_name ?? '-' }}</span></div>
      <div><span class="label">{{ trans('messages.batch_id', [], session('locale')) }}:</span> <span class="val">{{ $production->batch_id ?? '-' }}</span></div>
      <div><span class="label">{{ trans('messages.estimated_output', [], session('locale')) }}:</span> <span class="val">{{ number_format($estimatedOutput, 0) }}</span></div>
      <div><span class="label">{{ trans('messages.actual_output', [], session('locale')) }}:</span> <span class="val">{{ $actualOutput > 0 ? number_format($actualOutput, 0) : '-' }}</span></div>
      <div><span class="label">{{ trans('messages.actual_packaging_units', [], session('locale')) ?: 'Actual Packaging Units' }}:</span> <span class="val">{{ number_format($actualPackagingOutput, 2) }}</span></div>
      <div><span class="label">{{ trans('messages.completed_at', [], session('locale')) }}:</span> <span class="val">{{ $production->completed_at ? $production->completed_at->format('d M Y, H:i') : '-' }}</span></div>
      <div><span class="label">{{ trans('messages.added_by', [], session('locale')) }}:</span> <span class="val">{{ $production->added_by ?? '-' }}</span></div>
    </div>

    <div class="section-title">{{ trans('messages.production', [], session('locale')) }} {{ trans('messages.materials', [], session('locale')) }}</div>
    <table>
      <thead>
        <tr>
          <th style="width:50%">{{ trans('messages.material_name', [], session('locale')) }}</th>
          <th class="text-center" style="width:20%">{{ trans('messages.unit', [], session('locale')) }}</th>
          <th class="text-right" style="width:30%">{{ trans('messages.quantity', [], session('locale')) }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($productionMaterials as $m)
        <tr>
          <td>{{ $m['material_name'] ?? '-' }}</td>
          <td class="text-center">{{ $m['unit'] ?? '-' }}</td>
          <td class="text-right">{{ number_format((float)($m['quantity'] ?? 0), 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="3" class="text-center no-data">{{ trans('messages.no_data', [], session('locale')) }}</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="section-title packaging">{{ trans('messages.packaging', [], session('locale')) }} {{ trans('messages.materials', [], session('locale')) }}</div>
    <table>
      <thead>
        <tr>
          <th style="width:50%">{{ trans('messages.material_name', [], session('locale')) }}</th>
          <th class="text-center" style="width:20%">{{ trans('messages.unit', [], session('locale')) }}</th>
          <th class="text-right" style="width:30%">{{ trans('messages.quantity', [], session('locale')) }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($packagingMaterials as $m)
        <tr>
          <td>{{ $m['material_name'] ?? '-' }}</td>
          <td class="text-center">{{ $m['unit'] ?? '-' }}</td>
          <td class="text-right">{{ number_format((float)($m['quantity'] ?? 0), 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="3" class="text-center no-data">{{ trans('messages.no_data', [], session('locale')) }}</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="section-title wastage">{{ trans('messages.material_wastage', [], session('locale')) }}</div>
    <table>
      <thead>
        <tr>
          <th style="width:50%">{{ trans('messages.material_name', [], session('locale')) }}</th>
          <th class="text-center" style="width:20%">{{ trans('messages.unit', [], session('locale')) }}</th>
          <th class="text-right" style="width:30%">{{ trans('messages.quantity', [], session('locale')) }}</th>
        </tr>
      </thead>
      <tbody>
        @php $hasWastage = false; @endphp
        @foreach($productionWastage as $w)
        @php $hasWastage = true; @endphp
        <tr>
          <td>{{ $w->material_name ?? '-' }}</td>
          <td class="text-center">{{ $w->unit ?? '-' }}</td>
          <td class="text-right">{{ number_format((float)$w->quantity, 2) }}</td>
        </tr>
        @endforeach
        @foreach($packagingWastage as $w)
        @php $hasWastage = true; @endphp
        <tr>
          <td>{{ $w->material_name ?? '-' }}</td>
          <td class="text-center">{{ $w->unit ?? '-' }}</td>
          <td class="text-right">{{ number_format((float)$w->quantity, 2) }}</td>
        </tr>
        @endforeach
        @if(!$hasWastage)
        <tr><td colspan="3" class="text-center no-data">{{ trans('messages.no_data', [], session('locale')) }}</td></tr>
        @endif
      </tbody>
    </table>

    <div class="summary">
      <div class="summary-row"><span>{{ trans('messages.total_cost', [], session('locale')) }} {{ trans('messages.materials', [], session('locale')) }}:</span><span><strong>{{ number_format($totalMaterialCost, 2) }}</strong></span></div>
      <div class="summary-row"><span>{{ trans('messages.estimated_output', [], session('locale')) }}:</span><span>{{ number_format($estimatedOutput, 0) }}</span></div>
      <div class="summary-row"><span>{{ trans('messages.actual_output', [], session('locale')) }} / {{ trans('messages.total', [], session('locale')) }} {{ trans('messages.unit', [], session('locale')) }}:</span><span>{{ number_format($finalOutput, 0) }}</span></div>
      <div class="summary-row"><span>{{ trans('messages.actual_packaging_units', [], session('locale')) ?: 'Actual Packaging Units' }}:</span><span>{{ number_format($actualPackagingOutput, 2) }}</span></div>
      <div class="summary-row"><span>{{ trans('messages.cost_per_unit', [], session('locale')) }} ({{ trans('messages.estimated_output', [], session('locale')) }}):</span><span>{{ number_format($costPerUnitEstimated, 2) }}</span></div>
      <div class="summary-row"><span>{{ trans('messages.cost_per_unit', [], session('locale')) }} ({{ trans('messages.actual_output', [], session('locale')) }}):</span><span>{{ number_format($costPerUnitActual, 2) }}</span></div>
      <div class="summary-row total"><span>{{ trans('messages.cost_per_unit', [], session('locale')) }} ({{ trans('messages.actual_packaging_units', [], session('locale')) ?: 'Actual Packaging Units' }}):</span><span>{{ $actualPackagingOutput > 0 ? number_format($costPerUnitActualPackaging, 2) : '-' }}</span></div>
    </div>
  </div>
</body>
</html>
