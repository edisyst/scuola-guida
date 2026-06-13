# Guida al test — Ruolo Admin

Credenziali e URL di accesso forniti separatamente.  
Autenticazione a due fattori (2FA TOTP) **obbligatoria**: installa un'app come Google Authenticator o Aegis prima di iniziare.

---

## 1. Accesso e 2FA

1. Vai alla pagina di login e inserisci email e password admin.
2. Al primo accesso ti verrà chiesto di configurare il 2FA: scansiona il QR code con la tua app TOTP e salva i codici di recupero in un posto sicuro.
3. Ad ogni login successivo inserisci il codice a 6 cifre generato dall'app.

---

## 2. Gestione domande

- **Crea una domanda**: menu *Domande → Nuova domanda*. Compila testo, risposta corretta, categoria e carica un'immagine opzionale.
- **Modifica / elimina**: dalla lista domande usa le icone sulla riga. L'eliminazione richiede conferma.
- **Importa da Excel**: *Domande → Importa*. Scarica il template, compilalo e carica il file.
- **Importa da MIT**: *Domande → Importa MIT* per importare il dataset ufficiale ministeriale.
- **Elimina in blocco**: seleziona più domande con le checkbox e usa "Elimina selezionate".
- **Esporta**: scarica l'elenco domande in formato Excel.
- **Storico versioni**: clicca l'icona cronologia su una domanda per vedere le revisioni precedenti e ripristinarne una.

---

## 3. Gestione categorie

- *Categorie → Nuova categoria*: nome e slug generato automaticamente.
- Da *Materiale didattico* di ogni categoria puoi aggiungere PDF, video YouTube, note o link esterni (drag-and-drop per riordinare).
- **Traduzioni**: aggiungi traduzioni della categoria in italiano, inglese e spagnolo dalla scheda *Traduzioni*.

---

## 4. Tipi di patente

- *Tipi patente*: crea e gestisci le categorie di patente (es. B, A, AM).
- Ogni tipo di patente può essere associato a specifiche categorie di domande tramite *Sincronizza categorie*.
- Gli utenti selezionano il tipo di patente attivo dal proprio profilo.

---

## 5. Ciclo di vita dei quiz

Un quiz percorre tre stati: **Bozza → Pubblicato → Confermato**.

1. *Quiz → Nuovo quiz*: scegli titolo, categoria, parametri (n. domande, tempo, max errori). Aggiungi domande manualmente o tramite selezione casuale.
2. **Pubblica** il quiz per renderlo visibile agli iscritti.
3. **Conferma** per chiuderlo a nuove iscrizioni e renderlo definitivo.
4. Dalla pagina del quiz puoi **pianificare la data d'esame** (*Modifica pianificazione*).
5. **Esporta risultati**: una volta confermato, scarica i risultati in Excel dalla pagina di riepilogo.

---

## 6. Iscrizioni degli utenti

- *Iscrizioni* mostra le richieste in attesa. Puoi **approvare** o **rifiutare** ogni richiesta; l'utente riceve una notifica email e in-app.
- Controlla lo stato di avanzamento dei partecipanti dalla pagina di dettaglio del quiz.
- Puoi **riaprire** un'iscrizione per permettere a un utente di ripetere il quiz.
- *Risultati confermati*: elenco di tutti i tentativi su quiz confermati.

---

## 7. Registrazioni esami ufficiali

- *Registrazioni*: lista degli utenti che hanno inviato i dati anagrafici per l'iscrizione ufficiale agli esami.
- Puoi **approvare** o **rifiutare** ogni richiesta di registrazione con un commento.

---

## 8. Gestione utenti e ruoli

- *Utenti*: visualizza, crea, modifica e cambia ruolo (admin / editor / viewer / instructor).
- Modifica i permessi granulari di un editor dalla scheda *Permessi* dell'utente.
- Per eliminare un utente puoi usare l'**anonimizzazione GDPR**: i dati personali vengono rimossi ma le statistiche aggregate restano.
- *Ruoli e permessi*: configura le permission di default per ogni ruolo.

---

## 9. Gestione istruttori

- *Istruttori*: elenco degli utenti con ruolo instructor.
- Dalla pagina di un istruttore puoi **assegnare** o **rimuovere** studenti (viewer) dalla sua lista.

---

## 10. Moduli guida pratica

- *Moduli guida*: crea i moduli di guida pratica con nome, descrizione e numero minimo di sessioni richieste.
- Gli istruttori registrano le sessioni per ogni studente; gli studenti vedono il proprio avanzamento.

---

## 11. Segnalazioni domande

- *Segnalazioni*: elenco delle segnalazioni inviate dai viewer (risposta errata, testo ambiguo, immagine mancante, ecc.).
- Puoi **accettare** (la domanda viene corretta), **rifiutare** o **eliminare** ogni segnalazione.

---

## 12. Statistiche e report

- *Statistiche*: dashboard con KPI globali (utenti attivi, quiz completati, tasso di successo, domande per categoria).
- *Report*: analisi periodiche con filtri per data; esportabili in PDF.

---

## 13. Media manager

- *Media*: visualizza e gestisci i file caricati (immagini domande, PDF materiali). Puoi eliminare i file non più utilizzati.

---

## 14. Comandi Artisan

- *Comandi*: interfaccia web per eseguire i comandi artisan del progetto (importazione domande MIT, chiusura iscrizioni scadute, anonimizzazione GDPR, ecc.) senza accesso SSH.

---

## 15. Stato del sistema e backup

- *Stato sistema*: dashboard con i controlli di salute (database, storage, queue, email, cache, backup).
- Puoi avviare un **backup manuale** direttamente dall'interfaccia.
- *Impostazioni sistema*: nome scuola, logo, colore accent e altre configurazioni globali.

---

## 16. Log di audit

- *Audit log*: cronologia completa di ogni creazione, modifica e cancellazione su tutte le entità, con i valori prima/dopo. Esportabile.

---

## 17. Dashboard editor (vista admin)

- *Editor → Dashboard*: l'admin può vedere le metriche di produzione contenuti di tutti gli editor (domande create, quiz, traduzioni), con filtri per tipo di patente e periodo.

---

## Cosa verificare alla fine del test

- [ ] Hai creato almeno una domanda, una categoria e un quiz.
- [ ] Hai pubblicato e confermato il quiz.
- [ ] Hai approvato almeno un'iscrizione.
- [ ] Hai modificato il ruolo di un utente.
- [ ] Hai assegnato uno studente a un istruttore.
- [ ] Hai creato un modulo di guida pratica.
- [ ] Hai approvato una registrazione all'esame ufficiale.
- [ ] Hai visualizzato le statistiche e almeno un report.
- [ ] Hai verificato lo stato del sistema.
- [ ] Hai visualizzato il log di audit.
- [ ] Hai ricevuto (o visto in-app) almeno una notifica.
