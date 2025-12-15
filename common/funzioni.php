<?php
/**
 * FUNZIONI.PHP - Funzioni di backend per interazione con il database
 * 
 * Contiene tutte le funzioni per:
 * - Autenticazione (login, registrazione)
 * - Gestione utenti
 * - Gestione prenotazioni
 * - Gestione inviti
 * 
 * Ogni funzione ritorna un array con struttura:
 * ["status" => "ok"/"ko", "msg" => "messaggio", "contenuto" => dati]
 */

// ============================================
// FUNZIONI DI AUTENTICAZIONE
// ============================================

/**
 * Effettua il login di un utente
 * 
 * @param PDO $db Connessione al database
 * @param string $email Email dell'utente
 * @param string $password Password dell'utente
 * @return array Risultato dell'operazione
 */
function login($db, $email, $password) {
    $risultato = ["status" => "ok", "msg" => "", "contenuto" => null];
    
    // Cerco l'utente nel database
    $sql = "SELECT * FROM iscritto WHERE email = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$email]);
    $utente = $stmt->fetch();
    
    // Verifico se l'utente esiste e la password è corretta
    if (!$utente) {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Email non trovata";
        return $risultato;
    }
    
    // Confronto password (semplice, senza hash come richiesto)
    if ($utente["password"] !== $password) {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Password errata";
        return $risultato;
    }
    
    // Login riuscito: salvo i dati in sessione
    $_SESSION["user_email"] = $utente["email"];
    $_SESSION["user_nome"] = $utente["nome"];
    $_SESSION["user_cognome"] = $utente["cognome"];
    $_SESSION["user_ruolo"] = $utente["ruolo"];
    
    $risultato["msg"] = "Login effettuato con successo";
    $risultato["contenuto"] = $utente;
    return $risultato;
}

/**
 * Registra un nuovo utente nel sistema
 * 
 * @param PDO $db Connessione al database
 * @param array $dati Dati dell'utente (email, nome, cognome, password, data_nascita, ruolo)
 * @return array Risultato dell'operazione
 */
function registraUtente($db, $dati) {
    $risultato = ["status" => "ok", "msg" => "", "contenuto" => null];
    
    // Validazione campi obbligatori
    if (empty($dati["email"]) || empty($dati["nome"]) || empty($dati["cognome"]) || 
        empty($dati["password"]) || empty($dati["data_nascita"])) {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Tutti i campi sono obbligatori";
        return $risultato;
    }
    
    // Verifico che l'email non sia già registrata
    $check = $db->prepare("SELECT email FROM iscritto WHERE email = ?");
    $check->execute([$dati["email"]]);
    if ($check->fetch()) {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Email già registrata";
        return $risultato;
    }
    
    // Ruolo di default è 'allievo'
    $ruolo = isset($dati["ruolo"]) ? $dati["ruolo"] : "allievo";
    
    // Inserisco il nuovo utente
    $sql = "INSERT INTO iscritto (email, nome, cognome, password, data_nascita, ruolo) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $esito = $stmt->execute([
        $dati["email"],
        $dati["nome"],
        $dati["cognome"],
        $dati["password"],
        $dati["data_nascita"],
        $ruolo
    ]);
    
    if ($esito) {
        $risultato["msg"] = "Registrazione completata con successo";
    } else {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Errore durante la registrazione";
    }
    
    return $risultato;
}

/**
 * Ottiene i dati del profilo di un utente
 * 
 * @param PDO $db Connessione al database
 * @param string $email Email dell'utente
 * @return array Risultato con i dati dell'utente
 */
function getProfiloUtente($db, $email) {
    $risultato = ["status" => "ok", "msg" => "", "contenuto" => null];
    
    $sql = "SELECT email, nome, cognome, data_nascita, foto, ruolo, data_inizio_responsabile 
            FROM iscritto WHERE email = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$email]);
    $utente = $stmt->fetch();
    
    if ($utente) {
        $risultato["contenuto"] = $utente;
    } else {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Utente non trovato";
    }
    
    return $risultato;
}

/**
 * Modifica i dati del profilo di un utente
 * 
 * @param PDO $db Connessione al database
 * @param string $email Email dell'utente da modificare
 * @param array $dati Nuovi dati (nome, cognome, data_nascita)
 * @return array Risultato dell'operazione
 */
function modificaUtente($db, $email, $dati) {
    $risultato = ["status" => "ok", "msg" => "", "contenuto" => null];
    
    $sql = "UPDATE iscritto SET nome = ?, cognome = ?, data_nascita = ? WHERE email = ?";
    $stmt = $db->prepare($sql);
    $esito = $stmt->execute([
        $dati["nome"],
        $dati["cognome"],
        $dati["data_nascita"],
        $email
    ]);
    
    if ($esito) {
        $risultato["msg"] = "Profilo aggiornato con successo";
    } else {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Errore durante l'aggiornamento";
    }
    
    return $risultato;
}

/**
 * Elimina un utente dal sistema
 * 
 * @param PDO $db Connessione al database
 * @param string $email Email dell'utente da eliminare
 * @return array Risultato dell'operazione
 */
function eliminaUtente($db, $email) {
    $risultato = ["status" => "ok", "msg" => "", "contenuto" => null];
    
    // Prima elimino gli inviti dell'utente
    $db->prepare("DELETE FROM invito WHERE iscritto_email = ?")->execute([$email]);
    
    // Poi elimino l'utente
    $sql = "DELETE FROM iscritto WHERE email = ?";
    $stmt = $db->prepare($sql);
    $esito = $stmt->execute([$email]);
    
    if ($esito) {
        $risultato["msg"] = "Utente eliminato con successo";
    } else {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Errore durante l'eliminazione";
    }
    
    return $risultato;
}

// ============================================
// FUNZIONI PER LE PRENOTAZIONI
// ============================================

/**
 * Ottiene le prenotazioni di una sala per una settimana specifica
 * 
 * @param PDO $db Connessione al database
 * @param int $sala_id ID della sala (opzionale, se null ritorna tutte le sale)
 * @param string $data_riferimento Una data qualsiasi della settimana (formato Y-m-d)
 * @return array Risultato con le prenotazioni
 */
function getPrenotazioniSettimana($db, $sala_id, $data_riferimento) {
    $risultato = ["status" => "ok", "msg" => "", "contenuto" => []];
    
    // Calcolo inizio e fine settimana (lunedì-domenica)
    $data = new DateTime($data_riferimento);
    $giorno_settimana = $data->format('N'); // 1=lunedì, 7=domenica
    $inizio_settimana = clone $data;
    $inizio_settimana->modify('-' . ($giorno_settimana - 1) . ' days');
    $fine_settimana = clone $inizio_settimana;
    $fine_settimana->modify('+6 days');
    
    // Query per le prenotazioni
    $sql = "SELECT p.*, s.nome_sala, s.capienza, i.nome as responsabile_nome, i.cognome as responsabile_cognome
            FROM prenotazione p
            JOIN sala s ON p.sala_id = s.id
            JOIN iscritto i ON p.responsabile_email = i.email
            WHERE DATE(p.data_ora_inizio) BETWEEN ? AND ?";
    
    $params = [$inizio_settimana->format('Y-m-d'), $fine_settimana->format('Y-m-d')];
    
    // Se specificata una sala, filtro per quella
    if ($sala_id) {
        $sql .= " AND p.sala_id = ?";
        $params[] = $sala_id;
    }
    
    $sql .= " ORDER BY p.data_ora_inizio ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $risultato["contenuto"] = $stmt->fetchAll();
    
    return $risultato;
}

/**
 * Ottiene gli impegni (prenotazioni accettate) di un utente per una settimana
 * 
 * @param PDO $db Connessione al database
 * @param string $email Email dell'utente
 * @param string $data_riferimento Una data qualsiasi della settimana
 * @return array Risultato con gli impegni
 */
function getImpegniSettimana($db, $email, $data_riferimento) {
    $risultato = ["status" => "ok", "msg" => "", "contenuto" => []];
    
    // Calcolo inizio e fine settimana
    $data = new DateTime($data_riferimento);
    $giorno_settimana = $data->format('N');
    $inizio_settimana = clone $data;
    $inizio_settimana->modify('-' . ($giorno_settimana - 1) . ' days');
    $fine_settimana = clone $inizio_settimana;
    $fine_settimana->modify('+6 days');
    
    // Query per le prenotazioni accettate dall'utente
    $sql = "SELECT p.*, s.nome_sala, inv.risposta
            FROM prenotazione p
            JOIN sala s ON p.sala_id = s.id
            JOIN invito inv ON p.id = inv.prenotazione_id
            WHERE inv.iscritto_email = ?
            AND inv.risposta = 'si'
            AND DATE(p.data_ora_inizio) BETWEEN ? AND ?
            ORDER BY p.data_ora_inizio ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$email, $inizio_settimana->format('Y-m-d'), $fine_settimana->format('Y-m-d')]);
    $risultato["contenuto"] = $stmt->fetchAll();
    
    return $risultato;
}

/**
 * Crea una nuova prenotazione (solo per responsabili)
 * 
 * @param PDO $db Connessione al database
 * @param array $dati Dati della prenotazione
 * @return array Risultato dell'operazione
 */
function creaPrenotazione($db, $dati) {
    $risultato = ["status" => "ok", "msg" => "", "contenuto" => null];
    
    // Validazione campi obbligatori
    if (empty($dati["data_ora_inizio"]) || empty($dati["durata"]) || 
        empty($dati["attivita"]) || empty($dati["sala_id"])) {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Tutti i campi sono obbligatori";
        return $risultato;
    }
    
    // Verifico che l'utente sia un responsabile
    $check_resp = $db->prepare("SELECT ruolo, data_inizio_responsabile FROM iscritto WHERE email = ?");
    $check_resp->execute([$dati["responsabile_email"]]);
    $utente = $check_resp->fetch();
    
    if (!$utente || $utente["ruolo"] !== "responsabile" || empty($utente["data_inizio_responsabile"])) {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Solo i responsabili possono creare prenotazioni";
        return $risultato;
    }
    
    // Verifico ora intera (minuti = 00)
    $data_ora = new DateTime($dati["data_ora_inizio"]);
    if ($data_ora->format('i') != '00') {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Le prenotazioni devono essere ad ore intere";
        return $risultato;
    }
    
    // Verifico orario 9-23
    $ora = (int)$data_ora->format('H');
    if ($ora < 9 || $ora > 23) {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Orario prenotazioni: 09:00 - 23:00";
        return $risultato;
    }
    
    // Ottengo il settore della sala
    $get_sala = $db->prepare("SELECT nome_settore FROM sala WHERE id = ?");
    $get_sala->execute([$dati["sala_id"]]);
    $sala = $get_sala->fetch();
    
    if (!$sala) {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Sala non trovata";
        return $risultato;
    }
    
    // Verifico sovrapposizioni nella stessa sala
    $fine_nuova = clone $data_ora;
    $fine_nuova->modify('+' . $dati["durata"] . ' hours');
    
    $check_sovrap = $db->prepare("
        SELECT id FROM prenotazione 
        WHERE sala_id = ? 
        AND (
            (data_ora_inizio < ? AND DATE_ADD(data_ora_inizio, INTERVAL durata HOUR) > ?)
        )
    ");
    $check_sovrap->execute([
        $dati["sala_id"],
        $fine_nuova->format('Y-m-d H:i:s'),
        $data_ora->format('Y-m-d H:i:s')
    ]);
    
    if ($check_sovrap->fetch()) {
        $risultato["status"] = "ko";
        $risultato["msg"] = "La sala è già occupata in questo orario";
        return $risultato;
    }
    
    // Inserisco la prenotazione
    $sql = "INSERT INTO prenotazione (data_ora_inizio, durata, attivita, criterio, nome_settore, sala_id, responsabile_email)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $criterio = isset($dati["criterio"]) ? $dati["criterio"] : "selezione";
    
    $esito = $stmt->execute([
        $dati["data_ora_inizio"],
        $dati["durata"],
        $dati["attivita"],
        $criterio,
        $sala["nome_settore"],
        $dati["sala_id"],
        $dati["responsabile_email"]
    ]);
    
    if ($esito) {
        $risultato["msg"] = "Prenotazione creata con successo";
        $risultato["contenuto"] = ["id" => $db->lastInsertId()];
    } else {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Errore durante la creazione";
    }
    
    return $risultato;
}

/**
 * Modifica una prenotazione esistente
 * 
 * @param PDO $db Connessione al database
 * @param int $id ID della prenotazione
 * @param array $dati Nuovi dati
 * @return array Risultato dell'operazione
 */
function modificaPrenotazione($db, $id, $dati) {
    $risultato = ["status" => "ok", "msg" => "", "contenuto" => null];
    
    $sql = "UPDATE prenotazione SET data_ora_inizio = ?, durata = ?, attivita = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $esito = $stmt->execute([
        $dati["data_ora_inizio"],
        $dati["durata"],
        $dati["attivita"],
        $id
    ]);
    
    if ($esito) {
        $risultato["msg"] = "Prenotazione modificata con successo";
    } else {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Errore durante la modifica";
    }
    
    return $risultato;
}

/**
 * Elimina una prenotazione
 * 
 * @param PDO $db Connessione al database
 * @param int $id ID della prenotazione
 * @return array Risultato dell'operazione
 */
function eliminaPrenotazione($db, $id) {
    $risultato = ["status" => "ok", "msg" => "", "contenuto" => null];
    
    // Prima elimino gli inviti collegati
    $db->prepare("DELETE FROM invito WHERE prenotazione_id = ?")->execute([$id]);
    
    // Poi elimino la prenotazione
    $sql = "DELETE FROM prenotazione WHERE id = ?";
    $stmt = $db->prepare($sql);
    $esito = $stmt->execute([$id]);
    
    if ($esito) {
        $risultato["msg"] = "Prenotazione eliminata";
    } else {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Errore durante l'eliminazione";
    }
    
    return $risultato;
}

// ============================================
// FUNZIONI PER GLI INVITI
// ============================================

/**
 * Ottiene gli inviti pendenti di un utente
 * 
 * @param PDO $db Connessione al database
 * @param string $email Email dell'utente
 * @return array Risultato con gli inviti
 */
function getInvitiUtente($db, $email) {
    $risultato = ["status" => "ok", "msg" => "", "contenuto" => []];
    
    $sql = "SELECT inv.*, p.data_ora_inizio, p.durata, p.attivita, s.nome_sala, s.capienza,
                   r.nome as resp_nome, r.cognome as resp_cognome
            FROM invito inv
            JOIN prenotazione p ON inv.prenotazione_id = p.id
            JOIN sala s ON p.sala_id = s.id
            JOIN iscritto r ON p.responsabile_email = r.email
            WHERE inv.iscritto_email = ?
            AND p.data_ora_inizio >= NOW()
            ORDER BY p.data_ora_inizio ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$email]);
    $risultato["contenuto"] = $stmt->fetchAll();
    
    return $risultato;
}

/**
 * Risponde a un invito (accetta o rifiuta)
 * 
 * @param PDO $db Connessione al database
 * @param string $email Email dell'utente
 * @param int $prenotazione_id ID della prenotazione
 * @param string $risposta 'si' o 'no'
 * @param string $motivazione Motivazione (obbligatoria se rifiuto)
 * @return array Risultato dell'operazione
 */
function rispondiInvito($db, $email, $prenotazione_id, $risposta, $motivazione = null) {
    $risultato = ["status" => "ok", "msg" => "", "contenuto" => null];
    
    // Se rifiuto, la motivazione è obbligatoria
    if ($risposta === 'no' && empty($motivazione)) {
        $risultato["status"] = "ko";
        $risultato["msg"] = "La motivazione è obbligatoria per rifiutare";
        return $risultato;
    }
    
    // Se accetto, verifico capienza sala
    if ($risposta === 'si') {
        // Ottengo info sulla prenotazione e sala
        $check = $db->prepare("
            SELECT p.*, s.capienza,
                   (SELECT COUNT(*) FROM invito WHERE prenotazione_id = p.id AND risposta = 'si') as partecipanti
            FROM prenotazione p
            JOIN sala s ON p.sala_id = s.id
            WHERE p.id = ?
        ");
        $check->execute([$prenotazione_id]);
        $info = $check->fetch();
        
        if ($info && $info["partecipanti"] >= $info["capienza"]) {
            $risultato["status"] = "ko";
            $risultato["msg"] = "Capienza sala esaurita";
            return $risultato;
        }
        
        // Verifico sovrapposizioni con altri impegni dell'utente
        if ($info) {
            $fine_preno = date('Y-m-d H:i:s', strtotime($info["data_ora_inizio"]) + ($info["durata"] * 3600));
            
            $check_sovrap = $db->prepare("
                SELECT p.id FROM prenotazione p
                JOIN invito inv ON p.id = inv.prenotazione_id
                WHERE inv.iscritto_email = ?
                AND inv.risposta = 'si'
                AND p.id != ?
                AND (
                    (p.data_ora_inizio < ? AND DATE_ADD(p.data_ora_inizio, INTERVAL p.durata HOUR) > ?)
                )
            ");
            $check_sovrap->execute([$email, $prenotazione_id, $fine_preno, $info["data_ora_inizio"]]);
            
            if ($check_sovrap->fetch()) {
                $risultato["status"] = "ko";
                $risultato["msg"] = "Hai già un impegno in questo orario";
                return $risultato;
            }
        }
    }
    
    // Aggiorno l'invito
    $sql = "UPDATE invito SET risposta = ?, motivazione = ?, data_ora_risposta = NOW() 
            WHERE iscritto_email = ? AND prenotazione_id = ?";
    $stmt = $db->prepare($sql);
    $esito = $stmt->execute([$risposta, $motivazione, $email, $prenotazione_id]);
    
    // Aggiorno il contatore nella prenotazione
    if ($esito && $risposta === 'si') {
        $db->prepare("UPDATE prenotazione SET num_iscritti = num_iscritti + 1 WHERE id = ?")->execute([$prenotazione_id]);
    }
    
    if ($esito) {
        $risultato["msg"] = $risposta === 'si' ? "Invito accettato" : "Invito rifiutato";
    } else {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Errore durante la risposta";
    }
    
    return $risultato;
}

/**
 * Rimuove la partecipazione a una prenotazione
 * 
 * @param PDO $db Connessione al database
 * @param string $email Email dell'utente
 * @param int $prenotazione_id ID della prenotazione
 * @return array Risultato dell'operazione
 */
function rimuoviPartecipazione($db, $email, $prenotazione_id) {
    $risultato = ["status" => "ok", "msg" => "", "contenuto" => null];
    
    // Verifico che l'invito esista e sia accettato
    $check = $db->prepare("SELECT risposta FROM invito WHERE iscritto_email = ? AND prenotazione_id = ?");
    $check->execute([$email, $prenotazione_id]);
    $invito = $check->fetch();
    
    if (!$invito) {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Invito non trovato";
        return $risultato;
    }
    
    // Rimetto l'invito in attesa
    $sql = "UPDATE invito SET risposta = 'attesa', motivazione = NULL, data_ora_risposta = NULL 
            WHERE iscritto_email = ? AND prenotazione_id = ?";
    $stmt = $db->prepare($sql);
    $esito = $stmt->execute([$email, $prenotazione_id]);
    
    // Decremento il contatore se era accettato
    if ($esito && $invito["risposta"] === 'si') {
        $db->prepare("UPDATE prenotazione SET num_iscritti = GREATEST(0, num_iscritti - 1) WHERE id = ?")->execute([$prenotazione_id]);
    }
    
    if ($esito) {
        $risultato["msg"] = "Partecipazione rimossa";
    } else {
        $risultato["status"] = "ko";
        $risultato["msg"] = "Errore durante la rimozione";
    }
    
    return $risultato;
}

// ============================================
// FUNZIONI DI UTILITÀ
// ============================================

/**
 * Ottiene la lista delle sale disponibili
 */
function getSale($db) {
    $sql = "SELECT * FROM sala ORDER BY nome_settore, nome_sala";
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

/**
 * Ottiene la lista degli iscritti (per inviti)
 */
function getIscritti($db) {
    $sql = "SELECT email, nome, cognome, ruolo FROM iscritto ORDER BY cognome, nome";
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

/**
 * Verifica se l'utente è loggato
 */
function isLoggato() {
    return isset($_SESSION["user_email"]);
}

/**
 * Verifica se l'utente è un responsabile
 */
function isResponsabile() {
    return isset($_SESSION["user_ruolo"]) && $_SESSION["user_ruolo"] === "responsabile";
}

/**
 * Invia inviti per una prenotazione
 */
function inviaInviti($db, $prenotazione_id, $emails) {
    foreach ($emails as $email) {
        $sql = "INSERT IGNORE INTO invito (iscritto_email, prenotazione_id, risposta) VALUES (?, ?, 'attesa')";
        $db->prepare($sql)->execute([$email, $prenotazione_id]);
    }
}
?>
