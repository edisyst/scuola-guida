# Guida al test — Ruolo Instructor (Istruttore)

Credenziali e URL di accesso forniti separatamente.  
Autenticazione a due fattori (2FA TOTP) **obbligatoria**: installa un'app come Google Authenticator o Aegis prima di iniziare.

---

## 1. Accesso e 2FA

1. Inserisci email e password istruttore.
2. Al primo accesso configura il 2FA: scansiona il QR code e conserva i codici di recupero.
3. Ad ogni login inserisci il codice a 6 cifre dell'app TOTP.

> **Ruolo read-only sui contenuti**: l'istruttore non può creare né modificare domande, quiz o categorie. Il suo ambito è il monitoraggio e il supporto agli studenti assegnati.

---

## 2. Lista studenti assegnati

- *Studenti*: elenco degli studenti che l'admin ha assegnato a questo istruttore.
- Per ogni studente sono visibili: nome, data di registrazione e un riepilogo rapido dell'avanzamento.
- Clicca su uno studente per accedere alla sua scheda dettaglio.

---

## 3. Scheda studente

La pagina di dettaglio di uno studente mostra:

- **Statistiche quiz**: numero di tentativi, media punteggi, percentuale di successo.
- **Sessioni di guida pratica**: moduli assegnati, sessioni completate per ogni modulo, avanzamento percentuale.
- **Note istruttore**: elenco delle note private aggiunte in precedenza.

---

## 4. Note sugli studenti

- Dalla scheda studente, usa il form **Aggiungi nota** per inserire osservazioni personali (es. difficoltà riscontrate, obiettivi raggiunti, comportamento alla guida).
- Le note sono private: visibili solo all'istruttore e all'admin.
- Puoi **eliminare** una nota tramite il pulsante di rimozione accanto ad essa (richiede conferma).

---

## 5. Registrazione sessioni di guida pratica

- Dalla scheda studente, sezione *Guida pratica*, seleziona il modulo e registra una nuova sessione cliccando **Registra sessione**.
- Ogni sessione registrata viene conteggiata nell'avanzamento dello studente verso il completamento del modulo.
- Puoi **eliminare** una sessione registrata per errore.

---

## 6. Export progresso studente (PDF)

- Dalla scheda studente, pulsante **Esporta PDF**: genera un documento con il riepilogo del percorso formativo (statistiche quiz, sessioni di guida, note dell'istruttore).
- Il PDF è pensato per essere consegnato allo studente o conservato come documentazione della scuola.

---

## 7. Download attestazione guida pratica

- Quando uno studente ha completato tutti i moduli di guida previsti, dalla sua scheda diventa disponibile il pulsante **Scarica attestazione**.
- L'attestazione è un PDF firmato con i dati della scuola che certifica il completamento delle ore di guida pratica.

---

## 8. Profilo personale

- Dal menu utente in alto a destra puoi aggiornare i tuoi dati personali e la password.
- Puoi gestire i **codici di recupero 2FA** e rigenerarli se necessario.

---

## Cosa verificare alla fine del test

- [ ] Hai visualizzato la lista degli studenti assegnati.
- [ ] Hai aperto la scheda di almeno uno studente e letto le sue statistiche.
- [ ] Hai aggiunto una nota su uno studente.
- [ ] Hai eliminato una nota.
- [ ] Hai registrato almeno una sessione di guida pratica per uno studente.
- [ ] Hai esportato il PDF del progresso di uno studente.
- [ ] Hai verificato che non hai accesso alle aree admin (es. aprendo `/admin/questions` dovresti ricevere un errore 403).
