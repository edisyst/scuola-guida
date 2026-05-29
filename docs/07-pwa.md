# Progressive Web App (PWA)

ScuolaGUIDA è installabile come app nativa sul dispositivo dell'utente (Android / Chrome desktop / iOS Safari) e fornisce un'esperienza offline limitata alla **modalità studio**: domande pre-caricate in IndexedDB, risposte accodate localmente e sincronizzate al ritorno online.

---

## Cosa funziona offline

- **Modalità studio** — le ultime domande revisionate (fino a 100, pre-caricate via `/api/offline/questions` all'avvio di ogni sessione) sono disponibili in IndexedDB. Quando la connessione cade, il componente Alpine entra in "offline mode": le risposte vengono accodate localmente e sincronizzate automaticamente al ritorno online via `POST /api/offline/sync-answers`.
- **App shell** — la UI viene servita dalla cache del service worker. I Vite asset sono immutabili (content hash), quindi restano validi fino al prossimo deploy.

## Cosa NON funziona offline

| Funzione | Motivo |
|---|---|
| Simulatore esame | Richiede estrazione casuale server-side + integrità tentativo |
| Quiz ufficiali e iscrizioni | Integrità in tempo reale su dati condivisi |
| 2FA | Verifica TOTP sincrona |
| Area admin/editor | Scritture DB che richiedono integrità |
| Navigazione generica | Le pagine non visitate non sono in cache |

Tutte le rotte non in cache mostrano la pagina `/offline` (no dipendenze esterne).

---

## Installazione dell'app

**Android / Chrome desktop**:
Al primo accesso al sito dopo alcuni utilizzi, il browser mostra il banner "Installa l'app" in dashboard (solo viewer). In alternativa: barra degli indirizzi → icona installa (⊕). L'app si apre in modalità standalone senza chrome del browser.

**iOS Safari**:
Apri il sito in Safari → pulsante Condividi → "Aggiungi alla schermata Home". Non è disponibile il prompt automatico (limitazione Safari / iOS < 16.4 per PWA).

---

## Architettura

### File chiave

```
public/
  manifest.json                    # Web App Manifest
  sw.js                            # Service Worker (CACHE_VERSION da bumpare ad ogni deploy)
  icons/icon.svg                   # Icona sorgente
  icons/icon-{192,256,384,512}.png # Generate da SVG
  icons/apple-touch-icon.png

resources/js/
  pwa.js                           # Registrazione SW + beforeinstallprompt
  offline-store.js                 # Wrapper IndexedDB (window.offlineStore)

resources/views/
  offline.blade.php                # Pagina offline standalone (no dipendenze esterne)

app/Http/
  Controllers/Api/OfflineController.php   # GET questions + POST sync-answers
  Requests/SyncAnswersRequest.php
```

### Manifest

`public/manifest.json`:
- `name` "ScuolaGUIDA — Quiz Patente"
- `short_name` "ScuolaGUIDA"
- `display: standalone`
- `start_url: /dashboard`
- `theme_color: #4361ee`
- `orientation: portrait-primary`
- Icone in 192, 256, 384, 512 px

### Service Worker

`public/sw.js` — `CACHE_VERSION = 'sg-v1'`:

- **Install event**: pre-caching di `/offline`, manifest, icone.
- **Fetch handler**:
  - Cache-first per asset Vite content-hashed (`/build/assets/**`)
  - Network-first con fallback `/offline` per navigazioni HTML
  - Cache-first per altri asset statici
  - **Mai cachea**: POST/PUT/DELETE/PATCH, `/livewire/update`, `/admin/*`, `/2fa/*`
- **Activate event**: cleanup delle vecchie cache.
- **Background sync**: handler che delega ai client via `postMessage`.

### IndexedDB

`resources/js/offline-store.js` espone `window.offlineStore` con DB `scuolaguida_offline` v1.

Object store:
- `questions` — keyPath `id`, indici su `category_id`, `last_fetched_at`
- `categories` — keyPath `id`
- `pending_answers` — autoIncrement, indice su `synced`

API:
- `saveQuestions()`, `getAllQuestions()`, `getQuestionsByCategory()`, `getQuestionsCount()`
- `enqueuePendingAnswer()`, `getPendingAnswers()`, `markAnswersSynced()`

Tutte le operazioni sono async/Promise; grazie alla guardia `if (!window.offlineStore)` l'app degrada silenziosamente se IndexedDB non è disponibile (Safari private).

### Endpoint API

Entrambi viewer-only, autorizzati nel controller.

| Endpoint | Descrizione |
|---|---|
| `GET /api/offline/questions` | Ultime 100 domande revisionate via `question_reviews`, throttle `1,5`; eager load `category`. |
| `POST /api/offline/sync-answers` | Itera array di risposte offline, chiama `SpacedRepetitionService::recordAnswer()` per ciascuna e `StreakService::recordActivity()` + `BadgeService::checkAllBadges()` una sola volta per sync (DB transaction). Restituisce `synced_ids`. |
| `GET /offline` | Pubblica (no `auth`), cacheable dal SW, serve `offline.blade.php`. |

### Integrazione nella modalità studio

`resources/views/study/play.blade.php` — il componente Alpine `studyPlay()` viene esteso con:

- `init()` — prefetch via `/api/offline/questions` al caricamento online.
- `answer()` — se `!navigator.onLine`, salva in `pending_answers` IndexedDB e mostra badge "Sei offline — risposta salvata".
- `_enterOfflineMode()` — carica le domande dall'IDB e abilita la navigazione JS (`offlineNext()` / `offlinePrev()`).
- `_exitOfflineMode()` — on-reconnect chiama `_syncPendingAnswers()` e mostra toast con il conteggio sincronizzato.

Il testo della domanda, il badge categoria e l'immagine sono resi reattivi ad Alpine per supportare lo swap offline. Il `@section('js')` include `@vite(['resources/js/offline-store.js'])`.

### Banner add-to-home-screen

`resources/views/stats/dashboard.blade.php` contiene una card Alpine.js visibile solo ai viewer non in standalone mode, con dismissal in `localStorage` per 7 giorni. Pulsanti "Installa" (chiama `window.__pwaInstallPrompt.prompt()`) e "Non ora".

Il prompt viene catturato in `pwa.js`:

```js
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    window.__pwaInstallPrompt = e;
    window.dispatchEvent(new CustomEvent('pwa:installable'));
});
```

---

## Test manuali

| Scenario | Passaggi |
|---|---|
| Installazione Chrome desktop | Visita `/dashboard` → aspetta il banner → "Installa" → verifica standalone |
| Installazione Android Chrome | Stesso flusso da mobile |
| Installazione iOS Safari | Condividi → "Aggiungi alla schermata Home" |
| Offline modalità studio | Avvia sessione studio → DevTools Network → Offline → rispondi alle domande → torna Online → verifica toast sincronizzazione |
| Sincronizzazione al ritorno online | Rispondi offline → torna online → controlla `question_reviews` + `user_activity_log` aggiornati |

---

## Note tecniche per sviluppatori

### Versionamento service worker

Ogni release che modifica asset compilati **deve** bumpare la costante in `public/sw.js`:

```javascript
const CACHE_VERSION = 'sg-v2'; // incrementa ad ogni deploy con asset cambiati
```

L'evento `activate` del SW cancella automaticamente le cache vecchie.

### Generazione icone PNG

Le icone SVG in `public/icons/icon.svg` sono la sorgente. Genera le PNG prima di ogni deploy:

```bash
# Con Inkscape (CLI)
inkscape public/icons/icon.svg --export-width=192 --export-filename=public/icons/icon-192.png
inkscape public/icons/icon.svg --export-width=256 --export-filename=public/icons/icon-256.png
inkscape public/icons/icon.svg --export-width=384 --export-filename=public/icons/icon-384.png
inkscape public/icons/icon.svg --export-width=512 --export-filename=public/icons/icon-512.png
inkscape public/icons/icon.svg --export-width=180 --export-filename=public/icons/apple-touch-icon.png

# Oppure online: https://realfavicongenerator.net
```

### Testare PWA in sviluppo

Il service worker richiede HTTPS (o `localhost`). Con Laragon, `localhost` funziona nativamente. Per testare su dispositivo mobile nella stessa rete LAN:

```bash
# Genera un certificato locale con mkcert e configura Laragon su HTTPS
mkcert scuola-guida.test
# oppure usa ngrok per un tunnel HTTPS temporaneo
ngrok http 80
```

### Ispezionare IndexedDB

Chrome DevTools → Application → Storage → IndexedDB → `scuolaguida_offline`:

- `questions`: domande pre-caricate
- `pending_answers`: risposte in attesa di sync (campo `synced: 0` = non ancora sincronizzate)
- `categories`: categorie associate alle domande

### Throttle endpoint questions

`GET /api/offline/questions` è limitato a 1 request ogni 5 minuti per utente (Laravel rate limiter `throttle:1,5`). In sviluppo, svuota il rate limiter con:

```bash
php artisan cache:clear
```

---

## Test automatici

`tests/Feature/OfflineApiTest` — 18 test:
- Autenticazione e autorizzazione viewer-only su entrambi gli endpoint
- Throttle (200 poi 429)
- Validazione `question_id`
- Mock di `SpacedRepetitionService` (chiamato per ogni risposta) e `StreakService` (chiamato una volta per sync)
- Verifica scrittura in `question_reviews` e `user_activity_log`
- Test `synced_ids` nel response body
- Accessibilità pubblica di `/offline`
