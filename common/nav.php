<!-- 
    NAV.PHP - Barra di navigazione dinamica
    Mostra link diversi in base allo stato di login e ruolo dell'utente
-->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <!-- Logo e nome sito -->
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-music-note-beamed"></i> Play Room Planner
        </a>
        
        <!-- Bottone hamburger per mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Menu di navigazione -->
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto">
                <?php if (isLoggato()): ?>
                    <!-- Link visibili solo agli utenti loggati -->
                    <li class="nav-item">
                        <a class="nav-link" href="sale.php">
                            <i class="bi bi-building"></i> Sale Prove
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inviti.php">
                            <i class="bi bi-envelope"></i> I Miei Inviti
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="impegni.php">
                            <i class="bi bi-calendar-check"></i> I Miei Impegni
                        </a>
                    </li>
                    <?php if (isResponsabile()): ?>
                        <!-- Link visibili solo ai responsabili -->
                        <li class="nav-item">
                            <a class="nav-link" href="nuova_prenotazione.php">
                                <i class="bi bi-plus-circle"></i> Nuova Prenotazione
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="gestione_prenotazioni.php">
                                <i class="bi bi-gear"></i> Gestisci Prenotazioni
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            
            <!-- Menu utente a destra -->
            <ul class="navbar-nav">
                <?php if (isLoggato()): ?>
                    <!-- Utente loggato: mostra nome e logout -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <?php echo htmlspecialchars($_SESSION["user_nome"] . " " . $_SESSION["user_cognome"]); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profilo.php">
                                <i class="bi bi-person"></i> Profilo
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Utente non loggato: mostra login e registrazione -->
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Accedi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="registrazione.php">
                            <i class="bi bi-person-plus"></i> Registrati
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
