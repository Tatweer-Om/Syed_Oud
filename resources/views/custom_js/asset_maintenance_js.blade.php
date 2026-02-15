<script>
    $(document).ready(function() {

       function loadassetsmaintenance(page = 1) {
    $.get("{{ url('assetsmaintenance/list') }}?page=" + page, function(res) {

        // ---- Table Rows ----
        let rows = '';
        $.each(res.data, function(i, asset) {
            
            let statusText = '';
            let statusClass = '';

            if (asset.maintenance_type == 1) {
                statusText = '{{ trans("messages.asset_maintenance_emergency", [], session("locale")) }}';
                statusClass = 'text-red-600';

            } else if (asset.maintenance_type == 2) {
                statusText = '{{ trans("messages.asset_maintenance_schedule", [], session("locale")) }}';
                statusClass = 'text-green-600';

            } else {
                statusText = 'N/A';
                statusClass = 'text-gray-500';
            }


            let performed_by = '';
            let performed_class = '';

            if (asset.performed_by == 1) {
                performed_by = '{{ trans("messages.asset_internal", [], session("locale")) }}';
                performed_class = 'text-red-600';

            } else if (asset.performed_by == 2) {
                performed_by = '{{ trans("messages.asset_external", [], session("locale")) }}';
                performed_class = 'text-green-600';

            } else {
                performed_by = 'N/A';
                performed_class = 'text-gray-500';
            }

            
            rows += `
            <tr class="hover:bg-pink-50/50 transition-colors" data-id="${asset.id}">
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${asset.asset.name || '-'}</td>
                <td class="px-4 sm:px-6 py-5 ${statusClass}">${statusText || '-'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${asset.maintenance_date || '-'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${asset.next_maintenance_date || '-'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${asset.cost || '0.000'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${asset.description || '-'}</td>
                 <td class="px-4 sm:px-6 py-5 ${performed_class} font-semibold">${performed_by}</td>
                <td class="px-4 sm:px-6 py-5 text-center">
                    <div class="flex items-center justify-center gap-4 sm:gap-6">
                        <button class="edit-btn icon-btn">
                            <span class="material-symbols-outlined">edit</span>
                        </button>
                        <button class="delete-btn icon-btn hover:text-red-500">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                </td>
            </tr>
            `;
        });
        $('tbody').html(rows);

        // ---- Pagination ----
        let pagination = '';

        // Previous
        pagination += `
        <li class="px-3 py-1 rounded-full ${!res.prev_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300'}">
            <a href="${res.prev_page_url ? res.prev_page_url : '#'}">&laquo;</a>
        </li>`;

        // Page numbers
        for (let i = 1; i <= res.last_page; i++) {
            pagination += `
            <li class="px-3 py-1 rounded-full ${res.current_page == i ? ' text-white' : 'bg-gray-200 hover:bg-gray-300'}">
                <a href="{{ url('assetsmaintenance/list') }}?page=${i}">${i}</a>
            </li>
            `;
        }

        // Next
        pagination += `
        <li class="px-3 py-1 rounded-full ${!res.next_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300'}">
            <a href="${res.next_page_url ? res.next_page_url : '#'}">&raquo;</a>
        </li>`;

        $('#pagination').html(pagination);
    });
}

// Handle pagination click
$(document).on('click', '#pagination a', function(e) {
    e.preventDefault();
    let href = $(this).attr('href');
    if (href && href !== '#') {
        let page = new URL(href).searchParams.get('page');
        if (page) loadassetsmaintenance(page);
    }
});

        // Initial load
        loadassetsmaintenance();

        $('#search_asset').on('keyup', function() {
            let value = $(this).val().toLowerCase();

            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        });
        
        // Add / Update asset
        $('#assetmaintenance_form').submit(function(e) {
            e.preventDefault();
            let id = $('#asset_edit_id').val();
            let asset_id = $('#asset_id').val().trim();
            let maintenance_type = $('#maintenance_type').val() || 1;
            let maintenance_date = $('#maintenance_date').val();
            let next_maintenance_date = $('#next_maintenance_date').val();
            let description = $('#description').val().trim();
            let performed_by = $('#performed_by').val() || 1;
            let cost = $('#cost').val() || 0.000;
             

            // Simple validation
            if (!maintenance_date) {
                show_notification('error', '<?= trans("messages.enter_maintenance_date", [], session("locale")) ?>');
                return;
            }
            if(maintenance_type == 2)
            {
                if (!next_maintenance_date) {
                    show_notification('error', '<?= trans("messages.enter_next_maintenance_date", [], session("locale")) ?>');
                    return;
                }
            }
            if (!cost) {
                show_notification('error', '<?= trans("messages.enter_cost", [], session("locale")) ?>');
                return;
            }

            let url = id ? `{{ url('assetsmaintenance') }}/${id}` : "{{ url('assetsmaintenance') }}";

            // Serialize form data
            let data = {
                asset_id: asset_id,
                maintenance_type: maintenance_type,
                maintenance_date: maintenance_date,
                next_maintenance_date: next_maintenance_date,
                description: description,
                performed_by: performed_by,
                cost: cost,
                _token: '{{ csrf_token() }}'
            };
            
            if (id) {
                data._method = 'PUT';
            }
            
            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                success: function(res) {
                    // Reset Alpine.js state using custom event
                    window.dispatchEvent(new CustomEvent('close-modal'));
                    
                    // Reset form
                    $('#assetmaintenance_form')[0].reset();
                    $('#asset_edit_id').val('');
                    loadassetsmaintenance();
                    show_notification(
                        'success',
                        id ?
                        '<?= trans("messages.updated_success", [], session("locale")) ?>' :
                        '<?= trans("messages.added_success", [], session("locale")) ?>'
                    );
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            show_notification('error', value[0]);
                        });
                    } else {
                        show_notification('error', '<?= trans("messages.generic_error", [], session("locale")) ?>');
                    }
                }
            });
        });

        // Close modal button
        $('#close_modal').click(function() {
            // Reset Alpine.js state using custom event
            window.dispatchEvent(new CustomEvent('close-modal'));
            $('#assetmaintenance_form')[0].reset();
            $('#asset_edit_id').val('');
        });

        // Edit asset
        $(document).on('click', '.edit-btn', function() {
            let id = $(this).closest('tr').data('id');

            $.get("{{ url('assetsmaintenance') }}/" + id, function(asset) {
                $('#asset_edit_id').val(asset.id);
                $('#asset_id').val(asset.asset_id || '');
                $('#maintenance_type').val(asset.maintenance_type || '');
                $('#maintenance_date').val(asset.maintenance_date || '');
                $('#next_maintenance_date').val(asset.next_maintenance_date || '');
                $('#description').val(asset.description || '');
                $('#performed_by').val(asset.performed_by || 1);
                $('#cost').val(asset.cost || 1);
                
                // Open modal using Alpine event
                window.dispatchEvent(new CustomEvent('open-modal'));
            }).fail(function() {
                show_notification('error', '<?= trans("messages.fetch_error", [], session("locale")) ?>');
            });
        });

        // Delete asset
        $(document).on('click', '.delete-btn', function() {
            let id = $(this).closest('tr').data('id');

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
                        url: '<?= url("assetsmaintenance") ?>/' + id,
                        method: 'DELETE',
                        data: {
                            _token: '<?= csrf_token() ?>'
                        },
                        success: function(data) {
                            loadassetsmaintenance(); // reload table
                            Swal.fire(
                                '<?= trans("messages.deleted_success", [], session("locale")) ?>',
                                '<?= trans("messages.deleted_success_text", [], session("locale")) ?>',
                                'success'
                            );
                        },
                        error: function() {
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

    });
</script>

