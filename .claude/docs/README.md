# .claude/docs — Riferimenti rapidi per Claude Code

File di riferimento operativi per lavorare sul progetto ScuolaGUIDA. Sono complementari ai docs/ pubblici e alla CLAUDE.md.

| File | Quando usarlo |
|---|---|
| [domain-model.md](domain-model.md) | Prima di scrivere migrations, relazioni Eloquent, query o services. Contiene entità, relazioni, tabelle pivot e regole FK. |
| [services-map.md](services-map.md) | Prima di aggiungere logica: controlla se esiste già un service pertinente. Contiene responsabilità di ogni service e chiavi cache. |
| [auth-roles.md](auth-roles.md) | Per qualsiasi cosa riguardi autorizzazione, ruoli, 2FA, GDPR, middleware. Contiene metodi ruolo/permesso, pattern `abort_unless`, gates. |
| [notifications.md](notifications.md) | Prima di aggiungere o modificare notifiche. Contiene la mappa completa con canali e punti di dispatch. |
| [scheduled-commands.md](scheduled-commands.md) | Per aggiungere comandi schedulati o capire cosa gira in produzione. |

## Cosa NON è qui

- Convenzioni di sviluppo → `CLAUDE.md` (radice del progetto)
- Architettura e flusso request → `docs/02-architecture.md`
- Funzionalità utente → `docs/03-features.md`
- Installazione e env vars → `docs/01-installation.md`
- Test pattern → `docs/09-testing.md`
