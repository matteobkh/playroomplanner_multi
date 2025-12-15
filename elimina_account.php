<?php
/**
 * ELIMINA_ACCOUNT.PHP - Eliminazione account utente
 * 
 * Elimina l'account dell'utente loggato e tutti i dati associati.
 */

require_once "common/config.php";
require_once "common/funzioni.php";

// Controllo accesso
if (!isLoggato()) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$email = $_SESSION["user_email"];

// Elimino l'utente
$risultato = eliminaUtente($db, $email);

if ($risultato["status"] == "ok") {
    // Distruggo la sessione
    $_SESSION = [];
    session_destroy();
    
    // Redirect alla home con messaggio
    header("Location: index.php?account_eliminato=1");
    exit;
} else {
    // In caso di errore, torno al profilo
    header("Location: profilo.php?errore=" . urlencode($risultato["msg"]));
    exit;
}
?>
