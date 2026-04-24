# NARAKA

Progetto GDR multi-land sviluppato in PHP 8 su hosting Aruba Linux.

## Struttura attuale

```
htdocs/
├── index.php
├── switch_land.php
└── config/
    ├── database.php
    └── land.php
```

## Sistema Multi-Land

Il sistema è basato su due land:

* city (default)
* echoes

La land attiva è gestita tramite sessione:

```
$_SESSION['current_land']
```

## Database

Tabella principale:

```
lands
- id_land
- name
- slug
- theme_folder
- is_active
```

## Comportamento

* Il sito si avvia sempre su **city**
* Da city si può accedere a echoes
* Da echoes si può tornare a city
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
✔ Base routing index

## Prossimi step

* Sistema utenti
* Creazione personaggi
* Chat
* Skill system
* Forum
* Admin panel

