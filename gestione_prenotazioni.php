<?php
/**
 * GESTIONE_PRENOTAZIONI.PHP - Gestione prenotazioni del responsabile
 * 
 * Permette ai responsabili di visualizzare, modificare e cancellare
 * le proprie prenotazioni.
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

// Gestisco la cancellazione
if (isset($_GET["cancella"])) {
    $id = (int)$_GET["cancella"];
    
    // Verifico che la prenotazione appartenga al responsabile loggato
    $check = $db->prepare("SELECT id FROM prenotazione WHERE id = ? AND responsabile_email = ?");
    $check->execute([$id, $_SESSION["user_email"]]);
    
    if ($check->fetch()) {
        $risultato = eliminaPrenotazione($db, $id);
        if ($risultato["status"] == "ok") {
            $successo = "Prenotazione eliminata con successo";
        } else {
            $errore = $risultato["msg"];
        }
    } else {
        $errore = "Non puoi eliminare questa prenotazione";
    }
}

// Carico le prenotazioni del responsabile
$sql = "SELECT p.*, s.nome_sala, s.capienza 
        FROM prenotazione p
        JOIN sala s ON p.sala_id = s.id
        WHERE p.responsabile_email = ?
        ORDER BY p.data_ora_inizio DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$_SESSION["user_email"]]);
$prenotazioni = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<?php require "common/header.html"; ?>
<body>
    <?php require "common/nav.php"; ?>
    
    <main class="container content-wrapper">
        <h2 class="mb-4">
            <i class="bi bi-calendar-check text-primary"></i> Le Mie Prenotazioni
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
        
        <!-- Bottone nuova prenotazione -->
        <div class="mb-4">
            <a href="nuova_prenotazione.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nuova Prenotazione
            </a>
        </div>
        
        <?php if (empty($prenotazioni)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Non hai ancora creato prenotazioni.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Data/Ora</th>
                            <th>Sala</th>
                            <th>Attività</th>
                            <th>Durata</th>
                            <th>Partecipanti</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prenotazioni as $p): ?>
                            <tr>
                                <td><?php echo $p["id"]; ?></td>
                                <td>
                                    <strong><?php echo date('d/m/Y', strtotime($p["data_ora_inizio"])); ?></strong><br>
                                    <small><?php echo date('H:i', strtotime($p["data_ora_inizio"])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($p["nome_sala"]); ?></td>
                                <td><?php echo htmlspecialchars($p["attivita"]); ?></td>
                                <td><?php echo $p["durata"]; ?> ore</td>
                                <td>
                                    <span class="badge bg-info"><?php echo $p["num_iscritti"]; ?>/<?php echo $p["capienza"]; ?></span>
                                </td>
                                <td>
                                    <a href="modifica_prenotazione.php?id=<?php echo $p["id"]; ?>" 
                                       class="btn btn-sm btn-warning" title="Modifica">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="?cancella=<?php echo $p["id"]; ?>" 
                                       class="btn btn-sm btn-danger" title="Elimina"
                                       onclick="return confirm('Sei sicuro di voler eliminare questa prenotazione? Tutti gli inviti verranno cancellati.')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
    
    <?php require "common/footer.html"; ?>
</body>
</html>
