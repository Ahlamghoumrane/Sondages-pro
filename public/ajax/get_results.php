<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$sondage_id = intval($_GET['id'] ?? 0);

if (!$sondage_id) {
    echo json_encode(['error' => 'ID invalide']);
    exit;
}

// Récupérer le sondage
$stmt = $pdo->prepare("SELECT * FROM sondages WHERE id = ?");
$stmt->execute([$sondage_id]);
$sondage = $stmt->fetch();

if (!$sondage) {
    echo json_encode(['error' => 'Sondage introuvable']);
    exit;
}

// Récupérer les résultats
$stmt = $pdo->prepare("
    SELECT o.id, o.texte, COUNT(v.id) as votes
    FROM options o
    LEFT JOIN votes v ON o.id = v.option_id
    WHERE o.sondage_id = ?
    GROUP BY o.id
    ORDER BY o.id
");
$stmt->execute([$sondage_id]);
$resultats = $stmt->fetchAll();

$totalVotes = array_sum(array_column($resultats, 'votes'));

$response = [
    'success' => true,
    'sondage' => [
        'id' => $sondage['id'],
        'question' => $sondage['question'],
        'date_debut' => $sondage['date_debut'],
        'date_fin' => $sondage['date_fin'],
        'actif' => (bool)$sondage['actif']
    ],
    'resultats' => [],
    'total_votes' => $totalVotes
];

foreach ($resultats as $result) {
    $percentage = $totalVotes > 0 ? round(($result['votes'] / $totalVotes) * 100, 1) : 0;
    $response['resultats'][] = [
        'id' => $result['id'],
        'texte' => $result['texte'],
        'votes' => (int)$result['votes'],
        'percentage' => $percentage
    ];
}

echo json_encode($response);
?>
