@extends('layouts.admin')

@section('css')
@parent

<style>
    #sortable-questions li {
        font-size: 13px;
        padding: 8px 10px;
    }
    .index-badge {
        min-width: 28px;
        text-align: center;
    }
</style>
@stop

@section('header', 'Gestione Domande Quiz')

@section('content')

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5>{{ $quiz->title }}</h5>

                <a href="{{ route('admin.quizzes.index') }}" class="btn btn-secondary btn-sm">Indietro</a>
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
                    <button id="bulk-add" class="btn btn-success btn-sm">Aggiungi selezionate</button>

                    <button id="bulk-remove" class="btn btn-danger btn-sm">Rimuovi selezionate</button>

                    <button id="select-all-filtered" class="btn btn-secondary btn-sm">Seleziona TUTTI (filtrati)</button>
                </div>

                {{-- TABELLA --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <strong>
                            <span id="current-count">{{ $currentCount }}</span>
                            /
                            <span id="max-count">{{ $max }}</span>
                            domande
                        </strong>
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
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5>Ordine del Quiz</h5>

                <button id="shuffle-questions" class="btn btn-sm btn-warning">🔀 Shuffle</button>
            </div>

            <div class="card-body" style="max-height: 800px; overflow-y: auto;">

                <ul id="sortable-questions" class="list-group">

                    @foreach($quiz->questions as $i => $q)
                        <li class="list-group-item d-flex align-items-center justify-content-between"
                            data-id="{{ $q->id }}"
                            data-text="{{ $q->question }}">

                            <div class="d-flex align-items-center gap-2">
                                <span class="badge badge-secondary index-badge">{{ $i + 1 }}</span>

                                <span class="small text-muted">{{ Str::limit($q->question, 60) }}</span>

                                <span class="badge badge-info ml-2">{{ $q->category->name }}</span>
                            </div>

                            <button class="btn btn-sm btn-outline-danger btn-remove-from-list">✕</button>
                        </li>
                    @endforeach

                </ul>

            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
@parent

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
    const sortable = new Sortable(document.getElementById('sortable-questions'), {
        animation: 150,

        onEnd: function() {

            updateIndexes(); // 🔥 aggiungi questo

            let ids = [];

            $('#sortable-questions li').each(function() {
                ids.push($(this).data('id'));
            });

            $.post("{{ route('admin.quizzes.reorder', $quiz) }}", {
                _token: "{{ csrf_token() }}",
                ids: ids
            }, function() {
                toastr.success('Ordine aggiornato');
            });

        }
    });

    let table = null;

    $(document).ready(function() {
        updateProgress({{ $currentCount }}, {{ $max }});

        table = $('#questions-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.quizzes.questions.data', $quiz) }}",
                data: function(d) {
                    d.category_id = $('#filter-category').val();
                }
            },
            columns: [
                { data: 'id' },
                { data: 'question' },
                { data: 'category' },
                { data: 'status', orderable: false },
                {
                    data: 'action',
                    orderable: false,
                    render: function(data, type, row) {

                        const questionText = row.question;
                        // 🔥 USA is_in_quiz, non status!
                        if (row.is_in_quiz === 'added') {
                            return `<button class="btn btn-danger btn-sm btn-remove"
                                        data-id="${row.id}">
                                        Rimuovi
                                    </button>`;
                        } else {
                            return `<button class="btn btn-success btn-sm btn-add"
                                        data-id="${row.id}"
                                        data-text="${questionText}"
                                        data-category="${row.category}">
                                        Aggiungi
                                    </button>`;
                        }
                    }
                },
                {
                    data: 'id',
                    orderable: false,
                    render: function(data) {
                        return `<input type="checkbox" class="row-checkbox" value="${data}">`;
                    }
                },
                // 🔥 COLONNA NASCOSTA (non la metti nel HTML della tabella)
                {
                    data: 'is_in_quiz',
                    visible: false
                }
            ],
            // 🔥 QUI
                rowCallback: function (row, data) {

                    if (data.in_quiz) {
                        $(row).addClass('table-success'); // verde bootstrap
                    } else {
                        $(row).removeClass('table-success');
                    }

                }
        });

        // selezione multipla
        if (table) {
            table.on('draw', function() {

                $('.row-checkbox').each(function() {

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
        $('#filter-category').change(function() {
            table.ajax.reload();
        });
    });

    // ADD
    $(document).on('click', '.btn-add', function() {

        let id = $(this).data('id');
        let text = $(this).data('text');
        let category = $(this).data('category');

        $.post("{{ route('admin.quizzes.questions.add', $quiz) }}", {
            _token: "{{ csrf_token() }}",
            question_id: id
        }, function(res) {

            addToQuizList(id, text, category); // 🔥 sync UI

            let max = parseInt($('#max-count').text());
            updateProgress(res.current, max); // 🔥 USA backend

            toastr.success('Aggiunta');

            table.ajax.reload();

        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.error ?? 'Errore');
        });
    });

    // REMOVE
    $(document).on('click', '.btn-remove', function() {

        let id = $(this).data('id');

        $.post("{{ route('admin.quizzes.questions.remove', $quiz) }}", {
            _token: "{{ csrf_token() }}",
            question_id: id
        }, function(res) {

            removeFromQuizList(id); // 🔥 sync

            let max = parseInt($('#max-count').text());

            updateProgress(res.current, {{ $max }}); // 🔥 USA backend

            toastr.warning('Rimossa');

            table.ajax.reload();

        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.error ?? 'Errore');
        });
    });

    // BULK SELECT
    let selectedIds = new Set();
    // let selectAllFiltered = false;
    let selectionMode = 'manual'; // 'manual' | 'all'

    $(document).on('change', '.row-checkbox', function() {

        const id = $(this).val();

        // 👉 appena tocchi manualmente → torni in manual mode
        if (selectionMode === 'all') {
            selectionMode = 'manual';
            selectedIds.clear();

            // ricostruisco selezione da checkbox attuali
            $('.row-checkbox:checked').each(function() {
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

    $('#select-all').on('change', function() {

        selectionMode = 'manual';

        $('.row-checkbox').each(function() {
            $(this).prop('checked', $('#select-all').is(':checked'));

            if ($(this).is(':checked')) {
                selectedIds.add($(this).val());
            } else {
                selectedIds.delete($(this).val());
            }
        });

    });

    $('#select-all-filtered').on('click', function() {

        selectionMode = 'all';
        selectedIds.clear();

        $('.row-checkbox').prop('checked', true);

        toastr.info('Selezionate tutte le righe filtrate');

    });

    // 🔥 BULK ADD
    $('#bulk-add').click(function() {
        if (selectionMode === 'manual' && selectedIds.size === 0) {
            toastr.warning('Nessuna selezione');
            return;
        }

        $.post("{{ route('admin.quizzes.bulkAdd', $quiz) }}", {
            _token: "{{ csrf_token() }}",
            ids: Array.from(selectedIds),
            mode: selectionMode,
            category_id: $('#filter-category').val()

        }, function(res) {
            let current = res.current; // 🔥 arriva dal backend
            let max = parseInt($('#max-count').text());

            if (selectionMode === 'manual') {

                selectedIds.forEach(id => {
                    let row = $(`.btn-add[data-id="${id}"]`);
                    let text = row.data('text');
                    let category = row.data('category');

                    addToQuizList(id, text, category);
                });

            } else {
                // modalità ALL → reload lista da server (più sicuro)
                reloadQuizList();
            }

            updateProgress(current, max);

            toastr.success(`Domande aggiunte (${res.added ?? ''})`);

            selectedIds.clear();
            selectionMode = 'manual';

            table.ajax.reload(null, false);

        }).fail(function(xhr) {
            console.log(xhr.responseText);
            let msg = 'Errore generico';

            if (xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            toastr.error(msg);
        });
    });

    // 🔥 BULK REMOVE
    $('#bulk-remove').click(function() {
        if (selectionMode === 'manual' && selectedIds.size === 0) {
            toastr.warning('Nessuna selezione');
            return;
        }

        $.post("{{ route('admin.quizzes.bulkRemove', $quiz) }}", {
            _token: "{{ csrf_token() }}",
            ids: Array.from(selectedIds),
            mode: selectionMode,
            category_id: $('#filter-category').val()

        }, function(res) {
            // 🔥 aggiorno progress con valore reale backend
            let current = res.current;
            let max = parseInt($('#max-count').text());

            if (selectionMode === 'manual') {

                selectedIds.forEach(id => {
                    removeFromQuizList(id);
                });

            } else {
                reloadQuizList();
            }

            updateProgress(current, max);

            toastr.warning('Domande rimosse');

            selectedIds.clear();
            selectionMode = 'manual';

            // 🔥 NON resetta pagina
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

        current = parseInt(current) || 0;
        max = parseInt(max) || 1;

        const percent = Math.round((current / max) * 100);

        $('#quiz-progress-bar')
            .css('width', percent + '%')
            .text(percent + '%');

        $('#current-count').text(current);

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

    // ADD ALLA LISTA
    function addToQuizList(id, text, category) {
        // evita duplicati
        if ($('#sortable-questions li[data-id="' + id + '"]').length) return;
        // aggiungi elemento <li> nell'elenco domande
        $('#sortable-questions').append(`
            <li class="list-group-item d-flex align-items-center justify-content-between"
            data-id="${id}" data-text="${text}" >
                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-secondary index-badge"></span>
                    <span class="small text-muted">${truncate(text, 60)}</span>
                    <span class="badge badge-info ml-2">${category}</span>
                </div>
                <button class="btn btn-sm btn-outline-danger btn-remove-from-list">✕</button>
            </li>
        `);
        // aggiorna indici
        updateIndexes();
    }
    // REMOVE DALLA LISTA
    function removeFromQuizList(id) {
        $('#sortable-questions li[data-id="' + id + '"]').remove();
        updateIndexes();
    }

    // REMOVE DALLA LISTA DAL PULSANTINO SULLA LISTA
    $(document).on('click', '.btn-remove-from-list', function() {

        let li = $(this).closest('li');
        let id = li.data('id');

        $.post("{{ route('admin.quizzes.questions.remove', $quiz) }}", {
            _token: "{{ csrf_token() }}",
            question_id: id
        }, function(res) {

            li.remove();
            updateIndexes();
            updateProgress(res.current, {{ $max }});

            toastr.warning('Rimossa');

            table.ajax.reload(null, false);

        });
    });

    // RELOAD LISTA DA SERVER (fallback safe)
    function reloadQuizList() {
        $.get("{{ route('admin.quizzes.questions.list', $quiz) }}", function(data) {

            $('#quiz-list').html('');

            data.forEach(q => {
                addToQuizList(q.id, q.question);
            });

        });
    }

    // JS → shuffle lista (frontend)
    $('#shuffle-questions').click(function () {

        let list = $('#sortable-questions');
        let items = list.children('li').get();

        // 🔥 shuffle array (Fisher-Yates)
        for (let i = items.length - 1; i > 0; i--) {
            let j = Math.floor(Math.random() * (i + 1));
            [items[i], items[j]] = [items[j], items[i]];
        }

        // 🔥 reinserisco nel DOM
        $.each(items, function (_, li) {
            list.append(li);
        });

        updateIndexes(); // 🔥 qui
        saveOrder(); // 🔥 riuso tua funzione esistente
    });

    // HELPER saveOrder() (se non ce l’hai già)
    function saveOrder() {

        let ids = [];

        $('#sortable-questions li').each(function () {
            ids.push($(this).data('id'));
        });

        $.post("{{ route('admin.quizzes.reorder', $quiz) }}", {
            _token: "{{ csrf_token() }}",
            ids: ids
        }, function () {
            toastr.success('Ordine aggiornato');
        });
    }

    // HELPER: AGGIORNARE INDICE dopo drag/shuffle
    function updateIndexes() {
        $('#sortable-questions li').each(function (index) {
            $(this).find('.index-badge').text(index + 1);
        });
    }

    // HELPER truncate
    function truncate(text, max) {
        return text.length > max ? text.substring(0, max) + '...' : text;
    }

</script>

@stop
