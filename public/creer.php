<?php
/**
 * Page de création de sondage
 */
require_once 'config/database.php';

$pageTitle = 'Créer un sondage';
$message = '';
$messageType = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question'] ?? '');
    $options = array_filter(array_map('trim', $_POST['options'] ?? []));
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin = !empty($_POST['date_fin']) ? $_POST['date_fin'] : null;
    
    // Validation
    $errors = [];
    
    if (empty($question)) {
        $errors[] = "La question est obligatoire.";
    }
    
    if (count($options) < 2) {
        $errors[] = "Veuillez fournir au moins 2 options de réponse.";
    }
    
    if (empty($date_debut)) {
        $errors[] = "La date de début est obligatoire.";
    }
    
    if ($date_fin && strtotime($date_fin) <= strtotime($date_debut)) {
        $errors[] = "La date de fin doit être postérieure à la date de début.";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insérer le sondage
            $stmt = $pdo->prepare("
                INSERT INTO sondages (question, date_debut, date_fin, actif) 
                VALUES (?, ?, ?, 1)
            ");
            $stmt->execute([$question, $date_debut, $date_fin]);
            $sondage_id = $pdo->lastInsertId();
            
            // Insérer les options
            $stmt = $pdo->prepare("INSERT INTO options (sondage_id, texte) VALUES (?, ?)");
            foreach ($options as $option) {
                if (!empty($option)) {
                    $stmt->execute([$sondage_id, $option]);
                }
            }
            
            $pdo->commit();
            
            $message = "Sondage créé avec succès !";
            $messageType = "success";
            
            // Redirection après 2 secondes
            header("Refresh: 2; url=voter.php?id=" . $sondage_id);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Erreur lors de la création du sondage : " . $e->getMessage();
            $messageType = "danger";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "danger";
    }
}

include 'includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-plus-circle me-2"></i>Créer un nouveau sondage
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="sondageForm">
                        <!-- Question -->
                        <div class="mb-4">
                            <label for="question" class="form-label">
                                <i class="bi bi-question-circle me-1"></i>Question du sondage
                            </label>
                            <textarea 
                                class="form-control" 
                                id="question" 
                                name="question" 
                                rows="3" 
                                placeholder="Posez votre question ici..."
                                required
                            ><?= htmlspecialchars($_POST['question'] ?? '') ?></textarea>
                        </div>
                        
                        <!-- Options de réponse -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-list-check me-1"></i>Options de réponse
                            </label>
                            <div id="optionsContainer">
                                <div class="option-input-group">
                                    <input type="text" class="form-control" name="options[]" placeholder="Option 1" required>
                                </div>
                                <div class="option-input-group">
                                    <input type="text" class="form-control" name="options[]" placeholder="Option 2" required>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addOption">
                                <i class="bi bi-plus-lg me-1"></i>Ajouter une option
                            </button>
                        </div>
                        
                        <!-- Dates -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="date_debut" class="form-label">
                                    <i class="bi bi-calendar-event me-1"></i>Date de début
                                </label>
                                <input 
                                    type="datetime-local" 
                                    class="form-control" 
                                    id="date_debut" 
                                    name="date_debut"
                                    value="<?= htmlspecialchars($_POST['date_debut'] ?? date('Y-m-d\TH:i')) ?>"
                                    required
                                >
                            </div>
                            <div class="col-md-6">
                                <label for="date_fin" class="form-label">
                                    <i class="bi bi-calendar-x me-1"></i>Date de fin (optionnel)
                                </label>
                                <input 
                                    type="datetime-local" 
                                    class="form-control" 
                                    id="date_fin" 
                                    name="date_fin"
                                    value="<?= htmlspecialchars($_POST['date_fin'] ?? '') ?>"
                                >
                                <small class="text-muted">Laissez vide pour un sondage sans limite de temps</small>
                            </div>
                        </div>
                        
                        <!-- Boutons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Créer le sondage
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Conseils -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-lightbulb me-2"></i>Conseils</h6>
                    <ul class="mb-0 small text-muted">
                        <li>Formulez une question claire et précise</li>
                        <li>Proposez des options de réponse distinctes et exhaustives</li>
                        <li>Utilisez les dates pour programmer votre sondage</li>
                        <li>Vous pouvez ajouter jusqu'à 10 options de réponse</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Gestion dynamique des options
let optionCount = 2;
const maxOptions = 10;

document.getElementById('addOption').addEventListener('click', function() {
    if (optionCount >= maxOptions) {
        alert('Maximum ' + maxOptions + ' options autorisées');
        return;
    }
    
    optionCount++;
    const container = document.getElementById('optionsContainer');
    const div = document.createElement('div');
    div.className = 'option-input-group';
    div.innerHTML = `
        <input type="text" class="form-control" name="options[]" placeholder="Option ${optionCount}" required>
        <button type="button" class="btn btn-remove" onclick="removeOption(this)">
            <i class="bi bi-trash"></i>
        </button>
    `;
    container.appendChild(div);
});

function removeOption(btn) {
    if (optionCount <= 2) {
        alert('Minimum 2 options requises');
        return;
    }
    btn.parentElement.remove();
    optionCount--;
}
</script>

<?php include 'includes/footer.php'; ?>
