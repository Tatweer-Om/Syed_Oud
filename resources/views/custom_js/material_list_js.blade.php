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
        quantity: "{{ trans('messages.quantity', [], session('locale')) }}"
    };

    // ------------------ Track state ------------------
    let currentPage = 1;
    let currentMaterialId = '';
    let currentSearch = '';

    // ------------------ Calculate Total Meters/Pieces ------------------
    function calculateTotalMeters(material) {
        const rolls = parseFloat(material.rolls_count) || 0;
        const metersPerRoll = parseFloat(material.meters_per_roll) || 0;
        const total = rolls * metersPerRoll;
        if (total > 0) {
            return total.toFixed(2);
        }
        return '0.00';
    }

    // ------------------ Render Table & Mobile Cards ------------------
    function renderTable(materials) {
        let tableRows = '';
        let mobileCards = '';

        $.each(materials, function(_, material) {
            let image = material.material_image ? `/images/materials/${material.material_image}` : '';

            tableRows += `
                <tr class="border-t hover:bg-pink-50/60 transition" data-id="${material.id}">
                    <td class="px-3 py-3 text-center">
                        <div class="flex items-center justify-center gap-3">
                            <img src="${image}" class="w-12 h-16 object-cover rounded-md" />
                            <span class="font-bold">${material.material_name}</span>
                        </div>
                    </td>
                    <td class="px-3 py-3 text-center">
                        <div class="flex flex-col items-center">
                            <span>${trans.unit}: ${material.unit ?? '-'}</span>
                            <span>${trans.category}: ${material.category ?? '-'}</span>
                        </div>
                    </td>
                    <td class="px-3 py-3 text-center font-bold">${calculateTotalMeters(material)}</td>
                    <td class="px-3 py-3 text-center">${material.buy_price ?? '-'}</td>
                    <td class="px-3 py-3 text-center">
                        <div class="flex justify-center gap-5 text-[12px] font-semibold text-gray-700">
                            <button class="flex flex-col items-center gap-1 hover:text-green-600 transition add-quantity-btn"
                                data-material-id="${material.id}"
                                data-material-name="${material.material_name}"
                                data-rolls-count="${material.rolls_count ?? 0}"
                                data-meters-per-roll="${material.meters_per_roll ?? 0}"
                                data-unit="${material.unit ?? 'meters'}">
                                <span class="material-symbols-outlined bg-green-50 text-green-500 p-2 rounded-full text-base">add_circle</span>
                                {{ trans('messages.add_quantity', [], session('locale')) ?: 'Add Quantity' }}
                            </button>
                            <button class="flex flex-col items-center gap-1 hover:text-blue-600 transition"
                                onclick="window.location.href='/edit_material/${material.id}'">
                                <span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full text-base">edit</span>
                                ${trans.edit}
                            </button>
                            <button class="flex flex-col items-center gap-1 hover:text-red-600 transition delete-material-btn">
                                <span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full text-base">delete</span>
                                ${trans.delete}
                            </button>
                        </div>
                    </td>
                </tr>
            `;

            // Mobile cards
            let size = material.sizes?.[0]?.size?.size_name_ar ?? '-';
            let color = material.colors?.[0]?.color?.color_name_ar ?? '-';
            let quantity = material.sizes?.[0]?.qty ?? 0;

            mobileCards += `
                <div class="bg-white border border-pink-100 rounded-2xl shadow-sm hover:shadow-md transition p-4 mb-4 md:hidden" data-id="${material.id}">
                    <h3 class="font-bold text-gray-900 truncate">${material.material_name}</h3>
                    <p>${trans.unit}: ${material.unit ?? '-'}</p>
                    <p>${trans.category}: ${material.category ?? '-'}</p>
                    <p>${trans.size}: ${size}</p>
                    <p>${trans.color}: ${color}</p>
                    <p>${trans.quantity}: ${quantity}</p>
                    <div class="flex justify-around mt-3 text-xs font-semibold">
                        <button class="flex flex-col items-center gap-1 hover:text-green-600 transition add-quantity-btn"
                            data-material-id="${material.id}"
                            data-material-name="${material.material_name}"
                            data-rolls-count="${material.rolls_count ?? 0}"
                            data-meters-per-roll="${material.meters_per_roll ?? 0}"
                            data-unit="${material.unit ?? 'meters'}">
                            <span class="material-symbols-outlined bg-green-50 text-green-500 p-2 rounded-full text-base">add_circle</span>
                            {{ trans('messages.add_quantity', [], session('locale')) ?: 'Add Quantity' }}
                        </button>
                        <button class="flex flex-col items-center gap-1 hover:text-blue-600 transition" onclick="window.location.href='/edit_material/${material.id}'">
                            <span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full text-base">edit</span>
                            ${trans.edit}
                        </button>
                        <button class="flex flex-col items-center gap-1 hover:text-red-600 transition delete-material-btn">
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
    $(document).on('click', '.add-quantity-btn', function() {
        const materialId = $(this).data('material-id');
        const materialName = $(this).data('material-name');
        const rollsCount = parseFloat($(this).data('rolls-count')) || 0;
        const metersPerRoll = parseFloat($(this).data('meters-per-roll')) || 0;
        const unit = $(this).data('unit') || 'meters';

        // Calculate total meters/pieces
        const totalMeters = (rollsCount > 0 && metersPerRoll > 0) ? (rollsCount * metersPerRoll).toFixed(2) : parseFloat(metersPerRoll).toFixed(2) || '0';

        // Populate modal with current values
        $('#modalMaterialName').text(materialName);
        $('#currentTotalMeters').text(totalMeters + ' ' + unit);
        
        // Set material ID
        $('#materialId').val(materialId);
        
        // Clear and focus input
        $('#newMetersPieces').val('').focus();
        
        // Show modal
        $('#addQuantityModal').removeClass('hidden');
    });

    // Close modal
    $(document).on('click', '#cancelAddQuantityBtn', function() {
        $('#addQuantityModal').addClass('hidden');
        $('#addQuantityForm')[0].reset();
    });

    // Close modal on backdrop click
    $(document).on('click', '#addQuantityModal', function(e) {
        if ($(e.target).attr('id') === 'addQuantityModal') {
            $('#addQuantityModal').addClass('hidden');
            $('#addQuantityForm')[0].reset();
        }
    });

    // Handle form submission
    $('#addQuantityForm').on('submit', function(e) {
        e.preventDefault();

        const materialId = $('#materialId').val();
        const newMetersPieces = parseFloat($('#newMetersPieces').val()) || 0;

        if (newMetersPieces <= 0) {
            Swal.fire({
                icon: 'warning',
                title: '{{ trans('messages.error', [], session('locale')) }}',
                text: '@if(session('locale') == 'ar') يرجى إدخال عدد صحيح من الأمتار/القطع @else Please enter a valid number of meters/pieces @endif'
            });
            return;
        }

        $.ajax({
            url: '{{ url('materials/add-quantity') }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            data: {
                material_id: materialId,
                new_meters_pieces: newMetersPieces,
                new_buy_price: null
            },
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ trans('messages.success', [], session('locale')) }}',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#addQuantityModal').addClass('hidden');
                    $('#addQuantityForm')[0].reset();
                    // Reload materials
                    loadmaterial(currentPage, currentMaterialId, currentSearch);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ trans('messages.error', [], session('locale')) }}',
                        text: response.message || '{{ trans('messages.error_adding_quantity', [], session('locale')) ?: 'Error adding quantity' }}'
                    });
                }
            },
            error: function(xhr) {
                let errorMsg = '{{ trans('messages.error_adding_quantity', [], session('locale')) ?: 'Error adding quantity' }}';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: '{{ trans('messages.error', [], session('locale')) }}',
                    text: errorMsg
                });
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

    // ------------------ Add Material Modal (popup) ------------------
    $('#openAddMaterialModal').on('click', function() {
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
                material_unit: material_unit,
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
