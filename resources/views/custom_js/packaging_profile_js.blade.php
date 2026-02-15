<script>
$(document).ready(function() {
    var allMaterials = [];
    var packagingMaterials = [];

    $.get("{{ url('materials/for-packaging') }}", function(data) { allMaterials = data || []; });

    function loadPackagingMaterials() {
        $.get("{{ url('packaging') }}/" + PACKAGING_ID + "/materials", function(res) {
            packagingMaterials = res.materials || [];
        });
    }
    loadPackagingMaterials();

    function loadPackagingHistory() {
        $.get("{{ url('packaging') }}/" + PACKAGING_ID + "/history", function(res) {
            var rows = '';
            var history = res.history || [];
            var actionLabels = { 'addition': '{{ trans("messages.addition", [], session("locale")) }}', 'removal': '{{ trans("messages.removal", [], session("locale")) }}', 'wastage': '{{ trans("messages.wastage", [], session("locale")) }}', 'packaging_completed': '{{ trans("messages.packaging_completed", [], session("locale")) }}' };
            if (history.length === 0) {
                rows = '<tr><td colspan="6" class="px-3 py-4 text-center text-gray-500">{{ trans("messages.no_data", [], session("locale")) }}</td></tr>';
            } else {
                history.forEach(function(h) {
                    var actionLabel = actionLabels[h.action] || h.action;
                    var actionClass = 'bg-blue-100 text-blue-700';
                    if (h.action === 'removal') actionClass = 'bg-red-100 text-red-700';
                    if (h.action === 'wastage') actionClass = 'bg-amber-100 text-amber-700';
                    if (h.action === 'packaging_completed') actionClass = 'bg-green-100 text-green-700';
                    var dt = h.created_at ? new Date(h.created_at).toLocaleString() : '-';
                    var quantityStr = (h.action === 'packaging_completed') ? '-' : (parseFloat(h.quantity || 0).toFixed(2) + ' ' + (h.unit || ''));
                    rows += '<tr class="border-t hover:bg-gray-50"><td class="px-3 py-2">' + dt + '</td><td class="px-3 py-2"><span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold ' + actionClass + '">' + actionLabel + '</span></td><td class="px-3 py-2">' + (h.material_name || '-') + '</td><td class="px-3 py-2 text-center">' + quantityStr + '</td><td class="px-3 py-2">' + (h.added_by || '-') + '</td><td class="px-3 py-2 text-xs max-w-[200px] truncate" title="' + (h.notes || '').replace(/"/g, '&quot;') + '">' + (h.notes || '-') + '</td></tr>';
                });
            }
            $('#packaging_history_body').html(rows);
        });
    }
    loadPackagingHistory();

    $('#btn_view_materials_table').on('click', function() { $('#materialsTableModal').removeClass('hidden'); });

    $('#btn_add_material').on('click', function() {
        $('#add_material_search').val(''); $('#add_material_id').val(''); $('#add_material_qty').val(''); $('#add_material_notes').val('');
        $('#addMaterialModal').removeClass('hidden');
    });
    $('#btn_remove_material').on('click', function() {
        $('#remove_material_search').val(''); $('#remove_material_id').val(''); $('#remove_material_qty').val(''); $('#remove_material_notes').val('');
        $('#removeMaterialModal').removeClass('hidden');
    });
    $('#btn_add_wastage').on('click', function() {
        $('#wastage_material_search').val(''); $('#wastage_material_id').val(''); $('#wastage_qty').val(''); $('#wastage_notes').val('');
        $('#addWastageModal').removeClass('hidden');
    });
    $('#btn_complete_packaging').on('click', function() {
        $('#complete_actual_output').val('');
        $('#completePackagingModal').removeClass('hidden');
    });

    $('.close-modal').on('click', function() { $(this).closest('.fixed').addClass('hidden'); });
    $(document).on('click', '.modal-overlay', function(e) { if (e.target === this) $(this).addClass('hidden'); });

    function renderDropdown($container, list, filter, type) {
        var l = list;
        if (filter && filter.length) {
            var f = filter.toLowerCase();
            l = list.filter(function(m) { return (m.material_name || '').toLowerCase().indexOf(f) >= 0; });
        }
        var html = '';
        l.forEach(function(m) {
            html += '<div class="material-select-option" data-id="' + m.id + '" data-name="' + (m.material_name || '').replace(/"/g, '&quot;') + '" data-qty="' + (m.quantity || 0) + '" data-unit="' + (m.unit || '') + '">' + (m.material_name || '') + (type === 'remove' ? ' (' + parseFloat(m.quantity || 0).toFixed(2) + ' ' + (m.unit || '') + ')' : '') + '</div>';
        });
        if (!html) html = '<div class="material-select-option text-gray-500">{{ trans("messages.no_material_found", [], session("locale")) }}</div>';
        $container.html(html).addClass('show');
    }

    $('#add_material_search').on('focus', function() { renderDropdown($('#add_material_dropdown'), allMaterials, $(this).val(), 'add'); });
    $('#add_material_search').on('input', function() { $('#add_material_id').val(''); renderDropdown($('#add_material_dropdown'), allMaterials, $(this).val(), 'add'); });
    $(document).on('click', '#add_material_dropdown .material-select-option[data-id]', function() {
        $('#add_material_id').val($(this).data('id'));
        $('#add_material_search').val($(this).data('name'));
        $('#add_material_dropdown').removeClass('show');
    });

    $('#remove_material_search').on('focus', function() { renderDropdown($('#remove_material_dropdown'), packagingMaterials, $(this).val(), 'remove'); });
    $('#remove_material_search').on('input', function() { $('#remove_material_id').val(''); renderDropdown($('#remove_material_dropdown'), packagingMaterials, $(this).val(), 'remove'); });
    $(document).on('click', '#remove_material_dropdown .material-select-option[data-id]', function() {
        $('#remove_material_id').val($(this).data('id'));
        $('#remove_material_search').val($(this).data('name'));
        $('#remove_material_qty').attr('max', $(this).data('qty'));
        $('#remove_material_dropdown').removeClass('show');
    });

    $('#wastage_material_search').on('focus', function() { renderDropdown($('#wastage_material_dropdown'), allMaterials, $(this).val(), 'add'); });
    $('#wastage_material_search').on('input', function() { $('#wastage_material_id').val(''); renderDropdown($('#wastage_material_dropdown'), allMaterials, $(this).val(), 'add'); });
    $(document).on('click', '#wastage_material_dropdown .material-select-option[data-id]', function() {
        $('#wastage_material_id').val($(this).data('id'));
        $('#wastage_material_search').val($(this).data('name'));
        $('#wastage_material_dropdown').removeClass('show');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#add_material_search, #add_material_dropdown').length) $('#add_material_dropdown').removeClass('show');
        if (!$(e.target).closest('#remove_material_search, #remove_material_dropdown').length) $('#remove_material_dropdown').removeClass('show');
        if (!$(e.target).closest('#wastage_material_search, #wastage_material_dropdown').length) $('#wastage_material_dropdown').removeClass('show');
    });

    function doAjax(url, data, successMsg) {
        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            success: function(res) {
                if (res.status === 'success') {
                    show_notification('success', res.message);
                    $('.modal-overlay').addClass('hidden');
                    location.reload();
                } else {
                    show_notification('error', res.message || 'Error');
                }
            },
            error: function(xhr) {
                show_notification('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
            }
        });
    }

    $('#confirm_add_material').on('click', function() {
        var mid = $('#add_material_id').val();
        var qty = parseFloat($('#add_material_qty').val());
        var notes = ($('#add_material_notes').val() || '').trim();
        if (!mid) { show_notification('error', '{{ trans("messages.please_select_material", [], session("locale")) }}'); return; }
        if (!qty || qty <= 0) { show_notification('error', '{{ trans("messages.enter_valid_quantity", [], session("locale")) }}'); return; }
        if (!notes) { show_notification('error', '{{ trans("messages.please_add_notes", [], session("locale")) }}'); return; }
        doAjax("{{ url('packaging') }}/" + PACKAGING_ID + "/add-material", { _token: '{{ csrf_token() }}', material_id: mid, quantity: qty, notes: notes }, '');
    });

    $('#confirm_remove_material').on('click', function() {
        var mid = $('#remove_material_id').val();
        var qty = parseFloat($('#remove_material_qty').val());
        var notes = ($('#remove_material_notes').val() || '').trim();
        if (!mid) { show_notification('error', '{{ trans("messages.please_select_material", [], session("locale")) }}'); return; }
        if (!qty || qty <= 0) { show_notification('error', '{{ trans("messages.enter_valid_quantity", [], session("locale")) }}'); return; }
        if (!notes) { show_notification('error', '{{ trans("messages.please_add_notes", [], session("locale")) }}'); return; }
        doAjax("{{ url('packaging') }}/" + PACKAGING_ID + "/remove-material", { _token: '{{ csrf_token() }}', material_id: mid, quantity: qty, notes: notes }, '');
    });

    $('#confirm_add_wastage').on('click', function() {
        var mid = $('#wastage_material_id').val();
        var qty = parseFloat($('#wastage_qty').val());
        var notes = ($('#wastage_notes').val() || '').trim();
        if (!mid) { show_notification('error', '{{ trans("messages.please_select_material", [], session("locale")) }}'); return; }
        if (!qty || qty <= 0) { show_notification('error', '{{ trans("messages.enter_valid_quantity", [], session("locale")) }}'); return; }
        if (!notes) { show_notification('error', '{{ trans("messages.please_add_notes", [], session("locale")) }}'); return; }
        doAjax("{{ url('packaging') }}/" + PACKAGING_ID + "/add-wastage", { _token: '{{ csrf_token() }}', material_id: mid, quantity: qty, notes: notes }, '');
    });

    $('#confirm_complete_packaging').on('click', function() {
        var actual = parseFloat($('#complete_actual_output').val());
        if (!actual || actual <= 0) {
            show_notification('error', '{{ trans("messages.enter_valid_quantity", [], session("locale")) }}');
            return;
        }
        $('#completePackagingModal').addClass('hidden');
        $.ajax({
            url: "{{ url('packaging') }}/" + PACKAGING_ID + "/complete",
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', actual_output: actual },
            success: function(res) {
                if (res.status === 'success') {
                    show_notification('success', res.message);
                    window.location.href = "{{ url('production') }}/" + PRODUCTION_ID + "/profile";
                } else {
                    show_notification('error', res.message || 'Error');
                }
            },
            error: function(xhr) {
                show_notification('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
            }
        });
    });
});
</script>
