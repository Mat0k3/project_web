<?php
require_once 'dbh.inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $descrizione = trim($_POST['descrizione']);
    $prodotto_panino = $_POST['prodotto_panino'] ?? null;
    $prodotto_bevanda = $_POST['prodotto_bevanda'] ?? null;
    $prodotto_fritti = $_POST['prodotto_fritti'] ?? null;
    
    if (empty($nome)) {
        header('Location: ../aggiungi_elementi_2.php?error=Nome menu richiesto');
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Inserisci il menu
        $stmt = $pdo->prepare("INSERT INTO menu (Nome, Descrizione) VALUES (?, ?)");
        $stmt->execute([$nome, $descrizione]);
        $menu_id = $pdo->lastInsertId();
        
        // Inserisci i prodotti del menu
        $stmtProdotto = $pdo->prepare("INSERT INTO prodotti_menu (ID_Prodotto, ID_Menu) VALUES (?, ?)");
        
        if (!empty($prodotto_panino)) {
            $stmtProdotto->execute([$prodotto_panino, $menu_id]);
        }
        
        if (!empty($prodotto_bevanda)) {
            $stmtProdotto->execute([$prodotto_bevanda, $menu_id]);
        }
        
        if (!empty($prodotto_fritti)) {
            $stmtProdotto->execute([$prodotto_fritti, $menu_id]);
        }
        
        $pdo->commit();
        header('Location: ../aggiungi_elementi_2.php?success=Menu aggiunto con successo');
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Location: ../aggiungi_elementi_2.php?error=Errore durante l\'inserimento: ' . $e->getMessage());
    }
} else {
    header('Location: ../aggiungi_elementi_2.php');
}
