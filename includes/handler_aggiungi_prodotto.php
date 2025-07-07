<?php
require_once 'dbh.inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $prezzo = $_POST['prezzo'];
    $descrizione = $_POST['descrizione'];
    $categoria_id = $_POST['categoria_id'];
    $ingredienti = $_POST['ingredienti'] ?? [];
    $allergeni = $_POST['allergeni'] ?? [];

    try {
        $pdo->beginTransaction();

        // Inserisci prodotto
        $stmt = $pdo->prepare("INSERT INTO prodotto (Nome, Prezzo, ID_Categoria, Descrizione) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $prezzo, $categoria_id, $descrizione]);
        $prodotto_id = $pdo->lastInsertId();

        // Inserisci ingredienti
        $stmtIng = $pdo->prepare("INSERT INTO prodotti_ingredienti (ID_Prodotto, ID_Ingrediente) VALUES (?, ?)");
        foreach ($ingredienti as $idIng) {
            $stmtIng->execute([$prodotto_id, $idIng]);
        }

        // Inserisci allergeni
        $stmtAll = $pdo->prepare("INSERT INTO prodotti_allergeni (ID_Prodotto, ID_Allergene) VALUES (?, ?)");
        foreach ($allergeni as $idAll) {
            $stmtAll->execute([$prodotto_id, $idAll]);
        }

        $pdo->commit();
        header('Location: ../aggiungi_elementi_2.php?success=1');
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Errore: " . $e->getMessage();
    }
}

