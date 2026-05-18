<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Throwable;

class CommandController extends Controller
{
    /**
     * Whitelist comandi artisan eseguibili dalla UI.
     * Ogni voce: slug => [group, label, description, command, args, icon, danger?].
     * I comandi long-running (queue:work) usano --stop-when-empty per garantire
     * la terminazione entro la durata di una request HTTP.
     */
    private const COMMANDS = [
        // ── CODE ────────────────────────────────────────────────────────────
        'queue-emails' => [
            'group'       => 'Code',
            'label'       => 'Invia email in coda',
            'description' => "Processa i job sulla coda \"emails\" e termina quando vuota.\nphp artisan queue:work --queue=emails --stop-when-empty --tries=3",
            'command'     => 'queue:work',
            'args'        => ['--queue' => 'emails', '--stop-when-empty' => true, '--tries' => 3],
            'icon'        => 'fas fa-envelope',
        ],
        'queue-default' => [
            'group'       => 'Code',
            'label'       => 'Processa tutte le code',
            'description' => "Processa i job sulla coda default e termina quando vuota.\nphp artisan queue:work --stop-when-empty --tries=3",
            'command'     => 'queue:work',
            'args'        => ['--stop-when-empty' => true, '--tries' => 3],
            'icon'        => 'fas fa-bolt',
        ],
        'queue-failed' => [
            'group'       => 'Code',
            'label'       => 'Lista job falliti',
            'description' => "Mostra l'elenco dei job nella tabella failed_jobs.\nphp artisan queue:failed",
            'command'     => 'queue:failed',
            'args'        => [],
            'icon'        => 'fas fa-list',
        ],
        'queue-retry-all' => [
            'group'       => 'Code',
            'label'       => 'Riprova tutti i job falliti',
            'description' => "Ricoda tutti i job falliti per un nuovo tentativo.\nphp artisan queue:retry all",
            'command'     => 'queue:retry',
            'args'        => ['id' => ['all']],
            'icon'        => 'fas fa-redo',
        ],
        'queue-flush' => [
            'group'       => 'Code',
            'label'       => 'Elimina job falliti',
            'description' => "Cancella definitivamente tutti i job dalla tabella failed_jobs.\nphp artisan queue:flush",
            'command'     => 'queue:flush',
            'args'        => [],
            'icon'        => 'fas fa-trash',
            'danger'      => true,
        ],

        // ── CACHE ───────────────────────────────────────────────────────────
        'cache-clear' => [
            'group'       => 'Cache',
            'label'       => 'Pulisci cache applicazione',
            'description' => "Svuota la cache dei dati applicativi.\nphp artisan cache:clear",
            'command'     => 'cache:clear',
            'args'        => [],
            'icon'        => 'fas fa-broom',
        ],
        'config-clear' => [
            'group'       => 'Cache',
            'label'       => 'Pulisci cache config',
            'description' => "Rimuove la cache dei file di configurazione.\nphp artisan config:clear",
            'command'     => 'config:clear',
            'args'        => [],
            'icon'        => 'fas fa-cog',
        ],
        'route-clear' => [
            'group'       => 'Cache',
            'label'       => 'Pulisci cache route',
            'description' => "Rimuove la cache delle rotte.\nphp artisan route:clear",
            'command'     => 'route:clear',
            'args'        => [],
            'icon'        => 'fas fa-route',
        ],
        'view-clear' => [
            'group'       => 'Cache',
            'label'       => 'Pulisci cache view',
            'description' => "Rimuove i template Blade compilati.\nphp artisan view:clear",
            'command'     => 'view:clear',
            'args'        => [],
            'icon'        => 'fas fa-file-code',
        ],
        'optimize-clear' => [
            'group'       => 'Cache',
            'label'       => 'Pulisci tutto',
            'description' => "Esegue clear di config, route, view e cache applicativa.\nphp artisan optimize:clear",
            'command'     => 'optimize:clear',
            'args'        => [],
            'icon'        => 'fas fa-eraser',
        ],

        // ── SISTEMA ─────────────────────────────────────────────────────────
        'migrate-status' => [
            'group'       => 'Sistema',
            'label'       => 'Stato migrazioni',
            'description' => "Elenca migrazioni eseguite e in attesa.\nphp artisan migrate:status",
            'command'     => 'migrate:status',
            'args'        => [],
            'icon'        => 'fas fa-database',
        ],
        'storage-link' => [
            'group'       => 'Sistema',
            'label'       => 'Crea symlink storage',
            'description' => "Crea il symlink public/storage → storage/app/public.\nphp artisan storage:link",
            'command'     => 'storage:link',
            'args'        => [],
            'icon'        => 'fas fa-link',
        ],
        'about' => [
            'group'       => 'Sistema',
            'label'       => 'Info ambiente',
            'description' => "Mostra info su versioni, driver, ambiente.\nphp artisan about",
            'command'     => 'about',
            'args'        => [],
            'icon'        => 'fas fa-info-circle',
        ],
    ];

    public function index(): View
    {
        Gate::authorize('admin-only');

        $grouped = collect(self::COMMANDS)
            ->map(fn ($cfg, $slug) => array_merge($cfg, ['slug' => $slug]))
            ->groupBy('group');

        return view('admin.commands.index', [
            'grouped' => $grouped,
            'result'  => session('command_result'),
        ]);
    }

    public function run(string $slug): RedirectResponse
    {
        Gate::authorize('admin-only');

        if (! array_key_exists($slug, self::COMMANDS)) {
            abort(404);
        }

        $cfg = self::COMMANDS[$slug];

        @set_time_limit(0);
        ignore_user_abort(true);

        $startedAt = microtime(true);
        $exitCode  = null;
        $output    = '';
        $error     = null;

        try {
            $exitCode = Artisan::call($cfg['command'], $cfg['args']);
            $output   = Artisan::output();
        } catch (Throwable $e) {
            $error  = $e->getMessage();
            $output = $e->getMessage();
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $ok         = $error === null && $exitCode === 0;

        $flash = $ok ? 'success' : 'error';
        $msg   = $ok
            ? "Comando \"{$cfg['label']}\" eseguito in {$durationMs} ms."
            : "Comando \"{$cfg['label']}\" terminato con errore.";

        return redirect()
            ->route('admin.commands.index')
            ->with($flash, $msg)
            ->with('command_result', [
                'slug'        => $slug,
                'label'       => $cfg['label'],
                'command'     => $cfg['command'],
                'command_str' => 'php artisan '.$cfg['command'].self::formatArgs($cfg['args']),
                'exit_code'   => $exitCode,
                'duration_ms' => $durationMs,
                'output'      => trim($output) !== '' ? $output : '(nessun output)',
                'error'       => $error,
                'ok'          => $ok,
                'ran_at'      => now()->format('d/m/Y H:i:s'),
            ]);
    }

    private static function formatArgs(array $args): string
    {
        $parts = [];

        foreach ($args as $key => $value) {
            if (is_int($key)) {
                $parts[] = (string) $value;
                continue;
            }
            if ($value === true) {
                $parts[] = $key;
                continue;
            }
            if ($value === false || $value === null) {
                continue;
            }
            if (is_array($value)) {
                foreach ($value as $v) {
                    $parts[] = "{$key}={$v}";
                }
                continue;
            }
            $parts[] = "{$key}={$value}";
        }

        return $parts === [] ? '' : ' '.implode(' ', $parts);
    }
}
