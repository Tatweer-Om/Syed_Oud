<script>
    $(document).ready(function() {
        let currentPage = 1;

        function loadAuditData(page = 1) {
            currentPage = page;
            const search = $('#search_material').val().trim();
            const dateFrom = $('#date_from').val();
            const dateTo = $('#date_to').val();

            $('#auditTableBody').html('<tr><td colspan="8" class="px-2 py-4 text-xs text-center text-gray-500 border border-gray-300">{{ trans('messages.loading', [], session('locale')) }}...</td></tr>');

            $.get("{{ url('material-quantity-audit/data') }}", {
                page: page,
                search: search,
                date_from: dateFrom,
                date_to: dateTo
            }, function(res) {
                if (!res.success) {
                    $('#auditTableBody').html('<tr><td colspan="8" class="px-4 sm:px-6 py-8 text-center text-red-500">Error loading audit data</td></tr>');
                    return;
                }

                // ---- Table Rows ----
                let rows = '';
                if (res.data && res.data.length > 0) {
                    $.each(res.data, function(i, item) {
                        const changeClass = parseFloat(item.quantity_change) >= 0 ? 'text-green-600' : 'text-red-600';
                        const changeIcon = parseFloat(item.quantity_change) >= 0 ? 'arrow_upward' : 'arrow_downward';
                        
                        let operationBadge = '';
                        if (item.operation_type === 'added') {
                            operationBadge = '<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">' + item.operation_type_label + '</span>';
                        } else if (item.operation_type === 'quantity_added') {
                            operationBadge = '<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">' + item.operation_type_label + '</span>';
                        } else if (item.operation_type === 'material_deducted') {
                            operationBadge = '<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">' + item.operation_type_label + '</span>';
                        } else {
                            operationBadge = '<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700">' + item.operation_type_label + '</span>';
                        }

                        // Source badge
                        let sourceBadge = '';
                        if (item.source === 'stock') {
                            sourceBadge = '<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-600">' + (item.source_label || item.source) + '</span>';
                        } else if (item.source === 'special_order') {
                            let sourceText = item.source_label || item.source || 'Special Order';
                            // Always show special order number from database if it exists
                            if (item.special_order_number && item.special_order_number !== null && item.special_order_number !== '' && item.special_order_number !== undefined) {
                                sourceText = sourceText + ' (' + item.special_order_number + ')';
                            }
                            sourceBadge = '<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-600">' + sourceText + '</span>';
                        } else if (item.source === 'manage_quantity') {
                            sourceBadge = '<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs font-medium bg-cyan-50 text-cyan-600">' + (item.source_label || item.source) + '</span>';
                        } else if (item.source === 'purchase') {
                            sourceBadge = '<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs font-medium bg-green-50 text-green-600">' + (item.source_label || item.source) + '</span>';
                        } else {
                            sourceBadge = '<span class="text-xs text-gray-500">' + (item.source_label || item.source || '-') + '</span>';
                        }

                        rows += `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-2 py-2 text-xs text-[var(--text-primary)] border border-gray-300">${item.date || '-'}</td>
                            <td class="px-2 py-2 text-xs text-[var(--text-primary)] font-medium border border-gray-300">${item.material_name || '-'}</td>
                            <td class="px-2 py-2 text-xs text-center border border-gray-300">${sourceBadge}</td>
                            <td class="px-2 py-2 text-xs text-center border border-gray-300">${operationBadge}</td>
                            <td class="px-2 py-2 text-xs text-[var(--text-primary)] border border-gray-300">${item.previous_quantity || '0.00'}</td>
                            <td class="px-2 py-2 text-xs text-[var(--text-primary)] font-medium ${changeClass} border border-gray-300">
                                <div class="flex items-center justify-center gap-1">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">${changeIcon}</span>
                                    ${item.quantity_change || '0.00'}
                                </div>
                            </td>
                            <td class="px-2 py-2 text-xs text-[var(--text-primary)] font-semibold text-primary border border-gray-300">${item.remaining_quantity || '0.00'}</td>
                            <td class="px-2 py-2 text-xs text-[var(--text-primary)] border border-gray-300">${item.added_by || '-'}</td>
                        </tr>
                        `;
                    });
                } else {
                    rows = '<tr><td colspan="8" class="px-2 py-4 text-xs text-center text-gray-500 border border-gray-300">{{ trans('messages.no_data', [], session('locale')) ?: 'No data found' }}</td></tr>';
                }
                $('#auditTableBody').html(rows);

                // Show remaining quantity if search is provided and value exists
                const remainingQtyDisplay = $('#remaining_quantity_display');
                const remainingQtyValue = $('#remaining_quantity_value');
                if (res.remaining_quantity !== null && res.remaining_quantity !== undefined && search) {
                    remainingQtyValue.text(res.remaining_quantity);
                    remainingQtyDisplay.removeClass('hidden');
                } else {
                    remainingQtyDisplay.addClass('hidden');
                }

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
                $('#auditTableBody').html('<tr><td colspan="8" class="px-2 py-4 text-xs text-center text-red-500 border border-gray-300">Error loading audit data</td></tr>');
            });
        }

        // Handle pagination click
        $(document).on('click', '#pagination a', function(e) {
            e.preventDefault();
            let page = $(this).data('page');
            if (page && page > 0) {
                loadAuditData(page);
            }
        });

        // Search button click
        $('#search_btn').on('click', function() {
            currentPage = 1;
            loadAuditData(1);
        });

        // Enter key in search field
        $('#search_material').on('keypress', function(e) {
            if (e.key === 'Enter') {
                currentPage = 1;
                loadAuditData(1);
            }
        });

        // Date change events
        $('#date_from, #date_to').on('change', function() {
            currentPage = 1;
            loadAuditData(1);
        });

        // Initial load
        loadAuditData(1);
    });
</script>
