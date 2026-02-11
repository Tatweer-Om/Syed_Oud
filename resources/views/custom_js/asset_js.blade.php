<script>
    $(document).ready(function() {

       function loadassets(page = 1) {
    $.get("{{ url('assets/list') }}?page=" + page, function(res) {

        // ---- Table Rows ----
        let rows = '';
        $.each(res.data, function(i, asset) {
            
            let statusText = '';
            let statusClass = '';

            if (asset.status == 1) {
                statusText = '{{ trans("messages.asset_working", [], session("locale")) }}';
                statusClass = 'text-green-600';

            } else if (asset.status == 2) {
                statusText = '{{ trans("messages.asset_under_maintenance", [], session("locale")) }}';
                statusClass = 'text-yellow-600';

            } else if (asset.status == 3) {
                statusText = '{{ trans("messages.asset_stopped", [], session("locale")) }}';
                statusClass = 'text-red-600';

            } else {
                statusText = 'N/A';
                statusClass = 'text-gray-500';
            }

            
            rows += `
            <tr class="hover:bg-pink-50/50 transition-colors" data-id="${asset.id}">
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${asset.name || '-'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${asset.department || '-'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${asset.purchase_date || '-'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${asset.purchase_cost ? parseFloat(asset.purchase_cost).toFixed(3) : '0.000'}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${asset.usage || '-'}</td>
                 <td class="px-4 sm:px-6 py-5 ${statusClass} font-semibold">${statusText}</td>
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
                <a href="{{ url('assets/list') }}?page=${i}">${i}</a>
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
        if (page) loadassets(page);
    }
});

        // Initial load
        loadassets();

        $('#search_asset').on('keyup', function() {
            let value = $(this).val().toLowerCase();

            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        });
        
        // Add / Update asset
        $('#asset_form').submit(function(e) {
            e.preventDefault();
            let id = $('#asset_edit_id').val();
            let name = $('#name').val().trim();
            let department = $('#department').val().trim();
            let purchase_date = $('#purchase_date').val().trim();
            let purchase_cost = $('#purchase_cost').val() || 0;
            let usage = $('#usage').val().trim();
            let status = $('#status').val() || 1;
             

            // Simple validation
            if (!name) {
                show_notification('error', '<?= trans("messages.enter_asset_name", [], session("locale")) ?>');
                return;
            }
            if (!department) {
                show_notification('error', '<?= trans("messages.enter_asset_department", [], session("locale")) ?>');
                return;
            }
            if (!purchase_date) {
                show_notification('error', '<?= trans("messages.enter_asset_purchase_date", [], session("locale")) ?>');
                return;
            }
            if (!purchase_cost) {
                show_notification('error', '<?= trans("messages.enter_asset_purchase_cost", [], session("locale")) ?>');
                return;
            }

            let url = id ? `{{ url('assets') }}/${id}` : "{{ url('assets') }}";

            // Serialize form data
            let data = {
                name: name,
                department: department,
                purchase_date: purchase_date,
                purchase_cost: purchase_cost,
                usage: usage,
                status: status,
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
                    $('#asset_form')[0].reset();
                    $('#asset_edit_id').val('');
                    loadassets();
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
            $('#asset_form')[0].reset();
            $('#asset_edit_id').val('');
        });

        // Edit asset
        $(document).on('click', '.edit-btn', function() {
            let id = $(this).closest('tr').data('id');

            $.get("{{ url('assets') }}/" + id, function(asset) {
                $('#asset_edit_id').val(asset.id);
                $('#name').val(asset.name || '');
                $('#department').val(asset.department || '');
                $('#purchase_date').val(asset.purchase_date || '');
                $('#purchase_cost').val(asset.purchase_cost || '');
                $('#usage').val(asset.usage || '');
                $('#status').val(asset.status || 1);
                
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
                        url: '<?= url("assets") ?>/' + id,
                        method: 'DELETE',
                        data: {
                            _token: '<?= csrf_token() ?>'
                        },
                        success: function(data) {
                            loadassets(); // reload table
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

