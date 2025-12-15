<?php
/**
 * API.PHP - API RESTful centralizzata
 * 
 * Questo file gestisce tutte le chiamate API del sistema.
 * Le risposte sono sempre in formato JSON.
 * 
 * Endpoints disponibili:
 * - POST   /api.php?action=login         - Login utente
 * - POST   /api.php?action=register      - Registrazione utente
 * - GET    /api.php?action=user          - Profilo utente loggato
 * - POST   /api.php?action=logout        - Logout utente
 * - GET    /api.php?action=prenotazioni  - Lista prenotazioni (con filtri)
 * - POST   /api.php?action=prenotazioni  - Crea prenotazione (solo responsabili)
 * - PUT    /api.php?action=prenotazioni  - Modifica prenotazione
 * - DELETE /api.php?action=prenotazioni  - Elimina prenotazione
 * - POST   /api.php?action=inviti        - Rispondi a invito
 * - GET    /api.php?action=sale          - Lista sale
 * - GET    /api.php?action=iscritti      - Lista iscritti
 * - GET    /api.php?action=impegni       - Impegni utente
 */

// Includo configurazione e funzioni
require_once "common/config.php";
require_once "common/funzioni.php";

// Imposto header per risposte JSON
header("Content-Type: application/json; charset=UTF-8");

// Connessione al database
$db = getDB();

// Se la connessione fallisce, ritorno errore
if (!$db) {
    echo json_encode(["ok" => false, "error" => "Errore di connessione al database"]);
    exit;
}

// Ottengo l'azione richiesta
$action = $_GET["action"] ?? "";
$method = $_SERVER["REQUEST_METHOD"];

// Leggo i dati dal body per richieste POST/PUT
$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    $input = $_POST;
}

// Funzione helper per le risposte JSON
function rispondi($ok, $data = null, $error = null) {
    $risposta = ["ok" => $ok];
    if ($data !== null) $risposta["data"] = $data;
    if ($error !== null) $risposta["error"] = $error;
    echo json_encode($risposta);
    exit;
}

// Router principale basato sull'azione
switch ($action) {
    
    // ========================================
    // LOGIN
    // ========================================
    case "login":
        if ($method !== "POST") {
            rispondi(false, null, "Metodo non consentito");
        }
        
        $email = trim($input["email"] ?? "");
        $password = trim($input["password"] ?? "");
        
        if (empty($email) || empty($password)) {
            rispondi(false, null, "Email e password richieste");
        }
        
        $risultato = login($db, $email, $password);
        
        if ($risultato["status"] == "ok") {
            // Ritorno i dati dell'utente (senza password)
            unset($risultato["contenuto"]["password"]);
            rispondi(true, $risultato["contenuto"]);
        } else {
            rispondi(false, null, $risultato["msg"]);
        }
        break;
    
    // ========================================
    // REGISTRAZIONE
    // ========================================
    case "register":
        if ($method !== "POST") {
            rispondi(false, null, "Metodo non consentito");
        }
        
        $dati = [
            "email" => trim($input["email"] ?? ""),
            "nome" => trim($input["nome"] ?? ""),
            "cognome" => trim($input["cognome"] ?? ""),
            "password" => trim($input["password"] ?? ""),
            "data_nascita" => trim($input["data_nascita"] ?? ""),
            "ruolo" => "allievo"
        ];
        
        $risultato = registraUtente($db, $dati);
        
        if ($risultato["status"] == "ok") {
            rispondi(true, ["message" => $risultato["msg"]]);
        } else {
            rispondi(false, null, $risultato["msg"]);
        }
        break;
    
    // ========================================
    // PROFILO UTENTE LOGGATO
    // ========================================
    case "user":
        if ($method !== "GET") {
            rispondi(false, null, "Metodo non consentito");
        }
        
        if (!isLoggato()) {
            rispondi(false, null, "Non autenticato");
        }
        
        $risultato = getProfiloUtente($db, $_SESSION["user_email"]);
        
        if ($risultato["status"] == "ok") {
            rispondi(true, $risultato["contenuto"]);
        } else {
            rispondi(false, null, $risultato["msg"]);
        }
        break;
    
    // ========================================
    // LOGOUT
    // ========================================
    case "logout":
        if ($method !== "POST") {
            rispondi(false, null, "Metodo non consentito");
        }
        
        $_SESSION = [];
        session_destroy();
        rispondi(true, ["message" => "Logout effettuato"]);
        break;
    
    // ========================================
    // PRENOTAZIONI
    // ========================================
    case "prenotazioni":
        switch ($method) {
            case "GET":
                // Parametri opzionali: sala_id, week (data di riferimento)
                $sala_id = isset($_GET["sala_id"]) ? (int)$_GET["sala_id"] : null;
                $week = isset($_GET["week"]) ? $_GET["week"] : date("Y-m-d");
                
                $risultato = getPrenotazioniSettimana($db, $sala_id, $week);
                rispondi(true, $risultato["contenuto"]);
                break;
                
            case "POST":
                // Solo responsabili possono creare prenotazioni
                if (!isLoggato()) {
                    rispondi(false, null, "Non autenticato");
                }
                if (!isResponsabile()) {
                    rispondi(false, null, "Solo i responsabili possono creare prenotazioni");
                }
                
                $dati = [
                    "data_ora_inizio" => $input["data_ora_inizio"] ?? "",
                    "durata" => (int)($input["durata"] ?? 0),
                    "attivita" => trim($input["attivita"] ?? ""),
                    "sala_id" => (int)($input["sala_id"] ?? 0),
                    "criterio" => $input["criterio"] ?? "selezione",
                    "responsabile_email" => $_SESSION["user_email"]
                ];
                
                $risultato = creaPrenotazione($db, $dati);
                
                if ($risultato["status"] == "ok") {
                    // Se ci sono invitati, li inserisco
                    if (isset($input["invitati"]) && is_array($input["invitati"])) {
                        inviaInviti($db, $risultato["contenuto"]["id"], $input["invitati"]);
                    }
                    rispondi(true, $risultato["contenuto"]);
                } else {
                    rispondi(false, null, $risultato["msg"]);
                }
                break;
                
            case "PUT":
                if (!isLoggato()) {
                    rispondi(false, null, "Non autenticato");
                }
                
                $id = (int)($input["id"] ?? 0);
                $dati = [
                    "data_ora_inizio" => $input["data_ora_inizio"] ?? "",
                    "durata" => (int)($input["durata"] ?? 0),
                    "attivita" => trim($input["attivita"] ?? "")
                ];
                
                $risultato = modificaPrenotazione($db, $id, $dati);
                
                if ($risultato["status"] == "ok") {
                    rispondi(true, ["message" => $risultato["msg"]]);
                } else {
                    rispondi(false, null, $risultato["msg"]);
                }
                break;
                
            case "DELETE":
                if (!isLoggato()) {
                    rispondi(false, null, "Non autenticato");
                }
                
                $id = (int)($_GET["id"] ?? 0);
                $risultato = eliminaPrenotazione($db, $id);
                
                if ($risultato["status"] == "ok") {
                    rispondi(true, ["message" => $risultato["msg"]]);
                } else {
                    rispondi(false, null, $risultato["msg"]);
                }
                break;
                
            default:
                rispondi(false, null, "Metodo non consentito");
        }
        break;
    
    // ========================================
    // INVITI - Risposta
    // ========================================
    case "inviti":
        if ($method === "GET") {
            if (!isLoggato()) {
                rispondi(false, null, "Non autenticato");
            }
            
            $risultato = getInvitiUtente($db, $_SESSION["user_email"]);
            rispondi(true, $risultato["contenuto"]);
        }
        
        if ($method !== "POST") {
            rispondi(false, null, "Metodo non consentito");
        }
        
        if (!isLoggato()) {
            rispondi(false, null, "Non autenticato");
        }
        
        $prenotazione_id = (int)($input["prenotazione_id"] ?? 0);
        $risposta = $input["risposta"] ?? "";
        $motivazione = trim($input["motivazione"] ?? "");
        
        if (!in_array($risposta, ['si', 'no'])) {
            rispondi(false, null, "Risposta non valida (si/no)");
        }
        
        $risultato = rispondiInvito($db, $_SESSION["user_email"], $prenotazione_id, $risposta, $motivazione);
        
        if ($risultato["status"] == "ok") {
            rispondi(true, ["message" => $risultato["msg"]]);
        } else {
            rispondi(false, null, $risultato["msg"]);
        }
        break;
    
    // ========================================
    // SALE - Lista sale disponibili
    // ========================================
    case "sale":
        if ($method !== "GET") {
            rispondi(false, null, "Metodo non consentito");
        }
        
        $sale = getSale($db);
        rispondi(true, $sale);
        break;
    
    // ========================================
    // ISCRITTI - Lista iscritti
    // ========================================
    case "iscritti":
        if ($method !== "GET") {
            rispondi(false, null, "Metodo non consentito");
        }
        
        if (!isLoggato()) {
            rispondi(false, null, "Non autenticato");
        }
        
        $iscritti = getIscritti($db);
        rispondi(true, $iscritti);
        break;
    
    // ========================================
    // IMPEGNI - Impegni settimanali utente
    // ========================================
    case "impegni":
        if ($method !== "GET") {
            rispondi(false, null, "Metodo non consentito");
        }
        
        if (!isLoggato()) {
            rispondi(false, null, "Non autenticato");
        }
        
        $week = isset($_GET["week"]) ? $_GET["week"] : date("Y-m-d");
        $risultato = getImpegniSettimana($db, $_SESSION["user_email"], $week);
        rispondi(true, $risultato["contenuto"]);
        break;
    
    // ========================================
    // UTENTE - CRUD
    // ========================================
    case "utente":
        switch ($method) {
            case "PUT":
                if (!isLoggato()) {
                    rispondi(false, null, "Non autenticato");
                }
                
                $dati = [
                    "nome" => trim($input["nome"] ?? ""),
                    "cognome" => trim($input["cognome"] ?? ""),
                    "data_nascita" => trim($input["data_nascita"] ?? "")
                ];
                
                $risultato = modificaUtente($db, $_SESSION["user_email"], $dati);
                
                if ($risultato["status"] == "ok") {
                    $_SESSION["user_nome"] = $dati["nome"];
                    $_SESSION["user_cognome"] = $dati["cognome"];
                    rispondi(true, ["message" => $risultato["msg"]]);
                } else {
                    rispondi(false, null, $risultato["msg"]);
                }
                break;
                
            case "DELETE":
                if (!isLoggato()) {
                    rispondi(false, null, "Non autenticato");
                }
                
                $risultato = eliminaUtente($db, $_SESSION["user_email"]);
                
                if ($risultato["status"] == "ok") {
                    $_SESSION = [];
                    session_destroy();
                    rispondi(true, ["message" => $risultato["msg"]]);
                } else {
                    rispondi(false, null, $risultato["msg"]);
                }
                break;
                
            default:
                rispondi(false, null, "Metodo non consentito");
        }
        break;
    
    // ========================================
    // AZIONE NON RICONOSCIUTA
    // ========================================
    default:
        rispondi(false, null, "Azione non riconosciuta. Azioni disponibili: login, register, user, logout, prenotazioni, inviti, sale, iscritti, impegni, utente");
}
?>
