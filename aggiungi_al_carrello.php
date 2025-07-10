<?php
session_start();
require_once 'includes/dbh.inc.php';

if (!isset($_SESSION['utente_id'])) {
    http_response_code(403);
    echo 'Utente non autenticato';
    exit;
}

$userId = $_SESSION['utente_id'];
$id = (int)($_POST['id'] ?? 0);
$tipo = $_POST['tipo'] ?? '';

if (!$id || !in_array($tipo, ['prodotto', 'menu'])) {
    http_response_code(400);
    echo 'Richiesta non valida';
    exit;
}

// Verifica se l'utente appartiene al gruppo "utenti"
$stmt = $pdo->prepare("
    SELECT ug.ID_Utente 
    FROM utente_gruppo ug 
    JOIN gruppo g ON ug.ID_Gruppo = g.ID_Gruppo 
    WHERE ug.ID_Utente = ? AND g.Nome = 'utenti'
");
$stmt->execute([$userId]);
$isUserInGroup = $stmt->fetchColumn();

if (!$isUserInGroup) {
    http_response_code(403);
    echo 'Non hai i permessi per aggiungere elementi al carrello';
    exit;
}

// Trova ID_Carrello dell'utente
$stmt = $pdo->prepare("SELECT ID_Carrello FROM carrello WHERE ID_Utente = ?");
$stmt->execute([$userId]);
$carrello = $stmt->fetchColumn();

if (!$carrello) {
    // Crea nuovo carrello se non esiste
    $stmt = $pdo->prepare("INSERT INTO carrello (ID_Utente) VALUES (?)");
    $stmt->execute([$userId]);
    $carrello = $pdo->lastInsertId();
}

// Inserimento o incremento quantità
if ($tipo === 'prodotto') {
    $stmt = $pdo->prepare("SELECT Quantità FROM prodotti_carrello WHERE ID_Carrello = ? AND ID_Prodotto = ?");
    $stmt->execute([$carrello, $id]);
    $quantita = $stmt->fetchColumn();

    if ($quantita) {
        $stmt = $pdo->prepare("UPDATE prodotti_carrello SET Quantità = Quantità + 1 WHERE ID_Carrello = ? AND ID_Prodotto = ?");
        $stmt->execute([$carrello, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO prodotti_carrello (ID_Carrello, ID_Prodotto, Quantità) VALUES (?, ?, 1)");
        $stmt->execute([$carrello, $id]);
    }
} else if ($tipo === 'menu') {
    $stmt = $pdo->prepare("SELECT Quantità FROM menu_carrello WHERE ID_Carrello = ? AND ID_Menu = ?");
    $stmt->execute([$carrello, $id]);
    $quantita = $stmt->fetchColumn();

    if ($quantita) {
        $stmt = $pdo->prepare("UPDATE menu_carrello SET Quantità = Quantità + 1 WHERE ID_Carrello = ? AND ID_Menu = ?");
        $stmt->execute([$carrello, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO menu_carrello (ID_Carrello, ID_Menu, Quantità) VALUES (?, ?, 1)");
        $stmt->execute([$carrello, $id]);
    }
}

echo 'success';
?>