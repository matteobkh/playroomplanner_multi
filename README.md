# Play Room Planner

Sistema di gestione prenotazioni sale prove per un'associazione culturale. Versione multi-page.

## Requisiti

- XAMPP per Mac (o Windows/Linux)
- PHP 8.x
- MySQL/MariaDB
- Browser moderno

## Installazione

### 1. Copia del progetto
Copia la cartella `playroomplanner` nella directory `htdocs` di XAMPP:
```
/Applications/XAMPP/htdocs/playroomplanner
```

### 2. Creazione del database
1. Avvia XAMPP (Apache + MySQL)
2. Apri phpMyAdmin: http://localhost/phpmyadmin
3. Crea un nuovo database chiamato `playroomplanner`
4. Importa lo script SQL fornito (vedi sotto)

### 3. Configurazione
Se necessario, modifica i parametri di connessione in `common/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'playroomplanner');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 4. Avvio
Apri nel browser: http://localhost/playroomplanner

## Script SQL per creare e popolare il database
Da inserire tramite phpMyAdmin

```sql
-- Creazione database
CREATE DATABASE IF NOT EXISTS playroomplanner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE playroomplanner;

-- Tabella iscritti
CREATE TABLE IF NOT EXISTS iscritto (
  email VARCHAR(255) PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  cognome VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  data_nascita DATE NOT NULL,
  foto VARCHAR(255) DEFAULT NULL,
  ruolo ENUM('docente','allievo','tecnico','responsabile') NOT NULL DEFAULT 'allievo',
  data_inizio_responsabile DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabella settori
CREATE TABLE IF NOT EXISTS settore (
  nome_settore VARCHAR(100) PRIMARY KEY,
  num_iscritti INT NOT NULL DEFAULT 0,
  responsabile_email VARCHAR(255) NOT NULL,
  FOREIGN KEY (responsabile_email) REFERENCES iscritto(email)
);

-- Tabella sale
CREATE TABLE IF NOT EXISTS sala (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome_sala VARCHAR(150) NOT NULL,
  nome_settore VARCHAR(100) NOT NULL,
  capienza INT NOT NULL CHECK (capienza > 0),
  UNIQUE(nome_sala, nome_settore),
  FOREIGN KEY (nome_settore) REFERENCES settore(nome_settore)
);

-- Tabella dotazioni
CREATE TABLE IF NOT EXISTS dotazione (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome_dotazione VARCHAR(150) NOT NULL,
  nome_settore VARCHAR(100) NOT NULL,
  nome_sala_id INT NOT NULL,
  FOREIGN KEY (nome_settore) REFERENCES settore(nome_settore),
  FOREIGN KEY (nome_sala_id) REFERENCES sala(id)
);

-- Tabella prenotazioni
CREATE TABLE IF NOT EXISTS prenotazione (
  id INT AUTO_INCREMENT PRIMARY KEY,
  data_ora_inizio DATETIME NOT NULL,
  durata INT NOT NULL CHECK (durata > 0),
  attivita VARCHAR(255) NOT NULL,
  num_iscritti INT NOT NULL DEFAULT 0,
  criterio ENUM('tutti','settore','ruolo','selezione') NOT NULL DEFAULT 'selezione',
  nome_settore VARCHAR(100) NOT NULL,
  sala_id INT NOT NULL,
  responsabile_email VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (nome_settore) REFERENCES settore(nome_settore),
  FOREIGN KEY (sala_id) REFERENCES sala(id),
  FOREIGN KEY (responsabile_email) REFERENCES iscritto(email)
);

-- Tabella inviti
CREATE TABLE IF NOT EXISTS invito (
  iscritto_email VARCHAR(255) NOT NULL,
  prenotazione_id INT NOT NULL,
  data_ora_risposta DATETIME DEFAULT NULL,
  risposta ENUM('si','no','attesa') NOT NULL DEFAULT 'attesa',
  motivazione TEXT DEFAULT NULL,
  PRIMARY KEY (iscritto_email, prenotazione_id),
  FOREIGN KEY (iscritto_email) REFERENCES iscritto(email),
  FOREIGN KEY (prenotazione_id) REFERENCES prenotazione(id)
);

-- Indici
CREATE INDEX idx_preno_sala ON prenotazione(sala_id, data_ora_inizio);
CREATE INDEX idx_preno_resp ON prenotazione(responsabile_email);

-- Dati di esempio

-- Responsabili (devono avere data_inizio_responsabile NOT NULL)
INSERT INTO iscritto (email, nome, cognome, password, data_nascita, ruolo, data_inizio_responsabile) VALUES
('mario.rossi@example.com', 'Mario', 'Rossi', 'password123', '1980-05-15', 'responsabile', '2020-01-01'),
('anna.verdi@example.com', 'Anna', 'Verdi', 'password123', '1985-08-22', 'responsabile', '2021-06-15');

-- Iscritti normali
INSERT INTO iscritto (email, nome, cognome, password, data_nascita, ruolo) VALUES
('luca.bianchi@example.com', 'Luca', 'Bianchi', 'password123', '1995-03-10', 'allievo'),
('giulia.neri@example.com', 'Giulia', 'Neri', 'password123', '1998-11-28', 'allievo'),
('paolo.gialli@example.com', 'Paolo', 'Gialli', 'password123', '1990-07-05', 'docente'),
('sara.rosa@example.com', 'Sara', 'Rosa', 'password123', '1993-02-14', 'tecnico');

-- Settori
INSERT INTO settore (nome_settore, num_iscritti, responsabile_email) VALUES
('Musica', 10, 'mario.rossi@example.com'),
('Teatro', 8, 'anna.verdi@example.com'),
('Danza', 12, 'mario.rossi@example.com');

-- Sale
INSERT INTO sala (nome_sala, nome_settore, capienza) VALUES
('Sala Beethoven', 'Musica', 20),
('Sala Mozart', 'Musica', 15),
('Sala Shakespeare', 'Teatro', 30),
('Sala Danza 1', 'Danza', 25),
('Sala Danza 2', 'Danza', 15);

-- Dotazioni
INSERT INTO dotazione (nome_dotazione, nome_settore, nome_sala_id) VALUES
('Pianoforte', 'Musica', 1),
('Impianto audio', 'Musica', 1),
('Batteria', 'Musica', 2),
('Palcoscenico', 'Teatro', 3),
('Specchi', 'Danza', 4),
('Sbarra', 'Danza', 4);

-- Prenotazioni di esempio
INSERT INTO prenotazione (data_ora_inizio, durata, attivita, nome_settore, sala_id, responsabile_email) VALUES
(DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, 2, 'Prove musicali orchestra', 'Musica', 1, 'mario.rossi@example.com'),
(DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 15 HOUR, 3, 'Lezione di teatro', 'Teatro', 3, 'anna.verdi@example.com'),
(DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL 18 HOUR, 2, 'Corso di danza moderna', 'Danza', 4, 'mario.rossi@example.com');

-- Inviti
INSERT INTO invito (iscritto_email, prenotazione_id, risposta) VALUES
('luca.bianchi@example.com', 1, 'attesa'),
('giulia.neri@example.com', 1, 'attesa'),
('paolo.gialli@example.com', 2, 'attesa'),
('sara.rosa@example.com', 3, 'attesa');
```

## Struttura del progetto

```
playroomplanner/
├── common/           # File condivisi
│   ├── config.php    # Configurazione database
│   ├── funzioni.php  # Funzioni di backend
│   ├── header.html   # Header HTML comune
│   ├── nav.php       # Barra navigazione
│   └── footer.html   # Footer comune
├── css/
│   └── style.css     # Stili personalizzati
├── index.php         # Home page
├── login.php         # Pagina login
├── registrazione.php # Pagina registrazione
├── logout.php        # Script logout
├── profilo.php       # Profilo utente
├── sale.php          # Visualizzazione sale
├── inviti.php        # Gestione inviti
├── impegni.php       # Impegni settimanali
├── nuova_prenotazione.php # Crea prenotazione (solo responsabili)
├── api.php           # API RESTful
└── README.md         # Questo file
```

## API Endpoints

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| POST | api.php?action=login | Login utente |
| POST | api.php?action=register | Registrazione |
| GET | api.php?action=user | Profilo utente |
| POST | api.php?action=logout | Logout |
| GET | api.php?action=prenotazioni | Lista prenotazioni |
| POST | api.php?action=prenotazioni | Crea prenotazione |
| GET | api.php?action=inviti | Lista inviti |
| POST | api.php?action=inviti | Rispondi invito |
| GET | api.php?action=sale | Lista sale |
| GET | api.php?action=impegni | Impegni settimanali |

## Credenziali di test

| Email | Password | Ruolo |
|-------|----------|-------|
| mario.rossi@example.com | password123 | Responsabile |
| anna.verdi@example.com | password123 | Responsabile |
| luca.bianchi@example.com | password123 | Allievo |

## Autori

- Giorgi Bibilashvili
- Elia Francesco Galasso
- Matteo Bukhgalter

Corso di Programmazione per il Web - A.A. 2025/2026
