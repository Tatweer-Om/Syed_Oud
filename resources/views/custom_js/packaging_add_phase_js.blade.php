<script>
$(document).ready(function() {
    var materials = [];
    var rowIndex = 0;
    var PACKAGING_ID = $('#packaging_id').val();

    $.get("{{ url('materials/for-packaging') }}", function(data) {
        materials = data || [];
    });

    var $globalDropdown = $('#packaging_material_dropdown_global');
    var activeRow = null;
    var availableLabel = '{{ trans("messages.available", [], session("locale")) ?: "Available" }}';

    function getMaxAvailableForMaterial(materialId, excludeRow) {
        var mat = materials.find(function(m) { return m.id == materialId; });
        if (!mat) return 0;
        var total = parseFloat(mat.quantity) || 0;
        var usedElsewhere = 0;
        $('#packaging_materials_body tr').each(function() {
            if (excludeRow && this === excludeRow[0]) return;
            if ($(this).find('.packaging-material-id').val() == materialId) {
                usedElsewhere += parseFloat($(this).find('.packaging-qty').val()) || 0;
            }
        });
        return Math.max(0, total - usedElsewhere);
    }

    function validateAndClampRowQty($row) {
        var materialId = $row.find('.packaging-material-id').val();
        if (!materialId) return;
        var maxAvail = getMaxAvailableForMaterial(materialId, $row);
        var qty = parseFloat($row.find('.packaging-qty').val()) || 0;
        var unit = $row.find('.packaging-unit').val() || '';
        if (qty > maxAvail) {
            $row.find('.packaging-qty').val(maxAvail > 0 ? maxAvail : '');
            show_notification('error', '{{ trans("messages.quantity_cannot_exceed_available", [], session("locale")) ?: "Quantity cannot exceed available stock" }}');
        }
        $row.attr('data-available', maxAvail);
        $row.find('.packaging-qty').attr('max', maxAvail);
        $row.find('.packaging-available-hint').text(availableLabel + ': ' + maxAvail.toFixed(2) + ' ' + unit);
    }

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
            var avail = parseFloat(m.quantity) || 0;
            var availText = ' (' + availableLabel + ': ' + avail.toFixed(2) + ' ' + (m.unit || '') + ')';
            html += '<div class="packaging-material-option" data-id="' + m.id + '" data-name="' + (m.material_name || '').replace(/"/g, '&quot;') + '" data-unit="' + (m.unit || '').replace(/"/g, '&quot;') + '" data-price="' + (parseFloat(m.buy_price) || 0) + '" data-available="' + avail + '">' + (m.material_name || '').replace(/</g, '&lt;') + '<span class="text-xs text-gray-500 ml-1">' + availText.replace(/</g, '&lt;') + '</span></div>';
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
            '<td class="px-3 py-2 col-material"><div class="packaging-material-wrap">' +
            '<input type="text" class="packaging-material-search w-full h-10 rounded-lg border border-gray-300 px-3" autocomplete="off" placeholder="{{ trans("messages.search_material_placeholder", [], session("locale")) }}" />' +
            '<input type="hidden" class="packaging-material-id" value="" />' +
            '</div></td>' +
            '<td class="px-2 py-2 text-center"><input type="text" class="packaging-unit h-10 rounded-lg border-0 bg-gray-50 px-1 text-center text-sm w-[70px]" readonly /></td>' +
            '<td class="px-2 py-2 text-center"><span class="packaging-unit-price inline-block h-10 leading-10 rounded-lg bg-gray-100 px-2 text-center text-sm min-w-[70px]">0.00</span><input type="hidden" class="packaging-unit-price-val" value="0" /></td>' +
            '<td class="px-3 py-2"><input type="number" min="0" step="0.01" class="packaging-qty w-full h-10 rounded-lg border border-gray-300 px-3 text-center" placeholder="0" /></td>' +
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
                    var mergedQty = existingQty + addQty;
                    var maxAvail = getMaxAvailableForMaterial(materialId, null);
                    if (mergedQty > maxAvail) {
                        mergedQty = maxAvail;
                        show_notification('warning', '{{ trans("messages.quantity_cannot_exceed_available", [], session("locale")) ?: "Quantity cannot exceed available stock" }}');
                    }
                    otherRow.find('.packaging-qty').val(mergedQty > 0 ? mergedQty : '');
                    otherRow.attr('data-available', maxAvail);
                    otherRow.find('.packaging-qty').attr('max', maxAvail);
                    if (otherRow.find('.packaging-available-hint').length) otherRow.find('.packaging-available-hint').text(availableLabel + ': ' + maxAvail.toFixed(2) + ' ' + (otherRow.find('.packaging-unit').val() || ''));
                    row.find('.packaging-material-id').val('');
                    row.find('.packaging-material-search').val('');
                    row.find('.packaging-unit').val('');
                    row.find('.packaging-unit-price').text('0.00');
                    row.find('.packaging-unit-price-val').val(0);
                    row.find('.packaging-qty').val('');
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
        var available = parseFloat($opt.data('available')) || 0;
        row.find('.packaging-unit-price').text(unitPrice.toFixed(2));
        row.find('.packaging-unit-price-val').val(unitPrice);
        row.attr('data-available', available);
        row.find('.packaging-qty').attr('max', available);
        row.find('.packaging-available-hint').remove();
        row.find('.col-material .packaging-material-wrap').after('<span class="packaging-available-hint block text-xs text-gray-500 mt-0.5">' + availableLabel + ': ' + available.toFixed(2) + ' ' + ($opt.data('unit') || '') + '</span>');
        hideGlobalDropdown();
        validateAndClampRowQty(row);
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
        var raw = ($(this).val() || '').toString().trim();
        var v = parseFloat(raw);
        if (raw === '' || isNaN(v)) { $(this).val(''); }
        if (v < 0) { $(this).val(''); }
        var $row = $(this).closest('tr');
        var materialId = $row.find('.packaging-material-id').val();
        validateAndClampRowQty($row);
        if (materialId) {
            $('#packaging_materials_body tr').each(function() {
                if ($(this).find('.packaging-material-id').val() == materialId && this !== $row[0]) {
                    validateAndClampRowQty($(this));
                }
            });
        }
        updateAllRowTotals();
    });

    $('#production_output_taken').on('input blur', function() {
        var v = parseFloat($(this).val());
        var max = parseFloat($(this).data('max')) || 0;
        if (isNaN(v) || v < 0) {
            $(this).val('');
        } else if (max > 0 && v > max) {
            $(this).val(max.toFixed(2));
        }
    });

    $('#expected_packaging_units').on('input blur', function() {
        var raw = ($(this).val() || '').toString().trim();
        var v = parseFloat(raw);
        if (raw === '' || isNaN(v)) { $(this).val(''); return; }
        if (v < 0) $(this).val('');
    });

    $(document).on('click', '.packaging-remove-row', function() {
        $(this).closest('tr').remove();
        updateAllRowTotals();
    });

    $('#add_material_row').on('click', addMaterialRow);
    addMaterialRow();

    $('#packaging_save_btn').on('click', function() {
        if ($(this).prop('disabled')) return;
        var packagingDate = ($('#packaging_date').val() || '').trim();
        var productionOutputTakenVal = ($('#production_output_taken').val() || '').trim();
        var expectedPackagingUnitsVal = ($('#expected_packaging_units').val() || '').trim();
        var productionOutputTaken = parseFloat(productionOutputTakenVal) || 0;
        var expectedPackagingUnits = parseFloat(expectedPackagingUnitsVal) || 0;
        var maxAvailable = parseFloat($('#production_output_taken').data('max')) || 0;

        if (!packagingDate) {
            show_notification('error', '{{ trans("messages.please_select_date", [], session("locale")) ?: "Please select date" }}');
            return;
        }
        if (productionOutputTakenVal === '') {
            show_notification('error', '{{ trans("messages.please_fill_production_output_taken", [], session("locale")) ?: "Please fill Production output taken" }}');
            return;
        }
        if (expectedPackagingUnitsVal === '') {
            show_notification('error', '{{ trans("messages.please_fill_expected_packaging_units", [], session("locale")) ?: "Please fill Expected packaging units" }}');
            return;
        }
        if (productionOutputTaken < 0) {
            show_notification('error', '{{ trans("messages.production_output_taken", [], session("locale")) ?: "Production output taken" }} {{ trans("messages.cannot_be_negative", [], session("locale")) ?: "cannot be negative" }}');
            return;
        }
        if (productionOutputTaken > maxAvailable) {
            show_notification('error', '{{ trans("messages.production_output_taken", [], session("locale")) ?: "Production output taken" }} {{ trans("messages.cannot_exceed_max", [], session("locale")) ?: "cannot exceed max available" }}');
            return;
        }
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
            url: "{{ url('packaging') }}/" + PACKAGING_ID + "/add-phase",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                packaging_date: packagingDate,
                production_output_taken: productionOutputTaken,
                expected_packaging_units: expectedPackagingUnits,
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
