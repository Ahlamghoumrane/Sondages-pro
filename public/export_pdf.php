<?php
/**
 * Export des résultats en PDF
 * Utilise une approche HTML qui peut être imprimée en PDF via le navigateur
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

// Récupérer les résultats
$stmt = $pdo->prepare("
    SELECT o.texte, COUNT(v.id) as votes
    FROM options o
    LEFT JOIN votes v ON o.id = v.option_id
    WHERE o.sondage_id = ?
    GROUP BY o.id
    ORDER BY votes DESC
");
$stmt->execute([$sondage_id]);
$resultats = $stmt->fetchAll();

$totalVotes = array_sum(array_column($resultats, 'votes'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats - <?= htmlspecialchars($sondage['question']) ?></title>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px;
            background: white;
            color: #1a1a2e;
            line-height: 1.6;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #121e84;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #121e84;
            margin-bottom: 10px;
        }
        
        .question {
            font-size: 20px;
            color: #8f225a;
            margin-top: 15px;
        }
        
        .info-box {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .info-item {
            text-align: center;
        }
        
        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: #121e84;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .results-table th {
            background: linear-gradient(135deg, #121e84, #8f225a);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .results-table th:first-child {
            border-radius: 10px 0 0 0;
        }
        
        .results-table th:last-child {
            border-radius: 0 10px 0 0;
            text-align: center;
        }
        
        .results-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .results-table td:last-child {
            text-align: center;
            font-weight: 600;
        }
        
        .results-table tr:last-child td:first-child {
            border-radius: 0 0 0 10px;
        }
        
        .results-table tr:last-child td:last-child {
            border-radius: 0 0 10px 0;
        }
        
        .result-bar {
            background: #e9ecef;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .result-fill {
            height: 100%;
            background: linear-gradient(135deg, #121e84, #8f225a);
            border-radius: 10px;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 12px;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #121e84, #8f225a);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        
        .print-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        Imprimer / Enregistrer PDF
    </button>
    
    <div class="header">
        <div class="logo">SondagePro</div>
        <div>Rapport des résultats</div>
        <div class="question"><?= htmlspecialchars($sondage['question']) ?></div>
    </div>
    
    <div class="info-box">
        <div class="info-item">
            <div class="info-label">Total des votes</div>
            <div class="info-value"><?= $totalVotes ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Date de début</div>
            <div class="info-value"><?= date('d/m/Y H:i', strtotime($sondage['date_debut'])) ?></div>
        </div>
        <?php if ($sondage['date_fin']): ?>
        <div class="info-item">
            <div class="info-label">Date de fin</div>
            <div class="info-value"><?= date('d/m/Y H:i', strtotime($sondage['date_fin'])) ?></div>
        </div>
        <?php endif; ?>
        <div class="info-item">
            <div class="info-label">Statut</div>
            <div class="info-value"><?= $sondage['actif'] ? 'Actif' : 'Inactif' ?></div>
        </div>
    </div>
    
    <table class="results-table">
        <thead>
            <tr>
                <th>Option</th>
                <th style="width: 100px;">Votes</th>
                <th style="width: 100px;">%</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($resultats as $result): 
                $percentage = $totalVotes > 0 ? round(($result['votes'] / $totalVotes) * 100, 1) : 0;
            ?>
            <tr>
                <td>
                    <?= htmlspecialchars($result['texte']) ?>
                    <div class="result-bar">
                        <div class="result-fill" style="width: <?= $percentage ?>%"></div>
                    </div>
                </td>
                <td><?= $result['votes'] ?></td>
                <td><?= $percentage ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p>Rapport généré le <?= date('d/m/Y à H:i') ?></p>
        <p>SondagePro - Système de Sondages</p>
    </div>
    
    <script>
        // Ouvrir automatiquement la boîte de dialogue d'impression
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
