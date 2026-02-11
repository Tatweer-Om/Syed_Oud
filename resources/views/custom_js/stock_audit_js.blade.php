<script>
    $(document).ready(function() {
        let currentPage = 1;

        function loadAuditList(page = 1) {
            currentPage = page;
            $.get("{{ url('stock/audit/list') }}?page=" + page, function(res) {
                if (!res.success) {
                    $('#auditTableBody').html('<tr><td colspan="9" class="px-4 sm:px-6 py-8 text-center text-red-500">Error loading audit data</td></tr>');
                    return;
                }

                // ---- Table Rows ----
                let rows = '';
                if (res.data && res.data.length > 0) {
                    $.each(res.data, function(i, item) {
                        // Make quantity cells clickable if they have values > 0
                        let addedClass = item.quantity_added > 0 ? 'cursor-pointer hover:bg-green-100 transition' : '';
                        let pulledClass = item.quantity_pulled > 0 ? 'cursor-pointer hover:bg-red-100 transition' : '';
                        let posClass = item.quantity_sold_pos > 0 ? 'cursor-pointer hover:bg-red-100 transition' : '';
                        let transferredClass = item.quantity_transferred_out > 0 ? 'cursor-pointer hover:bg-orange-100 transition' : '';
                        let receivedClass = item.quantity_received > 0 ? 'cursor-pointer hover:bg-blue-100 transition' : '';

                        rows += `
                        <tr class="hover:bg-pink-50/50 transition-colors">
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] font-semibold">${item.barcode || '-'}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] font-semibold">${item.stock_code || '-'}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${item.design_name || '-'}</td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] font-semibold text-green-600 ${addedClass}" 
                                ${item.quantity_added > 0 ? `onclick="showAuditDetails(${item.stock_id}, 'added')"` : ''}>
                                ${item.quantity_added || 0}
                            </td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] font-semibold text-red-600 ${pulledClass}"
                                ${item.quantity_pulled > 0 ? `onclick="showAuditDetails(${item.stock_id}, 'pulled')"` : ''}>
                                ${item.quantity_pulled || 0}
                            </td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] text-red-600 ${posClass}"
                                ${item.quantity_sold_pos > 0 ? `onclick="showAuditDetails(${item.stock_id}, 'pos')"` : ''}>
                                ${item.quantity_sold_pos || 0}
                            </td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] text-orange-600 ${transferredClass}"
                                ${item.quantity_transferred_out > 0 ? `onclick="showAuditDetails(${item.stock_id}, 'transferred')"` : ''}>
                                ${item.quantity_transferred_out || 0}
                            </td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] text-blue-600 ${receivedClass}"
                                ${item.quantity_received > 0 ? `onclick="showAuditDetails(${item.stock_id}, 'received')"` : ''}>
                                ${item.quantity_received || 0}
                            </td>
                            <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)] font-bold text-primary">${item.remaining_quantity || 0}</td>
                        </tr>
                        `;
                    });
                } else {
                    rows = '<tr><td colspan="9" class="px-4 sm:px-6 py-8 text-center text-gray-500">{{ trans('messages.no_data', [], session('locale')) ?: 'No data found' }}</td></tr>';
                }
                $('#auditTableBody').html(rows);

                // ---- Pagination ----
                let pagination = '';
                if (res.total > 0) {
                    // Show pagination info
                    let startItem = ((res.current_page - 1) * res.per_page) + 1;
                    let endItem = Math.min(res.current_page * res.per_page, res.total);
                    
                    // Previous Button
                    pagination += `
                    <li class="flex">
                        <a href="#" data-page="${res.current_page - 1}"
                           class="px-3 py-1 border rounded-lg mx-1 text-sm transition
                                  ${res.current_page == 1 
                                    ? 'opacity-40 cursor-not-allowed bg-gray-200' 
                                    : 'bg-white hover:bg-gray-100'}">
                            &laquo;
                        </a>
                    </li>`;

                    // Page numbers - show max 5 pages around current
                    let startPage = Math.max(1, res.current_page - 2);
                    let endPage = Math.min(res.last_page, res.current_page + 2);
                    
                    if (startPage > 1) {
                        pagination += `
                        <li>
                            <a href="#" data-page="1"
                               class="px-4 py-1 mx-1 text-sm font-medium border rounded-lg transition bg-white hover:bg-gray-100">
                                1
                            </a>
                        </li>`;
                        if (startPage > 2) {
                            pagination += `<li class="px-2 text-gray-500 flex items-center">...</li>`;
                        }
                    }
                    
                    for (let i = startPage; i <= endPage; i++) {
                        pagination += `
                        <li>
                            <a href="#" data-page="${i}"
                               class="px-4 py-1 mx-1 text-sm font-medium border rounded-lg transition
                                      ${res.current_page == i 
                                        ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md' 
                                        : 'bg-white hover:bg-gray-100'}">
                                ${i}
                            </a>
                        </li>
                        `;
                    }
                    
                    if (endPage < res.last_page) {
                        if (endPage < res.last_page - 1) {
                            pagination += `<li class="px-2 text-gray-500 flex items-center">...</li>`;
                        }
                        pagination += `
                        <li>
                            <a href="#" data-page="${res.last_page}"
                               class="px-4 py-1 mx-1 text-sm font-medium border rounded-lg transition bg-white hover:bg-gray-100">
                                ${res.last_page}
                            </a>
                        </li>`;
                    }

                    // Next Button
                    pagination += `
                    <li class="flex">
                        <a href="#" data-page="${res.current_page + 1}"
                           class="px-3 py-1 border rounded-lg mx-1 text-sm transition
                                  ${res.current_page == res.last_page 
                                    ? 'opacity-40 cursor-not-allowed bg-gray-200' 
                                    : 'bg-white hover:bg-gray-100'}">
                            &raquo;
                        </a>
                    </li>`;
                    
                    // Show page info
                    pagination += `<li class="px-4 py-1 text-sm text-gray-600 flex items-center ml-4 font-medium">Showing ${startItem}-${endItem} of ${res.total}</li>`;
                } else {
                    pagination = '<li class="px-4 py-1 text-sm text-gray-600">No items found</li>';
                }
                $('#pagination').html(pagination);
            }).fail(function() {
                $('#auditTableBody').html('<tr><td colspan="9" class="px-4 sm:px-6 py-8 text-center text-red-500">Error loading audit data</td></tr>');
            });
        }

        // Handle pagination click
        $(document).on('click', '#pagination a', function(e) {
            e.preventDefault();
            let page = $(this).data('page');
            if (page) {
                loadAuditList(page);
            }
        });

        // Initial load
        loadAuditList();

        // Search functionality
        $('#search_audit').on('keyup', function() {
            let value = $(this).val().toLowerCase();
            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        });
    });

    // Function to show audit details modal
    function showAuditDetails(stockId, type) {
        $('#auditDetailsModal').removeClass('hidden');
        $('#modalContent').html('<p class="text-gray-500 text-center">{{ trans('messages.loading', [], session('locale')) }}...</p>');

        // Set modal title based on type
        let title = '';
        if (type === 'added') {
            title = '{{ trans('messages.quantity_added_details', [], session('locale')) }}';
        } else if (type === 'pulled') {
            title = '{{ trans('messages.quantity_pulled_details', [], session('locale')) ?: 'Quantity Pulled Details' }}';
        } else if (type === 'pos') {
            title = '{{ trans('messages.quantity_sold_pos_details', [], session('locale')) }}';
        } else if (type === 'transferred') {
            title = '{{ trans('messages.quantity_transferred_details', [], session('locale')) }}';
        } else if (type === 'received') {
            title = '{{ trans('messages.quantity_received_details', [], session('locale')) ?: 'Quantity Received Details' }}';
        }
        $('#modalTitle').text(title);

        // Load details from backend
        $.get("{{ url('stock/audit/details') }}", {
            stock_id: stockId,
            type: type
        }, function(res) {
            if (!res.success || !res.data || res.data.length === 0) {
                $('#modalContent').html('<p class="text-gray-500 text-center py-4">{{ trans('messages.no_data', [], session('locale')) }}</p>');
                return;
            }

            // Build details table
            let html = '<div class="overflow-x-auto"><table class="w-full text-xs">';
            html += '<thead class="bg-gray-50 border-b"><tr>';
            
            if (type === 'added') {
                html += '<th class="px-3 py-2 text-center font-semibold text-[10px]">{{ trans('messages.name', [], session('locale')) }}</th>';
                html += '<th class="px-3 py-2 text-center font-semibold text-[10px]">{{ trans('messages.quantity', [], session('locale')) }}</th>';
                html += '<th class="px-3 py-2 text-center font-semibold text-[10px]">{{ trans('messages.added_by', [], session('locale')) ?: 'Added By' }}</th>';
                html += '<th class="px-3 py-2 text-center font-semibold text-[10px]">{{ trans('messages.date', [], session('locale')) }}</th>';
            } else if (type === 'pos') {
                html += '<th class="px-3 py-2 text-center font-semibold text-[10px]">{{ trans('messages.order_no', [], session('locale')) ?: 'Order No' }}</th>';
                html += '<th class="px-3 py-2 text-center font-semibold text-[10px]">{{ trans('messages.quantity', [], session('locale')) }}</th>';
                html += '<th class="px-3 py-2 text-center font-semibold text-[10px]">{{ trans('messages.date', [], session('locale')) }}</th>';
            } else if (type === 'transferred') {
                html += '<th class="px-3 py-2 text-center font-semibold text-[10px]">{{ trans('messages.name', [], session('locale')) }}</th>';
                html += '<th class="px-3 py-2 text-center font-semibold text-[10px]">{{ trans('messages.transfer_code', [], session('locale')) }}</th>';
                html += '<th class="px-3 py-2 text-center font-semibold text-[10px]">{{ trans('messages.quantity', [], session('locale')) }}</th>';
                html += '<th class="px-3 py-2 text-center font-semibold text-[10px]">{{ trans('messages.date', [], session('locale')) }}</th>';
            } else {
                html += '<th class="px-3 py-2 text-center font-semibold text-[10px]">{{ trans('messages.name', [], session('locale')) }}</th>';
                html += '<th class="px-3 py-2 text-center font-semibold text-[10px]">{{ trans('messages.quantity', [], session('locale')) }}</th>';
                if (type === 'pulled') {
                    html += '<th class="px-3 py-2 text-center font-semibold text-[10px]">{{ trans('messages.reason', [], session('locale')) ?: 'Reason' }}</th>';
                }
                html += '<th class="px-3 py-2 text-center font-semibold text-[10px]">{{ trans('messages.date', [], session('locale')) }}</th>';
            }
            
            html += '</tr></thead><tbody>';

            $.each(res.data, function(i, detail) {
                html += '<tr class="border-b hover:bg-gray-50">';
                
                if (type === 'added') {
                    html += `<td class="px-3 py-2 text-center text-[var(--text-primary)] text-[10px]">${detail.name || '-'}</td>`;
                    html += `<td class="px-3 py-2 text-center text-[var(--text-primary)] font-semibold text-[10px]">${detail.quantity || 0}</td>`;
                    html += `<td class="px-3 py-2 text-center text-[var(--text-primary)] text-[10px]">${detail.added_by || '-'}</td>`;
                    html += `<td class="px-3 py-2 text-center text-[var(--text-primary)] text-[10px]">${detail.added_on || detail.date || '-'}</td>`;
                } else if (type === 'pos') {
                    html += `<td class="px-3 py-2 text-center text-[var(--text-primary)] text-[10px]">${detail.order_no || detail.name || '-'}</td>`;
                    html += `<td class="px-3 py-2 text-center text-[var(--text-primary)] font-semibold text-[10px]">${detail.quantity || 0}</td>`;
                    html += `<td class="px-3 py-2 text-center text-[var(--text-primary)] text-[10px]">${detail.date || '-'}</td>`;
                } else if (type === 'transferred') {
                    html += `<td class="px-3 py-2 text-center text-[var(--text-primary)] text-[10px]">${detail.name || '-'}</td>`;
                    html += `<td class="px-3 py-2 text-center text-[var(--text-primary)] text-[10px]">${detail.transfer_code || '-'}</td>`;
                    html += `<td class="px-3 py-2 text-center text-[var(--text-primary)] font-semibold text-[10px]">${detail.quantity || 0}</td>`;
                    html += `<td class="px-3 py-2 text-center text-[var(--text-primary)] text-[10px]">${detail.date || '-'}</td>`;
                } else {
                    html += `<td class="px-3 py-2 text-center text-[var(--text-primary)] text-[10px]">${detail.name || '-'}</td>`;
                    html += `<td class="px-3 py-2 text-center text-[var(--text-primary)] font-semibold text-[10px]">${detail.quantity || 0}</td>`;
                    if (type === 'pulled') {
                        html += `<td class="px-3 py-2 text-center text-[var(--text-primary)] text-[10px]">${detail.reason || '-'}</td>`;
                    }
                    html += `<td class="px-3 py-2 text-center text-[var(--text-primary)] text-[10px]">${detail.date || '-'}</td>`;
                }
                
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            $('#modalContent').html(html);
        }).fail(function() {
            $('#modalContent').html('<p class="text-red-500 text-center">{{ trans('messages.error_loading_data', [], session('locale')) }}</p>');
        });
    }

    // Function to close modal
    function closeAuditModal() {
        $('#auditDetailsModal').addClass('hidden');
        $('#modalContent').html('');
    }

    // Close modal when clicking outside
    $(document).on('click', '#auditDetailsModal', function(e) {
        if ($(e.target).attr('id') === 'auditDetailsModal') {
            closeAuditModal();
        }
    });

    // Close modal with Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && !$('#auditDetailsModal').hasClass('hidden')) {
            closeAuditModal();
        }
    });
</script>
