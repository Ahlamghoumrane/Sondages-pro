<?php
/**
 * Page de vote
 */
require_once 'config/database.php';

$sondage_id = intval($_GET['id'] ?? 0);

if (!$sondage_id) {
    header('Location: index.php');
    exit;
}

// Récupérer le sondage
$stmt = $pdo->prepare("SELECT * FROM sondages WHERE id = ?");
$stmt->execute([$sondage_id]);
$sondage = $stmt->fetch();

if (!$sondage) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Voter - ' . $sondage['question'];

// Vérifier si le sondage est actif
$now = new DateTime();
$date_debut = new DateTime($sondage['date_debut']);
$date_fin = $sondage['date_fin'] ? new DateTime($sondage['date_fin']) : null;

$isActive = $sondage['actif'] && $now >= $date_debut && (!$date_fin || $now <= $date_fin);
$isUpcoming = $now < $date_debut;
$isExpired = $date_fin && $now > $date_fin;

// Récupérer les options
$stmt = $pdo->prepare("SELECT * FROM options WHERE sondage_id = ? ORDER BY id");
$stmt->execute([$sondage_id]);
$options = $stmt->fetchAll();

// Vérifier si l'utilisateur a déjà voté (IP + Cookie)
$ip = getClientIP();
$cookie_name = 'voted_' . $sondage_id;
$hasVoted = isset($_COOKIE[$cookie_name]);

if (!$hasVoted) {
    // Vérifier aussi par IP dans la base de données
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM votes v 
        JOIN options o ON v.option_id = o.id 
        WHERE o.sondage_id = ? AND v.ip_votant = ?
    ");
    $stmt->execute([$sondage_id, $ip]);
    $hasVoted = $stmt->fetchColumn() > 0;
}

$message = '';
$messageType = '';

// Traitement du vote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isActive && !$hasVoted) {
    $option_id = intval($_POST['option'] ?? 0);
    
    // Vérifier que l'option appartient au sondage
    $stmt = $pdo->prepare("SELECT id FROM options WHERE id = ? AND sondage_id = ?");
    $stmt->execute([$option_id, $sondage_id]);
    
    if ($stmt->fetch()) {
        try {
            $stmt = $pdo->prepare("INSERT INTO votes (option_id, ip_votant) VALUES (?, ?)");
            $stmt->execute([$option_id, $ip]);
            
            // Définir le cookie pour 30 jours
            setcookie($cookie_name, '1', time() + (30 * 24 * 60 * 60), '/');
            
            $message = "Votre vote a été enregistré avec succès !";
            $messageType = "success";
            $hasVoted = true;
            
            // Redirection vers les résultats
            header("Refresh: 2; url=resultats.php?id=" . $sondage_id);
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Vous avez déjà voté pour ce sondage.";
                $hasVoted = true;
            } else {
                $message = "Erreur lors de l'enregistrement du vote.";
            }
            $messageType = "danger";
        }
    } else {
        $message = "Option invalide.";
        $messageType = "danger";
    }
}

include 'includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Status du sondage -->
            <?php if ($isUpcoming): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-clock me-2"></i>
                    Ce sondage commencera le <?= date('d/m/Y à H:i', strtotime($sondage['date_debut'])) ?>
                </div>
            <?php elseif ($isExpired || !$sondage['actif']): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle me-2"></i>
                    Ce sondage est terminé.
                    <a href="resultats.php?id=<?= $sondage_id ?>" class="alert-link">Voir les résultats</a>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-question-circle me-2"></i><?= htmlspecialchars($sondage['question']) ?>
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($hasVoted): ?>
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            Vous avez déjà participé à ce sondage.
                        </div>
                        <a href="resultats.php?id=<?= $sondage_id ?>" class="btn btn-primary">
                            <i class="bi bi-graph-up me-1"></i>Voir les résultats
                        </a>
                    <?php elseif ($isActive): ?>
                        <form method="POST" id="voteForm">
                            <div class="mb-4">
                                <?php foreach ($options as $option): ?>
                                    <div class="vote-option" onclick="selectOption(this)">
                                        <input 
                                            type="radio" 
                                            name="option" 
                                            id="option_<?= $option['id'] ?>" 
                                            value="<?= $option['id'] ?>"
                                            required
                                        >
                                        <label for="option_<?= $option['id'] ?>">
                                            <?= htmlspecialchars($option['texte']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check2-circle me-2"></i>Valider mon vote
                            </button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted mb-0">
                            Le vote n'est pas disponible pour le moment.
                        </p>
                    <?php endif; ?>
                    
                    <!-- Info dates -->
                    <div class="date-info mt-4">
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
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="d-flex justify-content-between mt-4">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Retour à l'accueil
                </a>
                <a href="resultats.php?id=<?= $sondage_id ?>" class="btn btn-outline-primary">
                    <i class="bi bi-graph-up me-1"></i>Voir les résultats
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function selectOption(element) {
    // Retirer la classe selected de tous les éléments
    document.querySelectorAll('.vote-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    
    // Ajouter la classe à l'élément cliqué
    element.classList.add('selected');
    
    // Cocher le radio button
    element.querySelector('input[type="radio"]').checked = true;
}
</script>

<?php include 'includes/footer.php'; ?>
