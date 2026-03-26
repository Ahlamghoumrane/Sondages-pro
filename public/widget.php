<?php
/**
 * Page de génération du widget embeddable
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

$pageTitle = 'Widget - ' . $sondage['question'];

// URL du widget
$widgetUrl = SITE_URL . '/embed.php?id=' . $sondage_id;

include 'includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-code-slash me-2"></i>Widget Embeddable
                    </h4>
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($sondage['question']) ?></h5>
                    <p class="text-muted">
                        Intégrez ce sondage sur votre site web en utilisant l'un des codes ci-dessous.
                    </p>
                    
                    <!-- Code iframe -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="bi bi-code-square me-1"></i>Code iframe (Recommandé)
                        </label>
                        <div class="widget-code">
                            <code id="iframeCode">&lt;iframe src="<?= $widgetUrl ?>" width="100%" height="450" frameborder="0" style="border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);"&gt;&lt;/iframe&gt;</code>
                        </div>
                        <button class="btn btn-outline-primary btn-sm mt-2" onclick="copyCode('iframeCode')">
                            <i class="bi bi-clipboard me-1"></i>Copier le code
                        </button>
                    </div>
                    
                    <!-- Code JavaScript -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="bi bi-filetype-js me-1"></i>Code JavaScript
                        </label>
                        <div class="widget-code">
                            <code id="jsCode">&lt;div id="sondage-widget-<?= $sondage_id ?>"&gt;&lt;/div&gt;
&lt;script src="<?= SITE_URL ?>/js/widget.js" data-sondage="<?= $sondage_id ?>"&gt;&lt;/script&gt;</code>
                        </div>
                        <button class="btn btn-outline-primary btn-sm mt-2" onclick="copyCode('jsCode')">
                            <i class="bi bi-clipboard me-1"></i>Copier le code
                        </button>
                    </div>
                    
                    <!-- Lien direct -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="bi bi-link-45deg me-1"></i>Lien direct
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="directLink" 
                                   value="<?= $widgetUrl ?>" readonly>
                            <button class="btn btn-outline-primary" onclick="copyLink()">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Options de personnalisation -->
                    <div class="card bg-light mt-4">
                        <div class="card-body">
                            <h6><i class="bi bi-palette me-2"></i>Options de personnalisation</h6>
                            <p class="small text-muted mb-3">
                                Ajoutez ces paramètres à l'URL pour personnaliser l'apparence du widget.
                            </p>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled small mb-0">
                                        <li><code>?theme=dark</code> - Thème sombre</li>
                                        <li><code>?hide_results=1</code> - Cacher les résultats</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled small mb-0">
                                        <li><code>?compact=1</code> - Mode compact</li>
                                        <li><code>?auto_refresh=1</code> - Actualisation auto</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Prévisualisation -->
            <div class="card mt-4">
                <div class="card-header">
                    <i class="bi bi-eye me-2"></i>Prévisualisation
                </div>
                <div class="card-body">
                    <iframe src="embed.php?id=<?= $sondage_id ?>" 
                            width="100%" 
                            height="450" 
                            frameborder="0" 
                            style="border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                    </iframe>
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
function copyCode(elementId) {
    const code = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(code).then(() => {
        alert('Code copié !');
    });
}

function copyLink() {
    const input = document.getElementById('directLink');
    input.select();
    document.execCommand('copy');
    alert('Lien copié !');
}
</script>

<?php include 'includes/footer.php'; ?>
