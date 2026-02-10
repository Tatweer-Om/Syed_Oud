<script>
$(document).ready(function() {
    let currentPage = 1;

    function renderUnits(data) {
        let rows = '';
        let index = (data.current_page - 1) * data.per_page;
        $.each(data.data, function(i, unit) {
            index++;
            rows += `
                <tr class="border-t hover:bg-pink-50/60 transition" data-id="${unit.id}">
                    <td class="px-3 py-3 text-center">${index}</td>
                    <td class="px-3 py-3 text-center font-semibold">${unit.unit_name}</td>
                    <td class="px-3 py-3 text-center">
                        <div class="flex justify-center gap-3">
                            <button type="button" class="edit-unit-btn text-blue-600 hover:text-blue-800" data-id="${unit.id}" data-name="${unit.unit_name}">
                                <span class="material-symbols-outlined text-lg">edit</span>
                            </button>
                            <button type="button" class="delete-unit-btn text-red-600 hover:text-red-800" data-id="${unit.id}">
                                <span class="material-symbols-outlined text-lg">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>`;
        });
        $('#units_tbody').html(rows);
    }

    function renderPagination(res) {
        let pagination = '';
        pagination += `<li class="w-10 h-10 flex items-center justify-center rounded-full ${!res.prev_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300'}"><a href="?page=${res.current_page - 1}">&laquo;</a></li>`;
        for (let i = 1; i <= res.last_page; i++) {
            pagination += `<li class="w-10 h-10 flex items-center justify-center rounded-full"><a href="?page=${i}" class="flex items-center justify-center w-10 h-10 ${res.current_page == i ? 'bg-[var(--primary-color)] text-white' : 'bg-gray-200 hover:bg-gray-300'}">${i}</a></li>`;
        }
        pagination += `<li class="w-10 h-10 flex items-center justify-center rounded-full ${!res.next_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300'}"><a href="?page=${res.current_page + 1}">&raquo;</a></li>`;
        $('#units_pagination').html(pagination);
    }

    function loadUnits(page) {
        currentPage = page || 1;
        $.get("{{ url('units/list') }}", { page: currentPage }, function(res) {
            renderUnits(res);
            renderPagination(res);
        });
    }

    $(document).on('click', '#units_pagination a', function(e) {
        e.preventDefault();
        let href = $(this).attr('href');
        if (!href || href === '#') return;
        let page = new URL(href, window.location.origin).searchParams.get('page') || 1;
        loadUnits(parseInt(page));
    });

    $('#unit_search').on('keyup', function() {
        let val = $(this).val().toLowerCase();
        $('#units_tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
        });
    });

    $('#openAddUnitModal').on('click', function() {
        $('#addUnitModalTitle').text('{{ trans("messages.add_unit", [], session("locale")) }}');
        $('#unit_id').val('');
        $('#unit_name').val('');
        $('#addUnitModal').removeClass('hidden');
        $('#unit_name').focus();
    });

    $('#closeAddUnitModal, #cancelAddUnitBtn').on('click', function() {
        $('#addUnitModal').addClass('hidden');
        $('#addUnitForm')[0].reset();
    });

    $(document).on('click', '#addUnitModal', function(e) {
        if ($(e.target).attr('id') === 'addUnitModal') {
            $('#addUnitModal').addClass('hidden');
            $('#addUnitForm')[0].reset();
        }
    });

    $(document).on('click', '.edit-unit-btn', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        $('#addUnitModalTitle').text('{{ trans("messages.edit_unit", [], session("locale")) }}');
        $('#unit_id').val(id);
        $('#unit_name').val(name);
        $('#addUnitModal').removeClass('hidden');
        $('#unit_name').focus();
    });

    $('#addUnitForm').on('submit', function(e) {
        e.preventDefault();
        var unitId = $('#unit_id').val();
        var unitName = $('#unit_name').val().trim();
        if (!unitName) {
            show_notification('error', '{{ trans("messages.enter_unit_name", [], session("locale")) }}');
            return;
        }
        var $btn = $(this).find('button[type="submit"]');
        var origText = $btn.html();
        $btn.prop('disabled', true).html('...');
        var url = unitId ? "{{ url('units') }}/" + unitId : "{{ url('units') }}";
        var method = unitId ? 'PUT' : 'POST';
        var data = { _token: '{{ csrf_token() }}', unit_name: unitName };
        if (unitId) data._method = 'PUT';
        $.ajax({
            url: url,
            type: method,
            data: data,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) {
                show_notification('success', res.message);
                $('#addUnitModal').addClass('hidden');
                $('#addUnitForm')[0].reset();
                loadUnits(currentPage);
                $btn.prop('disabled', false).html(origText);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(origText);
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ __("Something went wrong") }}';
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                }
                show_notification('error', msg);
            }
        });
    });

    $(document).on('click', '.delete-unit-btn', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: '{{ trans("messages.confirm_delete_title", [], session("locale")) }}',
            text: '{{ trans("messages.confirm_delete_text", [], session("locale")) }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '{{ trans("messages.yes_delete", [], session("locale")) }}',
            cancelButtonText: '{{ trans("messages.cancel", [], session("locale")) }}'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('units') }}/" + id,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function() {
                        show_notification('success', '{{ trans("messages.deleted_success", [], session("locale")) }}');
                        loadUnits(currentPage);
                    },
                    error: function() {
                        show_notification('error', '{{ trans("messages.delete_error", [], session("locale")) }}');
                    }
                });
            }
        });
    });

    loadUnits();
});
</script>
