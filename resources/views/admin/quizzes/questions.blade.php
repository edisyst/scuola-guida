@extends('layouts.admin')

@section('header', 'Gestione Domande Quiz')

@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between">

            <h5>{{ $quiz->title }}</h5>

            <a href="{{ route('admin.quizzes.index') }}"
               class="btn btn-secondary btn-sm">
                Indietro
            </a>

        </div>

        <div class="card-body">

            {{-- FILTRI --}}
            <div class="row mb-3">
                <div class="col-md-4">
                    <select id="filter-category" class="form-control">
                        <option value="">-- Tutte le categorie --</option>
                            @foreach(\App\Models\Category::all() as $cat)
                                <option value="{{ $cat->id }}">
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                    </select>
                </div>
            </div>

            {{-- BULK ACTIONS --}}
            <div class="mb-3 d-flex gap-2">
                <button id="bulk-add" class="btn btn-success btn-sm">
                    Aggiungi selezionate
                </button>

                <button id="bulk-remove" class="btn btn-danger btn-sm">
                    Rimuovi selezionate
                </button>

                <button id="select-all-filtered" class="btn btn-secondary btn-sm">
                    Seleziona TUTTI (filtrati)
                </button>
            </div>

            {{-- TABELLA --}}
            <div class="mb-3">
                <strong>
                    Domande:
                    {{ $quiz->questions()->count() }}
                    / {{ $quiz->max_questions }}
                </strong>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <strong>
                        <span id="current-count">{{ $currentCount }}</span>
                        /
                        <span id="max-count">{{ $max }}</span>
                        domande
                    </strong>
                    <span id="percentage"></span>
                </div>

                <div class="progress">
                    <div id="quiz-progress-bar"
                         class="progress-bar bg-success"
                         role="progressbar"
                         style="width: 0%">
                    </div>
                </div>
            </div>

            <table id="questions-table" class="table table-bordered">

                <thead>
                <tr>
                    <th>ID</th>
                    <th>Domanda</th>
                    <th>Categoria</th>
                    <th>Stato</th>
                    <th>Azione</th>
                    <th><input type="checkbox" id="select-all"></th>
                </tr>
                </thead>

            </table>

        </div>

    </div>

@endsection

@section('js')
    @parent

    <script>
        let table = null;

        $(document).ready(function () {
            updateProgress({{ $currentCount }}, {{ $max }});

            table = $('#questions-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('admin.quizzes.questions.data', $quiz) }}",
                    data: function (d) {
                        d.category_id = $('#filter-category').val();
                    }
                },
                columns: [
                    {
                        data: 'id',
                        orderable: false,
                        render: function (data) {
                            return `<input type="checkbox" class="row-checkbox" value="${data}">`;
                        }
                    },
                    { data: 'id' },
                    { data: 'question' },
                    { data: 'category' },
                    { data: 'status', orderable: false },
                    { data: 'action', orderable: false }
                ]
            });

            // selezione multipla
            if (table) {
                table.on('draw', function () {

                    $('.row-checkbox').each(function () {

                        const id = $(this).val();

                        if (selectionMode === 'all') {
                            $(this).prop('checked', true);
                        } else {
                            $(this).prop('checked', selectedIds.has(id));
                        }

                    });

                });
            }

            // filtro
            $('#filter-category').change(function () {
                table.ajax.reload();
            });
        });

        // ADD
        $(document).on('click', '.btn-add', function () {
            let id = $(this).data('id');

            console.log({
                mode: selectionMode,
                selected: Array.from(selectedIds)
            });

            $.post("{{ route('admin.quizzes.questions.add', $quiz) }}", {
                _token: "{{ csrf_token() }}",
                question_id: id
            }, function () {
                let current = parseInt($('#current-count').text()) + 1;
                let max = parseInt($('#max-count').text());
                updateProgress(current, max);
                toastr.success('Aggiunta');
                table.ajax.reload();
            }).fail(function (xhr) {
                toastr.error(xhr.responseJSON.error);
            });
        });

        // REMOVE
        $(document).on('click', '.btn-remove', function () {
            let id = $(this).data('id');

            console.log({
                mode: selectionMode,
                selected: Array.from(selectedIds)
            });

            $.post("{{ route('admin.quizzes.questions.remove', $quiz) }}", {
                _token: "{{ csrf_token() }}",
                question_id: id
            }, function () {
                let current = parseInt($('#current-count').text()) - 1;
                let max = parseInt($('#max-count').text());
                updateProgress(current, max);
                toastr.warning('Rimossa');
                table.ajax.reload();
            });
        });

        // BULK SELECT
        let selectedIds = new Set();
        // let selectAllFiltered = false;
        let selectionMode = 'manual'; // 'manual' | 'all'

        $(document).on('change', '.row-checkbox', function () {

            const id = $(this).val();

            // 👉 appena tocchi manualmente → torni in manual mode
            if (selectionMode === 'all') {
                selectionMode = 'manual';
                selectedIds.clear();

                // ricostruisco selezione da checkbox attuali
                $('.row-checkbox:checked').each(function () {
                    selectedIds.add($(this).val());
                });

                return;
            }

            if (this.checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }

        });

        $('#select-all').on('change', function () {

            selectionMode = 'manual';

            $('.row-checkbox').each(function () {
                $(this).prop('checked', $('#select-all').is(':checked'));

                if ($(this).is(':checked')) {
                    selectedIds.add($(this).val());
                } else {
                    selectedIds.delete($(this).val());
                }
            });

        });

        $('#select-all-filtered').on('click', function () {

            selectionMode = 'all';
            selectedIds.clear();

            $('.row-checkbox').prop('checked', true);

            toastr.info('Selezionate tutte le righe filtrate');

        });

        // 🔥 BULK ADD
        $('#bulk-add').click(function () {
            if (selectionMode === 'manual' && selectedIds.size === 0) {
                toastr.warning('Nessuna selezione');
                return;
            }

            console.log({
                mode: selectionMode,
                selected: Array.from(selectedIds)
            });

            $.post("{{ route('admin.quizzes.bulkAdd', $quiz) }}", {
                _token: "{{ csrf_token() }}",
                ids: Array.from(selectedIds),
                mode: selectionMode,
                category_id: $('#filter-category').val()
            }, function (res) {
                let current = parseInt($('#current-count').text()) + res.added;
                let max = parseInt($('#max-count').text());

                updateProgress(current, max);

                toastr.success('Domande aggiunte');

                selectedIds.clear();
                selectionMode = 'manual';

                table.ajax.reload(null, false);
            }).fail(function (xhr) {

                toastr.error(xhr.responseJSON.error);

            });
        });

        // 🔥 BULK REMOVE
        $('#bulk-remove').click(function () {
            if (selectionMode === 'manual' && selectedIds.size === 0) {
                toastr.warning('Nessuna selezione');
                return;
            }

            console.log({
                mode: selectionMode,
                selected: Array.from(selectedIds)
            });

            $.post("{{ route('admin.quizzes.bulkRemove', $quiz) }}", {
                _token: "{{ csrf_token() }}",
                ids: Array.from(selectedIds),
                mode: selectionMode,
                category_id: $('#filter-category').val()
            }, function () {
                table.ajax.reload(function () {

                    // fallback: ricalcolo da server (più preciso)
                    location.reload();

                });

                toastr.warning('Domande rimosse');

                selectedIds.clear();
                selectionMode = 'manual';

                table.ajax.reload(null, false);
            });
        });

        // DISABILITA BOTTONI (UX PRO)
        function checkLimit(current, max) {
            if (current >= max) {
                $('.btn-add, #bulk-add').prop('disabled', true);
            }
        }

        function updateProgress(current, max) {

            const percent = Math.round((current / max) * 100);

            $('#quiz-progress-bar')
                .css('width', percent + '%')
                .text(percent + '%');

            $('#current-count').text(current);
            $('#percentage').text(percent + '%');

            // 🔥 colori dinamici (UX TOP)
            let bar = $('#quiz-progress-bar');

            bar.removeClass('bg-success bg-warning bg-danger');

            if (percent < 60) {
                bar.addClass('bg-success');
            } else if (percent < 90) {
                bar.addClass('bg-warning');
            } else {
                bar.addClass('bg-danger');
            }

            if (current >= max) {
                $('.btn-add, #bulk-add').prop('disabled', true);
            } else {
                $('.btn-add, #bulk-add').prop('disabled', false);
            }
        }

    </script>

@stop
