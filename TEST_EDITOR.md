# Guida al test — Ruolo Editor

Credenziali e URL di accesso forniti separatamente.  
Autenticazione a due fattori (2FA TOTP) **obbligatoria**: installa un'app come Google Authenticator o Aegis prima di iniziare.

---

## 1. Accesso e 2FA

1. Inserisci email e password editor.
2. Al primo accesso configura il 2FA: scansiona il QR code e conserva i codici di recupero.
3. Ad ogni login inserisci il codice a 6 cifre dell'app TOTP.

> **Nota sui permessi**: un editor può avere permessi granulari per entità (domande, categorie, quiz). Se un'azione non è disponibile, potrebbe essere disabilitata per il tuo account — segnalalo al responsabile del test.

---

## 2. Dashboard editor

- *Editor → Dashboard*: panoramica delle metriche di produzione contenuti (domande create/modificate, quiz, traduzioni completate).
- Filtri per **tipo di patente** e **periodo** per analizzare l'attività in un intervallo specifico.

---

## 3. Domande

- **Visualizza**: *Domande* mostra l'elenco completo con filtri per categoria e testo.
- **Crea**: *Nuova domanda* — compila testo, risposta corretta, categoria; carica un'immagine opzionale.
- **Modifica**: icona matita sulla riga. Puoi modificare tutti i campi inclusa l'immagine.
- **Elimina**: icona cestino (richiede conferma). Disponibile solo se il permesso `delete` è attivo.
- **Import Excel**: *Domande → Importa* — usa il template scaricabile.
- **Import MIT**: *Domande → Importa MIT* — importa il dataset ministeriale ufficiale.
- **Storico versioni**: icona cronologia su una domanda per vedere tutte le revisioni con diff prima/dopo e possibilità di ripristino.

---

## 4. Traduzioni domande

- Da *Modifica domanda*, sezione *Traduzioni*: aggiungi o aggiorna testo e risposta in **italiano, inglese e spagnolo**.
- Le traduzioni sono visibili agli studenti in base alla lingua selezionata nel profilo.

---

## 5. Categorie

- **Crea** una nuova categoria con nome; lo slug viene generato automaticamente.
- **Materiale didattico**: entra nella categoria e aggiungi materiali (PDF, video YouTube, note, link). Riordina trascinando.
- **Traduzioni**: aggiungi il nome della categoria nelle tre lingue supportate.

---

## 6. Quiz

- **Crea un quiz**: *Quiz → Nuovo quiz*. Imposta titolo, categoria, numero massimo di domande, tempo limite e numero massimo di errori consentiti.
- **Aggiungi domande**: manualmente dalla lista o tramite selezione casuale per categoria.
- **Riordina domande**: drag-and-drop nella pagina di modifica del quiz.
- **Rimuovi domande**: singolarmente o in blocco con le checkbox.

> Gli editor **non possono** pubblicare né confermare un quiz: queste azioni sono riservate all'admin.

---

## 7. Segnalazioni domande

- *Segnalazioni*: l'editor può visualizzare le segnalazioni inviate dagli studenti sulle domande.
- Può **accettare** una segnalazione (correggendo la domanda) o **rifiutarla**.

---

## Cosa verificare alla fine del test

- [ ] Hai visualizzato la dashboard editor con le metriche.
- [ ] Hai creato almeno una domanda con immagine.
- [ ] Hai aggiunto o modificato una traduzione su una domanda.
- [ ] Hai visualizzato lo storico versioni di una domanda.
- [ ] Hai modificato una domanda esistente.
- [ ] Hai aggiunto materiale didattico a una categoria.
- [ ] Hai aggiunto una traduzione a una categoria.
- [ ] Hai creato un quiz e aggiunto domande.
- [ ] Hai verificato cosa succede provando un'azione per cui non hai il permesso (es. pubblicare un quiz).
