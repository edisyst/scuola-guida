@extends('layouts.admin')

@section('css')
@parent
@stop

@section('content_header')@endsection

@section('content')
<div>

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">
                Quiz
                @if($licenseType)
                    — <span class="sg-badge sg-badge-info" style="font-size:.75rem;vertical-align:middle;">
                        <i class="fas fa-id-card mr-1"></i>{{ $licenseType->name }} ({{ $licenseType->code }})
                    </span>
                @endif
            </p>
            <h1 class="sg-header-title"><i class="fas fa-tasks mr-2"></i> {{ $quiz->title }}</h1>
        </div>
        <a href="{{ route('admin.quizzes.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
            <i class="fas fa-arrow-left"></i> Indietro
        </a>
    </div>

    @if(auth()->user()->canEditQuiz())
    <div class="sg-card sg-mb-3">
        <div class="sg-card-body">
            <div class="row align-items-end g-2">
                <div class="col-12 col-md-5">
                    <label class="sg-label">Titolo</label>
                    <input type="text" id="param-title" class="sg-form-control" value="{{ $quiz->title }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="sg-label">Max domande</label>
                    <input type="number" id="param-max" class="sg-form-control" value="{{ $quiz->max_questions }}" min="1" max="100">
                </div>
                <div class="col-6 col-md-3 d-flex align-items-center pt-3 pt-md-4">
                    <div>
                        <span class="sg-label sg-mb-0 d-block">Stato</span>
                        @if($quiz->isConfirmed())
                            <span class="sg-badge sg-badge-info"><i class="fas fa-lock"></i> Confermato</span>
                        @elseif($quiz->isPublished())
                            <span class="sg-badge sg-badge-success">Pubblicato</span>
                        @else
                            <span class="sg-badge">Bozza</span>
                        @endif
                    </div>
                </div>
                <div class="col-12 col-md-2 pt-2 pt-md-4">
                    <button id="btn-update-params" class="sg-btn sg-btn-primary sg-btn-sm w-100">
                        <i class="fas fa-save"></i> Aggiorna parametri
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-12 col-md-8 order-md-first">
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
                    <div class="row sg-mb-2 align-items-center g-2">
                        <div class="col-12 col-md-5">
                            <select id="filter-category" class="sg-form-control">
                                <option value="">— Tutte le categorie{{ $licenseType ? ' (' . $categories->count() . ')' : '' }} —</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button id="filter-in-quiz" class="sg-btn sg-btn-light sg-btn-sm" title="Mostra solo le domande presenti nel quiz">
                                <i class="fas fa-list-check mr-1"></i>Solo nel quiz
                            </button>
                        </div>
                        @if($licenseType)
                        <div class="col-auto">
                            <small class="text-muted">
                                <i class="fas fa-filter mr-1"></i>Filtrate per <strong>{{ $licenseType->code }}</strong>
                            </small>
                        </div>
                        @endif
                    </div>

                    {{-- BULK ACTIONS --}}
                    <div class="sg-mb-3 sg-d-flex sg-gap-2 flex-wrap">
                        <button id="bulk-add" class="sg-btn sg-btn-success sg-btn-sm">
                            <i class="fas fa-plus"></i> Aggiungi selezionate
                        </button>
                        <button id="bulk-remove" class="sg-btn sg-btn-danger sg-btn-sm">
                            <i class="fas fa-minus"></i> Rimuovi selezionate
                        </button>
                        @if(auth()->user()->canBulkQuiz())
                        <button id="btn-add-random" class="sg-btn sg-btn-warning sg-btn-sm">
                            <i class="fas fa-random"></i> Aggiungi random
                        </button>
                        @endif
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

        <div class="col-12 col-md-4 order-md-last mt-4 mt-md-0">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h2 class="sg-card-header-title">Ordine del quiz</h2>
                    <button id="shuffle-questions" class="sg-btn sg-btn-light sg-btn-sm">
                        <i class="fas fa-shuffle"></i> Shuffle
                    </button>
                </div>
                <div class="sg-sortable-scroll">
                    <ul id="sortable-questions" class="list-unstyled m-0">
                        @foreach($quiz->questions as $i => $q)
                            <li data-id="{{ $q->id }}" data-text="{{ $q->question }}"
                                class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center" style="gap:8px;flex:1;min-width:0;">
                                    <span class="index-badge">{{ $i + 1 }}</span>
                                    <span class="sg-text-muted sg-q-text">
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
    let filterInQuiz = false;

    $(document).ready(function() {
        updateProgress({{ $currentCount }}, {{ $max }});

        table = $('#questions-table').DataTable({
            pageLength: 25,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.quizzes.questions.data', $quiz) }}",
                data: function(d) {
                    d.category_id = $('#filter-category').val();
                    d.only_in_quiz = filterInQuiz ? 1 : 0;
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

        // filtro categoria
        $('#filter-category').change(function() {
            table.ajax.reload();
        });

        // toggle "Solo nel quiz"
        $('#filter-in-quiz').on('click', function() {
            filterInQuiz = !filterInQuiz;
            $(this).toggleClass('sg-btn-light', !filterInQuiz)
                   .toggleClass('sg-btn-info',  filterInQuiz);
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

    // 🔥 BULK ADD
    $('#bulk-add').click(function() {
        if (selectionMode === 'manual' && selectedIds.size === 0) {
            toastr.warning('Nessuna selezione');
            return;
        }

        $.post("{{ route('admin.quizzes.bulkAdd', $quiz) }}", {
            _token: "{{ csrf_token() }}",
            ids: Array.from(selectedIds),
            mode: selectionMode === 'all' ? 'all' : 'selection',
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
            mode: selectionMode === 'all' ? 'all' : 'selection',
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
                    <span class="sg-text-muted sg-q-text">${truncate(text, 60)}</span>
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

            $('#sortable-questions').html('');

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

    // AGGIORNA PARAMETRI QUIZ
    $('#btn-update-params').click(function() {
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.post("{{ route('admin.quizzes.updateParams', $quiz) }}", {
            _token: "{{ csrf_token() }}",
            title: $('#param-title').val(),
            max_questions: $('#param-max').val(),
        }, function(res) {
            $('#max-count').text(res.max_questions);
            updateProgress(parseInt($('#current-count').text()), res.max_questions);
            toastr.success('Parametri aggiornati');
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.error ?? 'Errore aggiornamento parametri');
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });

    // AGGIUNGI DOMANDE RANDOM FINO AL MASSIMO
    $('#btn-add-random').click(function() {
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.post("{{ route('admin.quizzes.fillRandom', $quiz) }}", {
            _token: "{{ csrf_token() }}"
        }, function(res) {
            (res.questions ?? []).forEach(q => addToQuizList(q.id, q.question));
            updateProgress(res.current, parseInt($('#max-count').text()));
            toastr.success('Aggiunte ' + res.added + ' domande random');
            table.ajax.reload(null, false);
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.error ?? 'Errore');
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });

</script>

@stop
