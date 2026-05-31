<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AuditLogService
{
    private const TYPE_LABELS = [
        'App\\Models\\Question'         => 'Domanda',
        'App\\Models\\Quiz'             => 'Quiz',
        'App\\Models\\User'             => 'Utente',
        'App\\Models\\Category'         => 'Categoria',
        'App\\Models\\QuizAttempt'      => 'Tentativo quiz',
        'App\\Models\\QuizEnrollment'   => 'Iscrizione quiz',
        'App\\Models\\LearnedQuestion'  => 'Domanda appresa',
        'App\\Models\\QuestionReport'   => 'Segnalazione domanda',
        'App\\Models\\CategoryMaterial' => 'Materiale categoria',
        'App\\Models\\AuditLog'         => 'Audit log',
    ];

    private const FIELD_LABELS = [
        'question'            => 'Testo domanda',
        'is_true'             => 'Risposta',
        'category_id'         => 'Categoria',
        'image'               => 'Immagine',
        'name'                => 'Nome',
        'email'               => 'Email',
        'role'                => 'Ruolo',
        'title'               => 'Titolo',
        'description'         => 'Descrizione',
        'starts_at'           => 'Data inizio',
        'ends_at'             => 'Data fine',
        'published_at'        => 'Pubblicato il',
        'status'              => 'Stato',
        'score'               => 'Punteggio',
        'created_at'          => 'Creato il',
        'first_name'          => 'Nome',
        'last_name'           => 'Cognome',
        'address'             => 'Indirizzo',
        'birth_date'          => 'Data nascita',
        'birth_place'         => 'Luogo nascita',
        'fiscal_code'         => 'Codice fiscale',
        'registration_status' => 'Stato iscrizione',
        'permissions'         => 'Permessi',
        'duration'            => 'Durata',
        'pass_score'          => 'Punteggio minimo',
        'quiz_id'             => 'Quiz',
        'user_id'             => 'Utente',
        'type'                => 'Tipo',
        'note'                => 'Nota',
        'text'                => 'Testo',
        'pass_threshold'      => 'Soglia di superamento',
        'time_limit'          => 'Limite tempo',
    ];

    private const EXCLUDED_FIELDS = ['updated_at', 'remember_token', 'password'];

    public function query(array $filters): Builder
    {
        $q = AuditLog::with('user')->latest();

        if (!empty($filters['user_id'])) {
            $q->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['auditable_type'])) {
            $q->where('model_type', $filters['auditable_type']);
        }

        if (!empty($filters['event'])) {
            $q->where('event', $filters['event']);
        }

        if (!empty($filters['from'])) {
            $q->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $q->whereDate('created_at', '<=', $filters['to']);
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $q->where(function (Builder $sub) use ($term) {
                $sub->where('old_values', 'LIKE', $term)
                    ->orWhere('new_values', 'LIKE', $term);
            });
        }

        return $q;
    }

    public function getAuditableTypes(): array
    {
        $types = AuditLog::query()
            ->select('model_type')
            ->distinct()
            ->orderBy('model_type')
            ->pluck('model_type');

        $result = [];
        foreach ($types as $type) {
            $result[$type] = self::TYPE_LABELS[$type] ?? class_basename($type);
        }

        return $result;
    }

    public function getDiff(AuditLog $log): array
    {
        $diff = [];

        if ($log->event === 'created') {
            foreach ($log->new_values ?? [] as $field => $value) {
                if (in_array($field, self::EXCLUDED_FIELDS, true)) {
                    continue;
                }
                $diff[] = [
                    'field' => $field,
                    'label' => self::FIELD_LABELS[$field] ?? $field,
                    'old'   => null,
                    'new'   => $value,
                ];
            }
        } elseif ($log->event === 'deleted') {
            foreach ($log->old_values ?? [] as $field => $value) {
                if (in_array($field, self::EXCLUDED_FIELDS, true)) {
                    continue;
                }
                $diff[] = [
                    'field' => $field,
                    'label' => self::FIELD_LABELS[$field] ?? $field,
                    'old'   => $value,
                    'new'   => null,
                ];
            }
        } elseif ($log->event === 'updated') {
            $new = $log->new_values ?? [];
            $old = $log->old_values ?? [];
            foreach ($new as $field => $newValue) {
                if (in_array($field, self::EXCLUDED_FIELDS, true)) {
                    continue;
                }
                $diff[] = [
                    'field' => $field,
                    'label' => self::FIELD_LABELS[$field] ?? $field,
                    'old'   => $old[$field] ?? null,
                    'new'   => $newValue,
                ];
            }
        }

        return $diff;
    }

    public function formatUser(AuditLog $log): string
    {
        if ($log->user_id === null) {
            return 'Sistema';
        }

        $user = $log->user;

        if (!$user) {
            return "Utente #$log->user_id";
        }

        if (str_ends_with((string) $user->email, '@eliminato.invalid')) {
            return 'Utente anonimizzato';
        }

        return $user->name;
    }

    public function typeLabel(string $type): string
    {
        return self::TYPE_LABELS[$type] ?? class_basename($type);
    }

    public function diffSummary(AuditLog $log): string
    {
        $diff = $this->getDiff($log);

        if (empty($diff)) {
            return '—';
        }

        $parts = [];
        foreach (array_slice($diff, 0, 3) as $item) {
            $label = $item['label'];
            if ($log->event === 'created') {
                $val = is_array($item['new']) ? '[array]' : Str::limit((string) ($item['new'] ?? ''), 30);
                $parts[] = $label . ': \'' . $val . '\'';
            } elseif ($log->event === 'deleted') {
                $val = is_array($item['old']) ? '[array]' : Str::limit((string) ($item['old'] ?? ''), 30);
                $parts[] = $label . ': \'' . $val . '\'';
            } else {
                $old = is_array($item['old']) ? '[array]' : Str::limit((string) ($item['old'] ?? ''), 20);
                $new = is_array($item['new']) ? '[array]' : Str::limit((string) ($item['new'] ?? ''), 20);
                $parts[] = $label . ': \'' . $old . '\' -> \'' . $new . '\'';
            }
        }

        $summary = implode(', ', $parts);
        if (count($diff) > 3) {
            $summary .= ' …(+' . (count($diff) - 3) . ')';
        }

        return $summary;
    }
}
