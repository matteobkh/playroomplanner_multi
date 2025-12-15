<?php
/**
 * CONFIG.PHP - Configurazione database e sessione
 * 
 * Questo file contiene:
 * - Parametri di connessione al database MySQL
 * - Avvio automatico della sessione
 * - Funzione per ottenere la connessione PDO
 * 
 * IMPORTANTE: Modificare i parametri se il database è su un server diverso
 */

// Avvio sessione (deve essere chiamato prima di qualsiasi output HTML)
session_start();

// Parametri di connessione al database
define('DB_HOST', 'localhost');      // Server MySQL (localhost per XAMPP)
define('DB_NAME', 'playroomplanner'); // Nome del database
define('DB_USER', 'root');            // Username MySQL (root di default in XAMPP)
define('DB_PASS', '');                // Password MySQL (vuota di default in XAMPP)

/**
 * Ottiene una connessione PDO al database
 * 
 * @return PDO|null Oggetto PDO se connessione riuscita, null altrimenti
 */
function getDB() {
    try {
        // Creo la connessione PDO con gestione errori e charset UTF-8
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Lancia eccezioni su errori
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC  // Array associativi di default
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        // In caso di errore, ritorno null (gestito dal chiamante)
        return null;
    }
}
?>
