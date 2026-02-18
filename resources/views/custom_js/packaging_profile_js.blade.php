<script>
$(document).ready(function() {
    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(function(el) {
        new bootstrap.Tooltip(el);
    });
    var allMaterials = [];
    var packagingMaterials = [];

    $.get("{{ url('materials/for-packaging') }}", function(data) { allMaterials = data || []; });

    function loadPackagingMaterials() {
        $.get("{{ url('packaging') }}/" + PACKAGING_ID + "/materials", function(res) {
            packagingMaterials = res.materials || [];
        });
    }
    loadPackagingMaterials();

    var packagingHistoryData = [];
    function showHistoryDetailPopup(idx) {
        var h = packagingHistoryData[idx];
        if (!h) return;
        var actionLabels = { 'addition': '{{ trans("messages.addition", [], session("locale")) }}', 'removal': '{{ trans("messages.removal", [], session("locale")) }}', 'wastage': '{{ trans("messages.wastage", [], session("locale")) }}', 'packaging_entry': '{{ trans("messages.packaging_entry", [], session("locale")) ?: "Packaging entry" }}', 'packaging_completed': '{{ trans("messages.packaging_completed", [], session("locale")) }}' };
        var phaseLabel = '{{ trans("messages.phase", [], session("locale")) ?: "Phase" }}';
        var actionLabel = (h.action === 'packaging_entry' && h.phase) ? (phaseLabel + ' ' + h.phase) : (actionLabels[h.action] || h.action);
        var extraInfo = '';
        if (h.action === 'packaging_entry') {
            var exp = parseFloat(h.expected_packaging_units || 0);
            var act = h.actual_pieces_packed != null ? parseFloat(h.actual_pieces_packed || 0) : null;
            if (exp > 0 || act != null) {
                extraInfo = '<div class="text-left mb-3 text-sm"><strong>{{ trans("messages.expected", [], session("locale")) }}:</strong> ' + exp.toFixed(2) + (act != null ? ' &nbsp;|&nbsp; <strong>{{ trans("messages.actual", [], session("locale")) }}:</strong> ' + act.toFixed(2) : '') + '</div>';
            }
        }
        var materialsHtml = '';
        if (h.action === 'packaging_entry' && h.materials_json && h.materials_json.length > 0) {
            materialsHtml = '<div class="text-left mb-3"><strong class="block mb-2">{{ trans("messages.materials_used", [], session("locale")) ?: "Materials Used" }}:</strong><table class="w-full text-sm border-collapse"><thead><tr class="bg-gray-100"><th class="text-left px-2 py-1 border">Material</th><th class="text-right px-2 py-1 border">Qty</th><th class="text-left px-2 py-1 border">Unit</th><th class="text-right px-2 py-1 border">Total</th></tr></thead><tbody>';
            h.materials_json.forEach(function(m) {
                materialsHtml += '<tr><td class="px-2 py-1 border">' + (m.material_name || '-') + '</td><td class="px-2 py-1 border text-right">' + parseFloat(m.quantity || 0).toFixed(2) + '</td><td class="px-2 py-1 border">' + (m.unit || '-') + '</td><td class="px-2 py-1 border text-right">' + parseFloat(m.total || 0).toFixed(2) + '</td></tr>';
            });
            materialsHtml += '</tbody></table></div>';
        } else if (h.material_name) {
            materialsHtml = '<div class="text-left mb-3"><strong>{{ trans("messages.material_name", [], session("locale")) }}:</strong> ' + h.material_name + ' (' + parseFloat(h.quantity || 0).toFixed(2) + ' ' + (h.unit || '') + ')</div>';
        }
        var notesHtml = (h.notes && String(h.notes).trim()) ? '<div class="text-left mt-3"><strong>{{ trans("messages.notes", [], session("locale")) }}:</strong> ' + String(h.notes || '-').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>' : '';
        var bodyHtml = extraInfo + materialsHtml + notesHtml || '<p class="text-gray-500">{{ trans("messages.no_details", [], session("locale")) ?: "No additional details" }}</p>';
        Swal.fire({
            title: actionLabel,
            html: bodyHtml,
            width: '600px',
            confirmButtonText: '{{ trans("messages.close", [], session("locale")) ?: "Close" }}',
            confirmButtonColor: '#3b82f6'
        });
    }
    function loadPackagingHistory() {
        $.get("{{ url('packaging') }}/" + PACKAGING_ID + "/history", function(res) {
            var rows = '';
            var history = res.history || [];
            packagingHistoryData = history;
            var actionLabels = { 'addition': '{{ trans("messages.addition", [], session("locale")) }}', 'removal': '{{ trans("messages.removal", [], session("locale")) }}', 'wastage': '{{ trans("messages.wastage", [], session("locale")) }}', 'packaging_entry': '{{ trans("messages.packaging_entry", [], session("locale")) ?: "Packaging entry" }}', 'packaging_completed': '{{ trans("messages.packaging_completed", [], session("locale")) }}' };
            var phaseLabel = '{{ trans("messages.phase", [], session("locale")) ?: "Phase" }}';
            if (history.length === 0) {
                rows = '<tr><td colspan="10" class="px-3 py-4 text-center text-gray-500">{{ trans("messages.no_data", [], session("locale")) }}</td></tr>';
            } else {
                var completedLabel = '{{ trans("messages.completed", [], session("locale")) }}';
                var inProgressLabel = '{{ trans("messages.in_progress", [], session("locale")) ?: "In Progress" }}';
                var viewDetailsTip = '{{ trans("messages.tooltip_view_details", [], session("locale")) }}';
                history.forEach(function(h, idx) {
                    var actionLabel = (h.action === 'packaging_entry' && h.phase) ? (phaseLabel + ' ' + h.phase) : (actionLabels[h.action] || h.action);
                    var actionClass = 'bg-blue-100 text-blue-700';
                    if (h.action === 'removal') actionClass = 'bg-red-100 text-red-700';
                    if (h.action === 'wastage') actionClass = 'bg-amber-100 text-amber-700';
                    if (h.action === 'packaging_completed') actionClass = 'bg-green-100 text-green-700';
                    if (h.action === 'packaging_entry') actionClass = 'bg-indigo-100 text-indigo-700';
                    var phaseStatusCell = '-';
                    if (h.action === 'packaging_entry' && h.phase) {
                        phaseStatusCell = h.phase_completed_at ? '<span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700">' + completedLabel + '</span>' : '<span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700">' + inProgressLabel + '</span>';
                    }
                    var expectedCell = (h.action === 'packaging_entry') ? parseFloat(h.expected_packaging_units || 0).toFixed(2) : '-';
                    var actualCell = (h.action === 'packaging_entry' && h.actual_pieces_packed != null) ? parseFloat(h.actual_pieces_packed || 0).toFixed(2) : '-';
                    var completionDateCell = '-';
                    if (h.phase_completed_at) {
                        try { completionDateCell = new Date(h.phase_completed_at).toLocaleString(); } catch(e) { completionDateCell = h.phase_completed_at; }
                    }
                    var dt = (h.action === 'packaging_entry' && h.packaging_date) ? h.packaging_date : (h.created_at ? new Date(h.created_at).toLocaleString() : '-');
                    var quantityStr = (h.action === 'packaging_completed') ? '-' : (h.action === 'packaging_entry' ? (parseFloat(h.production_output_taken || 0).toFixed(2) + (typeof PRODUCTION_UNIT !== 'undefined' && PRODUCTION_UNIT ? ' ' + PRODUCTION_UNIT : '')) : (parseFloat(h.quantity || 0).toFixed(2) + ' ' + (h.unit || '')));
                    var materialCell = (h.action === 'packaging_entry' && h.materials_json) ? (h.materials_json.length + ' {{ trans("messages.items", [], session("locale")) ?: "items" }}') : (h.material_name || '-');
                    var viewBtn = '<button type="button" class="history-view-detail px-2 py-1 rounded bg-indigo-100 text-indigo-700 hover:bg-indigo-200 text-xs font-medium" data-idx="' + idx + '" data-bs-toggle="tooltip" data-bs-placement="left" title="' + viewDetailsTip.replace(/"/g, '&quot;') + '"><span class="material-symbols-outlined text-sm align-middle">visibility</span></button>';
                    rows += '<tr class="border-t hover:bg-gray-50"><td class="px-3 py-2">' + dt + '</td><td class="px-3 py-2"><span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold ' + actionClass + '">' + actionLabel + '</span></td><td class="px-3 py-2">' + phaseStatusCell + '</td><td class="px-3 py-2 text-center">' + expectedCell + '</td><td class="px-3 py-2 text-center">' + actualCell + '</td><td class="px-3 py-2">' + completionDateCell + '</td><td class="px-3 py-2">' + materialCell + '</td><td class="px-3 py-2 text-center">' + quantityStr + '</td><td class="px-3 py-2">' + (h.added_by || '-') + '</td><td class="px-3 py-2 text-center">' + viewBtn + '</td></tr>';
                });
            }
            $('#packaging_history_body').html(rows);
            $('.history-view-detail').each(function() { try { new bootstrap.Tooltip(this); } catch(e){} });
            $(document).off('click', '.history-view-detail').on('click', '.history-view-detail', function() {
                showHistoryDetailPopup(parseInt($(this).data('idx'), 10));
            });
        });
    }
    loadPackagingHistory();

    $('.packaging-tab').on('click', function() {
        var tab = $(this).data('tab');
        $('.packaging-tab').removeClass('active');
        $(this).addClass('active');
        $('.packaging-tab-content').addClass('hidden');
        $('#tab_' + tab).removeClass('hidden');
    });

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
    $('#btn_complete_phase').on('click', function() {
        var phase = $(this).data('phase');
        var outputTaken = parseFloat($(this).data('output-taken') || 0);
        var prodUnit = (typeof PRODUCTION_UNIT !== 'undefined' && PRODUCTION_UNIT) ? PRODUCTION_UNIT : '';
        var expectedPackaging = parseFloat($(this).data('expected-packaging') || 0);
        if (!phase) return;
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).addClass('opacity-75 cursor-not-allowed');
        var outputTakenLabel = '{{ trans("messages.output_taken_for_phase", [], session("locale")) ?: "Output taken for this phase" }}';
        var expectedPackagingLabel = '{{ trans("messages.expected_packaging_units", [], session("locale")) ?: "Expected packaging units" }}';
        var actualPiecesLabel = '{{ trans("messages.actual_pieces_packed", [], session("locale")) ?: "Actual pieces packed" }}';
        var phaseTitle = ('{{ trans("messages.complete_phase", [], session("locale")) ?: "Complete Phase" }}').replace(/:ph/g, '') + ' ' + phase;
        Swal.fire({
            title: phaseTitle,
            html: '<div class="text-left">' +
                '<p class="mb-2 text-gray-600"><strong>' + outputTakenLabel + ':</strong> <span class="text-indigo-600 font-semibold">' + outputTaken.toFixed(2) + (prodUnit ? ' ' + prodUnit : '') + '</span></p>' +
                '<p class="mb-3 text-gray-600"><strong>' + expectedPackagingLabel + ':</strong> <span class="text-amber-600 font-semibold">' + expectedPackaging.toFixed(2) + '</span></p>' +
                '<label class="block text-sm font-medium text-gray-700 mb-1">' + actualPiecesLabel + ' <span class="text-red-500">*</span></label>' +
                '<input id="swal_actual_pieces" type="number" min="0" step="0.01" placeholder="0" class="swal2-input w-full" style="width:100%;margin:0;padding:0.5rem 1rem;" oninput="var v=parseFloat(this.value);if(!isNaN(v)&&v<0)this.value=0;">' +
                '</div>',
            showCancelButton: true,
            confirmButtonText: '{{ trans("messages.complete", [], session("locale")) ?: "Complete" }}',
            cancelButtonText: '{{ trans("messages.cancel", [], session("locale")) }}',
            confirmButtonColor: '#0d9488',
            preConfirm: function() {
                var val = parseFloat(document.getElementById('swal_actual_pieces').value);
                if (isNaN(val) || val < 0) {
                    Swal.showValidationMessage('{{ trans("messages.enter_valid_quantity", [], session("locale")) }}');
                    return false;
                }
                return val;
            }
        }).then(function(result) {
            if (result.isConfirmed && result.value !== false) {
                var actualPieces = result.value;
                var stockName = (typeof STOCK_NAME !== 'undefined' ? STOCK_NAME : '') || '';
                var confirmMsg = ('{{ trans("messages.confirm_add_to_stock_message", [], session("locale")) }}').replace(':qty', actualPieces.toFixed(2));
                Swal.fire({
                    title: '{{ trans("messages.confirm_add_to_stock_title", [], session("locale")) }}',
                    html: '<p class="text-gray-600">' + confirmMsg + '</p>' + (stockName ? '<p class="text-teal-600 font-semibold mt-2">' + stockName + '</p>' : ''),
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '{{ trans("messages.yes_add", [], session("locale")) ?: "Yes, Add" }}',
                    cancelButtonText: '{{ trans("messages.cancel", [], session("locale")) }}',
                    confirmButtonColor: '#0d9488'
                }).then(function(confirmResult) {
                    if (confirmResult.isConfirmed) {
                        $.ajax({
                    url: "{{ url('packaging') }}/" + PACKAGING_ID + "/complete-phase",
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}', phase: phase, actual_pieces_packed: actualPieces },
                    success: function(res) {
                        if (res.status === 'ok') {
                            var stockName = res.stock_name || (typeof STOCK_NAME !== 'undefined' ? STOCK_NAME : '');
                            var actualPacked = parseFloat(res.actual_pieces_packed || 0);
                            var actualOutput = parseFloat(res.actual_packaging_output || 0);
                            var outputToStock = parseFloat(res.output_added_to_stock || 0);
                            var html = '<div class="text-left space-y-2">' +
                                '<p class="text-base"><strong>{{ trans("messages.phase_completed_stock_label", [], session("locale")) }}:</strong> <span class="text-teal-600 font-semibold">' + (stockName || '-') + '</span></p>' +
                                '<p class="text-base"><strong>{{ trans("messages.actual_pieces_packed", [], session("locale")) }}:</strong> <span class="font-semibold">' + actualPacked.toFixed(2) + '</span></p>' +
                                '<p class="text-sm text-gray-600">✓ {{ trans("messages.phase_completed_recorded_in_output", [], session("locale")) }}</p>';
                            if (outputToStock > 0) {
                                html += '<p class="text-sm text-green-600 font-medium mt-2">✓ ' + outputToStock.toFixed(2) + ' {{ trans("messages.phase_completed_added_to_stock", [], session("locale")) }}</p>';
                            }
                            html += '</div>';
                            Swal.fire({
                                title: '{{ trans("messages.phase_completed_success_title", [], session("locale")) }}',
                                html: html,
                                icon: 'success',
                                confirmButtonColor: '#0d9488',
                                confirmButtonText: '{{ trans("messages.done", [], session("locale")) ?: "Done" }}'
                            }).then(function() {
                                if (res.packaging_completed) {
                                    window.location.href = "{{ url('production') }}/" + PRODUCTION_ID + "/profile";
                                } else {
                                    location.reload();
                                }
                            });
                        } else {
                            show_notification('error', res.message || 'Error');
                            $btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
                        }
                    },
                    error: function(xhr) {
                        show_notification('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error');
                        $btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
                    }
                });
                    } else {
                        $btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
                    }
                });
            } else {
                $btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
            }
        });
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
