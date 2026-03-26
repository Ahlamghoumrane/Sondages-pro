<?php
/**
 * Page des résultats avec graphiques Chart.js
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

$pageTitle = 'Résultats - ' . $sondage['question'];

// Récupérer les résultats
$stmt = $pdo->prepare("
    SELECT o.id, o.texte, COUNT(v.id) as votes
    FROM options o
    LEFT JOIN votes v ON o.id = v.option_id
    WHERE o.sondage_id = ?
    GROUP BY o.id
    ORDER BY votes DESC
");
$stmt->execute([$sondage_id]);
$resultats = $stmt->fetchAll();

// Calculer le total des votes
$totalVotes = array_sum(array_column($resultats, 'votes'));

// Préparer les données pour Chart.js
$labels = [];
$data = [];
$colors = [
    'rgba(18, 30, 132, 0.8)',
    'rgba(143, 34, 90, 0.8)',
    'rgba(26, 42, 158, 0.8)',
    'rgba(168, 45, 109, 0.8)',
    'rgba(13, 22, 96, 0.8)',
    'rgba(109, 26, 69, 0.8)',
    'rgba(66, 82, 183, 0.8)',
    'rgba(189, 73, 130, 0.8)',
    'rgba(99, 115, 193, 0.8)',
    'rgba(210, 102, 154, 0.8)'
];

foreach ($resultats as $index => $result) {
    $labels[] = $result['texte'];
    $data[] = $result['votes'];
}

include 'includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Résultats du sondage
                    </h4>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-light" onclick="refreshResults()">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <a href="export_pdf.php?id=<?= $sondage_id ?>" class="btn btn-sm btn-outline-light">
                            <i class="bi bi-file-pdf me-1"></i>PDF
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($sondage['question']) ?></h5>
                    
                    <div class="progress-indicator mb-4">
                        <i class="bi bi-people-fill"></i>
                        <span><strong><?= $totalVotes ?></strong> vote(s) au total</span>
                    </div>
                    
                    <!-- Barres de progression -->
                    <div class="results-bars mb-4">
                        <?php foreach ($resultats as $index => $result): 
                            $percentage = $totalVotes > 0 ? round(($result['votes'] / $totalVotes) * 100, 1) : 0;
                        ?>
                            <div class="result-option">
                                <div class="option-text">
                                    <span><?= htmlspecialchars($result['texte']) ?></span>
                                    <span class="votes-count"><?= $result['votes'] ?> vote(s)</span>
                                </div>
                                <div class="result-bar">
                                    <div class="result-fill" style="width: <?= max($percentage, 5) ?>%; background: <?= $colors[$index % count($colors)] ?>">
                                        <?= $percentage ?>%
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Graphiques -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="bi bi-pie-chart me-2"></i>Diagramme circulaire
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="pieChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="bi bi-bar-chart me-2"></i>Diagramme en barres
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="barChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Infos sondage -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-info-circle me-2"></i>Informations
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bi bi-calendar-event text-primary me-2"></i>
                            <strong>Début:</strong> <?= date('d/m/Y H:i', strtotime($sondage['date_debut'])) ?>
                        </li>
                        <?php if ($sondage['date_fin']): ?>
                            <li class="mb-2">
                                <i class="bi bi-calendar-x text-danger me-2"></i>
                                <strong>Fin:</strong> <?= date('d/m/Y H:i', strtotime($sondage['date_fin'])) ?>
                            </li>
                        <?php endif; ?>
                        <li class="mb-2">
                            <i class="bi bi-<?= $sondage['actif'] ? 'check-circle text-success' : 'x-circle text-danger' ?> me-2"></i>
                            <strong>Statut:</strong> <?= $sondage['actif'] ? 'Actif' : 'Inactif' ?>
                        </li>
                        <li>
                            <i class="bi bi-hash text-secondary me-2"></i>
                            <strong>ID:</strong> #<?= $sondage_id ?>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-gear me-2"></i>Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="voter.php?id=<?= $sondage_id ?>" class="btn btn-primary">
                            <i class="bi bi-hand-index me-1"></i>Participer au vote
                        </a>
                        <a href="widget.php?id=<?= $sondage_id ?>" class="btn btn-outline-primary">
                            <i class="bi bi-code-slash me-1"></i>Widget embeddable
                        </a>
                        <a href="export_pdf.php?id=<?= $sondage_id ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-file-pdf me-1"></i>Exporter en PDF
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Partage -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-share me-2"></i>Partager
                </div>
                <div class="card-body">
                    <div class="share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_URL . '/voter.php?id=' . $sondage_id) ?>" 
                           target="_blank" class="share-btn facebook" title="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode(SITE_URL . '/voter.php?id=' . $sondage_id) ?>&text=<?= urlencode('Participez au sondage: ' . $sondage['question']) ?>" 
                           target="_blank" class="share-btn twitter" title="Twitter">
                            <i class="bi bi-twitter"></i>
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode(SITE_URL . '/voter.php?id=' . $sondage_id) ?>" 
                           target="_blank" class="share-btn linkedin" title="LinkedIn">
                            <i class="bi bi-linkedin"></i>
                        </a>
                        <a href="https://wa.me/?text=<?= urlencode('Participez au sondage: ' . $sondage['question'] . ' - ' . SITE_URL . '/voter.php?id=' . $sondage_id) ?>" 
                           target="_blank" class="share-btn whatsapp" title="WhatsApp">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                    </div>
                    <hr>
                    <label class="form-label small">Lien direct:</label>
                    <div class="input-group">
                        <input type="text" class="form-control form-control-sm" 
                               value="<?= SITE_URL ?>/voter.php?id=<?= $sondage_id ?>" 
                               id="shareLink" readonly>
                        <button class="btn btn-outline-secondary btn-sm" onclick="copyLink()">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="d-flex justify-content-between mt-4">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour à l'accueil
        </a>
    </div>
</div>

<script>
// Données pour les graphiques
const chartLabels = <?= json_encode($labels) ?>;
const chartData = <?= json_encode($data) ?>;
const chartColors = <?= json_encode(array_slice($colors, 0, count($labels))) ?>;

// Graphique circulaire (Pie)
const pieCtx = document.getElementById('pieChart').getContext('2d');
new Chart(pieCtx, {
    type: 'doughnut',
    data: {
        labels: chartLabels,
        datasets: [{
            data: chartData,
            backgroundColor: chartColors,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    usePointStyle: true
                }
            }
        }
    }
});

// Graphique en barres
const barCtx = document.getElementById('barChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: chartLabels,
        datasets: [{
            label: 'Votes',
            data: chartData,
            backgroundColor: chartColors,
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            },
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 0
                }
            }
        }
    }
});

// Copier le lien
function copyLink() {
    const input = document.getElementById('shareLink');
    input.select();
    document.execCommand('copy');
    alert('Lien copié !');
}

// Rafraîchir les résultats
function refreshResults() {
    location.reload();
}

// Auto-refresh toutes les 30 secondes
setInterval(function() {
    // Appel AJAX pour mettre à jour les données
    fetch('ajax/get_results.php?id=<?= $sondage_id ?>')
        .then(response => response.json())
        .then(data => {
            // Mise à jour des graphiques ici si nécessaire
            console.log('Données actualisées');
        })
        .catch(err => console.log('Erreur de mise à jour'));
}, 30000);
</script>

<?php include 'includes/footer.php'; ?>
