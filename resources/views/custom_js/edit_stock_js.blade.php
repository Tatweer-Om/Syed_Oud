<script>
$(document).ready(function() {
    var units = [];
    $.get("{{ url('units/all') }}", function(data) {
        units = data || [];
    });

    var $unitSearch = $('#production_unit_search');
    var $unitId = $('#production_unit_id');
    var $unitDropdown = $('#production_unit_dropdown');
    var unitHighlightIndex = -1;

    function renderUnitDropdown(filter) {
        var list = units;
        if (filter && filter.length) {
            var f = filter.toLowerCase();
            list = units.filter(function(u) {
                return (u.unit_name || '').toLowerCase().indexOf(f) >= 0;
            });
        }
        var html = '';
        list.forEach(function(u) {
            html += '<div class="stock-unit-option" data-id="' + u.id + '" data-name="' + (u.unit_name || '').replace(/"/g, '&quot;') + '">' + (u.unit_name || '') + '</div>';
        });
        if (!html) html = '<div class="stock-unit-option text-gray-500">No unit found</div>';
        $unitDropdown.html(html).addClass('show');
        unitHighlightIndex = -1;
    }

    $unitSearch.on('focus', function() { renderUnitDropdown($(this).val()); });
    $unitSearch.on('input', function() {
        $unitId.val('');
        renderUnitDropdown($(this).val());
    });
    $unitSearch.on('keydown', function(e) {
        var $opts = $unitDropdown.find('.stock-unit-option[data-id]');
        if (e.keyCode === 40) {
            e.preventDefault();
            unitHighlightIndex = Math.min(unitHighlightIndex + 1, $opts.length - 1);
            $opts.removeClass('highlight').eq(unitHighlightIndex).addClass('highlight');
        } else if (e.keyCode === 38) {
            e.preventDefault();
            unitHighlightIndex = Math.max(unitHighlightIndex - 1, 0);
            $opts.removeClass('highlight').eq(unitHighlightIndex).addClass('highlight');
        } else if (e.keyCode === 13 && unitHighlightIndex >= 0 && $opts[unitHighlightIndex]) {
            e.preventDefault();
            $opts.eq(unitHighlightIndex).click();
        } else if (e.keyCode === 27) {
            $unitDropdown.removeClass('show');
        }
    });
    $(document).on('click', '.stock-unit-option[data-id]', function() {
        $unitId.val($(this).data('id'));
        $unitSearch.val($(this).data('name'));
        $unitDropdown.removeClass('show');
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.stock-unit-wrap').length) $unitDropdown.removeClass('show');
    });

    // Image preview
    $('#image').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#image_preview_img').attr('src', e.target.result);
                $('#image_preview').removeClass('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            $('#image_preview').addClass('hidden');
        }
    });

    $('#cost_price, #sales_price, #discount, #tax').on('input blur', function() {
        const value = parseFloat($(this).val());
        if (value < 0 || isNaN(value)) {
            $(this).val(0);
        }
    });
    // Notification limit: natural numbers only (no decimals)
    $('#notification_limit').on('input blur', function() {
        let val = $(this).val();
        const num = parseInt(val, 10);
        if (isNaN(num) || num < 0) {
            $(this).val('');
        } else {
            $(this).val(num);
        }
    });

    $('#update_stock').on('submit', function(e) {
        e.preventDefault();

        let $form = $(this);
        let $submitBtn = $form.find('button[type="submit"]');
        
        if ($submitBtn.prop('disabled')) {
            return false;
        }

        let stock_name = $('#stock_name').val().trim();
        let category_id = $('#category_id').val();
        let barcode = $('#barcode').val().trim();
        let cost_price = $('#cost_price').val();
        let sales_price = $('#sales_price').val();

        if (!stock_name) {
            show_notification('error', '<?= trans("messages.enter_stock_name", [], session("locale")) ?: "Please enter stock name" ?>');
            return;
        }
        if (!category_id) {
            show_notification('error', '<?= trans("messages.enter_category", [], session("locale")) ?: "Please select a category" ?>');
            return;
        }
        if (!barcode) {
            show_notification('error', '<?= trans("messages.enter_barcode", [], session("locale")) ?: "Please enter barcode" ?>');
            return;
        }
        if (!cost_price || parseFloat(cost_price) < 0) {
            show_notification('error', '<?= trans("messages.enter_cost_price", [], session("locale")) ?: "Please enter cost price" ?>');
            return;
        }
        if (!sales_price || parseFloat(sales_price) < 0) {
            show_notification('error', '<?= trans("messages.enter_sales_price", [], session("locale")) ?: "Please enter sales price" ?>');
            return;
        }

        let originalBtnText = $submitBtn.html();
        $submitBtn.prop('disabled', true).css('opacity', '0.6').html('<?= trans("messages.processing", [], session("locale")) ?>...');

        let formData = new FormData(this);

        $.ajax({
            url: "{{ route('update_stock') }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (response.status === 'success') {
                    show_notification('success', response.message || 'Stock updated successfully!');
                    setTimeout(function() {
                        window.location.href = response.redirect_url || '{{ url("view_stock") }}';
                    }, 1500);
                } else {
                    $submitBtn.prop('disabled', false).css('opacity', '1').html(originalBtnText);
                }
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).css('opacity', '1').html(originalBtnText);
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        show_notification('error', value[0]); 
                    });
                } else {
                    show_notification('error', 'Something went wrong!');
                }
            }
        });
    });
});
</script>
