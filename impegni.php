<?php
/**
 * IMPEGNI.PHP - Visualizzazione impegni settimanali dell'utente
 * 
 * Mostra le prenotazioni a cui l'utente ha confermato la partecipazione.
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

// Calcolo inizio e fine settimana
$data = new DateTime($data_rif);
$giorno_settimana = $data->format('N');
$inizio_settimana = clone $data;
$inizio_settimana->modify('-' . ($giorno_settimana - 1) . ' days');
$fine_settimana = clone $inizio_settimana;
$fine_settimana->modify('+6 days');

// Carico gli impegni della settimana
$impegni = getImpegniSettimana($db, $_SESSION["user_email"], $data_rif);
?>
<!DOCTYPE html>
<html lang="it">
<?php require "common/header.html"; ?>
<body>
    <?php require "common/nav.php"; ?>
    
    <main class="container content-wrapper">
        <h2 class="mb-4">
            <i class="bi bi-calendar-check text-info"></i> I Miei Impegni
        </h2>
        
        <!-- Selettore settimana -->
        <div class="week-selector">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label class="form-label">Seleziona una data della settimana</label>
                    <input type="date" class="form-control" name="data" 
                           value="<?php echo htmlspecialchars($data_rif); ?>">
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
            <a href="?data=<?php echo $prec->format('Y-m-d'); ?>" class="btn btn-outline-primary">
                <i class="bi bi-chevron-left"></i> Settimana precedente
            </a>
            
            <h5 class="mb-0">
                <?php echo $inizio_settimana->format('d/m/Y'); ?> - <?php echo $fine_settimana->format('d/m/Y'); ?>
            </h5>
            
            <a href="?data=<?php echo $succ->format('Y-m-d'); ?>" class="btn btn-outline-primary">
                Settimana successiva <i class="bi bi-chevron-right"></i>
            </a>
        </div>
        
        <!-- Lista impegni -->
        <?php if (empty($impegni["contenuto"])): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Non hai impegni in questa settimana.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-primary">
                        <tr>
                            <th>Data</th>
                            <th>Orario</th>
                            <th>Sala</th>
                            <th>Attività</th>
                            <th>Durata</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($impegni["contenuto"] as $impegno): ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('d/m/Y', strtotime($impegno["data_ora_inizio"])); ?></strong>
                                </td>
                                <td>
                                    <?php echo date('H:i', strtotime($impegno["data_ora_inizio"])); ?> - 
                                    <?php echo date('H:i', strtotime($impegno["data_ora_inizio"]) + $impegno["durata"] * 3600); ?>
                                </td>
                                <td><?php echo htmlspecialchars($impegno["nome_sala"]); ?></td>
                                <td><?php echo htmlspecialchars($impegno["attivita"]); ?></td>
                                <td><?php echo $impegno["durata"]; ?> ore</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Riepilogo -->
            <div class="alert alert-light">
                <i class="bi bi-info-circle"></i> 
                Hai <strong><?php echo count($impegni["contenuto"]); ?></strong> impegni in questa settimana.
            </div>
        <?php endif; ?>
    </main>
    
    <?php require "common/footer.html"; ?>
</body>
</html>
