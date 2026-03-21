# Nonna Celeste

Prima base applicativa PHP per la gestione di ricette tradizionali e familiari, pensata per girare su un server LAMP locale Ubuntu 24.04.

## Funzionalità incluse in questa prima versione
- Home con tre ingressi principali: ricette tradizionali, ricette familiari, inserimento ricetta.
- Distinzione ruoli: superadmin, admin, utente.
- Cataloghi iniziali:
  - 200 ingredienti della dieta mediterranea.
  - 30 utensili da cucina.
  - 10 modalità di cottura.
- Ricette con:
  - nome, cuoco da elenco approvato dall'admin, categoria, festività, momento della giornata, portata;
  - ingredienti con quantità in grammi, centilitri o `qb`;
  - utensili;
  - più modalità di cottura con tempo;
  - descrizione esecuzione;
  - galleria immagini;
  - stampa PDF via foglio di stampa del browser.
- Commenti pubblici modificabili solo dal proprietario.
- Area admin per inserimento ingredienti, utensili mancanti, anagrafica cuochi approvati e gestione foto principale della Home.
- Pagina contatti/richieste con richieste di cancellazione ricetta e richiesta inserimento nuovo cuoco.
- Link rapido per aggiungere una ricetta/festività a Google Calendar.

## Avvio rapido
1. Verificare di avere PHP 8.2+ con estensione SQLite abilitata.
2. Dalla root del progetto eseguire:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
3. Aprire `http://127.0.0.1:8000`.

## Demo utenti
- `superadmin@nonnaceleste.local` / `superadmin123`
- `admin@nonnaceleste.local` / `admin123`
- `maria@example.com` / `user123`

## Note
- In questa fase l'integrazione Google Calendar è implementata come link di creazione evento precompilato. Una futura iterazione può sostituirla con OAuth/API complete.
- L'upload immagini salva i file in `storage/`. La foto hero della Home può essere aggiornata dal pannello admin e viene salvata in `storage/home/`.
