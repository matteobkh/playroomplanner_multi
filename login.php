<?php
/**
 * LOGIN.PHP - Pagina di autenticazione
 * 
 * Permette agli utenti di effettuare il login al sistema.
 * Se già loggato, reindirizza alla home page.
 */

require_once "common/config.php";
require_once "common/funzioni.php";

// Se già loggato, vai alla home
if (isLoggato()) {
    header("Location: index.php");
    exit;
}

$errore = "";
$successo = "";

// Gestisco il form di login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    
    // Connessione al database
    $db = getDB();
    
    if ($db) {
        // Tento il login
        $risultato = login($db, $email, $password);
        
        if ($risultato["status"] == "ok") {
            // Login riuscito: redirect alla home
            header("Location: index.php");
            exit;
        } else {
            $errore = $risultato["msg"];
        }
    } else {
        $errore = "Errore di connessione al database";
    }
}

// Messaggio dalla registrazione
if (isset($_GET["registrato"])) {
    $successo = "Registrazione completata! Ora puoi accedere.";
}
?>
<!DOCTYPE html>
<html lang="it">
<?php require "common/header.html"; ?>
<body>
    <?php require "common/nav.php"; ?>
    
    <main class="container content-wrapper">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card auth-card">
                    <div class="card-header text-center">
                        <i class="bi bi-box-arrow-in-right"></i> Accedi al Sistema
                    </div>
                    <div class="card-body">
                        
                        <!-- Messaggi di errore o successo -->
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
                        
                        <!-- Form di login -->
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="tuaemail@esempio.it" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="La tua password" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right"></i> Accedi
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <p class="text-center mb-0">
                            Non hai un account? 
                            <a href="registrazione.php">Registrati qui</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php require "common/footer.html"; ?>
</body>
</html>
