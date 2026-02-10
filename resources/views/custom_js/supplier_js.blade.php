<script>
    $(document).ready(function() {

        function loadTotalCount() {
            $.get("{{ url('suppliers/count') }}", function(res) {
                $('#total_suppliers_count').text(res.count);
            });
        }

        function loadSuppliers(page = 1) {
            $.get("{{ url('suppliers/list') }}?page=" + page, function(res) {

                // ---- Table Rows ----
                let rows = '';
                $.each(res.data, function(i, supplier) {
                    const notesPreview = supplier.notes ? 
                        (supplier.notes.length > 50 ? supplier.notes.substring(0, 50) + '...' : supplier.notes) : 
                        '-';
                    
                    rows += `
                    <tr class="hover:bg-pink-50/50 transition-colors" data-id="${supplier.id}">
                        <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] font-medium">${supplier.supplier_name || '-'}</td>
                        <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${supplier.phone || '-'}</td>
                        <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${notesPreview}</td>
                        <td class="px-4 sm:px-6 py-5 text-center">
                            <div class="flex items-center justify-center gap-4 sm:gap-6">
                                <button class="edit-btn icon-btn" title="{{ trans('messages.edit', [], session('locale')) }}">
                                    <span class="material-symbols-outlined">edit</span>
                                </button>
                                <button class="delete-btn icon-btn hover:text-red-500" title="{{ trans('messages.delete', [], session('locale')) }}">
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
                <li class="px-3 py-1.5 rounded-full ${!res.prev_page_url ? 'opacity-50 pointer-events-none bg-gray-100' : 'bg-gray-100 hover:bg-gray-200'}">
                    <a href="${res.prev_page_url ? res.prev_page_url : '#'}" class="text-gray-700">&laquo;</a>
                </li>`;

                // Page numbers
                for (let i = 1; i <= res.last_page; i++) {
                    const isActive = res.current_page == i;
                    pagination += `
                    <li class="px-3 py-1.5 rounded-full min-w-[2rem] text-center ${isActive ? 'bg-[var(--primary-color)]' : 'bg-gray-100 hover:bg-gray-200'}">
                        <a href="{{ url('suppliers/list') }}?page=${i}" class="${isActive ? 'text-white font-semibold' : 'text-gray-700'}">${i}</a>
                    </li>
                    `;
                }

                // Next
                pagination += `
                <li class="px-3 py-1.5 rounded-full ${!res.next_page_url ? 'opacity-50 pointer-events-none bg-gray-100' : 'bg-gray-100 hover:bg-gray-200'}">
                    <a href="${res.next_page_url ? res.next_page_url : '#'}" class="text-gray-700">&raquo;</a>
                </li>`;

                $('#pagination').html(pagination);

                // Update total count
                loadTotalCount();
            });
        }

        // Handle pagination click
        $(document).on('click', '#pagination a', function(e) {
            e.preventDefault();
            let href = $(this).attr('href');
            if (href && href !== '#') {
                let page = new URL(href).searchParams.get('page');
                if (page) loadSuppliers(page);
            }
        });

        // Initial load
        loadSuppliers();
        loadTotalCount();

        $('#search_supplier').on('keyup', function() {
            let value = $(this).val().toLowerCase();

            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        });
        
        // Add / Update supplier
        $('#supplier_form').submit(function(e) {
            e.preventDefault();
            let id = $('#supplier_id').val();
            let supplier_name = $('#supplier_name').val().trim();
            let phone = $('#supplier_phone').val().trim();
            let notes = $('#supplier_notes').val().trim();

            // Validation
            if (!supplier_name) {
                show_notification('error', '<?= trans("messages.enter_supplier_name", [], session("locale")) ?>');
                return;
            }

            let url = id ? `{{ url('suppliers') }}/${id}` : "{{ url('suppliers') }}";

            let data = {
                supplier_name: supplier_name,
                phone: phone,
                notes: notes,
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
                    window.dispatchEvent(new CustomEvent('close-modal'));
                    
                    $('#supplier_form')[0].reset();
                    $('#supplier_id').val('');
                    loadSuppliers();
                    loadTotalCount();
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
            window.dispatchEvent(new CustomEvent('close-modal'));
            $('#supplier_form')[0].reset();
            $('#supplier_id').val('');
            $('#supplier_modal_title').text('<?= addslashes(trans("messages.add_new_supplier", [], session("locale"))) ?>');
        });

        // Add button - set modal title for add mode
        $('#add_supplier_btn').on('click', function() {
            $('#supplier_modal_title').text('<?= addslashes(trans("messages.add_new_supplier", [], session("locale"))) ?>');
        });

        // Edit supplier
        $(document).on('click', '.edit-btn', function() {
            let id = $(this).closest('tr').data('id');

            $.get("{{ url('suppliers') }}/" + id, function(supplier) {
                $('#supplier_id').val(supplier.id);
                $('#supplier_name').val(supplier.supplier_name);
                $('#supplier_phone').val(supplier.phone || '');
                $('#supplier_notes').val(supplier.notes || '');
                $('#supplier_modal_title').text('<?= addslashes(trans("messages.edit_supplier", [], session("locale"))) ?>');
                window.dispatchEvent(new CustomEvent('open-modal'));
            }).fail(function() {
                show_notification('error', '<?= trans("messages.fetch_error", [], session("locale")) ?>');
            });
        });

        // Delete supplier
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
                        url: '<?= url("suppliers") ?>/' + id,
                        method: 'DELETE',
                        data: {
                            _token: '<?= csrf_token() ?>'
                        },
                        success: function(data) {
                            loadSuppliers();
                            loadTotalCount();
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
