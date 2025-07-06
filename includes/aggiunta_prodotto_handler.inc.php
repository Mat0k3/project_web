<?php
require_once 'dbh_test.inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';

    try {
        if ($tipo === 'allergene') {
            $stmt = $pdo->prepare("INSERT INTO allergene (Nome) VALUES (:nome)");
            $stmt->execute([':nome' => $_POST['nome']]);
            echo "Allergene aggiunto con successo.";

        } elseif ($tipo === 'ingrediente') {
            $stmt = $pdo->prepare("INSERT INTO ingrediente (Nome) VALUES (:nome)");
            $stmt->execute([':nome' => $_POST['nome']]);
            echo "Ingrediente aggiunto con successo.";

        } elseif ($tipo === 'menu') {
            $dati = $_POST['dati'];
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO menu (Nome, Descrizione) VALUES (:n, :d)");
            $stmt->execute([':n' => $dati['nome'], ':d' => $dati['descrizione']]);
            $menu_id = $pdo->lastInsertId();

            $stmt2 = $pdo->prepare("INSERT INTO prodotti_menu (ID_Prodotto, ID_Menu) VALUES (:p, :m)");
            foreach (['panino', 'bevanda', 'fritto'] as $key) {
                if (!empty($dati[$key])) {
                    $stmt2->execute([':p' => $dati[$key], ':m' => $menu_id]);
                }
            }

            $pdo->commit();
            echo "Menu e prodotti associati aggiunti.";

        } elseif ($tipo === 'prodotto') {
            $dati = json_decode($_POST['dati'], true);
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO prodotto (Nome, Prezzo, ID_Categoria, Descrizione) VALUES (:n, :p, :c, :d)");
            $stmt->execute([
                ':n' => $dati['nome'],
                ':p' => $dati['prezzo'],
                ':c' => $dati['categoria'],
                ':d' => $dati['descrizione']
            ]);
            $prod_id = $pdo->lastInsertId();

            // Allergeni
            if (!empty($dati['allergeni'])) {
                $stmt2 = $pdo->prepare("INSERT INTO prodotti_allergeni (ID_Prodotto, ID_Allergene) VALUES (:p, :a)");
                foreach ($dati['allergeni'] as $a) {
                    if ($a) $stmt2->execute([':p' => $prod_id, ':a' => $a]);
                }
            }

            // Ingredienti
            if (!empty($dati['ingredienti'])) {
                $stmt3 = $pdo->prepare("INSERT INTO prodotto_ingrediente (ID_Prodotto, ID_Ingrediente) VALUES (:p, :i)");
                foreach ($dati['ingredienti'] as $i) {
                    if ($i) $stmt3->execute([':p' => $prod_id, ':i' => $i]);
                }
            }

            $pdo->commit();
            echo "Prodotto inserito con allergeni e ingredienti.";

        } else {
            http_response_code(400);
            echo "Tipo non valido.";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo "Errore: " . $e->getMessage();
    }
}
