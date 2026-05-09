<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

setHeaders();

$pdo = db();

$total    = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$processing  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn();
$shipped  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'shipped'")->fetchColumn();

// For now, flagged = processing (no separate flagged table implemented yet)
$flagged = $processing;

respond([
    'total'   => $total,
    'processing' => $processing,
    'shipped' => $shipped,
    'flagged' => $flagged,
    'month_label' => date('F Y'),
]);

