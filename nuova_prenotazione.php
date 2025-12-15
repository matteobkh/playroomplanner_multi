<?php
/**
 * NUOVA_PRENOTAZIONE.PHP - Creazione nuova prenotazione
 * 
 * Solo per utenti con ruolo "responsabile".
 * Permette di creare una prenotazione e inviare inviti.
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

// Carico sale e iscritti per i form
$sale = getSale($db);
$iscritti = getIscritti($db);

// Gestisco la creazione della prenotazione
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dati = [
        "data_ora_inizio" => $_POST["data"] . " " . $_POST["ora"] . ":00",
        "durata" => (int)$_POST["durata"],
        "attivita" => trim($_POST["attivita"] ?? ""),
        "sala_id" => (int)$_POST["sala_id"],
        "criterio" => $_POST["criterio"] ?? "selezione",
        "responsabile_email" => $_SESSION["user_email"]
    ];
    
    $risultato = creaPrenotazione($db, $dati);
    
    if ($risultato["status"] == "ok") {
        // Se sono stati selezionati invitati, li inserisco
        if (isset($_POST["invitati"]) && is_array($_POST["invitati"])) {
            inviaInviti($db, $risultato["contenuto"]["id"], $_POST["invitati"]);
        }
        
        $successo = $risultato["msg"];
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
        <h2 class="mb-4">
            <i class="bi bi-plus-circle text-warning"></i> Nuova Prenotazione
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
                <a href="sale.php" class="alert-link">Vai alle sale</a>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar-plus"></i> Dati Prenotazione
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <!-- Data -->
                        <div class="col-md-4 mb-3">
                            <label for="data" class="form-label">Data</label>
                            <input type="date" class="form-control" id="data" name="data" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <!-- Ora -->
                        <div class="col-md-4 mb-3">
                            <label for="ora" class="form-label">Ora inizio (9-23)</label>
                            <select class="form-select" id="ora" name="ora" required>
                                <?php for ($h = 9; $h <= 23; $h++): ?>
                                    <option value="<?php echo sprintf('%02d', $h); ?>:00">
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
                                    <option value="<?php echo $d; ?>"><?php echo $d; ?> or<?php echo $d > 1 ? 'e' : 'a'; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Sala -->
                        <div class="col-md-6 mb-3">
                            <label for="sala_id" class="form-label">Sala</label>
                            <select class="form-select" id="sala_id" name="sala_id" required>
                                <option value="">-- Seleziona una sala --</option>
                                <?php foreach ($sale as $sala): ?>
                                    <option value="<?php echo $sala["id"]; ?>">
                                        <?php echo htmlspecialchars($sala["nome_sala"]); ?> 
                                        (<?php echo htmlspecialchars($sala["nome_settore"]); ?>) 
                                        - Capienza: <?php echo $sala["capienza"]; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Criterio invito -->
                        <div class="col-md-6 mb-3">
                            <label for="criterio" class="form-label">Criterio Invito</label>
                            <select class="form-select" id="criterio" name="criterio">
                                <option value="selezione">Selezione manuale</option>
                                <option value="tutti">Tutti gli iscritti</option>
                                <option value="settore">Stesso settore</option>
                                <option value="ruolo">Stesso ruolo</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Attività -->
                    <div class="mb-3">
                        <label for="attivita" class="form-label">Attività</label>
                        <input type="text" class="form-control" id="attivita" name="attivita" 
                               placeholder="Es: Prove musicali, Lezione di ballo..." required>
                    </div>
                    
                    <!-- Selezione invitati -->
                    <div class="mb-3">
                        <label class="form-label">Invita partecipanti</label>
                        <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                            <?php foreach ($iscritti as $iscritto): ?>
                                <?php if ($iscritto["email"] != $_SESSION["user_email"]): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="invitati[]" value="<?php echo htmlspecialchars($iscritto["email"]); ?>"
                                               id="inv-<?php echo md5($iscritto["email"]); ?>">
                                        <label class="form-check-label" for="inv-<?php echo md5($iscritto["email"]); ?>">
                                            <?php echo htmlspecialchars($iscritto["nome"] . " " . $iscritto["cognome"]); ?>
                                            <small class="text-muted">(<?php echo htmlspecialchars($iscritto["ruolo"]); ?>)</small>
                                        </label>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Annulla
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-plus-circle"></i> Crea Prenotazione
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php require "common/footer.html"; ?>
</body>
</html>
