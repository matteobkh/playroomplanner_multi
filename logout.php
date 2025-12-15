<?php
/**
 * LOGOUT.PHP - Script di logout
 * 
 * Distrugge la sessione e reindirizza alla home page.
 */

require_once "common/config.php";

// Distruggo tutte le variabili di sessione
$_SESSION = [];

// Distruggo la sessione
session_destroy();

// Redirect alla home
header("Location: index.php");
exit;
?>
