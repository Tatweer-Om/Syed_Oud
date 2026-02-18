<script>
    // Store current page globally
    var currentStockPage = 1;

    // Translations
    const trans = {
        details: "{{ trans('messages.details', [], session('locale')) }}",
        enter_quantity: "{{ trans('messages.enter_quantity', [], session('locale')) }}",
        edit: "{{ trans('messages.edit', [], session('locale')) }}",
        delete: "{{ trans('messages.delete', [], session('locale')) }}",
        design: "{{ trans('messages.design', [], session('locale')) }}",
        category: "{{ trans('messages.category', [], session('locale')) }}",
        size: "{{ trans('messages.size', [], session('locale')) }}",
        color: "{{ trans('messages.color', [], session('locale')) }}",
        quantity: "{{ trans('messages.quantity', [], session('locale')) }}",
        failed_to_load_details: "{{ trans('messages.failed_to_load_details', [], session('locale')) }}",
        success_title: "{{ trans('messages.success_title', [], session('locale')) }}",
        error_title: "{{ trans('messages.error_title', [], session('locale')) }}",
        error_occurred: "{{ trans('messages.error_occurred', [], session('locale')) }}",
        error_saving: "{{ trans('messages.error_saving', [], session('locale')) }}",
        saving: "{{ trans('messages.saving', [], session('locale')) }}",
        please_enter_pull_notes: "{{ trans('messages.please_enter_pull_notes', [], session('locale')) }}",
        pieces: "{{ trans('messages.pieces', [], session('locale')) }}"
    };

    // Make loadStock globally accessible
    function loadStock(page = 1) {
        currentStockPage = page || currentStockPage || 1;
            $.get("/stock/list", {
                page: page
            })
            .done(function(res) {

                // --- Desktop Table ---
                let tableRows = "";
                $.each(res.data, function(index, stock) {
                    const quantity = parseFloat(stock.quantity) || 0;
                    const categoryName = stock.category ? stock.category.category_name : '-';
                    let quantityStatus = 'out_of_stock';
                    if (quantity > 0 && quantity <= 5) quantityStatus = 'low';
                    else if (quantity > 5) quantityStatus = 'available';
                    const salesPrice = stock.sales_price ? parseFloat(stock.sales_price).toFixed(3) : '-';
                    const formattedSalesPrice = salesPrice !== '-' ? `${salesPrice} OMR` : '-';
                    const imageUrl = stock.image ? (stock.image.startsWith('http') ? stock.image : '/' + stock.image.replace(/^\/+/, '')) : '';

                    tableRows += `
                <tr class="border-t hover:bg-pink-50/60 transition" data-id="${stock.id}" data-quantity-status="${quantityStatus}">
                    <td class="px-3 sm:px-4 md:px-6 py-3 text-center">
                        <div class="flex items-center justify-center gap-3">
                            ${imageUrl ? `<img src="${imageUrl}" alt="" class="w-12 h-16 object-cover rounded-md flex-shrink-0" onerror="this.style.display='none'" />` : ''}
                            <div class="flex flex-col items-center text-left min-w-0">
                                <span class="font-bold break-words">${stock.stock_name ?? '-'}</span>
                            ${categoryName !== '-' ? `<span class="text-sm text-gray-600">(${categoryName})</span>` : ''}
                        </div>
                    </td>
                    <td class="px-3 sm:px-4 md:px-6 py-3 text-center whitespace-nowrap font-medium">${stock.barcode || '-'}</td>
                    <td class="px-3 sm:px-4 md:px-6 py-3 text-center font-bold whitespace-nowrap">${quantity}</td>
                    <td class="px-3 sm:px-4 md:px-6 py-3 text-center whitespace-nowrap font-semibold text-[var(--primary-color)]">${formattedSalesPrice}</td>
                    <td class="px-3 sm:px-4 md:px-6 py-3 text-center whitespace-nowrap">
                        <div class="flex flex-wrap justify-center gap-2 text-[12px] font-semibold text-gray-700">
                            <button class="flex flex-col items-center gap-1 hover:text-purple-600 transition" onclick="openStockDetails(${stock.id})" title="${trans.details}">
                                <span class="material-symbols-outlined bg-purple-50 text-purple-500 p-2 rounded-full text-base">info</span>
                                ${trans.details}
                            </button>
                            <button class="flex flex-col items-center gap-1 hover:text-green-600 transition" onclick="openStockQuantity(${stock.id}, ${quantity})" title="${trans.enter_quantity}">
                                <span class="material-symbols-outlined bg-green-50 text-green-600 p-2 rounded-full text-base">add</span>
                                ${trans.enter_quantity}
                            </button>
                            <button class="flex flex-col items-center gap-1 hover:text-blue-600 transition" onclick="window.location.href='/edit_stock/${stock.id}?page=' + (currentStockPage || 1)">
                                <span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full text-base">edit</span>
                                ${trans.edit}
                            </button>
                            <button class="flex flex-col items-center gap-1 hover:text-red-600 transition delete-stock-btn">
                                <span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full text-base">delete</span>
                                ${trans.delete}
                            </button>
                        </div>
                    </td>
                </tr>`;
                });
                $("#desktop_stock_body").html(tableRows);

                // --- Mobile Cards ---
                let mobileCards = '';
                $.each(res.data, function(index, stock) {
                    const quantity = parseFloat(stock.quantity) || 0;
                    const categoryName = stock.category ? stock.category.category_name : '-';
                    let quantityStatusMobile = 'out_of_stock';
                    if (quantity > 0 && quantity <= 5) quantityStatusMobile = 'low';
                    else if (quantity > 5) quantityStatusMobile = 'available';
                    const salesPriceMobile = stock.sales_price ? parseFloat(stock.sales_price).toFixed(3) : '-';
                    const formattedSalesPriceMobile = salesPriceMobile !== '-' ? `${salesPriceMobile} OMR` : '-';

                    const imageUrlMobile = stock.image ? (stock.image.startsWith('http') ? stock.image : '/' + stock.image.replace(/^\/+/, '')) : '';
                    mobileCards += `
                <div class="bg-white rounded-xl shadow-sm border border-pink-100 p-4 flex flex-col gap-3" data-id="${stock.id}" data-quantity-status="${quantityStatusMobile}">
                    <div class="flex gap-4">
                        ${imageUrlMobile ? `<div class="w-20 h-24 rounded-md overflow-hidden bg-gray-100 flex-shrink-0"><img src="${imageUrlMobile}" alt="" class="w-full h-full object-cover" onerror="this.parentElement.style.display='none'" /></div>` : ''}
                        <div class="flex-1 text-sm">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="font-bold text-gray-900">${stock.stock_name ?? '-'}</h3>
                                <span class="text-[var(--primary-color)] font-semibold text-xs">${formattedSalesPriceMobile}</span>
                            </div>
                            ${categoryName !== '-' ? `<p class="text-gray-600 text-xs">${trans.category}: ${categoryName}</p>` : ''}
                            <p class="text-gray-600 text-xs">Barcode: ${stock.barcode || '-'}</p>
                            <p class="text-gray-600 text-xs font-semibold">${trans.quantity}: ${quantity}</p>
                        </div>
                    </div>
                    <div class="mt-4 border-t pt-3">
                        <div class="flex flex-wrap justify-around gap-2 text-xs font-semibold text-gray-600">
                            <button class="flex flex-col items-center gap-1 hover:text-purple-500 transition" onclick="openStockDetails(${stock.id})">
                                <span class="material-symbols-outlined bg-purple-50 text-purple-500 p-2 rounded-full text-base">info</span>
                                ${trans.details}
                            </button>
                            <button class="flex flex-col items-center gap-1 hover:text-green-500 transition" onclick="openStockQuantity(${stock.id}, ${quantity})">
                                <span class="material-symbols-outlined bg-green-50 text-green-600 p-2 rounded-full text-base">add</span>
                                ${trans.enter_quantity}
                            </button>
                            <button class="flex flex-col items-center gap-1 hover:text-blue-500 transition" onclick="window.location.href='/edit_stock/${stock.id}?page=' + (currentStockPage || 1)">
                                <span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full text-base">edit</span>
                                ${trans.edit}
                            </button>
                            <button class="flex flex-col items-center gap-1 hover:text-red-500 transition delete-stock-btn-mobile" data-stock-id="${stock.id}">
                                <span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full text-base">delete</span>
                                ${trans.delete}
                            </button>
                        </div>
                    </div>
                </div>`;
                });
                $("#mobile_stock_cards").html(mobileCards);

                // --- Pagination (windowed: first, ... window ..., last) ---
                let pagination = "";
                const cur = res.current_page;
                const last = res.last_page;
                const windowSize = 2; // pages to show on each side of current

                const btn = (href, label, active, disabled) => {
                    const base = "inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0 ";
                    const activeCls = active ? "bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md" : "bg-white hover:bg-gray-100 border-gray-200";
                    const disCls = disabled ? "opacity-40 pointer-events-none bg-gray-200 border-gray-200" : "";
                    const url = disabled ? "#" : (href || "#");
                    return `<li class="shrink-0"><a href="${url}" class="${base} ${disabled ? disCls : activeCls}">${label}</a></li>`;
                };

                // Previous
                pagination += btn(res.prev_page_url, "&laquo; Prev", false, !res.prev_page_url);

                if (last <= 7) {
                    for (let i = 1; i <= last; i++) {
                        pagination += btn("/stock/list?page=" + i, i, cur === i, false);
                    }
                } else {
                    const showFirst = cur > windowSize + 2;
                    const showLast = cur < last - windowSize - 1;
                    const start = Math.max(1, cur - windowSize);
                    const end = Math.min(last, cur + windowSize);

                    if (showFirst) pagination += btn("/stock/list?page=1", "1", cur === 1, false);
                    if (showFirst) pagination += '<li class="shrink-0 px-1 py-1.5 text-gray-400 text-sm">...</li>';

                    for (let i = start; i <= end; i++) {
                        pagination += btn("/stock/list?page=" + i, i, cur === i, false);
                    }

                    if (showLast) pagination += '<li class="shrink-0 px-1 py-1.5 text-gray-400 text-sm">...</li>';
                    if (showLast) pagination += btn("/stock/list?page=" + last, last, cur === last, false);
                }

                // Next
                pagination += btn(res.next_page_url, "Next &raquo;", false, !res.next_page_url);

                $("#stock_pagination").html(pagination);

                // Apply filters after data is loaded (in case there's an active search/filter)
                if (typeof applyFilters === 'function') {
                    setTimeout(function() {
                        applyFilters();
                    }, 100);
                }

            })
            .always(function() {
                $("#stock_pagination_loader").hide();
            });
    }

    // Function to apply both search and quantity filters (global scope)
    function applyFilters() {
        let search = $("#stock_search").val().toLowerCase();
        let filterValue = $("#stock_filter").val();

        // Filter desktop table
        $("#desktop_stock_body tr").each(function() {
            let $row = $(this);
            let rowText = $row.text().toLowerCase();
            let quantityStatus = $row.data('quantity-status') || '';
            
            let matchesSearch = search === '' || rowText.indexOf(search) > -1;
            let matchesFilter = filterValue === 'all' || quantityStatus === filterValue;
            
            $row.toggle(matchesSearch && matchesFilter);
        });

        // Filter mobile cards
        $("#mobile_stock_cards > div").each(function() {
            let $card = $(this);
            let cardText = $card.text().toLowerCase();
            let quantityStatus = $card.data('quantity-status') || '';
            
            let matchesSearch = search === '' || cardText.indexOf(search) > -1;
            let matchesFilter = filterValue === 'all' || quantityStatus === filterValue;
            
            $card.toggle(matchesSearch && matchesFilter);
        });
    }

    $(document).ready(function() {
        // Pagination click
        $(document).on("click", "#stock_pagination a", function(e) {
            e.preventDefault();
            let href = $(this).attr("href");
            if (href && href !== "#") {
                let page = new URL(href, window.location.origin).searchParams.get("page");
                if (page) {
                    $("#stock_pagination_loader").show();
                    loadStock(page);
                }
            }
        });

        // Client-side search
        $("#stock_search").on("keyup", function() {
            applyFilters();
        });

        // Quantity filter
        $("#stock_filter").on("change", function() {
            applyFilters();
        });

        // Initial load: use ?page=X from URL when returning from edit (e.g. view_stock?page=34)
        var startPage = 1;
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('page')) {
            var p = parseInt(urlParams.get('page'), 10);
            if (!isNaN(p) && p >= 1) {
                startPage = p;
            }
        }
        loadStock(startPage);
    });


function openStockDetails(stockId) {
    const mainEl = document.querySelector('main[x-data]');
    if (!mainEl || !mainEl._x_dataStack) return;
    const alpine = mainEl._x_dataStack[0];
    alpine.loading = true;
    alpine.showDetails = true;

    $.ajax({
        url: '{{ url("get_simple_stock_detail") }}',
        method: 'GET',
        data: { id: stockId },
        success: function(res) {
            $('#stock_detail_image').attr('src', res.image || '').parent().show();
            if (!res.image) $('#detail_image_wrap').hide();
            $('#detail_stock_name').text(res.stock_name || '-');
            $('#detail_category').text(res.category_name || '-');
            $('#detail_barcode').text(res.barcode || '-');
            $('#detail_production_unit').text(res.production_unit_name || '-');
            $('#detail_quantity').text(res.quantity ?? '-');
            $('#detail_cost_price').text(res.cost_price != null ? parseFloat(res.cost_price).toFixed(3) + ' OMR' : '-');
            $('#detail_sales_price').text(res.sales_price != null ? parseFloat(res.sales_price).toFixed(3) + ' OMR' : '-');
            $('#detail_discount').text(res.discount != null ? parseFloat(res.discount).toFixed(3) : '-');
            $('#detail_tax').text(res.tax != null ? parseFloat(res.tax).toFixed(3) : '-');
            $('#detail_notification_limit').text(res.notification_limit != null && res.notification_limit !== '' ? res.notification_limit : '-');
            $('#detail_notes').text(res.stock_notes || '-');
            alpine.loading = false;
        },
        error: function() {
            show_notification('error', trans.failed_to_load_details);
            alpine.loading = false;
            alpine.showDetails = false;
        }
    });
}

function openStockQuantity(stockId, availableQty) {
    const mainEl = document.querySelector('main[x-data]');
    if (!mainEl || !mainEl._x_dataStack) return;
    const alpine = mainEl._x_dataStack[0];
    alpine.availableQuantity = parseFloat(availableQty) || 0;
    alpine.actionType = 'add';
    alpine.showQuantity = true;
    $('#qty_stock_id').val(stockId);
    $('#qty_available_display').text(alpine.availableQuantity);
    $('#qty_amount').val('').removeAttr('max');
    $('#pull_reason').val('');
    $('#save_qty')[0].reset();
    $('#qty_stock_id').val(stockId);
}



    $(document).on('click', '.delete-stock-btn', function() {
        let id = $(this).closest('tr').data('id');
        deleteStock(id);
    });

    // Delete handler for mobile cards
    $(document).on('click', '.delete-stock-btn-mobile', function() {
        let id = $(this).data('stock-id');
        deleteStock(id);
    });

    // Common delete function
    function deleteStock(id) {
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
                    url: '<?= url("delete_stock") ?>/' + id,
                    method: 'DELETE',
                    data: {
                        _token: '<?= csrf_token() ?>'
                    },

                    success: function(data) {
                        loadStock(currentStockPage); // reload table with current page
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
    }


$(document).ready(function() {
    $('#save_qty').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const actionType = $('input[name="qtyType"]:checked').val() || 'add';
        const qty = parseFloat($('#qty_amount').val()) || 0;
        const stockId = $('#qty_stock_id').val();
        const pullReason = ($('#pull_reason').val() || '').trim();

        if (!stockId) {
            show_notification('error', trans.error_occurred);
            return false;
        }
        if (qty <= 0) {
            show_notification('error', '{{ trans("messages.please_enter_quantity", [], session("locale")) ?: "Please enter quantity" }}');
            return false;
        }
        if (actionType === 'pull' && !pullReason) {
            show_notification('error', '{{ trans("messages.please_enter_pull_notes", [], session("locale")) ?: "Pull reason is required" }}');
            return false;
        }

        const mainEl = document.querySelector('main[x-data]');
        const availableQty = mainEl && mainEl._x_dataStack ? (mainEl._x_dataStack[0].availableQuantity || 0) : 0;
        if (actionType === 'pull' && qty > availableQty) {
            show_notification('error', '{{ trans("messages.pull_quantity_exceeds_available", [], session("locale")) ?: "Pull quantity cannot exceed available" }}. {{ trans("messages.available", [], session("locale")) }}: ' + availableQty);
            return false;
        }

        submitBtn.prop('disabled', true).html('<span class="material-symbols-outlined align-middle me-2 text-sm animate-spin">hourglass_empty</span>' + trans.saving);

        const url = actionType === 'pull' ? '{{ url("stock_pull_quantity") }}' : '{{ url("stock_push_quantity") }}';
        const data = {
            _token: '{{ csrf_token() }}',
            stock_id: stockId,
            quantity: qty
        };
        if (actionType === 'pull') data.pull_reason = pullReason;

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            success: function(res) {
                if (res.status === 'success') {
                    show_notification('success', res.message);
                    mainEl._x_dataStack[0].showQuantity = false;
                    loadStock(currentStockPage);
                }
                submitBtn.prop('disabled', false).html('<span class="material-symbols-outlined align-middle me-2 text-sm">check</span>{{ trans("messages.save_operation", [], session("locale")) }}');
            },
            error: function(xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : trans.error_saving;
                show_notification('error', msg);
                submitBtn.prop('disabled', false).html('<span class="material-symbols-outlined align-middle me-2 text-sm">check</span>{{ trans("messages.save_operation", [], session("locale")) }}');
            }
        });
        return false;
    });
});
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Full Stock Details Modal Function
function openFullStockDetails(stockId) {
    // Get the Alpine.js component from the main element
    const mainElement = document.querySelector('main[x-data]');
    if (!mainElement) {
        console.error('Main element with Alpine.js not found');
        return;
    }
    
    // Try multiple methods to access Alpine.js data
    let alpineData = null;
    try {
        // Method 1: Direct access via _x_dataStack
        if (mainElement._x_dataStack && mainElement._x_dataStack[0]) {
            alpineData = mainElement._x_dataStack[0];
        }
        // Method 2: Use Alpine.$data if available
        else if (window.Alpine && typeof window.Alpine.$data === 'function') {
            alpineData = window.Alpine.$data(mainElement);
        }
        // Method 3: Try accessing via Alpine reactive
        else if (window.Alpine && mainElement._x_dataStack) {
            alpineData = mainElement._x_dataStack[0];
        }
    } catch (e) {
        console.error('Error accessing Alpine.js data:', e);
    }
    
    if (!alpineData) {
        console.error('Could not access Alpine.js data');
        return;
    }

    // Clear previous data
    $('#full_modal_stock_code').text('...');
    $('#full_stock_code').text('-');
    $('#full_design_name').text('-');
    $('#full_barcode').text('-');
    $('#full_description').text('-');
    $('#full_cost_price').text('-');
    $('#full_sales_price').text('-');
    $('#full_tailor_charges').text('-');
    $('#full_tailor_names').text('-');
    $('#full_total_quantity').text('0');
    $('#full_stock_images_container').html('');
    $('#full_size_color_container').html('');

    // Show modal and loader using Alpine.js
    if (alpineData) {
        alpineData.showFullDetails = true;
        alpineData.fullDetailsLoading = true;
    }

    // Fetch full stock details
    $.ajax({
        url: '{{ url("get_full_stock_details") }}',
        method: 'GET',
        data: { id: stockId },
        success: function(response) {
            if (response) {
                // Populate basic info
                $('#full_stock_code').text(response.stock_code || '-');
                $('#full_modal_stock_code').text(response.stock_code || '...');
                $('#full_design_name').text(response.design_name || '-');
                $('#full_barcode').text(response.barcode || '-');
                $('#full_description').text(response.stock_notes || '-');

                // Pricing info
                const costPrice = response.cost_price ? parseFloat(response.cost_price).toFixed(2) : '0.00';
                const salesPrice = response.sales_price ? parseFloat(response.sales_price).toFixed(2) : '0.00';
                const tailorCharges = response.tailor_charges ? parseFloat(response.tailor_charges).toFixed(2) : '0.00';
                
                $('#full_cost_price').text(costPrice);
                $('#full_sales_price').text(salesPrice);
                $('#full_tailor_charges').text(tailorCharges);

                // Tailor names
                const tailorNames = response.tailor_names && response.tailor_names.length > 0 
                    ? response.tailor_names.join(', ') 
                    : '-';
                $('#full_tailor_names').text(tailorNames);

                // Total quantity
                $('#full_total_quantity').text(response.total_quantity || 0);

                // Populate images
                if (response.images && response.images.length > 0) {
                    let imagesHtml = '';
                    response.images.forEach(function(imagePath, index) {
                        imagesHtml += `
                            <div class="rounded-xl overflow-hidden shadow-sm">
                                <div class="relative" style="height: 200px; overflow: hidden;">
                                    <img src="${imagePath}" 
                                         class="w-full h-full object-cover" 
                                         alt="${trans.stock_image} ${index + 1}"
                                         onerror="this.src='/images/placeholder.png'">
                                </div>
                            </div>
                        `;
                    });
                    $('#full_stock_images_container').html(imagesHtml);
                } else {
                    $('#full_stock_images_container').html(`
                        <div class="col-span-full">
                            <p class="text-gray-500 text-center">{{ trans('messages.no_images_available', [], session('locale')) }}</p>
                        </div>
                    `);
                }

                // Populate color-size combinations
                if (response.color_size_details && response.color_size_details.length > 0) {
                    let colorSizeHtml = '';
                    response.color_size_details.forEach(function(item) {
                        colorSizeHtml += `
                            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h6 class="font-bold text-gray-900 mb-1">${item.size_name}</h6>
                                        <div class="flex items-center gap-2">
                                            <div class="rounded-full border-2 border-gray-300" 
                                                 style="width: 24px; height: 24px; background-color: ${item.color_code};"></div>
                                            <span class="text-gray-600 text-sm">${item.color_name}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right mt-3">
                                    <span class="bg-[var(--primary-color)] text-white rounded-full px-4 py-2 text-sm font-semibold">${item.quantity} ${trans.pieces}</span>
                                </div>
                            </div>
                        `;
                    });
                    $('#full_size_color_container').html(colorSizeHtml);
                } else {
                    $('#full_size_color_container').html(`
                        <div class="col-span-full">
                            <p class="text-gray-500 text-center">{{ trans('messages.no_data_available', [], session('locale')) }}</p>
                        </div>
                    `);
                }
            }

            // Hide loader
            if (alpineData) {
                alpineData.fullDetailsLoading = false;
            }
        },
        error: function(err) {
            console.error('Error:', err);
            alert('{{ trans("messages.error_loading_details", [], session("locale")) }}');
            if (alpineData) {
                alpineData.fullDetailsLoading = false;
            }
        }
    });
}
</script>