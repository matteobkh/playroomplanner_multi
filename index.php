<?php
/**
 * INDEX.PHP - Pagina principale dell'applicazione
 * 
 * Questa è la home page del sito. Mostra:
 * - Un messaggio di benvenuto
 * - Le funzionalità principali del sistema
 * - Invito al login se non autenticato
 */

// Includo configurazione e funzioni
require_once "common/config.php";
require_once "common/funzioni.php";
?>
<!DOCTYPE html>
<html lang="it">
<?php require "common/header.html"; ?>
<body>
    <?php require "common/nav.php"; ?>
    
    <main class="container content-wrapper">
        
        <?php if (isset($_GET["account_eliminato"])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> Il tuo account è stato eliminato con successo.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Messaggio di benvenuto -->
        <div class="text-center mb-5">
            <h1 class="display-4">
                <i class="bi bi-music-note-beamed text-primary"></i>
                Benvenuto in Play Room Planner
            </h1>
            <p class="lead text-muted">
                Gestisci le prenotazioni delle sale prove della tua associazione culturale
            </p>
        </div>
        
        <?php if (!isLoggato()): ?>
            <!-- Sezione per utenti non loggati -->
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card auth-card">
                        <div class="card-body text-center">
                            <h5 class="card-title">Accedi per iniziare</h5>
                            <p class="card-text">
                                Effettua il login per visualizzare le sale prove, 
                                gestire i tuoi inviti e prenotare le sale.
                            </p>
                            <a href="login.php" class="btn btn-primary me-2">
                                <i class="bi bi-box-arrow-in-right"></i> Accedi
                            </a>
                            <a href="registrazione.php" class="btn btn-outline-primary">
                                <i class="bi bi-person-plus"></i> Registrati
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Dashboard per utenti loggati -->
            <div class="row g-4">
                <!-- Card Sale Prove -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-building display-4 text-primary mb-3"></i>
                            <h5 class="card-title">Sale Prove</h5>
                            <p class="card-text">
                                Visualizza le prenotazioni delle sale prove per settimana
                            </p>
                            <a href="sale.php" class="btn btn-primary">
                                Vai alle Sale
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Card Inviti -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-envelope display-4 text-success mb-3"></i>
                            <h5 class="card-title">I Miei Inviti</h5>
                            <p class="card-text">
                                Gestisci gli inviti ricevuti e conferma la tua partecipazione
                            </p>
                            <a href="inviti.php" class="btn btn-success">
                                Vedi Inviti
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Card Impegni -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-check display-4 text-info mb-3"></i>
                            <h5 class="card-title">I Miei Impegni</h5>
                            <p class="card-text">
                                Consulta le prove a cui parteciperai questa settimana
                            </p>
                            <a href="impegni.php" class="btn btn-info text-white">
                                Vedi Impegni
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php if (isResponsabile()): ?>
                    <!-- Card Nuova Prenotazione (solo responsabili) -->
                    <div class="col-md-6">
                        <div class="card bg-warning bg-opacity-10 border-warning h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-plus-circle display-4 text-warning mb-3"></i>
                                <h5 class="card-title">Nuova Prenotazione</h5>
                                <p class="card-text">
                                    Crea nuove prenotazioni per le sale prove
                                </p>
                                <a href="nuova_prenotazione.php" class="btn btn-warning">
                                    <i class="bi bi-plus"></i> Crea
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Gestione Prenotazioni (solo responsabili) -->
                    <div class="col-md-6">
                        <div class="card bg-secondary bg-opacity-10 border-secondary h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-gear display-4 text-secondary mb-3"></i>
                                <h5 class="card-title">Gestisci Prenotazioni</h5>
                                <p class="card-text">
                                    Modifica o elimina le tue prenotazioni esistenti
                                </p>
                                <a href="gestione_prenotazioni.php" class="btn btn-secondary">
                                    <i class="bi bi-pencil"></i> Gestisci
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Sezione informativa -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-info-circle"></i> Informazioni sul Sistema
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6><i class="bi bi-clock text-primary"></i> Orari Prenotazioni</h6>
                                <p>Le sale sono prenotabili dalle 09:00 alle 23:00, solo ad ore intere.</p>
                            </div>
                            <div class="col-md-4">
                                <h6><i class="bi bi-people text-success"></i> Inviti</h6>
                                <p>Ricevi inviti dai responsabili e conferma la tua partecipazione.</p>
                            </div>
                            <div class="col-md-4">
                                <h6><i class="bi bi-shield-check text-info"></i> Capienza</h6>
                                <p>Il sistema verifica automaticamente la capienza delle sale.</p>
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
