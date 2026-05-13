@extends('layouts.admin')

@section('css')
@parent

<style>
    #sortable-questions li {
        font-size: 13px;
        padding: 10px 12px;
        border: 1px solid var(--sg-border-light);
        border-radius: var(--sg-radius-sm);
        margin-bottom: 6px;
        background: #fff;
        transition: background .15s, box-shadow .15s;
    }
    #sortable-questions li:hover {
        background: var(--sg-bg-soft);
        box-shadow: var(--sg-shadow-card);
    }
    .index-badge {
        min-width: 28px;
        text-align: center;
        font-size: .7rem;
        padding: 4px 8px;
        background: var(--sg-gradient-dark) !important;
        color: #fff;
        border-radius: var(--sg-radius-pill);
        font-weight: 700;
    }
    .quiz-q-progress {
        height: 10px;
        border-radius: 5px;
        background: var(--sg-border);
        overflow: hidden;
    }
    .quiz-q-progress .bar {
        height: 100%;
        border-radius: 5px;
        transition: width .35s ease, background .15s;
        background: var(--sg-gradient-success);
    }
    .quiz-q-progress .bar.warn   { background: linear-gradient(90deg, #ffc107, #fd7e14); }
    .quiz-q-progress .bar.danger { background: linear-gradient(90deg, #dc3545, #e83e8c); }
</style>
@stop

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Quiz / {{ $quiz->title }}</p>
            <h1 class="sg-header-title"><i class="fas fa-list-check mr-2"></i> Gestione domande</h1>
        </div>
        <a href="{{ route('admin.quizzes.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
            <i class="fas fa-arrow-left"></i> Indietro
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="sg-card">
                <div class="sg-card-body">
                    {{-- PROGRESS --}}
                    <div class="sg-mb-3">
                        <div class="sg-flex-between sg-mb-1">
                            <span class="sg-label sg-mb-0">Domande inserite</span>
                            <strong>
                                <span id="current-count">{{ $currentCount }}</span>
                                / <span id="max-count">{{ $max }}</span>
                            </strong>
                        </div>
                        <div class="quiz-q-progress">
                            <div id="quiz-progress-bar" class="bar" style="width:0%"></div>
                        </div>
                    </div>

                    {{-- FILTRI --}}
                    <div class="row sg-mb-2">
                        <div class="col-md-6">
                            <select id="filter-category" class="sg-form-control">
                                <option value="">— Tutte le categorie —</option>
                                @foreach(\App\Models\Category::all() as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- BULK ACTIONS --}}
                    <div class="sg-mb-3 sg-d-flex sg-gap-2" style="flex-wrap:wrap;">
                        <button id="bulk-add" class="sg-btn sg-btn-success sg-btn-sm">
                            <i class="fas fa-plus"></i> Aggiungi selezionate
                        </button>
                        <button id="bulk-remove" class="sg-btn sg-btn-danger sg-btn-sm">
                            <i class="fas fa-minus"></i> Rimuovi selezionate
                        </button>
                        <button id="select-all-filtered" class="sg-btn sg-btn-light sg-btn-sm">
                            <i class="fas fa-check-double"></i> Seleziona tutti (filtrati)
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table id="questions-table" class="sg-table">
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
        </div>

        <div class="col-md-4">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">Ordine del quiz</h2>
                    <button id="shuffle-questions" class="sg-btn sg-btn-light sg-btn-sm">
                        <i class="fas fa-shuffle"></i> Shuffle
                    </button>
                </div>
                <div style="max-height:800px;overflow-y:auto;padding:14px;">
                    <ul id="sortable-questions" class="list-unstyled" style="margin:0;">
                        @foreach($quiz->questions as $i => $q)
                            <li data-id="{{ $q->id }}" data-text="{{ $q->question }}"
                                class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center" style="gap:8px;flex:1;min-width:0;">
                                    <span class="index-badge">{{ $i + 1 }}</span>
                                    <span class="sg-text-muted" style="font-size:.82rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        {{ Str::limit($q->question, 60) }}
                                    </span>
                                </div>
                                <button class="sg-btn-icon delete btn-remove-from-list" title="Rimuovi">
                                    <i class="fas fa-times"></i>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
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

        let bar = $('#quiz-progress-bar');
        bar.css('width', percent + '%');

        $('#current-count').text(current);

        bar.removeClass('warn danger');

        if (percent >= 90) {
            bar.addClass('danger');
        } else if (percent >= 60) {
            bar.addClass('warn');
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
            <li data-id="${id}" data-text="${text}"
                class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center" style="gap:8px;flex:1;min-width:0;">
                    <span class="index-badge"></span>
                    <span class="sg-text-muted" style="font-size:.82rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${truncate(text, 60)}</span>
                </div>
                <button class="sg-btn-icon delete btn-remove-from-list" title="Rimuovi">
                    <i class="fas fa-times"></i>
                </button>
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
