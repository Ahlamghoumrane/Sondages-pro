<?php

require_once 'config/database.php';

$sondage_id = intval($_GET['id'] ?? 0);
$theme = $_GET['theme'] ?? 'light';
$hideResults = isset($_GET['hide_results']);
$compact = isset($_GET['compact']);
$autoRefresh = isset($_GET['auto_refresh']);

if (!$sondage_id) {
    die('Sondage invalide');
}

// Récupérer le sondage
$stmt = $pdo->prepare("SELECT * FROM sondages WHERE id = ?");
$stmt->execute([$sondage_id]);
$sondage = $stmt->fetch();

if (!$sondage) {
    die('Sondage introuvable');
}

// Vérifier si le sondage est actif
$now = new DateTime();
$date_debut = new DateTime($sondage['date_debut']);
$date_fin = $sondage['date_fin'] ? new DateTime($sondage['date_fin']) : null;
$isActive = $sondage['actif'] && $now >= $date_debut && (!$date_fin || $now <= $date_fin);

// Récupérer les options avec votes
$stmt = $pdo->prepare("
    SELECT o.id, o.texte, COUNT(v.id) as votes
    FROM options o
    LEFT JOIN votes v ON o.id = v.option_id
    WHERE o.sondage_id = ?
    GROUP BY o.id
    ORDER BY o.id
");
$stmt->execute([$sondage_id]);
$options = $stmt->fetchAll();

$totalVotes = array_sum(array_column($options, 'votes'));

// Vérifier si l'utilisateur a déjà voté
$ip = getClientIP();
$cookie_name = 'voted_' . $sondage_id;
$hasVoted = isset($_COOKIE[$cookie_name]);

if (!$hasVoted) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM votes v 
        JOIN options o ON v.option_id = o.id 
        WHERE o.sondage_id = ? AND v.ip_votant = ?
    ");
    $stmt->execute([$sondage_id, $ip]);
    $hasVoted = $stmt->fetchColumn() > 0;
}

// Traitement du vote
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isActive && !$hasVoted) {
    $option_id = intval($_POST['option'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT id FROM options WHERE id = ? AND sondage_id = ?");
    $stmt->execute([$option_id, $sondage_id]);
    
    if ($stmt->fetch()) {
        try {
            $stmt = $pdo->prepare("INSERT INTO votes (option_id, ip_votant) VALUES (?, ?)");
            $stmt->execute([$option_id, $ip]);
            setcookie($cookie_name, '1', time() + (30 * 24 * 60 * 60), '/');
            $hasVoted = true;
            
            // Rafraîchir les données
            $stmt = $pdo->prepare("
                SELECT o.id, o.texte, COUNT(v.id) as votes
                FROM options o
                LEFT JOIN votes v ON o.id = v.option_id
                WHERE o.sondage_id = ?
                GROUP BY o.id
                ORDER BY o.id
            ");
            $stmt->execute([$sondage_id]);
            $options = $stmt->fetchAll();
            $totalVotes = array_sum(array_column($options, 'votes'));
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $hasVoted = true;
            }
        }
    }
}

// Couleurs du thème
$bgColor = $theme === 'dark' ? '#1a1a2e' : '#ffffff';
$textColor = $theme === 'dark' ? '#ffffff' : '#1a1a2e';
$borderColor = $theme === 'dark' ? '#2d2d44' : '#e9ecef';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($sondage['question']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: <?= $bgColor ?>;
            color: <?= $textColor ?>;
            padding: <?= $compact ? '15px' : '25px' ?>;
            line-height: 1.6;
        }
        
        .widget-container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .widget-header {
            margin-bottom: <?= $compact ? '15px' : '20px' ?>;
        }
        
        .widget-header h2 {
            font-size: <?= $compact ? '1rem' : '1.2rem' ?>;
            font-weight: 600;
            color: #121e84;
            margin-bottom: 5px;
        }
        
        .widget-meta {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .vote-option {
            background: <?= $theme === 'dark' ? '#2d2d44' : '#f8f9fa' ?>;
            border: 2px solid <?= $borderColor ?>;
            border-radius: 10px;
            padding: <?= $compact ? '10px 15px' : '12px 18px' ?>;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .vote-option:hover {
            border-color: #121e84;
            background: <?= $theme === 'dark' ? '#363652' : 'rgba(18, 30, 132, 0.05)' ?>;
        }
        
        .vote-option.selected {
            border-color: #121e84;
            background: rgba(18, 30, 132, 0.1);
        }
        
        .vote-option input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: #121e84;
        }
        
        .vote-option label {
            flex: 1;
            cursor: pointer;
            font-size: <?= $compact ? '0.9rem' : '1rem' ?>;
        }
        
        .btn-vote {
            width: 100%;
            background: linear-gradient(135deg, #121e84, #8f225a);
            color: white;
            border: none;
            padding: <?= $compact ? '10px' : '12px' ?>;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-vote:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .result-option {
            margin-bottom: 15px;
        }
        
        .result-text {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: <?= $compact ? '0.85rem' : '0.95rem' ?>;
        }
        
        .result-bar {
            background: <?= $theme === 'dark' ? '#2d2d44' : '#e9ecef' ?>;
            height: <?= $compact ? '20px' : '25px' ?>;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .result-fill {
            height: 100%;
            background: linear-gradient(135deg, #121e84, #8f225a);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 8px;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 35px;
            transition: width 0.8s ease;
        }
        
        .total-votes {
            text-align: center;
            padding: 10px;
            margin-top: 15px;
            background: <?= $theme === 'dark' ? '#2d2d44' : '#f8f9fa' ?>;
            border-radius: 10px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .total-votes strong {
            color: #121e84;
        }
        
        .message {
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        
        .message.info {
            background: rgba(18, 30, 132, 0.1);
            color: #121e84;
            border-left: 3px solid #121e84;
        }
        
        .message.warning {
            background: rgba(255, 193, 7, 0.15);
            color: #856404;
            border-left: 3px solid #ffc107;
        }
        
        .powered-by {
            text-align: center;
            margin-top: 15px;
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .powered-by a {
            color: #8f225a;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="widget-container">
        <div class="widget-header">
            <h2><?= htmlspecialchars($sondage['question']) ?></h2>
            <div class="widget-meta">
                <i class="bi bi-people-fill"></i> <?= $totalVotes ?> vote(s)
            </div>
        </div>
        
        <?php if (!$isActive): ?>
            <div class="message warning">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Ce sondage n'est pas actif actuellement.
            </div>
        <?php endif; ?>
        
        <?php if ($hasVoted || !$isActive || $hideResults): ?>
            <!-- Afficher les résultats -->
            <?php foreach ($options as $option): 
                $percentage = $totalVotes > 0 ? round(($option['votes'] / $totalVotes) * 100, 1) : 0;
            ?>
                <div class="result-option">
                    <div class="result-text">
                        <span><?= htmlspecialchars($option['texte']) ?></span>
                        <span><?= $option['votes'] ?> vote(s)</span>
                    </div>
                    <div class="result-bar">
                        <div class="result-fill" style="width: <?= max($percentage, 5) ?>%">
                            <?= $percentage ?>%
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if ($hasVoted): ?>
                <div class="message info">
                    <i class="bi bi-check-circle me-1"></i>
                    Merci pour votre participation !
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Formulaire de vote -->
            <form method="POST" id="voteForm">
                <?php foreach ($options as $option): ?>
                    <div class="vote-option" onclick="selectOption(this)">
                        <input type="radio" name="option" id="opt_<?= $option['id'] ?>" value="<?= $option['id'] ?>" required>
                        <label for="opt_<?= $option['id'] ?>"><?= htmlspecialchars($option['texte']) ?></label>
                    </div>
                <?php endforeach; ?>
                
                <button type="submit" class="btn-vote">
                    <i class="bi bi-check2-circle me-1"></i>Voter
                </button>
            </form>
        <?php endif; ?>
        
        <div class="total-votes">
            <strong><?= $totalVotes ?></strong> participant(s) au total
        </div>
        
        <div class="powered-by">
            Propulsé par <a href="<?= SITE_URL ?>" target="_blank">SondagePro</a>
        </div>
    </div>
    
    <script>
    function selectOption(element) {
        document.querySelectorAll('.vote-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        element.classList.add('selected');
        element.querySelector('input[type="radio"]').checked = true;
    }
    
    <?php if ($autoRefresh): ?>
    // Auto-refresh des résultats toutes les 10 secondes
    setInterval(function() {
        location.reload();
    }, 10000);
    <?php endif; ?>
    </script>
</body>
</html>
