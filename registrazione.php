<?php
/**
 * REGISTRAZIONE.PHP - Pagina di registrazione nuovo utente
 * 
 * Permette ai nuovi utenti di registrarsi al sistema.
 * Dopo la registrazione, reindirizza al login.
 */

require_once "common/config.php";
require_once "common/funzioni.php";

// Se già loggato, vai alla home
if (isLoggato()) {
    header("Location: index.php");
    exit;
}

$errore = "";

// Gestisco il form di registrazione
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Raccolgo i dati dal form
    $dati = [
        "email" => trim($_POST["email"] ?? ""),
        "nome" => trim($_POST["nome"] ?? ""),
        "cognome" => trim($_POST["cognome"] ?? ""),
        "password" => trim($_POST["password"] ?? ""),
        "data_nascita" => trim($_POST["data_nascita"] ?? ""),
        "ruolo" => "allievo"  // Ruolo di default per i nuovi utenti
    ];
    
    // Verifico che le password corrispondano
    $conferma_password = trim($_POST["conferma_password"] ?? "");
    if ($dati["password"] !== $conferma_password) {
        $errore = "Le password non corrispondono";
    } else {
        // Connessione al database
        $db = getDB();
        
        if ($db) {
            // Tento la registrazione
            $risultato = registraUtente($db, $dati);
            
            if ($risultato["status"] == "ok") {
                // Registrazione riuscita: redirect al login
                header("Location: login.php?registrato=1");
                exit;
            } else {
                $errore = $risultato["msg"];
            }
        } else {
            $errore = "Errore di connessione al database";
        }
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
                    <div class="card-header text-center">
                        <i class="bi bi-person-plus"></i> Registrati
                    </div>
                    <div class="card-body">
                        
                        <!-- Messaggio di errore -->
                        <?php if ($errore): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($errore); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Form di registrazione -->
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nome" class="form-label">Nome</label>
                                    <input type="text" class="form-control" id="nome" name="nome" 
                                           placeholder="Il tuo nome" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="cognome" class="form-label">Cognome</label>
                                    <input type="text" class="form-control" id="cognome" name="cognome" 
                                           placeholder="Il tuo cognome" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="tuaemail@esempio.it" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="data_nascita" class="form-label">Data di Nascita</label>
                                <input type="date" class="form-control" id="data_nascita" name="data_nascita" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Scegli una password" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="conferma_password" class="form-label">Conferma Password</label>
                                    <input type="password" class="form-control" id="conferma_password" 
                                           name="conferma_password" placeholder="Ripeti la password" required>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-person-plus"></i> Registrati
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <p class="text-center mb-0">
                            Hai già un account? 
                            <a href="login.php">Accedi qui</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php require "common/footer.html"; ?>
</body>
</html>
