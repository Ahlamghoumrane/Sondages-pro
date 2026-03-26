<?php
/**
 * Page d'administration des sondages
 */
require_once 'config/database.php';

$pageTitle = 'Administration';
$message = '';
$messageType = '';

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    
    switch ($action) {
        case 'toggle':
            $stmt = $pdo->prepare("UPDATE sondages SET actif = NOT actif WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Statut du sondage mis à jour.";
            $messageType = "success";
            break;
            
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM sondages WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Sondage supprimé avec succès.";
            $messageType = "success";
            break;
            
        case 'reset_votes':
            // Supprimer tous les votes pour ce sondage
            $stmt = $pdo->prepare("
                DELETE v FROM votes v
                INNER JOIN options o ON v.option_id = o.id
                WHERE o.sondage_id = ?
            ");
            $stmt->execute([$id]);
            $message = "Votes réinitialisés.";
            $messageType = "success";
            break;
    }
}

// Récupérer tous les sondages avec stats
$stmt = $pdo->query("
    SELECT s.*, 
           COUNT(DISTINCT o.id) as nb_options,
           COUNT(DISTINCT v.id) as total_votes,
           MAX(v.date_vote) as dernier_vote
    FROM sondages s
    LEFT JOIN options o ON s.id = o.sondage_id
    LEFT JOIN votes v ON o.id = v.option_id
    GROUP BY s.id
    ORDER BY s.date_debut DESC
");
$sondages = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-gear me-2"></i>Administration des Sondages</h2>
        <a href="creer.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nouveau sondage
        </a>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
            <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (empty($sondages)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Aucun sondage créé pour le moment.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th class="text-center">Options</th>
                            <th class="text-center">Votes</th>
                            <th>Période</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sondages as $sondage): 
                            $now = new DateTime();
                            $date_debut = new DateTime($sondage['date_debut']);
                            $date_fin = $sondage['date_fin'] ? new DateTime($sondage['date_fin']) : null;
                            
                            $isActive = $sondage['actif'] && $now >= $date_debut && (!$date_fin || $now <= $date_fin);
                            $isUpcoming = $now < $date_debut;
                            $isExpired = $date_fin && $now > $date_fin;
                        ?>
                        <tr>
                            <td><?= $sondage['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars(substr($sondage['question'], 0, 50)) ?><?= strlen($sondage['question']) > 50 ? '...' : '' ?></strong>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary"><?= $sondage['nb_options'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary"><?= $sondage['total_votes'] ?></span>
                            </td>
                            <td>
                                <small>
                                    <?= date('d/m/Y', strtotime($sondage['date_debut'])) ?>
                                    <?php if ($sondage['date_fin']): ?>
                                        <br>→ <?= date('d/m/Y', strtotime($sondage['date_fin'])) ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td class="text-center">
                                <?php if (!$sondage['actif']): ?>
                                    <span class="badge bg-secondary">Désactivé</span>
                                <?php elseif ($isUpcoming): ?>
                                    <span class="badge badge-upcoming">À venir</span>
                                <?php elseif ($isExpired): ?>
                                    <span class="badge badge-expired">Terminé</span>
                                <?php else: ?>
                                    <span class="badge badge-active">Actif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="resultats.php?id=<?= $sondage['id'] ?>" 
                                       class="btn btn-outline-primary" title="Résultats">
                                        <i class="bi bi-graph-up"></i>
                                    </a>
                                    <a href="voter.php?id=<?= $sondage['id'] ?>" 
                                       class="btn btn-outline-secondary" title="Voter">
                                        <i class="bi bi-hand-index"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-warning" 
                                            onclick="toggleSondage(<?= $sondage['id'] ?>)" 
                                            title="<?= $sondage['actif'] ? 'Désactiver' : 'Activer' ?>">
                                        <i class="bi bi-<?= $sondage['actif'] ? 'pause' : 'play' ?>"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-info"
                                            onclick="resetVotes(<?= $sondage['id'] ?>)"
                                            title="Réinitialiser les votes">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteSondage(<?= $sondage['id'] ?>)" 
                                            title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Formulaires cachés pour les actions -->
<form id="actionForm" method="POST" style="display: none;">
    <input type="hidden" name="action" id="actionType">
    <input type="hidden" name="id" id="actionId">
</form>

<script>
function toggleSondage(id) {
    document.getElementById('actionType').value = 'toggle';
    document.getElementById('actionId').value = id;
    document.getElementById('actionForm').submit();
}

function deleteSondage(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce sondage ? Cette action est irréversible.')) {
        document.getElementById('actionType').value = 'delete';
        document.getElementById('actionId').value = id;
        document.getElementById('actionForm').submit();
    }
}

function resetVotes(id) {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser tous les votes pour ce sondage ?')) {
        document.getElementById('actionType').value = 'reset_votes';
        document.getElementById('actionId').value = id;
        document.getElementById('actionForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
