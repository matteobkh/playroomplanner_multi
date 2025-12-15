<?php
/**
 * SALE.PHP - Visualizzazione prenotazioni delle sale prove
 * 
 * Mostra le prenotazioni per sala per una settimana specifica.
 * Permette di navigare tra le settimane.
 */

require_once "common/config.php";
require_once "common/funzioni.php";

// Controllo accesso
if (!isLoggato()) {
    header("Location: login.php");
    exit;
}

$db = getDB();

// Data di riferimento (oggi se non specificata)
$data_rif = isset($_GET["data"]) ? $_GET["data"] : date("Y-m-d");

// Sala selezionata (tutte se non specificata)
$sala_id = isset($_GET["sala_id"]) ? (int)$_GET["sala_id"] : null;

// Calcolo inizio e fine settimana
$data = new DateTime($data_rif);
$giorno_settimana = $data->format('N');
$inizio_settimana = clone $data;
$inizio_settimana->modify('-' . ($giorno_settimana - 1) . ' days');
$fine_settimana = clone $inizio_settimana;
$fine_settimana->modify('+6 days');

// Carico le sale disponibili
$sale = getSale($db);

// Carico le prenotazioni della settimana
$prenotazioni = getPrenotazioniSettimana($db, $sala_id, $data_rif);
?>
<!DOCTYPE html>
<html lang="it">
<?php require "common/header.html"; ?>
<body>
    <?php require "common/nav.php"; ?>
    
    <main class="container content-wrapper">
        <h2 class="mb-4">
            <i class="bi bi-building text-primary"></i> Sale Prove
        </h2>
        
        <!-- Selettore settimana e sala -->
        <div class="week-selector">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label class="form-label">Seleziona una data della settimana</label>
                    <input type="date" class="form-control" name="data" 
                           value="<?php echo htmlspecialchars($data_rif); ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Filtra per sala</label>
                    <select class="form-select" name="sala_id">
                        <option value="">Tutte le sale</option>
                        <?php foreach ($sale as $sala): ?>
                            <option value="<?php echo $sala["id"]; ?>" 
                                    <?php echo ($sala_id == $sala["id"]) ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($sala["nome_sala"] . " (" . $sala["nome_settore"] . ")"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">
                        <i class="bi bi-search"></i> Visualizza
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Navigazione settimana -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <?php 
            $prec = clone $inizio_settimana;
            $prec->modify('-7 days');
            $succ = clone $inizio_settimana;
            $succ->modify('+7 days');
            ?>
            <a href="?data=<?php echo $prec->format('Y-m-d'); ?>&sala_id=<?php echo $sala_id; ?>" class="btn btn-outline-primary">
                <i class="bi bi-chevron-left"></i> Settimana precedente
            </a>
            
            <h5 class="mb-0">
                <?php echo $inizio_settimana->format('d/m/Y'); ?> - <?php echo $fine_settimana->format('d/m/Y'); ?>
            </h5>
            
            <a href="?data=<?php echo $succ->format('Y-m-d'); ?>&sala_id=<?php echo $sala_id; ?>" class="btn btn-outline-primary">
                Settimana successiva <i class="bi bi-chevron-right"></i>
            </a>
        </div>
        
        <!-- Lista prenotazioni -->
        <?php if (empty($prenotazioni["contenuto"])): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Nessuna prenotazione in questa settimana.
            </div>
        <?php else: ?>
            <div class="row">
                <?php 
                // Raggruppo le prenotazioni per giorno
                $per_giorno = [];
                foreach ($prenotazioni["contenuto"] as $p) {
                    $giorno = date('Y-m-d', strtotime($p["data_ora_inizio"]));
                    $per_giorno[$giorno][] = $p;
                }
                
                // Mostro ogni giorno della settimana
                $giorno_corrente = clone $inizio_settimana;
                for ($i = 0; $i < 7; $i++):
                    $data_giorno = $giorno_corrente->format('Y-m-d');
                    $nome_giorno = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'][$i];
                ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <strong><?php echo $nome_giorno; ?></strong>
                                <br>
                                <small><?php echo $giorno_corrente->format('d/m/Y'); ?></small>
                            </div>
                            <div class="card-body">
                                <?php if (isset($per_giorno[$data_giorno])): ?>
                                    <?php foreach ($per_giorno[$data_giorno] as $p): ?>
                                        <div class="prenotazione-item">
                                            <strong>
                                                <?php echo date('H:i', strtotime($p["data_ora_inizio"])); ?> - 
                                                <?php echo date('H:i', strtotime($p["data_ora_inizio"]) + $p["durata"] * 3600); ?>
                                            </strong>
                                            <br>
                                            <i class="bi bi-building"></i> <?php echo htmlspecialchars($p["nome_sala"]); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($p["attivita"]); ?>
                                            </small>
                                            <br>
                                            <small>
                                                <i class="bi bi-person"></i> 
                                                <?php echo htmlspecialchars($p["responsabile_nome"] . " " . $p["responsabile_cognome"]); ?>
                                            </small>
                                            <br>
                                            <small>
                                                <i class="bi bi-people"></i> 
                                                <?php echo $p["num_iscritti"]; ?>/<?php echo $p["capienza"]; ?> partecipanti
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center mb-0">
                                        <i class="bi bi-calendar-x"></i> Nessuna prenotazione
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php 
                    $giorno_corrente->modify('+1 day');
                endfor; 
                ?>
            </div>
        <?php endif; ?>
    </main>
    
    <?php require "common/footer.html"; ?>
</body>
</html>
