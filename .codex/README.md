# Codex workspace notes

Questa cartella contiene materiale di supporto locale per lavorare con Codex
sul progetto Scuola Guida.

## File principali

- `../AGENTS.md` è la fonte operativa principale per Codex.
- `agents/*.toml` contiene prompt specialistici da usare come checklist per
  task Laravel, Livewire/Blade, test, review, frontend leggero e DevOps.
- `docs/` contiene riferimenti rapidi operativi derivati da `.claude/docs/`
  e allineati per l'uso con Codex.

## Come usarla

1. Parti sempre da `AGENTS.md`.
2. Prima di modificare migrations, relazioni o query → leggi `docs/domain-model.md`.
3. Prima di aggiungere logica → leggi `docs/services-map.md` (service già esistente?).
4. Per qualsiasi cosa riguardi ruoli, permessi, 2FA, GDPR → `docs/auth-roles.md`.
5. Prima di aggiungere o modificare notifiche → `docs/notifications.md`.
6. Per comandi schedulati → `docs/scheduled-commands.md`.
7. Per task con dominio specifico, consulta il profilo in `agents/`.
8. Applica comunque le convenzioni del codice esistente: i profili sono aiuti,
   non sostituiscono la lettura dei file reali.

## Mappa docs/

| File | Quando leggerlo |
|---|---|
| `docs/domain-model.md` | Entità, relazioni, tabelle pivot, regole FK |
| `docs/services-map.md` | Responsabilità di ogni service, pattern injection, cache keys |
| `docs/auth-roles.md` | Ruoli, permessi, middleware, gates, 2FA, GDPR |
| `docs/notifications.md` | Mappa notifiche, canali, punti di dispatch, Web Push |
| `docs/scheduled-commands.md` | Comandi schedulati in produzione |

Per documentazione più estesa (architettura, UI patterns, testing, installazione):
leggi i file in `../docs/` secondo la mappa in `AGENTS.md`.

## Note

- I file TOML non sono una configurazione automatica del runtime Laravel.
- Le regole di Git Flow restano quelle di `AGENTS.md`: non eseguire
  `git flow feature finish` senza comando esplicito dell'utente.
- Per nuove feature, aggiorna `CHANGELOG.md` e `README.md` quando pertinente.
