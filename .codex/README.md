# Codex workspace notes

Questa cartella contiene materiale di supporto locale per lavorare con Codex
sul progetto Scuola Guida.

## File principali

- `../AGENTS.md` è la fonte operativa principale per Codex.
- `agents/*.toml` contiene prompt specialistici da usare come checklist per
  task Laravel, Livewire/Blade, test, review, frontend leggero e DevOps.

## Come usarla

1. Parti sempre da `AGENTS.md`.
2. Leggi il documento in `docs/` più vicino al task.
3. Se il task ha un dominio specifico, consulta il profilo in `agents/`.
4. Applica comunque le convenzioni del codice esistente: i profili sono aiuti,
   non sostituiscono la lettura dei file reali.

## Note

- I file TOML non sono una configurazione automatica del runtime Laravel.
- Le regole di Git Flow restano quelle di `AGENTS.md`: non eseguire
  `git flow feature finish` senza comando esplicito dell'utente.
- Per nuove feature, aggiorna `CHANGELOG.md` e `README.md` quando pertinente.
