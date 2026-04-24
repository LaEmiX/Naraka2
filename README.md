# NARAKA

Progetto GDR multi-land sviluppato in PHP 8 su hosting Aruba Linux.

## Struttura attuale

```text
index.php
login.php
register.php
logout.php
switch_land.php

config/
├── database.php
├── land.php
└── auth.php
```

⚠️ Nota:
Il progetto NON utilizza una cartella `htdocs` nel repository.
I file sono già posizionati nella root del progetto GitHub e corrispondono direttamente alla root pubblica dell’hosting Aruba.

## Sistema Multi-Land

Il sistema è basato su due land:

* city (default)
* echoes

La land attiva è gestita tramite sessione:

```php
$_SESSION['current_land']
```

## Sistema Utenti

✔ Registrazione (`register.php`)
✔ Login (`login.php`)
✔ Logout (`logout.php`)
✔ Sessione utente attiva (`$_SESSION['user']`)
✔ Protezione pagine tramite `config/auth.php`

## Database

Tabelle attuali:

```text
lands
- id_land
- name
- slug
- theme_folder
- is_active

users
- id_user
- username
- email
- password_hash
- role
- is_active
- created_at
- updated_at
- last_login_at
```

## Comportamento

* Il sito si avvia sempre su **city**
* Da city si può accedere a echoes
* Da echoes si può tornare a city
* Le pagine principali richiedono login
* Il sistema legge la land attiva dal database

## Tecnologie

* PHP 8 (strict types)
* MySQL (PDO)
* Hosting Aruba Linux

## Regole progetto

* Nessun codice provvisorio
* Struttura modulare
* Tutti i file firmati: `// by LaEmiX`
* Compatibilità browser moderni
* Mobile-first

## Stato attuale

✔ Connessione database
✔ Sistema lands
✔ Switch tra land
✔ Sistema utenti completo (register/login/logout)
✔ Protezione accesso con auth
✔ Sessione utente funzionante

## Prossimi step

* Sistema personaggi
* Collegamento utenti → personaggi
* Chat
* Skill system
* Forum
* Admin panel
