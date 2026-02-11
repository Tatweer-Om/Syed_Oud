<script>
    let currentPage = 1;
    const perPage = 10;

    // Get Alpine.js data
    function getAlpineData() {
        const mainElement = document.querySelector('main[x-data]');
        if (!mainElement || !mainElement._x_dataStack || !mainElement._x_dataStack[0]) {
            return null;
        }
        return mainElement._x_dataStack[0];
    }

    // Load data
    function loadData(page = 1) {
        currentPage = page;
        const alpineData = getAlpineData();
        if (alpineData) {
            alpineData.loading = true;
        }

        const search = alpineData ? alpineData.search : '';

        $.ajax({
            url: '{{ route("stock_materials.data") }}',
            method: 'GET',
            data: {
                page: page,
                per_page: perPage,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    renderTable(response.data);
                    renderPagination(response);
                } else {
                    show_notification('error', response.message || 'Error loading data');
                }
                if (alpineData) {
                    alpineData.loading = false;
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                show_notification('error', 'Error loading data');
                if (alpineData) {
                    alpineData.loading = false;
                }
            }
        });
    }

    // Render table
    function renderTable(data) {
        const tbody = $('#stock_materials_body');
        if (data.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                        {{ trans('messages.no_data_found', [], session('locale')) ?: 'No data found' }}
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        data.forEach(function(item) {
            const hasMaterials = item.materials && item.materials.length > 0;
            const materialsCount = item.materials_count || 0;

            html += `
                <tr class="border-t hover:bg-pink-50/60 transition">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <img src="${item.image}" 
                                 alt="${item.design_name}"
                                 class="w-16 h-16 rounded-lg border object-cover shadow-sm"
                                 onerror="this.src='/images/placeholder.png'">
                            <div>
                                <div class="font-semibold text-indigo-600">${item.stock_code || '—'}</div>
                                ${item.barcode ? `<div class="text-xs text-gray-500">Barcode: ${item.barcode}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="font-medium text-gray-800">${item.design_name || '—'}</div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                            ${item.category || '—'}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="showMaterialsModalFromRow(this)" 
                                data-item='${JSON.stringify(item)}'
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold flex items-center gap-2 mx-auto transition">
                            <span class="material-symbols-outlined text-base">inventory_2</span>
                            {{ trans('messages.view_materials', [], session('locale')) }}
                            ${hasMaterials ? `<span class="bg-white text-indigo-600 px-2 py-0.5 rounded-full text-xs font-bold">${materialsCount}</span>` : ''}
                        </button>
                    </td>
                </tr>
            `;
        });
        tbody.html(html);
    }

    // Show materials modal from button click
    function showMaterialsModalFromRow(button) {
        const itemData = button.getAttribute('data-item');
        if (!itemData) return;
        
        try {
            const item = JSON.parse(itemData);
            showMaterialsModal(item);
        } catch (e) {
            console.error('Error parsing item data:', e);
        }
    }

    // Show materials modal
    function showMaterialsModal(item) {
        // Populate stock info
        document.getElementById('modal_stock_image').src = item.image || '/images/placeholder.png';
        document.getElementById('modal_stock_code').textContent = item.stock_code || '—';
        document.getElementById('modal_design_name').textContent = item.design_name || '—';
        const categoryLabel = '{{ trans('messages.category', [], session('locale')) }}';
        document.getElementById('modal_category').textContent = categoryLabel + ': ' + (item.category || '—');

        // Populate materials list
        const materialsList = document.getElementById('materials_list');
        if (item.materials && item.materials.length > 0) {
            let materialsHtml = '';
            item.materials.forEach(function(material, index) {
                materialsHtml += `
                    <div class="flex items-center justify-between bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-3 rounded-lg border border-gray-200 hover:shadow-md transition">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-sm">
                                ${index + 1}
                            </div>
                            <div>
                                <div class="font-semibold text-gray-800">${material.name || '—'}</div>
                                <div class="text-xs text-gray-500">Material ID: ${material.id || '—'}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-indigo-600 text-lg">${parseFloat(material.quantity || 0).toFixed(2)}</div>
                            <div class="text-xs text-gray-600">${material.unit || 'pieces'}</div>
                        </div>
                    </div>
                `;
            });
            materialsList.innerHTML = materialsHtml;
        } else {
            const noMaterialsText = '{{ trans('messages.no_materials', [], session('locale')) }}';
            materialsList.innerHTML = `
                <div class="text-center py-8">
                    <span class="material-symbols-outlined text-6xl text-gray-300 mb-3">inventory_2</span>
                    <p class="text-gray-400 italic text-lg">${noMaterialsText}</p>
                </div>
            `;
        }

        // Show modal
        document.getElementById('materials_modal').classList.remove('hidden');
    }

    // Close materials modal
    function closeMaterialsModal() {
        document.getElementById('materials_modal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('materials_modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeMaterialsModal();
                }
            });
        }
    });

    // Render pagination
    function renderPagination(response) {
        const pagination = $('#stock_materials_pagination');
        let html = '<div class="flex justify-center items-center gap-2">';

        // Previous
        html += `
            <button onclick="loadData(${response.current_page - 1})"
                    ${response.current_page === 1 ? 'disabled' : ''}
                    class="px-3 py-1 rounded-lg border ${response.current_page === 1 ? 'opacity-40 cursor-not-allowed bg-gray-200' : 'bg-white hover:bg-gray-100'}">
                &laquo;
            </button>
        `;

        // Page numbers
        for (let i = 1; i <= response.last_page; i++) {
            html += `
                <button onclick="loadData(${i})"
                        class="px-4 py-1 rounded-lg border ${response.current_page === i ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)]' : 'bg-white hover:bg-gray-100'}">
                    ${i}
                </button>
            `;
        }

        // Next
        html += `
            <button onclick="loadData(${response.current_page + 1})"
                    ${response.current_page === response.last_page ? 'disabled' : ''}
                    class="px-3 py-1 rounded-lg border ${response.current_page === response.last_page ? 'opacity-40 cursor-not-allowed bg-gray-200' : 'bg-white hover:bg-gray-100'}">
                &raquo;
            </button>
        `;

        const totalLabel = '{{ trans('messages.total', [], session('locale')) }}';
        html += `<span class="ml-4 text-sm text-gray-600">${response.total} ${totalLabel}</span>`;
        html += '</div>';
        pagination.html(html);
    }

    // Initialize on page load
    $(document).ready(function() {
        loadData();
    });
</script>
