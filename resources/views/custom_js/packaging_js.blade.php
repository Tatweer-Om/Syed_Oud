<script>
$(document).ready(function() {
    var materials = [];
    var rowIndex = 0;

    $.get("{{ url('materials/for-packaging') }}", function(data) {
        materials = data || [];
    });

    var $globalDropdown = $('#packaging_material_dropdown_global');
    var activeRow = null;

    function renderMaterialDropdown($input, filter) {
        var list = materials;
        if (filter && filter.length) {
            var f = filter.toLowerCase();
            list = materials.filter(function(m) {
                return (m.material_name || '').toLowerCase().indexOf(f) >= 0;
            });
        }
        var html = '';
        list.forEach(function(m) {
            html += '<div class="packaging-material-option" data-id="' + m.id + '" data-name="' + (m.material_name || '').replace(/"/g, '&quot;') + '" data-unit="' + (m.unit || '').replace(/"/g, '&quot;') + '" data-price="' + (parseFloat(m.buy_price) || 0) + '">' + (m.material_name || '').replace(/</g, '&lt;') + '</div>';
        });
        if (!html) html = '<div class="packaging-material-option text-gray-500">{{ trans("messages.no_material_found", [], session("locale")) }}</div>';
        $globalDropdown.html(html).addClass('show');
        var rect = $input[0].getBoundingClientRect();
        $globalDropdown.css({ top: (rect.bottom + 2) + 'px', left: rect.left + 'px', width: Math.max(rect.width, 280) + 'px' });
    }

    function hideGlobalDropdown() {
        $globalDropdown.removeClass('show');
        activeRow = null;
    }

    function addMaterialRow() {
        var idx = rowIndex++;
        var row = '<tr class="border-b border-gray-100" data-row="' + idx + '">' +
            '<td class="px-3 py-2"><div class="packaging-material-wrap">' +
            '<input type="text" class="packaging-material-search w-full h-10 rounded-lg border border-gray-300 px-3" autocomplete="off" placeholder="{{ trans("messages.search_material_placeholder", [], session("locale")) }}" />' +
            '<input type="hidden" class="packaging-material-id" value="" />' +
            '</div></td>' +
            '<td class="px-2 py-2 text-center"><input type="text" class="packaging-unit h-10 rounded-lg border-0 bg-gray-50 px-1 text-center text-sm w-[70px]" readonly /></td>' +
            '<td class="px-2 py-2 text-center"><span class="packaging-unit-price inline-block h-10 leading-10 rounded-lg bg-gray-100 px-2 text-center text-sm min-w-[70px]">0.00</span><input type="hidden" class="packaging-unit-price-val" value="0" /></td>' +
            '<td class="px-3 py-2"><input type="number" min="0" step="0.01" class="packaging-qty w-full h-10 rounded-lg border border-gray-300 px-3 text-center" value="0" /></td>' +
            '<td class="px-3 py-2 text-center"><span class="packaging-total font-semibold">0.00</span></td>' +
            '<td class="px-2 py-2 text-center"><button type="button" class="packaging-remove-row text-red-500 hover:text-red-700 p-1"><span class="material-symbols-outlined text-lg">delete</span></button></td>' +
            '</tr>';
        $('#packaging_materials_body').append(row);
        updateAllRowTotals();
    }

    $(document).on('focus', '.packaging-material-search', function() {
        activeRow = $(this).closest('tr');
        renderMaterialDropdown($(this), $(this).val());
    });
    $(document).on('input', '.packaging-material-search', function() {
        activeRow = $(this).closest('tr');
        activeRow.find('.packaging-material-id').val('');
        renderMaterialDropdown($(this), $(this).val());
    });
    $(document).on('click', '.packaging-material-option[data-id]', function() {
        var $opt = $(this);
        var row = activeRow;
        if (!row) return;
        var materialId = $opt.data('id');
        // Check if material already exists in another row
        var otherRow = null;
        $('#packaging_materials_body tr').each(function() {
            if (this !== row[0] && $(this).find('.packaging-material-id').val() == materialId) {
                otherRow = $(this);
                return false;
            }
        });
        if (otherRow && otherRow.length) {
            hideGlobalDropdown();
            Swal.fire({
                title: '{{ trans("messages.material_already_added", [], session("locale")) }}',
                text: '{{ trans("messages.material_already_added_increase_quantity", [], session("locale")) }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ trans("messages.yes", [], session("locale")) }}',
                cancelButtonText: '{{ trans("messages.cancel", [], session("locale")) }}'
            }).then(function(result) {
                if (result.isConfirmed) {
                    var addQty = parseFloat(row.find('.packaging-qty').val()) || 0;
                    var existingQty = parseFloat(otherRow.find('.packaging-qty').val()) || 0;
                    otherRow.find('.packaging-qty').val(existingQty + addQty);
                    row.find('.packaging-material-id').val('');
                    row.find('.packaging-material-search').val('');
                    row.find('.packaging-unit').val('');
                    row.find('.packaging-unit-price').text('0.00');
                    row.find('.packaging-unit-price-val').val(0);
                    row.find('.packaging-qty').val('0');
                    row.find('.packaging-total').text('0.00');
                    updateAllRowTotals();
                } else {
                    row.find('.packaging-material-id').val('');
                    row.find('.packaging-material-search').val('');
                }
            });
            return;
        }
        row.find('.packaging-material-id').val(materialId);
        row.find('.packaging-material-search').val($opt.data('name') || '');
        row.find('.packaging-unit').val($opt.data('unit') || '');
        var unitPrice = parseFloat($opt.data('price')) || 0;
        row.find('.packaging-unit-price').text(unitPrice.toFixed(2));
        row.find('.packaging-unit-price-val').val(unitPrice);
        hideGlobalDropdown();
        updateAllRowTotals();
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.packaging-material-wrap').length && !$(e.target).closest('#packaging_material_dropdown_global').length) hideGlobalDropdown();
    });

    function updateRowTotal($row) {
        var unitPrice = parseFloat($row.find('.packaging-unit-price-val').val()) || 0;
        var qty = parseFloat($row.find('.packaging-qty').val()) || 0;
        $row.find('.packaging-total').text((unitPrice * qty).toFixed(2));
    }

    function updateAllRowTotals() {
        $('#packaging_materials_body tr').each(function() { updateRowTotal($(this)); });
        var totalQty = 0, totalItems = 0, totalAmount = 0;
        $('#packaging_materials_body tr').each(function() {
            var qty = parseFloat($(this).find('.packaging-qty').val()) || 0;
            var materialId = $(this).find('.packaging-material-id').val();
            var rowTotal = parseFloat($(this).find('.packaging-total').text()) || 0;
            if (materialId && qty > 0) {
                totalQty += qty;
                totalItems++;
                totalAmount += rowTotal;
            }
        });
        $('#packaging_summary_total_qty').text(totalQty.toFixed(2));
        $('#packaging_summary_total_items').text(totalItems);
        $('#packaging_summary_total_amount').text(totalAmount.toFixed(2));
    }

    $(document).on('input blur', '.packaging-qty', function() {
        var v = parseFloat($(this).val());
        if (isNaN(v) || v < 0) $(this).val(0);
        updateAllRowTotals();
    });

    $(document).on('click', '.packaging-remove-row', function() {
        $(this).closest('tr').remove();
        updateAllRowTotals();
    });

    $('#add_material_row').on('click', addMaterialRow);
    addMaterialRow();

    $('#packaging_save_btn').on('click', function() {
        if ($(this).prop('disabled')) return;
        var productionId = $('#production_id').val();
        var rows = [];
        $('#packaging_materials_body tr').each(function() {
            var materialId = $(this).find('.packaging-material-id').val();
            var qty = parseFloat($(this).find('.packaging-qty').val()) || 0;
            if (materialId && qty > 0) {
                rows.push({
                    material_id: materialId,
                    quantity: qty
                });
            }
        });
        if (rows.length === 0) {
            show_notification('error', '{{ trans("messages.add_at_least_one_material", [], session("locale")) }}');
            return;
        }
        var $btn = $('#packaging_save_btn');
        $btn.prop('disabled', true).html('...');
        $.ajax({
            url: "{{ url('packaging') }}",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                production_id: productionId,
                materials: rows
            },
            success: function(res) {
                if (res.status === 'success') {
                    show_notification('success', res.message);
                    if (res.redirect) window.location.href = res.redirect;
                }
                $btn.prop('disabled', false).html('<span class="material-symbols-outlined">save</span> {{ trans("messages.save", [], session("locale")) }}');
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<span class="material-symbols-outlined">save</span> {{ trans("messages.save", [], session("locale")) }}');
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error';
                show_notification('error', msg);
            }
        });
    });
});
</script>
