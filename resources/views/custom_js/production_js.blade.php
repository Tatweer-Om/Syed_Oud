<script>
$(document).ready(function() {
    let stocks = [];
    let materials = [];
    let rowIndex = 0;

    // Load stocks for searchable select
    $.get("{{ url('stocks/for-production') }}", function(data) {
        stocks = data || [];
    });

    // Load materials for production (id, material_name, unit, buy_price)
    $.get("{{ url('materials/for-purchase') }}", function(data) {
        materials = data || [];
        if (window.PRODUCTION_DRAFT) fillFormFromDraft();
    });

    // ---------- Stock searchable dropdown ----------
    var $stockSearch = $('#stock_search');
    var $stockId = $('#stock_id');
    var $dropdown = $('#stock_dropdown');
    var highlightedIndex = -1;

    function renderStockDropdown(filter) {
        var list = stocks;
        if (filter && filter.length) {
            var f = filter.toLowerCase();
            list = stocks.filter(function(s) {
                return (s.stock_name || '').toLowerCase().indexOf(f) >= 0 ||
                       (s.barcode || '').toString().indexOf(f) >= 0;
            });
        }
        var html = '';
        list.forEach(function(s, i) {
            html += '<div class="production-stock-option" data-id="' + s.id + '" data-name="' + (s.stock_name || '').replace(/"/g, '&quot;') + '">' + (s.stock_name || '') + (s.barcode ? ' - ' + s.barcode : '') + '</div>';
        });
        if (!html) html = '<div class="production-stock-option text-gray-500">{{ trans("messages.no_stock_found", [], session("locale")) }}</div>';
        $dropdown.html(html).addClass('show');
        highlightedIndex = -1;
    }

    $stockSearch.on('focus', function() {
        renderStockDropdown($(this).val());
    });
    $stockSearch.on('input', function() {
        $stockId.val('');
        renderStockDropdown($(this).val());
    });
    $stockSearch.on('keydown', function(e) {
        var $opts = $dropdown.find('.production-stock-option[data-id]');
        if (e.keyCode === 40) {
            e.preventDefault();
            highlightedIndex = Math.min(highlightedIndex + 1, $opts.length - 1);
            $opts.removeClass('highlight').eq(highlightedIndex).addClass('highlight');
        } else if (e.keyCode === 38) {
            e.preventDefault();
            highlightedIndex = Math.max(highlightedIndex - 1, 0);
            $opts.removeClass('highlight').eq(highlightedIndex).addClass('highlight');
        } else if (e.keyCode === 13 && highlightedIndex >= 0 && $opts[highlightedIndex]) {
            e.preventDefault();
            $opts.eq(highlightedIndex).click();
        } else if (e.keyCode === 27) {
            $dropdown.removeClass('show');
        }
    });
    $(document).on('click', '.production-stock-option[data-id]', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        $stockId.val(id);
        $stockSearch.val(name);
        $dropdown.removeClass('show');
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.production-stock-wrap').length) $dropdown.removeClass('show');
    });

    // ---------- Material row: searchable input (using global dropdown) ----------
    var $globalDropdown = $('#production_material_dropdown_global');
    var activeRow = null;
    var materialHighlightIndex = -1;

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
            html += '<div class="production-material-option" data-id="' + m.id + '" data-name="' + (m.material_name || '').replace(/"/g, '&quot;') + '" data-unit="' + (m.unit || '').replace(/"/g, '&quot;') + '" data-price="' + (parseFloat(m.buy_price) || 0) + '">' + (m.material_name || '').replace(/</g, '&lt;') + '</div>';
        });
        if (!html) html = '<div class="production-material-option text-gray-500">{{ trans("messages.no_material_found", [], session("locale")) }}</div>';
        $globalDropdown.html(html).addClass('show');
        // Position fixed dropdown below input
        var rect = $input[0].getBoundingClientRect();
        $globalDropdown.css({ top: (rect.bottom + 2) + 'px', left: rect.left + 'px', width: Math.max(rect.width, 280) + 'px' });
        materialHighlightIndex = -1;
    }

    function hideGlobalDropdown() {
        $globalDropdown.removeClass('show');
        activeRow = null;
    }

    function addMaterialRow() {
        var idx = rowIndex++;
        var row = '<tr class="production-material-row border-b border-gray-100" data-row="' + idx + '">' +
            '<td class="px-3 py-2 col-material">' +
            '<div class="production-material-wrap">' +
            '<input type="text" class="production-material-search w-full h-10 rounded-lg border border-gray-300 px-3" autocomplete="off" placeholder="{{ trans("messages.search_material_placeholder", [], session("locale")) }}" data-row="' + idx + '" />' +
            '<input type="hidden" class="production-material-id" value="" />' +
            '</div></td>' +
            '<td class="px-2 py-2 text-center col-unit"><input type="text" class="production-unit h-10 rounded-lg border-0 bg-gray-50 px-1 text-center text-sm" style="width:70px" readonly /></td>' +
            '<td class="px-2 py-2 text-center col-price"><span class="production-unit-price inline-block h-10 leading-10 rounded-lg bg-gray-100 px-2 text-center text-sm min-w-[70px]">0.00</span><input type="hidden" class="production-unit-price-val" value="0" /></td>' +
            '<td class="px-3 py-2"><input type="number" min="0" step="0.01" class="production-qty w-full h-10 rounded-lg border border-gray-300 px-3 text-center" value="0" data-row="' + idx + '" /></td>' +
            '<td class="px-3 py-2 text-center"><span class="production-total font-semibold">0.00</span></td>' +
            '<td class="px-2 py-2 text-center"><button type="button" class="production-remove-row text-red-500 hover:text-red-700 p-1" data-row="' + idx + '" title="{{ trans("messages.delete", [], session("locale")) }}"><span class="material-symbols-outlined text-lg">delete</span></button></td>' +
            '</tr>';
        $('#production_materials_body').append(row);
        updateAllRowTotals();
    }

    $(document).on('focus', '.production-material-search', function() {
        activeRow = $(this).closest('tr');
        renderMaterialDropdown($(this), $(this).val());
    });
    $(document).on('input', '.production-material-search', function() {
        activeRow = $(this).closest('tr');
        activeRow.find('.production-material-id').val('');
        renderMaterialDropdown($(this), $(this).val());
    });
    $(document).on('keydown', '.production-material-search', function(e) {
        var $opts = $globalDropdown.find('.production-material-option[data-id]');
        if (e.keyCode === 40) {
            e.preventDefault();
            materialHighlightIndex = Math.min(materialHighlightIndex + 1, $opts.length - 1);
            $opts.removeClass('highlight').eq(materialHighlightIndex).addClass('highlight');
        } else if (e.keyCode === 38) {
            e.preventDefault();
            materialHighlightIndex = Math.max(materialHighlightIndex - 1, 0);
            $opts.removeClass('highlight').eq(materialHighlightIndex).addClass('highlight');
        } else if (e.keyCode === 13 && $opts.length) {
            e.preventDefault();
            var $target = (materialHighlightIndex >= 0 && $opts.eq(materialHighlightIndex).data('id')) ? $opts.eq(materialHighlightIndex) : $opts.filter('[data-id]').first();
            if ($target.length && $target.data('id')) $target.click();
        } else if (e.keyCode === 27) {
            hideGlobalDropdown();
        }
    });
    $(document).on('click', '.production-material-option[data-id]', function() {
        var $opt = $(this);
        var row = activeRow;
        if (!row) return;
        var materialId = $opt.data('id');
        // Check if material already exists in another row
        var otherRow = null;
        $('#production_materials_body tr').each(function() {
            if (this !== row[0] && $(this).find('.production-material-id').val() == materialId) {
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
                    var addQty = parseFloat(row.find('.production-qty').val()) || 0;
                    var existingQty = parseFloat(otherRow.find('.production-qty').val()) || 0;
                    otherRow.find('.production-qty').val(existingQty + addQty);
                    row.find('.production-material-id').val('');
                    row.find('.production-material-search').val('');
                    row.find('.production-unit').val('');
                    row.find('.production-unit-price').text('0.00');
                    row.find('.production-unit-price-val').val(0);
                    row.find('.production-qty').val('0');
                    row.find('.production-total').text('0.00');
                    updateAllRowTotals();
                } else {
                    row.find('.production-material-id').val('');
                    row.find('.production-material-search').val('');
                }
            });
            return;
        }
        row.find('.production-material-id').val(materialId);
        row.find('.production-material-search').val($opt.data('name') || '');
        row.find('.production-unit').val($opt.data('unit') || '');
        var unitPrice = parseFloat($opt.data('price')) || 0;
        row.find('.production-unit-price').text(unitPrice.toFixed(2));
        row.find('.production-unit-price-val').val(unitPrice);
        hideGlobalDropdown();
        updateAllRowTotals();
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.production-material-wrap').length && !$(e.target).closest('#production_material_dropdown_global').length) {
            hideGlobalDropdown();
        }
    });
    $(window).on('scroll', function() { hideGlobalDropdown(); });

    // Clamp non-negative
    function clampNonNegative($input) {
        var v = parseFloat($input.val());
        if (isNaN(v) || v < 0) $input.val(0);
    }
    $('#estimated_output').on('input blur', function() {
        clampNonNegative($(this));
        updateSummary(); // Recalculate cost per unit when estimated output changes
    });
    $(document).on('input blur', '.production-qty', function() {
        clampNonNegative($(this));
        updateAllRowTotals();
    });

    function updateRowTotal($row) {
        var unitPrice = parseFloat($row.find('.production-unit-price-val').val()) || 0;
        var qty = parseFloat($row.find('.production-qty').val()) || 0;
        var total = unitPrice * qty;
        $row.find('.production-total').text(total.toFixed(2));
    }

    function updateAllRowTotals() {
        $('#production_materials_body tr').each(function() {
            updateRowTotal($(this));
        });
        updateSummary();
    }

    function updateSummary() {
        var totalQty = 0;
        var totalItems = 0;
        var totalAmount = 0;
        $('#production_materials_body tr').each(function() {
            var qty = parseFloat($(this).find('.production-qty').val()) || 0;
            var materialId = $(this).find('.production-material-id').val();
            var rowTotal = parseFloat($(this).find('.production-total').text()) || 0;
            if (materialId && qty > 0) {
                totalQty += qty;
                totalItems++;
                totalAmount += rowTotal;
            }
        });
        $('#production_summary_total_qty').text(totalQty.toFixed(2));
        $('#production_summary_total_items').text(totalItems);
        $('#production_summary_total_amount').text(totalAmount.toFixed(2));
        
        // Calculate cost per unit = total_amount / estimated_output
        var estimatedOutput = parseFloat($('#estimated_output').val()) || 0;
        var costPerUnit = estimatedOutput > 0 ? (totalAmount / estimatedOutput) : 0;
        $('#production_summary_cost_per_unit').text(costPerUnit.toFixed(2));
    }

    $(document).on('click', '.production-remove-row', function() {
        $(this).closest('tr').remove();
        updateAllRowTotals();
    });

    $('#add_material_row').on('click', function() {
        addMaterialRow();
    });

    // Start with one empty row (or fill from draft)
    if (!window.PRODUCTION_DRAFT) addMaterialRow();

    function fillFormFromDraft() {
        var d = window.PRODUCTION_DRAFT;
        if (!d) return;
        if (d.production_date) {
            // Format date to YYYY-MM-DD for input[type=date]
            var dateVal = d.production_date;
            if (dateVal.indexOf('T') > -1) dateVal = dateVal.split('T')[0];
            $('#production_date').val(dateVal);
        }
        $('#stock_id').val(d.stock_id);
        $('#stock_search').val((d.stock && d.stock.stock_name) ? d.stock.stock_name : '');
        $('#estimated_output').val(d.estimated_output || 0);
        $('#production_notes').val(d.notes || '');
        $('#production_materials_body tr').remove();
        var list = d.materials_json || [];
        list.forEach(function(m) {
            addMaterialRow();
            var $row = $('#production_materials_body tr').last();
            $row.find('.production-material-id').val(m.material_id);
            $row.find('.production-material-search').val(m.material_name || '');
            $row.find('.production-unit').val(m.unit || '');
            var unitPrice = parseFloat(m.unit_price) || 0;
            $row.find('.production-unit-price').text(unitPrice.toFixed(2));
            $row.find('.production-unit-price-val').val(unitPrice);
            $row.find('.production-qty').val(parseFloat(m.quantity) || 0);
        });
        if (list.length === 0) addMaterialRow();
        updateAllRowTotals();
    }

    // Save as draft
    $('#production_save_btn').on('click', function() {
        if ($(this).prop('disabled')) return;
        var productionDate = $('#production_date').val();
        var stockId = $('#stock_id').val();
        var estimatedOutput = parseFloat($('#estimated_output').val()) || 0;
        var notes = $('#production_notes').val();
        var rows = [];
        var totalAmount = 0;
        $('#production_materials_body tr').each(function() {
            var materialId = $(this).find('.production-material-id').val();
            var qty = parseFloat($(this).find('.production-qty').val()) || 0;
            var unitPrice = parseFloat($(this).find('.production-unit-price-val').val()) || 0;
            var rowTotal = parseFloat($(this).find('.production-total').text()) || 0;
            if (materialId && qty > 0) {
                rows.push({
                    material_id: materialId,
                    material_name: $(this).find('.production-material-search').val() || '',
                    unit: $(this).find('.production-unit').val(),
                    unit_price: unitPrice,
                    quantity: qty,
                    total: rowTotal
                });
                totalAmount += rowTotal;
            }
        });
        if (!stockId) {
            show_notification('error', '{{ trans("messages.please_select_stock", [], session("locale")) }}');
            return;
        }
        if (rows.length === 0) {
            show_notification('error', '{{ trans("messages.add_at_least_one_material", [], session("locale")) }}');
            return;
        }
        var $btn = $('#production_save_btn');
        var origText = $btn.html();
        $btn.prop('disabled', true).html('...');
        var draftId = window.PRODUCTION_EDIT_ID || $('#production_draft_id').val();
        var url = draftId ? "{{ url('production/draft') }}/" + draftId : "{{ url('production/draft') }}";
        var method = draftId ? 'PUT' : 'POST';
        var data = {
            _token: '{{ csrf_token() }}',
            production_date: productionDate,
            stock_id: stockId,
            estimated_output: estimatedOutput,
            total_amount: totalAmount,
            notes: notes,
            materials: rows
        };
        if (method === 'PUT') data._method = 'PUT';
        $.ajax({
            url: url,
            type: method,
            data: data,
            success: function(res) {
                if (res.status === 'success') {
                    show_notification('success', res.message);
                    if (res.redirect_url) window.location.href = res.redirect_url;
                }
                $btn.prop('disabled', false).html(origText);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(origText);
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ __("Something went wrong") }}';
                if (xhr.responseJSON && xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                show_notification('error', msg);
            }
        });
    });
});
</script>
