# Codex agent prompts

Profili specialistici locali per aiutare Codex a ragionare sul progetto.

| Profilo | Usa quando tocchi |
|---------|-------------------|
| `laravel-architect.toml` | Controller, Service, Model, migration, command, queue, notifiche |
| `livewire-blade-specialist.toml` | Componenti Livewire, Blade, AdminLTE, design system `sg-*`, i18n UI |
| `alpine-frontend.toml` | Alpine.js, interazioni leggere, integrazione con Blade/Livewire |
| `test-writer.toml` | Feature test, Livewire test, fake side effect |
| `php-reviewer.toml` | Review di diff PHP/Blade/Livewire/migration/test |
| `docker-specialist.toml` | Dockerfile, Docker Compose, onboarding container |
| `cicd-engineer.toml` | Pipeline CI/CD, lint, PHPStan, test, build asset |
| `ansible-automator.toml` | Provisioning/deploy server, scheduler, worker, backup |

Regola pratica: se un profilo contraddice `../../AGENTS.md`, prevale
`AGENTS.md`.
