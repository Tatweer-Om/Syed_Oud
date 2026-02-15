<script>
$(document).ready(function() {

    // ------------------ Translations ------------------
    const trans = {
        details: "{{ trans('messages.details', [], session('locale')) }}",
        edit: "{{ trans('messages.edit', [], session('locale')) }}",
        delete: "{{ trans('messages.delete', [], session('locale')) }}",
        unit: "{{ trans('messages.unit', [], session('locale')) }}",
        category: "{{ trans('messages.category', [], session('locale')) }}",
        size: "{{ trans('messages.size', [], session('locale')) }}",
        color: "{{ trans('messages.color', [], session('locale')) }}",
        quantity: "{{ trans('messages.quantity', [], session('locale')) }}",
        material_type: "{{ trans('messages.material_type', [], session('locale')) }}",
        production: "{{ trans('messages.production', [], session('locale')) }}",
        packaging: "{{ trans('messages.packaging', [], session('locale')) }}"
    };

    // ------------------ Track state ------------------
    let currentPage = 1;
    let currentMaterialId = '';
    let currentSearch = '';

    // ------------------ Format quantity / unit_price for display ------------------
    function displayQuantity(material) {
        const q = parseFloat(material.quantity ?? material.meters_per_roll ?? 0);
        return (q > 0 || q === 0) ? Number(q).toFixed(2) : '-';
    }
    function displayUnitPrice(material) {
        const p = parseFloat(material.unit_price ?? material.buy_price ?? 0);
        return (p > 0 || p === 0) ? Number(p).toFixed(2) : '-';
    }

    // ------------------ Render Table & Mobile Cards ------------------
    function renderTable(materials) {
        let tableRows = '';
        let mobileCards = '';

        $.each(materials, function(_, material) {
            let notes = (material.description || '').toString().trim();
            let notesShort = notes.length > 50 ? notes.substring(0, 50) + '...' : (notes || '-');
            let qty = displayQuantity(material);
            let unitPrice = displayUnitPrice(material);
            let materialType = (material.material_type || 'production').toString();

            // Display material type with badge styling
            let materialTypeDisplay = materialType === 'packaging' ? trans.packaging : trans.production;
            let materialTypeBadge = materialType === 'packaging' 
                ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">${materialTypeDisplay}</span>`
                : `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">${materialTypeDisplay}</span>`;

            tableRows += `
                <tr class="border-t hover:bg-pink-50/60 transition" data-id="${material.id}">
                    <td class="px-3 py-3 text-center font-bold">${(material.material_name || '').replace(/</g, '&lt;')}</td>
                    <td class="px-3 py-3 text-center">${materialTypeBadge}</td>
                    <td class="px-3 py-3 text-center">${(material.unit ?? '-').toString().replace(/</g, '&lt;')}</td>
                    <td class="px-3 py-3 text-center">${qty}</td>
                    <td class="px-3 py-3 text-center">${unitPrice}</td>
                    <td class="px-3 py-3 text-center text-gray-600 max-w-[200px] truncate" title="${(material.description || '').replace(/"/g, '&quot;')}">${notesShort.replace(/</g, '&lt;')}</td>
                    <td class="px-3 py-3 text-center">
                        <div class="flex justify-center gap-3 text-[12px] font-semibold text-gray-700 flex-wrap">
                            <button class="add-quantity-material-btn flex flex-col items-center gap-1 hover:text-emerald-600 transition" data-id="${material.id}" data-name="${(material.material_name || '').replace(/"/g, '&quot;')}" data-unit="${(material.unit || '').replace(/"/g, '&quot;')}">
                                <span class="material-symbols-outlined bg-emerald-50 text-emerald-600 p-2 rounded-full text-base">add_circle</span>
                                {{ trans('messages.add_quantity', [], session('locale')) }}
                            </button>
                            <button class="edit-material-btn flex flex-col items-center gap-1 hover:text-blue-600 transition" data-id="${material.id}">
                                <span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full text-base">edit</span>
                                ${trans.edit}
                            </button>
                            <button class="delete-material-btn flex flex-col items-center gap-1 hover:text-red-600 transition">
                                <span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full text-base">delete</span>
                                ${trans.delete}
                            </button>
                        </div>
                    </td>
                </tr>
            `;

            mobileCards += `
                <div class="bg-white border border-pink-100 rounded-2xl shadow-sm hover:shadow-md transition p-4 mb-4 md:hidden" data-id="${material.id}">
                    <p class="font-bold text-gray-900">${(material.material_name || '').replace(/</g, '&lt;')}</p>
                    <p><span class="text-gray-500">${trans.material_type}:</span> ${materialTypeBadge}</p>
                    <p><span class="text-gray-500">${trans.unit}:</span> ${(material.unit ?? '-').toString().replace(/</g, '&lt;')}</p>
                    <p><span class="text-gray-500">${trans.quantity}:</span> ${qty}</p>
                    <p><span class="text-gray-500">{{ trans('messages.buy_price', [], session('locale')) }} / {{ trans('messages.unit_price', [], session('locale')) ?: 'Unit price' }}:</span> ${unitPrice}</p>
                    <p class="text-gray-600 text-sm">${notesShort.replace(/</g, '&lt;')}</p>
                    <div class="flex justify-around mt-3 text-xs font-semibold flex-wrap gap-2">
                        <button class="add-quantity-material-btn flex flex-col items-center gap-1 hover:text-emerald-600 transition" data-id="${material.id}" data-name="${(material.material_name || '').replace(/"/g, '&quot;')}" data-unit="${(material.unit || '').replace(/"/g, '&quot;')}">
                            <span class="material-symbols-outlined bg-emerald-50 text-emerald-600 p-2 rounded-full text-base">add_circle</span>
                            {{ trans('messages.add_quantity', [], session('locale')) }}
                        </button>
                        <button class="edit-material-btn flex flex-col items-center gap-1 hover:text-blue-600 transition" data-id="${material.id}">
                            <span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full text-base">edit</span>
                            ${trans.edit}
                        </button>
                        <button class="delete-material-btn flex flex-col items-center gap-1 hover:text-red-600 transition">
                            <span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full text-base">delete</span>
                            ${trans.delete}
                        </button>
                    </div>
                </div>
            `;
        });

        $("#desktop_material_body").html(tableRows);
        $("#mobile_material_cards").html(mobileCards);
    }

    // ------------------ Render Pagination ------------------
  function renderPagination(res) {
    let pagination = '';

    // Previous page
    pagination += `<li class="w-10 h-10 flex items-center justify-center rounded-full ${!res.prev_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300'}">
                      <a href="?page=${res.current_page-1}">&laquo;</a>
                   </li>`;

    // Page numbers
    for (let i = 1; i <= res.last_page; i++) {
        pagination += `<li class="w-10 h-10 flex items-center justify-center rounded-full">
                           <a href="?page=${i}" class="flex items-center justify-center w-10 h-10 ${res.current_page == i ? 'bg-[var(--primary-color)] text-white' : 'bg-gray-200 hover:bg-gray-300'}">
                               ${i}
                           </a>
                       </li>`;
    }

    // Next page
    pagination += `<li class="w-10 h-10 flex items-center justify-center rounded-full ${!res.next_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300'}">
                      <a href="?page=${res.current_page+1}">&raquo;</a>
                   </li>`;

    $("#material_pagination").html(pagination);
}

    // ------------------ Load Materials ------------------
    function loadmaterial(page = 1, materialId = '', search = '') {
        currentPage = page;
        currentMaterialId = materialId;
        currentSearch = search;

        $.get("/material/list", { page: page, material_id: materialId }, function(res) {
            renderTable(res.data);
            renderPagination(res);

            if (currentSearch) {
                applySearch(currentSearch);
            }
        });
    }

    // ------------------ Pagination Click ------------------
    $(document).on("click", "#material_pagination a", function(e) {
        e.preventDefault();
        let href = $(this).attr("href");
        if (!href || href === "#") return;

        let page = new URL(href, window.location.origin).searchParams.get("page") || 1;
        loadmaterial(parseInt(page), currentMaterialId, currentSearch);
    });

    // ------------------ Client-side Search ------------------
    function applySearch(search) {
        $("#desktop_material_body tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().includes(search));
        });
        $("#mobile_material_cards > div").filter(function() {
            $(this).toggle($(this).text().toLowerCase().includes(search));
        });
    }

    $("#q").on("keyup", function() {
        currentSearch = $(this).val().toLowerCase();
        applySearch(currentSearch);
    });

    // ------------------ Add Quantity Modal ------------------
    $(document).on('click', '.add-quantity-material-btn', function() {
        var id = $(this).data('id');
        var name = $(this).data('name') || '';
        var unit = $(this).data('unit') || '';
        $('#add_quantity_material_id').val(id);
        $('#add_quantity_material_name').val(name);
        $('#add_quantity_input').val('');
        $('#add_quantity_unit_hint').text(unit ? ('{{ trans("messages.unit", [], session("locale")) }}: ' + unit) : '');
        $('#addQuantityModal').removeClass('hidden');
        $('#add_quantity_input').focus();
    });

    $('#closeAddQuantityModal, #cancelAddQuantityBtn').on('click', function() {
        $('#addQuantityModal').addClass('hidden');
        $('#addQuantityModalForm')[0].reset();
    });

    $(document).on('click', '#addQuantityModal', function(e) {
        if ($(e.target).attr('id') === 'addQuantityModal') {
            $('#addQuantityModal').addClass('hidden');
            $('#addQuantityModalForm')[0].reset();
        }
    });

    $('#addQuantityModalForm').on('submit', function(e) {
        e.preventDefault();
        var qty = parseFloat($('#add_quantity_input').val());
        if (!qty || qty <= 0) {
            show_notification('error', '<?= addslashes(trans("messages.enter_valid_quantity", [], session("locale"))) ?>');
            return;
        }
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var origText = $btn.html();
        $btn.prop('disabled', true).html('...');
        $.ajax({
            url: "{{ route('materials.add_quantity') }}",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                material_id: $('#add_quantity_material_id').val(),
                new_meters_pieces: qty
            },
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (response.status === 'success') {
                    show_notification('success', response.message);
                    $('#addQuantityModal').addClass('hidden');
                    $('#addQuantityModalForm')[0].reset();
                    loadmaterial(currentPage, currentMaterialId, currentSearch);
                }
                $btn.prop('disabled', false).html(origText);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(origText);
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ __("messages.error") ?: "Something went wrong" }}';
                show_notification('error', msg);
            }
        });
    });

    // ------------------ Edit Material Modal ------------------
    $(document).on('click', '.edit-material-btn', function() {
        const id = $(this).data('id');
        // Load units first, then fetch material and set form so unit select has options before we set value
        loadUnitsForMaterialSelect(function() {
            $.get('{{ url("materials") }}/' + id, function(res) {
                if (res.status !== 'success' || !res.material) {
                    show_notification('error', '{{ trans("messages.fetch_error", [], session("locale")) }}');
                    return;
                }
                const m = res.material;
                const qty = parseFloat(m.quantity ?? m.meters_per_roll ?? 0);
                const unitPrice = m.unit_price ?? m.buy_price ?? '';
                const unitVal = (m.unit || '').toString().trim();
                $('#edit_material_id').val(m.id);
                $('#edit_popup_material_name').val(m.material_name || '');
                $('input[name="edit_material_type"][value="' + (m.material_type || 'production') + '"]').prop('checked', true);
                var $unitSelect = $('#edit_popup_material_unit');
                if (unitVal) {
                    var hasOption = $unitSelect.find('option').filter(function() { return $(this).val() === unitVal; }).length > 0;
                    if (!hasOption) {
                        $unitSelect.append($('<option></option>').attr('value', unitVal).text(unitVal));
                    }
                    $unitSelect.val(unitVal);
                } else {
                    $unitSelect.val('');
                }
                $('#edit_popup_material_quantity').val(qty);
                $('#edit_popup_purchase_price').val(unitPrice);
                $('#edit_popup_material_notes').val(m.description || '');
                $('#editMaterialModal').removeClass('hidden');
            }).fail(function() {
                show_notification('error', '{{ trans("messages.fetch_error", [], session("locale")) }}');
            });
        });
    });

    $('#closeEditMaterialModal, #cancelEditMaterialBtn').on('click', function() {
        $('#editMaterialModal').addClass('hidden');
    });

    $(document).on('click', '#editMaterialModal', function(e) {
        if ($(e.target).attr('id') === 'editMaterialModal') {
            $('#editMaterialModal').addClass('hidden');
        }
    });

    $('#editMaterialModalForm').on('submit', function(e) {
        e.preventDefault();
        const material_name = $('#edit_popup_material_name').val().trim();
        const material_unit = $('#edit_popup_material_unit').val();
        if (!material_name) {
            show_notification('error', '<?= addslashes(trans("messages.enter_material_name", [], session("locale"))) ?>');
            return;
        }
        if (!material_unit) {
            show_notification('error', '<?= addslashes(trans("messages.enter_material_unit", [], session("locale"))) ?>');
            return;
        }
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const origText = $btn.html();
        $btn.prop('disabled', true).html('...');
        $.ajax({
            url: "{{ route('update_material') }}",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                material_id: $('#edit_material_id').val(),
                material_name: material_name,
                material_type: $('input[name="edit_material_type"]:checked').val() || 'production',
                material_unit: material_unit,
                quantity: parseFloat($('#edit_popup_material_quantity').val()) || 0,
                purchase_price: $('#edit_popup_purchase_price').val() || 0,
                material_notes: $('#edit_popup_material_notes').val() || ''
            },
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (response.status === 'success') {
                    show_notification('success', response.message);
                    $('#editMaterialModal').addClass('hidden');
                    loadmaterial(currentPage, currentMaterialId, currentSearch);
                }
                $btn.prop('disabled', false).html(origText);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(origText);
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                    show_notification('error', msg);
                } else {
                    show_notification('error', '{{ __("messages.error") ?: "Something went wrong" }}');
                }
            }
        });
    });

    // ------------------ Delete Material ------------------
    $(document).on('click', '.delete-material-btn', function() {
        let id = $(this).closest('[data-id]').data('id');
        if (!id) return console.error('Material ID not found!');

        Swal.fire({
            title: '<?= trans("messages.confirm_delete_title", [], session("locale")) ?>',
            text: '<?= trans("messages.confirm_delete_text", [], session("locale")) ?>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '<?= trans("messages.yes_delete", [], session("locale")) ?>',
            cancelButtonText: '<?= trans("messages.cancel", [], session("locale")) ?>'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= url("delete_material") ?>/' + id,
                    method: 'DELETE',
                    data: { _token: '<?= csrf_token() ?>' },
                    success: function () {
                        Swal.fire(
                            '<?= trans("messages.deleted_success", [], session("locale")) ?>',
                            '<?= trans("messages.deleted_success_text", [], session("locale")) ?>',
                            'success'
                        );
                        loadmaterial(currentPage, currentMaterialId, currentSearch);
                    },
                    error: function () {
                        Swal.fire(
                            '<?= trans("messages.delete_error", [], session("locale")) ?>',
                            '<?= trans("messages.delete_error_text", [], session("locale")) ?>',
                            'error'
                        );
                    }
                });
            }
        });
    });

    // ------------------ Load units for material popup select ------------------
    // callback: optional function to run after units are loaded (e.g. to set edit form unit value)
    function loadUnitsForMaterialSelect(callback) {
        $.get("{{ url('units/all') }}", function(units) {
            function fillSelect($sel) {
                var firstOption = $sel.find('option:first').clone();
                $sel.empty().append(firstOption);
                $.each(units, function(i, u) {
                    $sel.append($('<option></option>').attr('value', u.unit_name).text(u.unit_name));
                });
            }
            fillSelect($('#popup_material_unit'));
            fillSelect($('#edit_popup_material_unit'));
            if (typeof callback === 'function') callback();
        });
    }
    loadUnitsForMaterialSelect();

    // ------------------ Add Material Modal (popup) ------------------
    $('#openAddMaterialModal').on('click', function() {
        loadUnitsForMaterialSelect();
        $('#addMaterialModal').removeClass('hidden');
        $('#addMaterialModalForm')[0].reset();
        $('#popup_material_name').focus();
    });

    $('#closeAddMaterialModal, #cancelAddMaterialBtn').on('click', function() {
        $('#addMaterialModal').addClass('hidden');
        $('#addMaterialModalForm')[0].reset();
    });

    $(document).on('click', '#addMaterialModal', function(e) {
        if ($(e.target).attr('id') === 'addMaterialModal') {
            $('#addMaterialModal').addClass('hidden');
            $('#addMaterialModalForm')[0].reset();
        }
    });

    $('#addMaterialModalForm').on('submit', function(e) {
        e.preventDefault();
        var material_name = $('#popup_material_name').val().trim();
        var material_unit = $('#popup_material_unit').val();
        if (!material_name) {
            show_notification('error', '<?= addslashes(trans("messages.enter_material_name", [], session("locale"))) ?>');
            return;
        }
        if (!material_unit) {
            show_notification('error', '<?= addslashes(trans("messages.enter_material_unit", [], session("locale"))) ?>');
            return;
        }
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var origText = $btn.html();
        $btn.prop('disabled', true).html('...');
        $.ajax({
            url: "{{ route('add_material') }}",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                material_name: material_name,
                material_type: $('input[name="material_type"]:checked').val() || 'production',
                material_unit: material_unit,
                quantity: parseFloat($('#popup_material_quantity').val()) || 0,
                purchase_price: $('#popup_purchase_price').val() || 0,
                material_notes: $('#popup_material_notes').val() || ''
            },
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (response.status === 'success') {
                    show_notification('success', response.message);
                    $('#addMaterialModal').addClass('hidden');
                    $('#addMaterialModalForm')[0].reset();
                    loadmaterial(currentPage, currentMaterialId, currentSearch);
                }
                $btn.prop('disabled', false).html(origText);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(origText);
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                    show_notification('error', msg);
                } else {
                    show_notification('error', '{{ __("messages.error") ?: "Something went wrong" }}');
                }
            }
        });
    });

    // ------------------ Initial Load ------------------
    loadmaterial();
});
</script>
