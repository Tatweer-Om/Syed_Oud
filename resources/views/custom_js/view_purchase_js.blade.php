<script>
$(document).ready(function() {
    var currentPage = 1;

    function loadDrafts(page) {
        page = page || 1;
        currentPage = page;
        $.get("{{ url('purchase/drafts') }}", { page: page }, function(res) {
            var rows = '';
            var start = (res.current_page - 1) * res.per_page;
            (res.data || []).forEach(function(d, i) {
                var supplierName = (d.supplier && d.supplier.supplier_name) ? d.supplier.supplier_name : '-';
                var isCompleted = !!d.is_completed;
                var invoiceAmt = parseFloat(d.invoice_amount || d.total_amount || 0).toFixed(2);
                var actionCells = '';
                if (isCompleted) {
                    actionCells = '<a href="{{ url("purchase") }}/' + d.id + '/profile" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700" title="{{ trans("messages.view_profile", [], session("locale")) }}"><span class="material-symbols-outlined text-sm">visibility</span> {{ trans("messages.view_profile", [], session("locale")) }}</a>' +
                        '<button type="button" class="purchase-view-materials-btn inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-gray-600 text-white text-xs font-semibold hover:bg-gray-700" data-id="' + d.id + '" data-completed="1"><span class="material-symbols-outlined text-sm">inventory_2</span> {{ trans("messages.view_materials", [], session("locale")) }}</button>' +
                        '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-gray-300 text-gray-500 text-xs cursor-not-allowed" title="{{ trans("messages.completed", [], session("locale")) }}"><span class="material-symbols-outlined text-sm">edit</span> {{ trans("messages.edit", [], session("locale")) }}</span>' +
                        '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-gray-300 text-gray-500 text-xs cursor-not-allowed"><span class="material-symbols-outlined text-sm">delete</span></span>';
                } else {
                    actionCells = '<button type="button" class="purchase-complete-btn inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-green-600 text-white text-xs font-semibold hover:bg-green-700" data-id="' + d.id + '" title="{{ trans("messages.complete", [], session("locale")) }}"><span class="material-symbols-outlined text-sm">check_circle</span> {{ trans("messages.complete", [], session("locale")) }}</button>' +
                        '<a href="{{ url("purchase/draft") }}/' + d.id + '/edit" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700"><span class="material-symbols-outlined text-sm">edit</span> {{ trans("messages.edit", [], session("locale")) }}</a>' +
                        '<button type="button" class="purchase-delete-btn inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700" data-id="' + d.id + '"><span class="material-symbols-outlined text-sm">delete</span></button>' +
                        '<button type="button" class="purchase-view-materials-btn inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-gray-600 text-white text-xs font-semibold hover:bg-gray-700" data-id="' + d.id + '"><span class="material-symbols-outlined text-sm">inventory_2</span> {{ trans("messages.view_materials", [], session("locale")) }}</button>';
                }
                rows += '<tr class="border-t hover:bg-pink-50/50 ' + (isCompleted ? 'bg-gray-50' : '') + '" data-id="' + d.id + '" data-completed="' + (isCompleted ? '1' : '0') + '">' +
                    '<td class="px-3 py-2">' + (start + i + 1) + '</td>' +
                    '<td class="px-3 py-2 font-semibold">' + supplierName + '</td>' +
                    '<td class="px-3 py-2">' + (d.invoice_no || '-') + '</td>' +
                    '<td class="px-3 py-2 text-center font-semibold">' + invoiceAmt + '</td>' +
                    '<td class="px-3 py-2 text-center">' + parseFloat(d.shipping_cost || 0).toFixed(2) + '</td>' +
                    '<td class="px-3 py-2 text-center">' + parseFloat(d.total_quantity || 0) + '</td>' +
                    '<td class="px-3 py-2 text-center font-semibold">' + parseFloat(d.total_amount || 0).toFixed(2) + '</td>' +
                    '<td class="px-3 py-2"><div class="flex flex-wrap gap-1 justify-center">' + actionCells + '</div></td></tr>';
            });
            $('#purchase_drafts_body').html(rows || '<tr><td colspan="8" class="px-3 py-6 text-center text-gray-500">{{ trans("messages.no_data", [], session("locale")) }}</td></tr>');
            renderPagination(res);
        });
    }

    function renderPagination(res) {
        var html = '';
        html += '<li class="w-10 h-10 flex items-center justify-center rounded-full ' + (!res.prev_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300') + '"><a href="?page=' + (res.current_page - 1) + '">&laquo;</a></li>';
        for (var i = 1; i <= res.last_page; i++) {
            html += '<li class="w-10 h-10 flex items-center justify-center rounded-full"><a href="?page=' + i + '" class="flex items-center justify-center w-10 h-10 ' + (res.current_page === i ? 'bg-[var(--primary-color)] text-white' : 'bg-gray-200 hover:bg-gray-300') + '">' + i + '</a></li>';
        }
        html += '<li class="w-10 h-10 flex items-center justify-center rounded-full ' + (!res.next_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300') + '"><a href="?page=' + (res.current_page + 1) + '">&raquo;</a></li>';
        $('#purchase_drafts_pagination').html(html);
    }

    $(document).on('click', '#purchase_drafts_pagination a', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        if (!href || href === '#') return;
        var page = 1;
        var match = href.match(/page=(\d+)/);
        if (match) page = parseInt(match[1], 10);
        loadDrafts(page);
    });

    $(document).on('click', '.purchase-complete-btn', function() {
        var id = $(this).data('id');
        if (!id) return;
        var $btn = $(this);
        Swal.fire({
            title: '{{ trans("messages.complete_purchase", [], session("locale")) }}',
            text: '{{ trans("messages.confirm_complete_purchase", [], session("locale")) }}',
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
                url: "{{ url('purchase/draft') }}/" + id + "/complete",
                type: "POST",
                data: { _token: '{{ csrf_token() }}' },
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(res) {
                    if (res.status === 'success') {
                        show_notification('success', res.message);
                        if (res.redirect_url) window.location.href = res.redirect_url;
                        else loadDrafts(currentPage);
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

    $(document).on('click', '.purchase-delete-btn', function() {
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
                url: "{{ url('purchase/draft') }}/" + id,
                type: "DELETE",
                data: { _token: '{{ csrf_token() }}' },
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(res) {
                    if (res.status === 'success') {
                        show_notification('success', res.message);
                        loadDrafts(currentPage);
                    }
                },
                error: function() {
                    show_notification('error', '{{ trans("messages.delete_error", [], session("locale")) }}');
                }
            });
        });
    });

    $(document).on('click', '.purchase-view-materials-btn', function() {
        var id = $(this).data('id');
        var isCompleted = $(this).data('completed') === 1 || $(this).data('completed') === true;
        var url = isCompleted ? "{{ url('purchase') }}/" + id : "{{ url('purchase/draft') }}/" + id;
        $.get(url, function(res) {
            if (res.status !== 'success' || !res.draft) return;
            var materials = res.draft.materials_json || [];
            var body = '';
            materials.forEach(function(m) {
                body += '<tr class="border-t">' +
                    '<td class="px-2 py-2">' + (m.material_name || '-') + '</td>' +
                    '<td class="px-2 py-2 text-center">' + (m.unit || '-') + '</td>' +
                    '<td class="px-2 py-2 text-center">' + parseFloat(m.price || 0).toFixed(2) + '</td>' +
                    '<td class="px-2 py-2 text-center">' + parseFloat(m.unit_price_plus_shipping || m.price || 0).toFixed(2) + '</td>' +
                    '<td class="px-2 py-2 text-center">' + (m.quantity || 0) + '</td>' +
                    '<td class="px-2 py-2 text-center font-semibold">' + parseFloat(m.total || 0).toFixed(2) + '</td>' +
                    '</tr>';
            });
            $('#view_materials_body').html(body || '<tr><td colspan="6" class="text-center py-4 text-gray-500">{{ trans("messages.no_data", [], session("locale")) }}</td></tr>');
            $('#viewMaterialsModal').removeClass('hidden');
        });
    });

    $('#closeViewMaterialsModal').on('click', function() {
        $('#viewMaterialsModal').addClass('hidden');
    });
    $('#viewMaterialsModal').on('click', function(e) {
        if (e.target.id === 'viewMaterialsModal') $('#viewMaterialsModal').addClass('hidden');
    });

    loadDrafts(1);
});
</script>
