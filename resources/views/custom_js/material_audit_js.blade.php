<script>
    $(document).ready(function() {
        let currentPage = 1;
        let currentSearch = '';
        const perPage = 10;

        // Load data
        function loadMaterialAuditData(page = 1, search = '') {
            currentPage = page;
            currentSearch = search;

            $('#materialAuditTableBody').html(`
                <tr>
                    <td colspan="8" class="px-4 sm:px-6 py-8 text-center text-gray-500">
                        {{ trans('messages.loading', [], session('locale')) }}...
                    </td>
                </tr>
            `);

            $.ajax({
                url: '{{ route("material.audit.data") }}',
                method: 'GET',
                data: {
                    page: page,
                    per_page: perPage,
                    search: search
                },
                success: function(response) {
                    if (response.success && response.data) {
                        renderTable(response.data);
                        renderPagination(response.current_page, response.last_page, response.total);
                    } else {
                        $('#materialAuditTableBody').html(`
                            <tr>
                                <td colspan="8" class="px-4 sm:px-6 py-8 text-center text-red-500">
                                    {{ trans('messages.error_loading_data', [], session('locale')) ?: 'Error loading data' }}
                                </td>
                            </tr>
                        `);
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                    $('#materialAuditTableBody').html(`
                        <tr>
                            <td colspan="8" class="px-4 sm:px-6 py-8 text-center text-red-500">
                                {{ trans('messages.error_loading_data', [], session('locale')) ?: 'Error loading data' }}
                            </td>
                        </tr>
                    `);
                }
            });
        }

        // Render table
        function renderTable(data) {
            if (!data || data.length === 0) {
                $('#materialAuditTableBody').html(`
                    <tr>
                        <td colspan="7" class="px-4 sm:px-6 py-8 text-center text-gray-500">
                            {{ trans('messages.no_data', [], session('locale')) ?: 'No data found' }}
                        </td>
                    </tr>
                `);
                return;
            }

            let html = '';
            data.forEach(function(item) {
                // Merge first three columns: barcode, code, design_name
                let mergedColumn = `
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-gray-400 text-sm">qr_code</span>
                            <span class="text-[var(--text-primary)] font-semibold">${item.stock_code || '-'}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-gray-400 text-sm">style</span>
                            <span class="text-[var(--text-primary)]">${item.design_name || '-'}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-gray-400 text-sm">barcode</span>
                            <span class="px-2 py-1 bg-gray-100 rounded border border-gray-300 font-mono text-xs text-gray-700">${item.barcode || '-'}</span>
                        </div>
                    </div>
                `;

                // Tailor name
                let tailorNameHtml = item.tailor_name && item.tailor_name !== '-' 
                    ? `<span class="px-2 py-1 bg-purple-50 rounded text-xs text-purple-800 border border-purple-200 font-semibold">${item.tailor_name}</span>`
                    : '<span class="text-gray-400 text-sm">-</span>';

                // Source
                let sourceHtml = item.source && item.source !== '-'
                    ? `<span class="px-2 py-1 bg-blue-50 rounded text-xs text-blue-800 border border-blue-200 font-semibold">${item.source}</span>`
                    : '<span class="text-gray-400 text-sm">-</span>';

                // Format total required materials (separate column)
                let requiredMaterialsHtml = '';
                if (item.required_materials && item.required_materials.length > 0) {
                    requiredMaterialsHtml = '<div class="space-y-1">';
                    item.required_materials.forEach(function(material) {
                        requiredMaterialsHtml += `
                            <div class="px-2 py-1 bg-indigo-50 rounded text-xs text-indigo-800 border border-indigo-200">
                                <span class="font-semibold">${material.name}</span>
                                <span class="text-gray-600">: ${parseFloat(material.quantity).toFixed(2)} ${material.unit}</span>
                            </div>
                        `;
                    });
                    requiredMaterialsHtml += '</div>';
                } else {
                    requiredMaterialsHtml = '<span class="text-gray-400 text-sm">-</span>';
                }

                // Format material available in stock (separate column)
                let materialStockHtml = '';
                if (item.material_stock_quantities && item.material_stock_quantities.length > 0) {
                    materialStockHtml = '<div class="space-y-1">';
                    item.material_stock_quantities.forEach(function(material) {
                        materialStockHtml += `
                            <div class="px-2 py-1 bg-teal-50 rounded text-xs text-teal-800 border border-teal-200">
                                <span class="font-semibold">${material.name}</span>
                                <span class="text-gray-600">: ${parseFloat(material.quantity).toFixed(2)} ${material.unit}</span>
                            </div>
                        `;
                    });
                    materialStockHtml += '</div>';
                } else {
                    materialStockHtml = '<span class="text-gray-400 text-sm">-</span>';
                }

                // Format materials with tailor (separate column) - compact format
                let tailorMaterialsHtml = '';
                if (item.tailor_materials && item.tailor_materials.length > 0) {
                    tailorMaterialsHtml = '<div class="space-y-1">';
                    item.tailor_materials.forEach(function(tm) {
                        const totalSent = parseFloat(tm.sent_quantity || 0);
                        tailorMaterialsHtml += `
                            <div class="px-2 py-1 bg-amber-50 rounded text-xs text-amber-800 border border-amber-200">
                                <span class="font-semibold">${tm.material_name}</span>
                                <span class="text-gray-600">: ${totalSent.toFixed(2)} ${tm.unit}</span>
                            </div>
                        `;
                    });
                    tailorMaterialsHtml += '</div>';
                } else {
                    tailorMaterialsHtml = '<span class="text-gray-400 text-sm">-</span>';
                }

                // Format material status (separate column) - compact format, show quantity short in red, surplus in green
                let statusHtml = '';
                if (item.tailor_materials && item.tailor_materials.length > 0) {
                    statusHtml = '<div class="space-y-1">';
                    item.tailor_materials.forEach(function(tm) {
                        const requiredQty = parseFloat(tm.required_quantity || 0);
                        const sentQty = parseFloat(tm.sent_quantity || 0);
                        const isShort = requiredQty > sentQty;
                        const shortfall = requiredQty - sentQty;
                        const surplus = sentQty - requiredQty;
                        
                        let statusClass, statusText, statusValue;
                        
                        if (isShort) {
                            // Quantity Short - show in red
                            statusClass = 'text-red-600 bg-red-50 border-red-200';
                            statusText = '{{ trans("messages.quantity_short", [], session("locale")) ?: "Quantity Short" }}';
                            statusValue = shortfall.toFixed(2) + ' ' + tm.unit;
                        } else if (surplus === 0) {
                            // Material Finished with Tailor - show in green
                            statusClass = 'text-green-600 bg-green-50 border-green-200';
                            statusText = '{{ trans("messages.material_finished_with_tailor", [], session("locale")) ?: "Material Finished with Tailor" }}';
                            statusValue = '0.00 ' + tm.unit;
                        } else {
                            // Quantity Surplus - show in green
                            statusClass = 'text-green-600 bg-green-50 border-green-200';
                            statusText = '{{ trans("messages.quantity_surplus", [], session("locale")) ?: "Quantity Surplus" }}';
                            statusValue = surplus.toFixed(2) + ' ' + tm.unit;
                        }
                        
                        statusHtml += `
                            <div class="px-2 py-1 rounded text-xs border ${statusClass}">
                                <span class="font-semibold">${tm.material_name}</span>
                                <span class="text-gray-600">: ${statusText} ${statusValue}</span>
                            </div>
                        `;
                    });
                    statusHtml += '</div>';
                } else {
                    statusHtml = '<span class="text-gray-400 text-sm">-</span>';
                }

                html += `
                    <tr class="hover:bg-pink-50/50 transition-colors">
                        <td class="px-4 sm:px-6 py-3 text-[var(--text-primary)]">${mergedColumn}</td>
                        <td class="px-4 sm:px-6 py-3 text-[var(--text-primary)]">${tailorNameHtml}</td>
                        <td class="px-4 sm:px-6 py-3 text-[var(--text-primary)]">${sourceHtml}</td>
                        <td class="px-4 sm:px-6 py-3 text-center text-[var(--text-primary)]">
                            <div class="space-y-1">
                                <div class="font-semibold text-blue-600 text-xs">${item.quantity_added || 0}</div>
                                <div class="text-xs text-gray-600">${item.added_by || '-'}</div>
                                <div class="text-xs text-gray-500">${item.added_at || '-'}</div>
                            </div>
                        </td>
                        <td class="px-4 sm:px-6 py-3 text-[var(--text-primary)]">${requiredMaterialsHtml}</td>
                        <td class="px-4 sm:px-6 py-3 text-[var(--text-primary)]">${materialStockHtml}</td>
                        <td class="px-4 sm:px-6 py-3 text-[var(--text-primary)]">${tailorMaterialsHtml}</td>
                        <td class="px-4 sm:px-6 py-3 text-[var(--text-primary)]">${statusHtml}</td>
                    </tr>
                `;
            });

            $('#materialAuditTableBody').html(html);
        }

        // Render pagination
        function renderPagination(currentPage, lastPage, total) {
            let html = '';
            
            if (lastPage <= 1) {
                $('#pagination').html('');
                return;
            }

            // Show pagination info
            let startItem = ((currentPage - 1) * perPage) + 1;
            let endItem = Math.min(currentPage * perPage, total);
            
            // Previous Button
            html += `
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
                html += `
                <li>
                    <a href="#" data-page="1"
                       class="px-4 py-1 mx-1 text-sm font-medium border rounded-lg transition bg-white hover:bg-gray-100">
                        1
                    </a>
                </li>`;
                if (startPage > 2) {
                    html += `<li class="px-2 text-gray-500 flex items-center">...</li>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                html += `
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
                    html += `<li class="px-2 text-gray-500 flex items-center">...</li>`;
                }
                html += `
                <li>
                    <a href="#" data-page="${lastPage}"
                       class="px-4 py-1 mx-1 text-sm font-medium border rounded-lg transition bg-white hover:bg-gray-100">
                        ${lastPage}
                    </a>
                </li>`;
            }

            // Next Button
            html += `
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
            html += `<li class="px-4 py-1 text-sm text-gray-600 flex items-center ml-4 font-medium">Showing ${startItem}-${endItem} of ${total}</li>`;
            
            $('#pagination').html(html);
        }

        // Handle pagination click
        $(document).on('click', '#pagination a', function(e) {
            e.preventDefault();
            let page = $(this).data('page');
            if (page && page > 0) {
                loadMaterialAuditData(page, currentSearch);
            }
        });

        // Search functionality
        $('#search_btn').on('click', function() {
            const search = $('#search_material_audit').val().trim();
            loadMaterialAuditData(1, search);
        });

        $('#search_material_audit').on('keypress', function(e) {
            if (e.which === 13) {
                const search = $(this).val().trim();
                loadMaterialAuditData(1, search);
            }
        });

        // Make loadMaterialAuditData available globally
        window.loadMaterialAuditData = loadMaterialAuditData;

        // Initial load
        loadMaterialAuditData(1, '');
    });
</script>
