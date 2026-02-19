<script>
$(document).ready(function() {
    let currentPage = 1;
    let currentSearch = '';
    let currentFromDate = '';
    let currentToDate = '';

    // Load audit data on page load
    loadAuditData();

    // Search on Enter key
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) {
            searchAudit();
        }
    });

    // Load audit data
    function loadAuditData(page = 1) {
        currentPage = page;
        
        const params = new URLSearchParams({
            page: page,
            per_page: 10,
        });

        if (currentSearch) {
            params.append('search', currentSearch);
        }
        if (currentFromDate) {
            params.append('from_date', currentFromDate);
        }
        if (currentToDate) {
            params.append('to_date', currentToDate);
        }

        $.get("{{ url('stock/comprehensive-audit/list') }}?" + params.toString(), function(res) {
            if (!res.success) {
                $('#auditTableBody').html('<tr><td colspan="9" class="px-4 py-8 text-center text-red-500">Error loading audit data</td></tr>');
                return;
            }

            // Show/hide remaining quantity section
            if (res.remaining_quantity !== null && res.remaining_quantity !== undefined) {
                $('#remainingQtySection').removeClass('hidden');
                $('#remainingQtyValue').text(res.remaining_quantity);
                
                // Remaining by size breakdown
                if (Array.isArray(res.remaining_by_size) && res.remaining_by_size.length > 0) {
                    const parts = res.remaining_by_size
                        .filter(x => x && x.size !== undefined && x.quantity !== undefined)
                        .map(x => `${x.size}: ${x.quantity}`);
                    $('#remainingBySize').text(parts.join(' | '));
                } else {
                    $('#remainingBySize').text('');
                }
            } else {
                $('#remainingQtySection').addClass('hidden');
                $('#remainingBySize').text('');
            }

            // Build table rows
            let rows = '';
            if (res.data && res.data.length > 0) {
                $.each(res.data, function(i, item) {
                    const changeClass = item.quantity_change >= 0 ? 'text-green-600' : 'text-red-600';
                    const changeSign = item.quantity_change >= 0 ? '+' : '';
                    
                    rows += `
                        <tr class="hover:bg-gray-50 border-b">
                            <td class="px-4 py-3 border text-right text-xs">${item.date}</td>
                            <td class="px-4 py-3 border text-right text-xs">${item.time}</td>
                            <td class="px-4 py-3 border text-right text-xs">${item.barcode}</td>
                            <td class="px-4 py-3 border text-right text-xs font-medium">${item.design_name || '—'}</td>
                            <td class="px-4 py-3 border text-right text-xs">
                                <span class="px-1 rounded text-xs font-medium ${
                                    item.operation_type === 'added' ? 'bg-green-100 text-green-800' :
                                    item.operation_type === 'updated' ? 'bg-blue-100 text-blue-800' :
                                    item.operation_type === 'sold' ? 'bg-red-100 text-red-800' :
                                    item.operation_type === 'transferred' ? 'bg-orange-100 text-orange-800' :
                                    'bg-purple-100 text-purple-800'
                                }">
                                    ${item.operation_label}
                                </span>
                            </td>
                            <td class="px-4 py-3 border text-right text-xs">${item.previous_quantity}</td>
                            <td class="px-4 py-3 border text-right text-xs font-semibold ${changeClass}">${changeSign}${item.quantity_change}</td>
                            <td class="px-4 py-3 border text-right text-xs font-semibold">${item.new_quantity}</td>
                            <td class="px-4 py-3 border text-right text-xs">${item.added_by}</td>
                        </tr>
                    `;
                });
            } else {
                rows = '<tr><td colspan="9" class="px-4 py-8 text-center text-gray-400 text-sm">{{ trans("messages.no_data_found", [], session("locale")) ?: "No data found" }}</td></tr>';
            }

            $('#auditTableBody').html(rows);
            renderPagination(res.current_page, res.last_page, res.total, res.per_page);
        }).fail(function() {
            $('#auditTableBody').html('<tr><td colspan="9" class="px-4 py-8 text-center text-red-500">Error loading audit data</td></tr>');
        });
    }

    // Render pagination
    function renderPagination(currentPage, lastPage, total, perPage) {
        let pagination = '';
        
        if (total > 0) {
            // Show pagination info
            let startItem = ((currentPage - 1) * perPage) + 1;
            let endItem = Math.min(currentPage * perPage, total);
            
            // Previous Button
            pagination += `
            <li class="flex">
                <a href="#" data-page="${currentPage - 1}"
                   class="px-3 py-1 border rounded-lg mx-1 text-sm transition
                          ${currentPage == 1 
                            ? 'opacity-40 cursor-not-allowed bg-gray-200' 
                            : 'bg-white hover:bg-gray-100'}">
                    &laquo;
                </a>
            </li>`;

            // Page numbers - show max 5 pages around current
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(lastPage, currentPage + 2);
            
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
                              ${currentPage == i 
                                ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md' 
                                : 'bg-white hover:bg-gray-100'}">
                        ${i}
                    </a>
                </li>
                `;
            }
            
            if (endPage < lastPage) {
                if (endPage < lastPage - 1) {
                    pagination += `<li class="px-2 text-gray-500 flex items-center">...</li>`;
                }
                pagination += `
                <li>
                    <a href="#" data-page="${lastPage}"
                       class="px-4 py-1 mx-1 text-sm font-medium border rounded-lg transition bg-white hover:bg-gray-100">
                        ${lastPage}
                    </a>
                </li>`;
            }

            // Next Button
            pagination += `
            <li class="flex">
                <a href="#" data-page="${currentPage + 1}"
                   class="px-3 py-1 border rounded-lg mx-1 text-sm transition
                          ${currentPage == lastPage 
                            ? 'opacity-40 cursor-not-allowed bg-gray-200' 
                            : 'bg-white hover:bg-gray-100'}">
                    &raquo;
                </a>
            </li>`;
            
            // Show page info
            pagination += `<li class="px-4 py-1 text-sm text-gray-600 flex items-center ml-4 font-medium">Showing ${startItem}-${endItem} of ${total}</li>`;
        } else {
            pagination = '<li class="px-4 py-1 text-sm text-gray-600">No items found</li>';
        }
        
        $('#pagination').html(pagination);
        
        // Handle pagination click
        $(document).off('click', '#pagination a').on('click', '#pagination a', function(e) {
            e.preventDefault();
            let page = $(this).data('page');
            if (page && page > 0 && page <= lastPage) {
                loadAuditData(page);
            }
        });
    }

    // Search function
    window.searchAudit = function() {
        currentSearch = $('#searchInput').val().trim();
        currentFromDate = $('#fromDate').val();
        currentToDate = $('#toDate').val();
        loadAuditData(1);
    };

    // Clear filters
    window.clearFilters = function() {
        $('#searchInput').val('');
        $('#fromDate').val('');
        $('#toDate').val('');
        currentSearch = '';
        currentFromDate = '';
        currentToDate = '';
        loadAuditData(1);
    };

    // Make loadAuditData available globally
    window.loadAuditData = loadAuditData;
});
</script>
