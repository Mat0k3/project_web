<?php
require_once 'dbh.inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    
    if (empty($nome)) {
        header('Location: ../aggiungi_elementi_2.php?error=Nome allergene richiesto');
        exit;
    }
    
    try {
        // Controlla se l'allergene esiste già
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM allergene WHERE Nome = ?");
        $checkStmt->execute([$nome]);
        
        if ($checkStmt->fetchColumn() > 0) {
            header('Location: ../aggiungi_elementi_2.php?error=Allergene già esistente');
            exit;
        }
        
        // Inserisci nuovo allergene
        $stmt = $pdo->prepare("INSERT INTO allergene (Nome) VALUES (?)");
        $stmt->execute([$nome]);
        
        header('Location: ../aggiungi_elementi_2.php?success=Allergene aggiunto con successo');
    } catch (Exception $e) {
        header('Location: ../aggiungi_elementi_2.php?error=Errore durante l\'inserimento: ' . $e->getMessage());
    }
} else {
    header('Location: ../aggiungi_elementi_2.php');
}
