<script>
$(document).ready(function() {
    let suppliers = [];
    let materials = [];
    let rowIndex = 0;

    // Load suppliers for searchable select
    $.get("{{ url('suppliers/all') }}", function(data) {
        suppliers = data || [];
    });

    // Load materials for purchase (id, material_name, unit, buy_price)
    $.get("{{ url('materials/for-purchase') }}", function(data) {
        materials = data || [];
        if (window.PURCHASE_DRAFT) fillFormFromDraft();
    });

    // ---------- Supplier searchable dropdown ----------
    var $supplierSearch = $('#supplier_search');
    var $supplierId = $('#supplier_id');
    var $dropdown = $('#supplier_dropdown');
    var highlightedIndex = -1;

    function renderSupplierDropdown(filter) {
        var list = suppliers;
        if (filter && filter.length) {
            var f = filter.toLowerCase();
            list = suppliers.filter(function(s) {
                return (s.supplier_name || '').toLowerCase().indexOf(f) >= 0 ||
                       (s.phone || '').toString().indexOf(f) >= 0;
            });
        }
        var html = '';
        list.forEach(function(s, i) {
            html += '<div class="purchase-supplier-option" data-id="' + s.id + '" data-name="' + (s.supplier_name || '').replace(/"/g, '&quot;') + '">' + (s.supplier_name || '') + (s.phone ? ' - ' + s.phone : '') + '</div>';
        });
        if (!html) html = '<div class="purchase-supplier-option text-gray-500">No supplier found</div>';
        $dropdown.html(html).addClass('show');
        highlightedIndex = -1;
    }

    $supplierSearch.on('focus', function() {
        renderSupplierDropdown($(this).val());
    });
    $supplierSearch.on('input', function() {
        $supplierId.val('');
        renderSupplierDropdown($(this).val());
    });
    $supplierSearch.on('keydown', function(e) {
        var $opts = $dropdown.find('.purchase-supplier-option[data-id]');
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
    $(document).on('click', '.purchase-supplier-option[data-id]', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        $supplierId.val(id);
        $supplierSearch.val(name);
        $dropdown.removeClass('show');
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.purchase-supplier-wrap').length) $dropdown.removeClass('show');
    });

    // ---------- Add Supplier from purchase page ----------
    var $addSupplierModal = $('#purchaseAddSupplierModal');
    var $addSupplierForm = $('#purchaseAddSupplierForm');

    $('#purchase_add_supplier_btn').on('click', function() {
        $addSupplierForm[0].reset();
        $addSupplierModal.removeClass('hidden');
    });
    $('#purchaseCloseSupplierModal, #purchaseCancelSupplierBtn').on('click', function() {
        $addSupplierModal.addClass('hidden');
    });
    $addSupplierModal.on('click', function(e) {
        if (e.target.id === 'purchaseAddSupplierModal') $addSupplierModal.addClass('hidden');
    });

    $addSupplierForm.on('submit', function(e) {
        e.preventDefault();
        var name = $('#purchase_supplier_name').val().trim();
        if (!name) {
            show_notification('error', '{{ trans("messages.enter_supplier_name", [], session("locale")) }}');
            return;
        }
        var $btn = $addSupplierForm.find('button[type="submit"]');
        $btn.prop('disabled', true);
        $.ajax({
            url: "{{ url('suppliers') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                supplier_name: name,
                phone: $('#purchase_supplier_phone').val().trim(),
                notes: $('#purchase_supplier_notes').val().trim()
            },
            success: function(res) {
                var newSupplier = { id: res.id, supplier_name: res.supplier_name || name, phone: res.phone || '' };
                suppliers.push(newSupplier);
                $supplierId.val(newSupplier.id);
                $supplierSearch.val(newSupplier.supplier_name + (newSupplier.phone ? ' - ' + newSupplier.phone : ''));
                $addSupplierModal.addClass('hidden');
                show_notification('success', '{{ trans("messages.added_success", [], session("locale")) }}');
                $btn.prop('disabled', false);
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ __("Something went wrong") }}';
                if (xhr.responseJSON && xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                show_notification('error', msg);
                $btn.prop('disabled', false);
            }
        });
    });

    // ---------- Material row: searchable input (like supplier) ----------
    function renderMaterialDropdown($wrap, filter) {
        var list = materials;
        if (filter && filter.length) {
            var f = filter.toLowerCase();
            list = materials.filter(function(m) {
                return (m.material_name || '').toLowerCase().indexOf(f) >= 0;
            });
        }
        var $dd = $wrap.find('.purchase-material-dropdown');
        var $input = $wrap.find('.purchase-material-search');
        var html = '';
        list.forEach(function(m) {
            html += '<div class="purchase-material-option" data-id="' + m.id + '" data-name="' + (m.material_name || '').replace(/"/g, '&quot;') + '" data-unit="' + (m.unit || '').replace(/"/g, '&quot;') + '" data-price="' + (parseFloat(m.buy_price) || 0) + '">' + (m.material_name || '').replace(/</g, '&lt;') + '</div>';
        });
        if (!html) html = '<div class="purchase-material-option text-gray-500">No material found</div>';
        $dd.html(html).addClass('show');
        // Position fixed dropdown below input so it is not clipped by overflow
        var rect = $input[0].getBoundingClientRect();
        $dd.css({ top: (rect.bottom + 2) + 'px', left: rect.left + 'px', width: Math.max(rect.width, 220) + 'px' });
    }

    function addMaterialRow() {
        var idx = rowIndex++;
        var row = '<tr class="purchase-material-row border-b border-gray-100" data-row="' + idx + '">' +
            '<td class="px-3 py-2 col-material">' +
            '<div class="purchase-material-wrap">' +
            '<input type="text" class="purchase-material-search w-full h-10 rounded-lg border border-gray-300 px-3" autocomplete="off" placeholder="{{ trans("messages.search_material_placeholder", [], session("locale")) }}" data-row="' + idx + '" />' +
            '<input type="hidden" class="purchase-material-id" value="" />' +
            '<div class="purchase-material-dropdown"></div>' +
            '</div></td>' +
            '<td class="px-2 py-2 text-center col-unit"><input type="text" class="purchase-unit h-10 rounded-lg border-0 bg-gray-50 px-1 text-center text-sm" readonly /></td>' +
            '<td class="px-2 py-2 text-center col-price"><input type="number" min="0" step="0.01" class="purchase-price w-full h-10 rounded-lg border border-gray-300 px-1 text-center text-sm" value="0" /></td>' +
            '<td class="px-2 py-2 text-center col-price-shipping"><span class="purchase-price-with-shipping inline-block h-10 leading-10 rounded-lg bg-gray-100 px-2 text-center text-sm min-w-[60px]">0.00</span></td>' +
            '<td class="px-3 py-2"><input type="number" min="0" step="1" class="purchase-qty w-full h-10 rounded-lg border border-gray-300 px-3 text-center" placeholder="0" data-row="' + idx + '" /></td>' +
            '<td class="px-3 py-2 text-center"><span class="purchase-total font-semibold">0.00</span></td>' +
            '<td class="px-2 py-2 text-center"><button type="button" class="purchase-remove-row text-red-500 hover:text-red-700 p-1" data-row="' + idx + '" title="{{ trans("messages.delete", [], session("locale")) }}"><span class="material-symbols-outlined text-lg">delete</span></button></td>' +
            '</tr>';
        $('#purchase_materials_body').append(row);
    }

    $(document).on('focus', '.purchase-material-search', function() {
        var $wrap = $(this).closest('.purchase-material-wrap');
        $('.purchase-material-dropdown.show').not($wrap.find('.purchase-material-dropdown')).removeClass('show');
        renderMaterialDropdown($wrap, $(this).val());
    });
    $(document).on('input', '.purchase-material-search', function() {
        var $wrap = $(this).closest('.purchase-material-wrap');
        $wrap.find('.purchase-material-id').val('');
        renderMaterialDropdown($wrap, $(this).val());
    });
    $(document).on('keydown', '.purchase-material-search', function(e) {
        var $wrap = $(this).closest('.purchase-material-wrap');
        var $dd = $wrap.find('.purchase-material-dropdown');
        var $opts = $dd.find('.purchase-material-option[data-id]');
        var hi = $opts.filter('.highlight').index();
        if (e.keyCode === 40) {
            e.preventDefault();
            hi = Math.min(hi + 1, $opts.length - 1);
            $opts.removeClass('highlight').eq(hi).addClass('highlight');
        } else if (e.keyCode === 38) {
            e.preventDefault();
            hi = Math.max(hi - 1, 0);
            $opts.removeClass('highlight').eq(hi).addClass('highlight');
        } else if (e.keyCode === 13 && $opts.length) {
            e.preventDefault();
            var $target = (hi >= 0 && $opts.eq(hi).data('id')) ? $opts.eq(hi) : $opts.filter('[data-id]').first();
            if ($target.length && $target.data('id')) $target.click();
        } else if (e.keyCode === 27) {
            $dd.removeClass('show');
        }
    });
    $(document).on('click', '.purchase-material-option[data-id]', function() {
        var $opt = $(this);
        var $wrap = $opt.closest('.purchase-material-wrap');
        var row = $wrap.closest('tr');
        var materialId = $opt.data('id');
        var otherRow = null;
        $('#purchase_materials_body tr').each(function() {
            if (this !== row[0] && $(this).find('.purchase-material-id').val() == materialId) {
                otherRow = $(this);
                return false;
            }
        });
        if (otherRow && otherRow.length) {
            $wrap.find('.purchase-material-dropdown').removeClass('show');
            Swal.fire({
                title: '{{ trans("messages.material_already_added", [], session("locale")) }}',
                text: '{{ trans("messages.material_already_added_increase_quantity", [], session("locale")) }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ trans("messages.yes", [], session("locale")) }}',
                cancelButtonText: '{{ trans("messages.cancel", [], session("locale")) }}'
            }).then(function(result) {
                if (result.isConfirmed) {
                    var addQty = parseInt(row.find('.purchase-qty').val(), 10) || 0;
                    var existingQty = parseInt(otherRow.find('.purchase-qty').val(), 10) || 0;
                    otherRow.find('.purchase-qty').val(existingQty + addQty);
                    row.find('.purchase-material-id').val('');
                    row.find('.purchase-material-search').val('');
                    row.find('.purchase-unit').val('');
                    row.find('.purchase-price').val('');
                    row.find('.purchase-qty').val('');
                    row.find('.purchase-price-with-shipping').text('0.00');
                    row.find('.purchase-total').text('0.00');
                    updateAllRowTotals();
                } else {
                    row.find('.purchase-material-id').val('');
                    row.find('.purchase-material-search').val('');
                }
            });
            return;
        }
        $wrap.find('.purchase-material-id').val(materialId);
        $wrap.find('.purchase-material-search').val($opt.data('name') || '');
        $wrap.find('.purchase-material-dropdown').removeClass('show');
        row.find('.purchase-unit').val($opt.data('unit') || '');
        row.find('.purchase-price').val($opt.data('price') || '0');
        updateAllRowTotals();
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.purchase-material-wrap').length) $('.purchase-material-dropdown').removeClass('show');
    });
    $(window).on('scroll', function() { $('.purchase-material-dropdown.show').removeClass('show'); });

    // No negative values. allowEmpty: when true, empty/negative stays as '' (use placeholder for 0)
    function clampNonNegative($input, allowEmpty) {
        var raw = ($input.val() || '').toString().trim();
        var v = parseFloat(raw);
        if (allowEmpty && (raw === '' || isNaN(v))) { $input.val(''); return; }
        if (allowEmpty && v < 0) { $input.val(''); return; }
        if (isNaN(v) || v < 0) $input.val(0);
    }
    function clampQuantityInteger($input, allowEmpty) {
        var raw = ($input.val() || '').toString().trim();
        var n = parseInt(raw, 10);
        if (allowEmpty && (raw === '' || isNaN(n))) { $input.val(''); return; }
        if (allowEmpty && n < 0) { $input.val(''); return; }
        if (isNaN(n) || n < 0) { $input.val(allowEmpty ? '' : 0); return; }
        if (String(raw).indexOf('.') >= 0 || String(raw).indexOf(',') >= 0) $input.val(n);
    }
    $('#shipping_cost').on('input blur', function() {
        clampNonNegative($(this), true);
        updateAllRowTotals();
    });
    $('#invoice_amount').on('input blur', function() {
        clampNonNegative($(this));
    });
    $('#invoice_amount').on('keydown', function(e) {
        if (e.key === '-' || e.key === 'e' || e.key === 'E') e.preventDefault();
    });
    $(document).on('input blur', '.purchase-price', function() {
        clampNonNegative($(this), true);
        updateAllRowTotals();
    });
    $(document).on('input blur', '.purchase-qty', function() {
        clampQuantityInteger($(this), true);
        updateAllRowTotals();
    });

    function getTotalQuantity() {
        var total = 0;
        $('#purchase_materials_body tr').each(function() {
            total += parseFloat($(this).find('.purchase-qty').val()) || 0;
        });
        return total;
    }
    function getShippingPerUnit() {
        var shipping = parseFloat($('#shipping_cost').val()) || 0;
        var totalQty = getTotalQuantity();
        if (totalQty <= 0) return 0;
        return shipping / totalQty;
    }
    function updateRowTotal($row) {
        var unitPrice = parseFloat($row.find('.purchase-price').val()) || 0;
        var qty = parseFloat($row.find('.purchase-qty').val()) || 0;
        var shippingPerUnit = getShippingPerUnit();
        var effectivePrice = unitPrice + shippingPerUnit;
        $row.find('.purchase-price-with-shipping').text(effectivePrice.toFixed(2));
        $row.find('.purchase-total').text((qty * effectivePrice).toFixed(2));
    }
    function updateAllRowTotals() {
        $('#purchase_materials_body tr').each(function() {
            updateRowTotal($(this));
        });
        updateSummary();
    }
    function updateSummary() {
        var totalQty = getTotalQuantity();
        var totalAmount = 0;
        $('#purchase_materials_body tr').each(function() {
            totalAmount += parseFloat($(this).find('.purchase-total').text()) || 0;
        });
        $('#purchase_summary_total_qty').text(totalQty);
        $('#purchase_summary_total_amount').text(totalAmount.toFixed(2));
    }

    $(document).on('click', '.purchase-remove-row', function() {
        $(this).closest('tr').remove();
        updateAllRowTotals();
    });

    $('#add_material_row').on('click', function() {
        addMaterialRow();
    });

    // Start with one empty row (or fill from draft)
    if (!window.PURCHASE_DRAFT) addMaterialRow();

    function fillFormFromDraft() {
        var d = window.PURCHASE_DRAFT;
        if (!d) return;
        $('#supplier_id').val(d.supplier_id);
        $('#supplier_search').val((d.supplier && d.supplier.supplier_name) ? d.supplier.supplier_name : '');
        $('#invoice_number').val(d.invoice_no || '');
        $('#invoice_amount').val(d.invoice_amount != null ? parseFloat(d.invoice_amount).toFixed(2) : (d.total_amount != null ? parseFloat(d.total_amount).toFixed(2) : '0.00'));
        $('#shipping_cost').val(d.shipping_cost != null && parseFloat(d.shipping_cost) > 0 ? parseFloat(d.shipping_cost).toFixed(2) : '');
        $('#purchase_notes').val(d.notes || '');
        $('#purchase_materials_body tr').remove();
        var list = d.materials_json || [];
        list.forEach(function(m) {
            addMaterialRow();
            var $row = $('#purchase_materials_body tr').last();
            $row.find('.purchase-material-id').val(m.material_id);
            $row.find('.purchase-material-search').val(m.material_name || '');
            $row.find('.purchase-unit').val(m.unit || '');
            $row.find('.purchase-price').val(m.price || 0);
            $row.find('.purchase-qty').val(parseInt(m.quantity, 10) || 0);
        });
        if (list.length === 0) addMaterialRow();
        updateAllRowTotals();
    }

    // Save as draft (stage 1) - one-time clickable
    $('#purchase_save_btn').on('click', function() {
        if ($(this).prop('disabled')) return;
        var supplierId = $('#supplier_id').val();
        var invoiceNumber = $('#invoice_number').val();
        var shippingCost = parseFloat($('#shipping_cost').val()) || 0;
        var notes = $('#purchase_notes').val();
        var rows = [];
        $('#purchase_materials_body tr').each(function() {
            var materialId = $(this).find('.purchase-material-id').val();
            var qty = parseFloat($(this).find('.purchase-qty').val()) || 0;
            if (materialId && qty > 0) {
                rows.push({
                    material_id: materialId,
                    material_name: $(this).find('.purchase-material-search').val() || '',
                    unit: $(this).find('.purchase-unit').val(),
                    price: parseFloat($(this).find('.purchase-price').val()) || 0,
                    unit_price_plus_shipping: parseFloat($(this).find('.purchase-price-with-shipping').text()) || 0,
                    quantity: qty,
                    total: parseFloat($(this).find('.purchase-total').text()) || 0
                });
            }
        });
        if (!supplierId) {
            show_notification('error', '{{ trans("messages.please_select_supplier", [], session("locale")) }}');
            return;
        }
        if (rows.length === 0) {
            show_notification('error', '{{ trans("messages.add_at_least_one_material", [], session("locale")) }}');
            return;
        }
        var $btn = $('#purchase_save_btn');
        var origText = $btn.html();
        $btn.prop('disabled', true).html('...');
        var invoiceAmount = parseFloat($('#invoice_amount').val()) || 0;
        var draftId = window.PURCHASE_EDIT_ID || $('#purchase_draft_id').val();
        var url = draftId ? "{{ url('purchase/draft') }}/" + draftId : "{{ url('purchase/draft') }}";
        var method = draftId ? 'PUT' : 'POST';
        var data = {
            _token: '{{ csrf_token() }}',
            supplier_id: supplierId,
            invoice_no: invoiceNumber,
            invoice_amount: invoiceAmount,
            shipping_cost: shippingCost,
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
