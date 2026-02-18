<script>
$(document).ready(function() {
    var currentPage = 1;

    function loadProductions(page) {
        page = page || 1;
        currentPage = page;
        $.get("{{ url('production/all') }}", { page: page })
        .done(function(res) {
            console.log('Production API response:', res);
            var rows = '';
            var start = (res.current_page - 1) * res.per_page;
            (res.data || []).forEach(function(d, i) {
                var stockName = (d.stock && d.stock.stock_name) ? d.stock.stock_name : '-';
                var isCompleted = !!d.is_completed;
                var prodStatus = (d.status || '').toLowerCase();
                var batchId = d.batch_id || '-';
                var totalAmount = parseFloat(d.total_amount || 0);
                var estimatedOutput = parseFloat(d.estimated_output || 0);
                var costPerUnit = estimatedOutput > 0 ? (totalAmount / estimatedOutput) : 0;
                var packagingStatusBadge = '-';
                if (isCompleted && d.packaging_status) {
                    if (d.packaging_status === '{{ trans("messages.completed", [], session("locale")) }}') {
                        packagingStatusBadge = '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-purple-100 text-purple-700 text-xs font-semibold"><span class="material-symbols-outlined text-sm">inventory_2</span> ' + d.packaging_status + '</span>';
                    } else if (d.packaging_status === '{{ trans("messages.under_process", [], session("locale")) }}') {
                        packagingStatusBadge = '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold"><span class="material-symbols-outlined text-sm">schedule</span> ' + d.packaging_status + '</span>';
                    } else {
                        packagingStatusBadge = d.packaging_status;
                    }
                }
                var statusBadge;
                if (!isCompleted) {
                    statusBadge = '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold"><span class="material-symbols-outlined text-sm">pending</span> {{ trans("messages.draft", [], session("locale")) }}</span>';
                } else if (prodStatus === 'completed') {
                    statusBadge = '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold"><span class="material-symbols-outlined text-sm">check_circle</span> {{ trans("messages.completed", [], session("locale")) }}</span>';
                } else {
                    statusBadge = '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold"><span class="material-symbols-outlined text-sm">schedule</span> {{ trans("messages.under_process", [], session("locale")) }}</span>';
                }
                
                // Get the actual numeric ID for API calls
                var actualId = isCompleted ? d.id : d.draft_id;
                
                var actionCells = '';
                if (isCompleted) {
                    // Approved production - show production, packaging, invoice (if completed), and view materials (no edit/delete)
                    actionCells = '<a href="{{ url("production") }}/' + actualId + '/profile" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700" title="{{ trans("messages.production", [], session("locale")) }}"><span class="material-symbols-outlined text-sm">visibility</span> {{ trans("messages.production", [], session("locale")) }}</a>' +
                        '<a href="{{ url("production") }}/' + actualId + '/packaging" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-purple-600 text-white text-xs font-semibold hover:bg-purple-700" title="{{ trans("messages.packaging", [], session("locale")) }}"><span class="material-symbols-outlined text-sm">inventory_2</span> {{ trans("messages.packaging", [], session("locale")) }}</a>' +
                        (prodStatus === 'completed' ? '<a href="{{ url("production") }}/' + actualId + '/invoice" target="_blank" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700" title="{{ trans("messages.production_invoice", [], session("locale")) }}"><span class="material-symbols-outlined text-sm">receipt_long</span> {{ trans("messages.production_invoice", [], session("locale")) }}</a>' : '') +
                        '<button type="button" class="production-view-materials-btn inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-gray-600 text-white text-xs font-semibold hover:bg-gray-700" data-id="' + actualId + '" data-completed="1"><span class="material-symbols-outlined text-sm">inventory_2</span> {{ trans("messages.view_materials", [], session("locale")) }}</button>';
                } else {
                    // Draft - show approve, edit, delete, view materials
                    actionCells = '<button type="button" class="production-approve-btn inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-green-600 text-white text-xs font-semibold hover:bg-green-700" data-id="' + actualId + '" title="{{ trans("messages.approve", [], session("locale")) }}"><span class="material-symbols-outlined text-sm">check_circle</span> {{ trans("messages.approve", [], session("locale")) }}</button>' +
                        '<a href="{{ url("production/draft") }}/' + actualId + '/edit" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700"><span class="material-symbols-outlined text-sm">edit</span></a>' +
                        '<button type="button" class="production-delete-btn inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700" data-id="' + actualId + '"><span class="material-symbols-outlined text-sm">delete</span></button>' +
                        '<button type="button" class="production-view-materials-btn inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-gray-600 text-white text-xs font-semibold hover:bg-gray-700" data-id="' + actualId + '"><span class="material-symbols-outlined text-sm">inventory_2</span> {{ trans("messages.view_materials", [], session("locale")) }}</button>';
                }
                var rowClass = 'border-t hover:bg-pink-50/50';
                if (prodStatus === 'completed') rowClass += ' bg-green-50/30';
                else if (isCompleted) rowClass += ' bg-blue-50/30';
                rows += '<tr class="' + rowClass + '" data-id="' + actualId + '" data-completed="' + (isCompleted ? '1' : '0') + '">' +
                    '<td class="px-3 py-2">' + (start + i + 1) + '</td>' +
                    '<td class="px-3 py-2 font-semibold">' + batchId + '</td>' +
                    '<td class="px-3 py-2">' + stockName + '</td>' +
                    '<td class="px-3 py-2 text-center">' + estimatedOutput.toFixed(2) + '</td>' +
                    '<td class="px-3 py-2 text-center">' + (d.total_items || 0) + '</td>' +
                    '<td class="px-3 py-2 text-center">' + parseFloat(d.total_quantity || 0).toFixed(2) + '</td>' +
                    '<td class="px-3 py-2 text-center">' + totalAmount.toFixed(2) + '</td>' +
                    '<td class="px-3 py-2 text-center font-semibold text-green-600">' + costPerUnit.toFixed(2) + '</td>' +
                    '<td class="px-3 py-2 text-center">' + statusBadge + '</td>' +
                    '<td class="px-3 py-2 text-center">' + packagingStatusBadge + '</td>' +
                    '<td class="px-3 py-2"><div class="flex flex-wrap gap-1 justify-center">' + actionCells + '</div></td></tr>';
            });
            $('#production_drafts_body').html(rows || '<tr><td colspan="11" class="px-3 py-6 text-center text-gray-500">{{ trans("messages.no_data", [], session("locale")) }}</td></tr>');
            renderPagination(res);
        })
        .fail(function(xhr, status, error) {
            console.error('Production API error:', status, error);
            console.error('Response:', xhr.responseText);
            $('#production_drafts_body').html('<tr><td colspan="11" class="px-3 py-6 text-center text-red-500">Error loading data</td></tr>');
        });
    }

    function renderPagination(res) {
        var html = '';
        html += '<li class="w-10 h-10 flex items-center justify-center rounded-full ' + (!res.prev_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300') + '"><a href="?page=' + (res.current_page - 1) + '">&laquo;</a></li>';
        for (var i = 1; i <= res.last_page; i++) {
            html += '<li class="w-10 h-10 flex items-center justify-center rounded-full"><a href="?page=' + i + '" class="flex items-center justify-center w-10 h-10 ' + (res.current_page === i ? 'bg-[var(--primary-color)] text-white' : 'bg-gray-200 hover:bg-gray-300') + '">' + i + '</a></li>';
        }
        html += '<li class="w-10 h-10 flex items-center justify-center rounded-full ' + (!res.next_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300') + '"><a href="?page=' + (res.current_page + 1) + '">&raquo;</a></li>';
        $('#production_drafts_pagination').html(html);
    }

    $(document).on('click', '#production_drafts_pagination a', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        if (!href || href === '#') return;
        var page = 1;
        var match = href.match(/page=(\d+)/);
        if (match) page = parseInt(match[1], 10);
        loadProductions(page);
    });

    // Approve production draft
    $(document).on('click', '.production-approve-btn', function() {
        var id = $(this).data('id');
        if (!id) return;
        var $btn = $(this);
        Swal.fire({
            title: '{{ trans("messages.approve_production", [], session("locale")) }}',
            text: '{{ trans("messages.confirm_approve_production", [], session("locale")) }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ trans("messages.yes", [], session("locale")) }}',
            cancelButtonText: '{{ trans("messages.cancel", [], session("locale")) }}',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $btn.prop('disabled', true);
            $.ajax({
                url: "{{ url('production/draft') }}/" + id + "/complete",
                type: "POST",
                data: { _token: '{{ csrf_token() }}' },
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(res) {
                    if (res.status === 'success') {
                        show_notification('success', res.message);
                        loadProductions(currentPage);
                    }
                    $btn.prop('disabled', false);
                },
                error: function() {
                    $btn.prop('disabled', false);
                    show_notification('error', '{{ trans("messages.error", [], session("locale")) }}');
                }
            });
        });
    });

    // Delete draft
    $(document).on('click', '.production-delete-btn', function() {
        var id = $(this).data('id');
        if (!id) return;
        Swal.fire({
            title: '{{ trans("messages.confirm_delete_title", [], session("locale")) }}',
            text: '{{ trans("messages.confirm_delete_draft_text", [], session("locale")) }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '{{ trans("messages.yes_delete", [], session("locale")) }}',
            cancelButtonText: '{{ trans("messages.cancel", [], session("locale")) }}'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('production/draft') }}/" + id,
                type: "DELETE",
                data: { _token: '{{ csrf_token() }}' },
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(res) {
                    if (res.status === 'success') {
                        show_notification('success', res.message);
                        loadProductions(currentPage);
                    }
                },
                error: function() {
                    show_notification('error', '{{ trans("messages.delete_error", [], session("locale")) }}');
                }
            });
        });
    });

    // View materials
    $(document).on('click', '.production-view-materials-btn', function() {
        var id = $(this).data('id');
        var isCompleted = $(this).data('completed') === 1 || $(this).data('completed') === true;
        var url = isCompleted ? "{{ url('production') }}/" + id : "{{ url('production/draft') }}/" + id;
        $.get(url, function(res) {
            if (res.status !== 'success' || !res.draft) return;
            var materials = res.draft.materials_json || [];
            var body = '';
            var totalCost = 0;
            materials.forEach(function(m) {
                var unitPrice = parseFloat(m.unit_price || 0);
                var qty = parseFloat(m.quantity || 0);
                var rowTotal = parseFloat(m.total || (unitPrice * qty));
                totalCost += rowTotal;
                body += '<tr class="border-t">' +
                    '<td class="px-2 py-2">' + (m.material_name || '-') + '</td>' +
                    '<td class="px-2 py-2 text-center">' + (m.unit || '-') + '</td>' +
                    '<td class="px-2 py-2 text-center">' + unitPrice.toFixed(2) + '</td>' +
                    '<td class="px-2 py-2 text-center">' + qty.toFixed(2) + '</td>' +
                    '<td class="px-2 py-2 text-center font-semibold">' + rowTotal.toFixed(2) + '</td>' +
                    '</tr>';
            });
            $('#view_production_materials_body').html(body || '<tr><td colspan="5" class="text-center py-4 text-gray-500">{{ trans("messages.no_data", [], session("locale")) }}</td></tr>');
            $('#view_production_total_cost').text(totalCost.toFixed(2));
            $('#viewProductionMaterialsModal').removeClass('hidden');
        });
    });

    $('#closeViewProductionMaterialsModal').on('click', function() {
        $('#viewProductionMaterialsModal').addClass('hidden');
    });
    $('#viewProductionMaterialsModal').on('click', function(e) {
        if (e.target.id === 'viewProductionMaterialsModal') $('#viewProductionMaterialsModal').addClass('hidden');
    });

    loadProductions(1);
});
</script>
