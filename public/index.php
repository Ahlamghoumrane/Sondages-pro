<?php
/**
 * Page d'accueil - Liste des sondages actifs
 */
require_once 'config/database.php';

$pageTitle = 'Accueil';

// Récupérer les statistiques
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM sondages")->fetchColumn(),
    'actifs' => $pdo->query("SELECT COUNT(*) FROM sondages WHERE actif = 1 AND (date_fin IS NULL OR date_fin > NOW()) AND date_debut <= NOW()")->fetchColumn(),
    'votes' => $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn()
];

// Récupérer les sondages actifs
$stmt = $pdo->query("
    SELECT s.*, 
           COUNT(DISTINCT v.id) as total_votes,
           CASE 
               WHEN s.date_debut > NOW() THEN 'upcoming'
               WHEN s.date_fin IS NOT NULL AND s.date_fin < NOW() THEN 'expired'
               WHEN s.actif = 0 THEN 'expired'
               ELSE 'active'
           END as status
    FROM sondages s
    LEFT JOIN options o ON s.id = o.sondage_id
    LEFT JOIN votes v ON o.id = v.option_id
    GROUP BY s.id
    ORDER BY 
        CASE 
            WHEN s.date_debut > NOW() THEN 1
            WHEN s.date_fin IS NULL OR s.date_fin > NOW() THEN 0
            ELSE 2
        END,
        s.date_debut DESC
");
$sondages = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <h1><i class="bi bi-bar-chart-fill me-3"></i>Système de Sondages</h1>
        <p>Créez, partagez et analysez vos sondages en temps réel</p>
    </div>
</section>

<div class="container">
    <!-- Stats Row -->
    <div class="row mb-5">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="stat-card fade-in">
                <i class="bi bi-clipboard-data"></i>
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label">Sondages créés</div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="stat-card fade-in" style="animation-delay: 0.1s">
                <i class="bi bi-check-circle"></i>
                <div class="stat-number"><?= $stats['actifs'] ?></div>
                <div class="stat-label">Sondages actifs</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card fade-in" style="animation-delay: 0.2s">
                <i class="bi bi-people"></i>
                <div class="stat-number"><?= $stats['votes'] ?></div>
                <div class="stat-label">Votes enregistrés</div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-list-ul me-2"></i>Sondages disponibles</h2>
        <a href="creer.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Nouveau sondage
        </a>
    </div>

    <!-- Liste des sondages -->
    <?php if (empty($sondages)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Aucun sondage disponible pour le moment. 
            <a href="creer.php" class="alert-link">Créez le premier !</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($sondages as $sondage): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card sondage-card <?= $sondage['status'] ?> fade-in">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($sondage['question']) ?></h5>
                                <?php if ($sondage['status'] === 'active'): ?>
                                    <span class="badge badge-active">
                                        <i class="bi bi-check-circle me-1"></i>Actif
                                    </span>
                                <?php elseif ($sondage['status'] === 'upcoming'): ?>
                                    <span class="badge badge-upcoming">
                                        <i class="bi bi-clock me-1"></i>À venir
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-expired">
                                        <i class="bi bi-x-circle me-1"></i>Terminé
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="date-info">
                                <span>
                                    <i class="bi bi-calendar-event me-1"></i>
                                    Début: <?= date('d/m/Y H:i', strtotime($sondage['date_debut'])) ?>
                                </span>
                                <?php if ($sondage['date_fin']): ?>
                                    <span>
                                        <i class="bi bi-calendar-x me-1"></i>
                                        Fin: <?= date('d/m/Y H:i', strtotime($sondage['date_fin'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="progress-indicator mt-3 mb-3">
                                <i class="bi bi-people-fill"></i>
                                <span><?= $sondage['total_votes'] ?> vote(s)</span>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <?php if ($sondage['status'] === 'active'): ?>
                                    <a href="voter.php?id=<?= $sondage['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-hand-index me-1"></i>Voter
                                    </a>
                                <?php endif; ?>
                                <a href="resultats.php?id=<?= $sondage['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-graph-up me-1"></i>Résultats
                                </a>
                                <a href="widget.php?id=<?= $sondage['id'] ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-code-slash me-1"></i>Widget
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
