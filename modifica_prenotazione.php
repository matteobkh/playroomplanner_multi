<?php
/**
 * MODIFICA_PRENOTAZIONE.PHP - Modifica prenotazione esistente
 * 
 * Permette ai responsabili di modificare i dati di una prenotazione.
 */

require_once "common/config.php";
require_once "common/funzioni.php";

// Controllo accesso: solo responsabili
if (!isLoggato()) {
    header("Location: login.php");
    exit;
}

if (!isResponsabile()) {
    header("Location: index.php");
    exit;
}

$db = getDB();
$errore = "";
$successo = "";

// Verifico che sia specificato un ID
if (!isset($_GET["id"])) {
    header("Location: gestione_prenotazioni.php");
    exit;
}

$id = (int)$_GET["id"];

// Carico la prenotazione
$sql = "SELECT p.*, s.nome_sala 
        FROM prenotazione p
        JOIN sala s ON p.sala_id = s.id
        WHERE p.id = ? AND p.responsabile_email = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$id, $_SESSION["user_email"]]);
$prenotazione = $stmt->fetch();

// Se non esiste o non appartiene al responsabile, redirect
if (!$prenotazione) {
    header("Location: gestione_prenotazioni.php");
    exit;
}

// Carico le sale per il form
$sale = getSale($db);

// Gestisco la modifica
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dati = [
        "data_ora_inizio" => $_POST["data"] . " " . $_POST["ora"] . ":00",
        "durata" => (int)$_POST["durata"],
        "attivita" => trim($_POST["attivita"] ?? "")
    ];
    
    // Validazione ora intera (9-23)
    $ora = (int)$_POST["ora"];
    if ($ora < 9 || $ora > 23) {
        $errore = "Orario prenotazioni: 09:00 - 23:00";
    } else {
        $risultato = modificaPrenotazione($db, $id, $dati);
        
        if ($risultato["status"] == "ok") {
            $successo = $risultato["msg"];
            // Ricarico la prenotazione aggiornata
            $stmt->execute([$id, $_SESSION["user_email"]]);
            $prenotazione = $stmt->fetch();
        } else {
            $errore = $risultato["msg"];
        }
    }
}

// Estraggo data e ora dalla prenotazione
$data_prenotazione = date('Y-m-d', strtotime($prenotazione["data_ora_inizio"]));
$ora_prenotazione = date('H', strtotime($prenotazione["data_ora_inizio"]));
?>
<!DOCTYPE html>
<html lang="it">
<?php require "common/header.html"; ?>
<body>
    <?php require "common/nav.php"; ?>
    
    <main class="container content-wrapper">
        <h2 class="mb-4">
            <i class="bi bi-pencil text-warning"></i> Modifica Prenotazione #<?php echo $id; ?>
        </h2>
        
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
        
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar-event"></i> Dati Prenotazione
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <!-- Data -->
                        <div class="col-md-4 mb-3">
                            <label for="data" class="form-label">Data</label>
                            <input type="date" class="form-control" id="data" name="data" 
                                   value="<?php echo $data_prenotazione; ?>" required>
                        </div>
                        
                        <!-- Ora -->
                        <div class="col-md-4 mb-3">
                            <label for="ora" class="form-label">Ora inizio (9-23)</label>
                            <select class="form-select" id="ora" name="ora" required>
                                <?php for ($h = 9; $h <= 23; $h++): ?>
                                    <option value="<?php echo sprintf('%02d', $h); ?>" 
                                            <?php echo ($h == $ora_prenotazione) ? "selected" : ""; ?>>
                                        <?php echo sprintf('%02d', $h); ?>:00
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <!-- Durata -->
                        <div class="col-md-4 mb-3">
                            <label for="durata" class="form-label">Durata (ore)</label>
                            <select class="form-select" id="durata" name="durata" required>
                                <?php for ($d = 1; $d <= 8; $d++): ?>
                                    <option value="<?php echo $d; ?>" 
                                            <?php echo ($d == $prenotazione["durata"]) ? "selected" : ""; ?>>
                                        <?php echo $d; ?> or<?php echo $d > 1 ? 'e' : 'a'; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Sala (non modificabile) -->
                    <div class="mb-3">
                        <label class="form-label">Sala</label>
                        <input type="text" class="form-control" 
                               value="<?php echo htmlspecialchars($prenotazione["nome_sala"]); ?>" disabled>
                        <small class="text-muted">La sala non può essere modificata. Per cambiarla, elimina e ricrea la prenotazione.</small>
                    </div>
                    
                    <!-- Attività -->
                    <div class="mb-3">
                        <label for="attivita" class="form-label">Attività</label>
                        <input type="text" class="form-control" id="attivita" name="attivita" 
                               value="<?php echo htmlspecialchars($prenotazione["attivita"]); ?>" required>
                    </div>
                    
                    <!-- Info aggiuntive -->
                    <div class="alert alert-light">
                        <small>
                            <strong>Partecipanti confermati:</strong> <?php echo $prenotazione["num_iscritti"]; ?><br>
                            <strong>Criterio invito:</strong> <?php echo ucfirst($prenotazione["criterio"]); ?><br>
                            <strong>Creata il:</strong> <?php echo date('d/m/Y H:i', strtotime($prenotazione["created_at"])); ?>
                        </small>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="gestione_prenotazioni.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Torna alla lista
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save"></i> Salva Modifiche
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php require "common/footer.html"; ?>
</body>
</html>
