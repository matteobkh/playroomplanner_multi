<?php
/**
 * INVITI.PHP - Gestione inviti ricevuti
 * 
 * Mostra gli inviti alle prenotazioni e permette di accettarli o rifiutarli.
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

// Gestisco la risposta all'invito
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prenotazione_id = (int)($_POST["prenotazione_id"] ?? 0);
    $risposta = $_POST["risposta"] ?? "";
    $motivazione = trim($_POST["motivazione"] ?? "");
    
    if ($prenotazione_id && in_array($risposta, ['si', 'no'])) {
        $risultato = rispondiInvito($db, $_SESSION["user_email"], $prenotazione_id, $risposta, $motivazione);
        
        if ($risultato["status"] == "ok") {
            $successo = $risultato["msg"];
        } else {
            $errore = $risultato["msg"];
        }
    }
}

// Gestisco la rimozione partecipazione
if (isset($_GET["rimuovi"])) {
    $prenotazione_id = (int)$_GET["rimuovi"];
    $risultato = rimuoviPartecipazione($db, $_SESSION["user_email"], $prenotazione_id);
    
    if ($risultato["status"] == "ok") {
        $successo = $risultato["msg"];
    } else {
        $errore = $risultato["msg"];
    }
}

// Carico gli inviti dell'utente
$inviti = getInvitiUtente($db, $_SESSION["user_email"]);
?>
<!DOCTYPE html>
<html lang="it">
<?php require "common/header.html"; ?>
<body>
    <?php require "common/nav.php"; ?>
    
    <main class="container content-wrapper">
        <h2 class="mb-4">
            <i class="bi bi-envelope text-success"></i> I Miei Inviti
        </h2>
        
        <!-- Messaggi -->
        <?php if ($errore): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($errore); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($successo): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($successo); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($inviti["contenuto"])): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Non hai inviti pendenti.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($inviti["contenuto"] as $invito): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>
                                    <i class="bi bi-calendar-event"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($invito["data_ora_inizio"])); ?>
                                </span>
                                <span class="badge 
                                    <?php 
                                    if ($invito["risposta"] == "si") echo "bg-success";
                                    elseif ($invito["risposta"] == "no") echo "bg-danger";
                                    else echo "bg-warning text-dark";
                                    ?>">
                                    <?php 
                                    if ($invito["risposta"] == "si") echo "Accettato";
                                    elseif ($invito["risposta"] == "no") echo "Rifiutato";
                                    else echo "In attesa";
                                    ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($invito["attivita"]); ?></h5>
                                <p class="card-text">
                                    <i class="bi bi-building"></i> 
                                    <strong>Sala:</strong> <?php echo htmlspecialchars($invito["nome_sala"]); ?>
                                    <br>
                                    <i class="bi bi-clock"></i> 
                                    <strong>Durata:</strong> <?php echo $invito["durata"]; ?> ore
                                    <br>
                                    <i class="bi bi-person"></i> 
                                    <strong>Organizzato da:</strong> 
                                    <?php echo htmlspecialchars($invito["resp_nome"] . " " . $invito["resp_cognome"]); ?>
                                    <br>
                                    <i class="bi bi-people"></i>
                                    <strong>Capienza:</strong> <?php echo $invito["capienza"]; ?> posti
                                </p>
                                
                                <?php if ($invito["risposta"] == "attesa"): ?>
                                    <!-- Form per rispondere all'invito -->
                                    <hr>
                                    <form method="POST" action="" class="mt-3">
                                        <input type="hidden" name="prenotazione_id" value="<?php echo $invito["prenotazione_id"]; ?>">
                                        
                                        <div class="mb-3" id="motivazione-<?php echo $invito["prenotazione_id"]; ?>" style="display:none;">
                                            <label class="form-label">Motivazione rifiuto</label>
                                            <textarea class="form-control" name="motivazione" rows="2" 
                                                      placeholder="Inserisci la motivazione..."></textarea>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button type="submit" name="risposta" value="si" class="btn btn-success btn-sm">
                                                <i class="bi bi-check-lg"></i> Accetta
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" 
                                                    onclick="mostraMotivazione(<?php echo $invito["prenotazione_id"]; ?>)">
                                                <i class="bi bi-x-lg"></i> Rifiuta
                                            </button>
                                            <button type="submit" name="risposta" value="no" 
                                                    id="btn-rifiuta-<?php echo $invito["prenotazione_id"]; ?>" 
                                                    class="btn btn-danger btn-sm" style="display:none;">
                                                Conferma rifiuto
                                            </button>
                                        </div>
                                    </form>
                                <?php elseif ($invito["risposta"] == "si"): ?>
                                    <!-- Opzione per rimuoversi -->
                                    <hr>
                                    <a href="?rimuovi=<?php echo $invito["prenotazione_id"]; ?>" 
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Sei sicuro di volerti rimuovere da questa prenotazione?')">
                                        <i class="bi bi-x-circle"></i> Rimuovi partecipazione
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <?php require "common/footer.html"; ?>
    
    <script>
    // Funzione per mostrare il campo motivazione quando si rifiuta
    function mostraMotivazione(id) {
        document.getElementById('motivazione-' + id).style.display = 'block';
        document.getElementById('btn-rifiuta-' + id).style.display = 'inline-block';
    }
    </script>
</body>
</html>
