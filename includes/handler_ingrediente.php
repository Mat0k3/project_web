<?php
require_once 'dbh.inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    
    if (empty($nome)) {
        header('Location: ../aggiungi_elementi_2.php?error=Nome ingrediente richiesto');
        exit;
    }
    
    try {
        // Controlla se l'ingrediente esiste già
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM ingrediente WHERE Nome = ?");
        $checkStmt->execute([$nome]);
        
        if ($checkStmt->fetchColumn() > 0) {
            header('Location: ../aggiungi_elementi_2.php?error=Ingrediente già esistente');
            exit;
        }
        
        // Inserisci nuovo ingrediente
        $stmt = $pdo->prepare("INSERT INTO ingrediente (Nome) VALUES (?)");
        $stmt->execute([$nome]);
        
        header('Location: ../aggiungi_elementi_2.php?success=Ingrediente aggiunto con successo');
    } catch (Exception $e) {
        header('Location: ../aggiungi_elementi_2.php?error=Errore durante l\'inserimento: ' . $e->getMessage());
    }
} else {
    header('Location: ../aggiungi_elementi_2.php');
}
?>