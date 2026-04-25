# NARAKA

GDR testuale multi-land sviluppato in PHP 8 su hosting Aruba Linux.

Il progetto è costruito da zero con struttura modulare, senza codice legacy, con attenzione a solidità backend e scalabilità.

---

## ⚙️ Struttura progetto

```text
index.php
login.php
register.php
logout.php
switch_land.php
onboarding.php

config/
├── database.php
├── land.php
└── auth.php

themes/
└── auth.css
```

⚠️ Nota:
Il progetto NON utilizza cartella `htdocs`.
I file sono già nella root del repository e corrispondono alla root pubblica dell’hosting.

---

## 🌍 Sistema Multi-Land

Il gioco è diviso in due land:

* `city` (default)
* `echoes`

La land attiva è gestita tramite sessione:

```php
$_SESSION['current_land']
```

### Comportamento

* Il sito si apre sempre su **city**
* Da city si può accedere a echoes
* Da echoes si può tornare a city
* Ogni utente ha **un personaggio per land**

---

## 👤 Sistema Utenti

✔ Registrazione (`register.php`)
✔ Login (`login.php`)
✔ Logout (`logout.php`)
✔ Sessione utente (`$_SESSION['user']`)
✔ Protezione pagine (`config/auth.php`)

### Dopo il login:

* Se non hai personaggi → onboarding obbligatorio
* Se hai entrambi i personaggi → accesso diretto al gioco

---

## 🧬 Sistema Personaggi

Ogni utente possiede:

* 1 personaggio in `city`
* 1 personaggio in `echoes`

### Creazione personaggi (Onboarding)

Flusso completo:

1. Nome personaggio City
2. Nome personaggio Echoes
3. Distribuzione statistiche City
4. Distribuzione statistiche Echoes
5. Conferma finale

---

## 📊 Sistema Statistiche

### City

* Influenza
* Intelletto
* Percezione
* Prontezza
* Vigore
* Volontà

### Echoes

* Forza
* Destrezza
* Costituzione
* Intelligenza
* Saggezza
* Carisma

### Regole

* Valore minimo: 1
* Valore massimo: 5
* Punti totali: 18
* Solo **una statistica può essere a 5**

✔ Validazione lato server
✔ Contatore punti lato client
✔ Blocco automatico distribuzione invalida

---

## 🗄️ Database

### Tabelle attuali

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

characters
- id_character
- id_user
- id_land
- name
- slug

stat_groups
- id_stat_group
- slug
- name
- points_total
- min_value
- max_value
- max_stats_at_cap

stats
- id_stat
- id_stat_group
- slug
- name
- sort_order
- is_active

character_stats
- id_character_stat
- id_character
- id_stat
- value
```

✔ Relazioni con foreign key
✔ Cascade delete attivo
✔ Sistema espandibile (skill/perk futuri)

---

## 🎨 UI / Frontend

Attualmente implementato:

* CSS esterno: `themes/auth.css`
* Nessun CSS inline
* Stile: **vaporwave informatico anni 80/90**

### Linee guida grafiche

* Font:

  * Titoli → VT323
  * Testo → IBM Plex Mono
* Palette:

  * Blu notte / viola
  * Neon magenta / cyan
* Effetti:

  * Griglia prospettica
  * Glow controllato
  * Scanline CRT leggere

---

## 🔐 Sicurezza

✔ Prepared statements (PDO)
✔ Password hash (`password_hash`)
✔ Session regeneration
✔ Validazione input
✔ Protezione accesso pagine

---

## 📌 Regole progetto

* Nessun codice provvisorio
* Nessuna duplicazione logica
* File sempre completi (no patch)
* Struttura modulare
* CSS solo esterno (no inline)
* Compatibilità browser moderni
* Mobile-first
* Tutti i file firmati:

```php
// by LaEmiX
```

---

## 🚧 Stato attuale

✔ Sistema utenti completo
✔ Sistema multi-land
✔ Sistema onboarding completo
✔ Sistema personaggi per land
✔ Sistema statistiche completo
✔ UI base vaporwave implementata
✔ Routing corretto login → onboarding → gioco

---

## 🚀 Prossimi step

* Sistema location
* Chat in tempo reale
* Skill system
* Perk system
* Inventario
* Forum
* Admin panel

---

## 🧠 Filosofia del progetto

Naraka non è un GDR generico.

È progettato come:

* sistema modulare
* backend solido prima della UI
* esperienza coerente tra land
* crescita progressiva senza refactor distruttivi

---

## 🧪 Ambiente

* PHP 8 (strict types)
* MySQL (PDO)
* Hosting Aruba Linux

---

## 👤 Autore

**LaEmiX**
