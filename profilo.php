<?php
/**
 * PROFILO.PHP - Pagina del profilo utente
 * 
 * Mostra e permette di modificare i dati del profilo.
 */

require_once "common/config.php";
require_once "common/funzioni.php";

// Controllo accesso
if (!isLoggato()) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$errore = "";
$successo = "";

// Carico i dati dell'utente
$profilo = getProfiloUtente($db, $_SESSION["user_email"]);
$utente = $profilo["contenuto"];

// Gestisco la modifica del profilo
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dati = [
        "nome" => trim($_POST["nome"] ?? ""),
        "cognome" => trim($_POST["cognome"] ?? ""),
        "data_nascita" => trim($_POST["data_nascita"] ?? "")
    ];
    
    $risultato = modificaUtente($db, $_SESSION["user_email"], $dati);
    
    if ($risultato["status"] == "ok") {
        $successo = $risultato["msg"];
        // Aggiorno i dati in sessione
        $_SESSION["user_nome"] = $dati["nome"];
        $_SESSION["user_cognome"] = $dati["cognome"];
        // Ricarico il profilo
        $profilo = getProfiloUtente($db, $_SESSION["user_email"]);
        $utente = $profilo["contenuto"];
    } else {
        $errore = $risultato["msg"];
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<?php require "common/header.html"; ?>
<body>
    <?php require "common/nav.php"; ?>
    
    <main class="container content-wrapper">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-person"></i> Il Mio Profilo
                    </div>
                    <div class="card-body">
                        
                        <!-- Messaggi -->
                        <?php if ($errore): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($errore); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($successo): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($successo); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Form modifica profilo -->
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($utente["email"]); ?>" disabled>
                                <small class="text-muted">L'email non può essere modificata</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nome" class="form-label">Nome</label>
                                    <input type="text" class="form-control" id="nome" name="nome" 
                                           value="<?php echo htmlspecialchars($utente["nome"]); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="cognome" class="form-label">Cognome</label>
                                    <input type="text" class="form-control" id="cognome" name="cognome" 
                                           value="<?php echo htmlspecialchars($utente["cognome"]); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="data_nascita" class="form-label">Data di Nascita</label>
                                <input type="date" class="form-control" id="data_nascita" name="data_nascita" 
                                       value="<?php echo htmlspecialchars($utente["data_nascita"]); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Ruolo</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo ucfirst(htmlspecialchars($utente["ruolo"])); ?>" disabled>
                            </div>
                            
                            <?php if ($utente["ruolo"] == "responsabile" && $utente["data_inizio_responsabile"]): ?>
                                <div class="mb-3">
                                    <label class="form-label">Responsabile dal</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo date('d/m/Y', strtotime($utente["data_inizio_responsabile"])); ?>" disabled>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Salva Modifiche
                                </button>
                            </div>
                        </form>
                        
                        <!-- Sezione eliminazione account -->
                        <hr class="my-4">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <i class="bi bi-exclamation-triangle"></i> Zona Pericolosa
                            </div>
                            <div class="card-body">
                                <p class="card-text">
                                    <strong>Elimina il tuo account</strong><br>
                                    <small class="text-muted">
                                        Questa azione è irreversibile. Tutti i tuoi dati, inviti e partecipazioni verranno eliminati.
                                    </small>
                                </p>
                                <a href="elimina_account.php" class="btn btn-outline-danger"
                                   onclick="return confirm('Sei ASSOLUTAMENTE sicuro di voler eliminare il tuo account? Questa azione è IRREVERSIBILE!')">
                                    <i class="bi bi-trash"></i> Elimina Account
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php require "common/footer.html"; ?>
</body>
</html>
