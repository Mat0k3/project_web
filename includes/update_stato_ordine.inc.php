<?php
session_start();
require_once 'dbh_test.inc.php';

if (!isset($_SESSION['utente_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $stato = $_POST['stato'] ?? null;

    $validi = ['In preparazione', 'In consegna', 'Consegnato'];

    if ($id && in_array($stato, $validi)) {
        $stmt = $pdo->prepare("UPDATE ordinazione SET Stato = :stato WHERE ID_Ordinazione = :id");
        $stmt->execute([':stato' => $stato, ':id' => $id]);
    }
}

header("Location: ../gestione_ordini.php");
exit;
