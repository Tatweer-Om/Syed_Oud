<script>
$(document).ready(function() {
    var allMaterials = [];
    var productionMaterials = [];
    
    // Load all materials for add dropdown
    $.get("{{ url('materials/for-purchase') }}", function(data) {
        allMaterials = data || [];
    });
    
    // Load production materials for remove dropdown
    function loadProductionMaterials() {
        $.get("{{ url('production') }}/" + PRODUCTION_ID + "/materials", function(res) {
            productionMaterials = res.materials || [];
        });
    }
    loadProductionMaterials();
    
    // Load production history
    function loadProductionHistory() {
        $.get("{{ url('production') }}/" + PRODUCTION_ID + "/history", function(res) {
            var rows = '';
            var history = res.history || [];
            var actionLabels = {
                'addition': '{{ trans("messages.addition", [], session("locale")) }}',
                'removal': '{{ trans("messages.removal", [], session("locale")) }}',
                'wastage': '{{ trans("messages.wastage", [], session("locale")) }}',
                'production_completed': '{{ trans("messages.production_completed", [], session("locale")) }}'
            };
            if (history.length === 0) {
                rows = '<tr><td colspan="6" class="px-3 py-4 text-center text-gray-500">{{ trans("messages.no_data", [], session("locale")) }}</td></tr>';
            } else {
                history.forEach(function(h) {
                    var actionLabel = actionLabels[h.action] || h.action;
                    var actionClass = 'bg-blue-100 text-blue-700';
                    if (h.action === 'removal') actionClass = 'bg-red-100 text-red-700';
                    if (h.action === 'wastage') actionClass = 'bg-amber-100 text-amber-700';
                    if (h.action === 'production_completed') actionClass = 'bg-green-100 text-green-700';
                    var dt = h.created_at ? new Date(h.created_at).toLocaleString() : '-';
                    var materialName = h.material_name || '-';
                    var quantityStr = (h.action === 'production_completed') ? '-' : (parseFloat(h.quantity || 0).toFixed(2) + ' ' + (h.unit || ''));
                    rows += '<tr class="border-t hover:bg-gray-50">' +
                        '<td class="px-3 py-2 text-gray-700">' + dt + '</td>' +
                        '<td class="px-3 py-2"><span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold ' + actionClass + '">' + actionLabel + '</span></td>' +
                        '<td class="px-3 py-2 font-medium">' + materialName + '</td>' +
                        '<td class="px-3 py-2 text-center">' + quantityStr + '</td>' +
                        '<td class="px-3 py-2 text-gray-600">' + (h.added_by || '-') + '</td>' +
                        '<td class="px-3 py-2 text-gray-500 text-xs max-w-[200px] truncate" title="' + (h.notes || '').replace(/"/g, '&quot;') + '">' + (h.notes || '-') + '</td>' +
                        '</tr>';
                });
            }
            $('#production_history_body').html(rows);
        });
    }
    loadProductionHistory();
    
    // Modal handlers
    $('#btn_view_materials_table').on('click', function() {
        $('#materialsTableModal').removeClass('hidden');
    });
    
    $('#btn_add_material').on('click', function() {
        $('#add_material_search').val('');
        $('#add_material_id').val('');
        $('#add_material_qty').val('');
        $('#add_material_notes').val('');
        $('#addMaterialModal').removeClass('hidden');
    });
    
    $('#btn_remove_material').on('click', function() {
        $('#remove_material_search').val('');
        $('#remove_material_id').val('');
        $('#remove_material_qty').val('');
        $('#remove_material_notes').val('');
        $('#removeMaterialModal').removeClass('hidden');
    });
    
    $('#btn_add_wastage').on('click', function() {
        $('#wastage_material_search').val('');
        $('#wastage_material_id').val('');
        $('#wastage_qty').val('');
        $('#wastage_notes').val('');
        $('input[name="wastage_type"]').prop('checked', false);
        $('#addWastageModal').removeClass('hidden');
    });
    
    // Close modals
    $('.close-modal').on('click', function() {
        $(this).closest('.fixed').addClass('hidden');
    });
    
    $(document).on('click', '.modal-overlay', function(e) {
        if (e.target === this) $(this).addClass('hidden');
    });
    
    // Searchable dropdown for Add Material
    function renderAddMaterialDropdown(filter) {
        var list = allMaterials;
        if (filter && filter.length) {
            var f = filter.toLowerCase();
            list = allMaterials.filter(function(m) {
                return (m.material_name || '').toLowerCase().indexOf(f) >= 0;
            });
        }
        var html = '';
        list.forEach(function(m) {
            html += '<div class="material-select-option" data-id="' + m.id + '" data-name="' + (m.material_name || '').replace(/"/g, '&quot;') + '">' + (m.material_name || '') + '</div>';
        });
        if (!html) html = '<div class="material-select-option text-gray-500">{{ trans("messages.no_material_found", [], session("locale")) }}</div>';
        $('#add_material_dropdown').html(html).addClass('show');
    }
    
    $('#add_material_search').on('focus', function() { renderAddMaterialDropdown($(this).val()); });
    $('#add_material_search').on('input', function() {
        $('#add_material_id').val('');
        renderAddMaterialDropdown($(this).val());
    });
    
    $(document).on('click', '#add_material_dropdown .material-select-option[data-id]', function() {
        $('#add_material_id').val($(this).data('id'));
        $('#add_material_search').val($(this).data('name'));
        $('#add_material_dropdown').removeClass('show');
    });
    
    // Searchable dropdown for Remove Material (from production materials)
    function renderRemoveMaterialDropdown(filter) {
        var list = productionMaterials;
        if (filter && filter.length) {
            var f = filter.toLowerCase();
            list = productionMaterials.filter(function(m) {
                return (m.material_name || '').toLowerCase().indexOf(f) >= 0;
            });
        }
        var html = '';
        list.forEach(function(m) {
            html += '<div class="material-select-option" data-id="' + m.material_id + '" data-name="' + (m.material_name || '').replace(/"/g, '&quot;') + '" data-qty="' + (m.quantity || 0) + '">' + (m.material_name || '') + ' (' + parseFloat(m.quantity || 0).toFixed(2) + ' ' + (m.unit || '') + ')</div>';
        });
        if (!html) html = '<div class="material-select-option text-gray-500">{{ trans("messages.no_material_found", [], session("locale")) }}</div>';
        $('#remove_material_dropdown').html(html).addClass('show');
    }
    
    $('#remove_material_search').on('focus', function() { renderRemoveMaterialDropdown($(this).val()); });
    $('#remove_material_search').on('input', function() {
        $('#remove_material_id').val('');
        renderRemoveMaterialDropdown($(this).val());
    });
    
    $(document).on('click', '#remove_material_dropdown .material-select-option[data-id]', function() {
        $('#remove_material_id').val($(this).data('id'));
        $('#remove_material_search').val($(this).data('name'));
        $('#remove_material_qty').attr('max', $(this).data('qty'));
        $('#remove_material_dropdown').removeClass('show');
    });
    
    // Searchable dropdown for Wastage Material
    function renderWastageMaterialDropdown(filter) {
        var list = allMaterials;
        if (filter && filter.length) {
            var f = filter.toLowerCase();
            list = allMaterials.filter(function(m) {
                return (m.material_name || '').toLowerCase().indexOf(f) >= 0;
            });
        }
        var html = '';
        list.forEach(function(m) {
            html += '<div class="material-select-option" data-id="' + m.id + '" data-name="' + (m.material_name || '').replace(/"/g, '&quot;') + '">' + (m.material_name || '') + '</div>';
        });
        if (!html) html = '<div class="material-select-option text-gray-500">{{ trans("messages.no_material_found", [], session("locale")) }}</div>';
        $('#wastage_material_dropdown').html(html).addClass('show');
    }
    
    $('#wastage_material_search').on('focus', function() { renderWastageMaterialDropdown($(this).val()); });
    $('#wastage_material_search').on('input', function() {
        $('#wastage_material_id').val('');
        renderWastageMaterialDropdown($(this).val());
    });
    
    $(document).on('click', '#wastage_material_dropdown .material-select-option[data-id]', function() {
        $('#wastage_material_id').val($(this).data('id'));
        $('#wastage_material_search').val($(this).data('name'));
        $('#wastage_material_dropdown').removeClass('show');
    });
    
    // Hide dropdowns on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#add_material_search, #add_material_dropdown').length) {
            $('#add_material_dropdown').removeClass('show');
        }
        if (!$(e.target).closest('#remove_material_search, #remove_material_dropdown').length) {
            $('#remove_material_dropdown').removeClass('show');
        }
        if (!$(e.target).closest('#wastage_material_search, #wastage_material_dropdown').length) {
            $('#wastage_material_dropdown').removeClass('show');
        }
    });
    
    // Add Material Action
    $('#confirm_add_material').on('click', function() {
        var materialId = $('#add_material_id').val();
        var qty = parseFloat($('#add_material_qty').val());
        var notes = ($('#add_material_notes').val() || '').trim();
        
        if (!materialId) {
            show_notification('error', '{{ trans("messages.please_select_material", [], session("locale")) }}');
            return;
        }
        if (!qty || qty <= 0) {
            show_notification('error', '{{ trans("messages.enter_valid_quantity", [], session("locale")) }}');
            return;
        }
        if (!notes) {
            show_notification('error', '{{ trans("messages.please_add_notes", [], session("locale")) }}');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('...');
        
        $.ajax({
            url: "{{ url('production') }}/" + PRODUCTION_ID + "/add-material",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                material_id: materialId,
                quantity: qty,
                notes: $('#add_material_notes').val()
            },
            success: function(res) {
                if (res.status === 'success') {
                    show_notification('success', res.message);
                    $('#addMaterialModal').addClass('hidden');
                    location.reload();
                } else {
                    show_notification('error', res.message || 'Error');
                }
                $btn.prop('disabled', false).text('{{ trans("messages.add", [], session("locale")) }}');
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error';
                show_notification('error', msg);
                $btn.prop('disabled', false).text('{{ trans("messages.add", [], session("locale")) }}');
            }
        });
    });
    
    // Remove Material Action
    $('#confirm_remove_material').on('click', function() {
        var materialId = $('#remove_material_id').val();
        var qty = parseFloat($('#remove_material_qty').val());
        var notes = ($('#remove_material_notes').val() || '').trim();
        
        if (!materialId) {
            show_notification('error', '{{ trans("messages.please_select_material", [], session("locale")) }}');
            return;
        }
        if (!qty || qty <= 0) {
            show_notification('error', '{{ trans("messages.enter_valid_quantity", [], session("locale")) }}');
            return;
        }
        if (!notes) {
            show_notification('error', '{{ trans("messages.please_add_notes", [], session("locale")) }}');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('...');
        
        $.ajax({
            url: "{{ url('production') }}/" + PRODUCTION_ID + "/remove-material",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                material_id: materialId,
                quantity: qty,
                notes: $('#remove_material_notes').val()
            },
            success: function(res) {
                if (res.status === 'success') {
                    show_notification('success', res.message);
                    $('#removeMaterialModal').addClass('hidden');
                    location.reload();
                } else {
                    show_notification('error', res.message || 'Error');
                }
                $btn.prop('disabled', false).text('{{ trans("messages.remove", [], session("locale")) }}');
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error';
                show_notification('error', msg);
                $btn.prop('disabled', false).text('{{ trans("messages.remove", [], session("locale")) }}');
            }
        });
    });
    
    // Add Wastage Action
    $('#confirm_add_wastage').on('click', function() {
        var materialId = $('#wastage_material_id').val();
        var qty = parseFloat($('#wastage_qty').val());
        var wastageType = $('input[name="wastage_type"]:checked').val();
        var notes = ($('#wastage_notes').val() || '').trim();
        
        if (!wastageType) {
            show_notification('error', '{{ trans("messages.select_wastage_type", [], session("locale")) }}');
            return;
        }
        if (!materialId) {
            show_notification('error', '{{ trans("messages.please_select_material", [], session("locale")) }}');
            return;
        }
        if (!qty || qty <= 0) {
            show_notification('error', '{{ trans("messages.enter_valid_quantity", [], session("locale")) }}');
            return;
        }
        if (!notes) {
            show_notification('error', '{{ trans("messages.please_add_notes", [], session("locale")) }}');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('...');
        
        $.ajax({
            url: "{{ url('production') }}/" + PRODUCTION_ID + "/add-wastage",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                material_id: materialId,
                quantity: qty,
                wastage_types: [wastageType],
                notes: $('#wastage_notes').val()
            },
            success: function(res) {
                if (res.status === 'success') {
                    show_notification('success', res.message);
                    $('#addWastageModal').addClass('hidden');
                    location.reload();
                } else {
                    show_notification('error', res.message || 'Error');
                }
                $btn.prop('disabled', false).text('{{ trans("messages.add", [], session("locale")) }}');
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error';
                show_notification('error', msg);
                $btn.prop('disabled', false).text('{{ trans("messages.add", [], session("locale")) }}');
            }
        });
    });
    
    // Production Completion - SweetAlert confirmation only
    $('#btn_complete_production').on('click', function() {
        Swal.fire({
            title: '{{ trans("messages.confirm_complete_production_title", [], session("locale")) }}',
            text: '{{ trans("messages.confirm_complete_production_packaging", [], session("locale")) }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '{{ trans("messages.yes_complete", [], session("locale")) }}',
            cancelButtonText: '{{ trans("messages.cancel", [], session("locale")) }}'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('production') }}/" + PRODUCTION_ID + "/complete",
                    type: "POST",
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                title: '{{ trans("messages.success", [], session("locale")) }}',
                                text: res.message,
                                icon: 'success',
                                confirmButtonColor: '#16a34a'
                            }).then(() => {
                                window.location.href = "{{ url('view_production') }}";
                            });
                        } else {
                            Swal.fire({
                                title: '{{ trans("messages.error", [], session("locale")) }}',
                                text: res.message || 'Error',
                                icon: 'error'
                            });
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error';
                        Swal.fire({
                            title: '{{ trans("messages.error", [], session("locale")) }}',
                            text: msg,
                            icon: 'error'
                        });
                    }
                });
            }
        });
    });
});
</script>
