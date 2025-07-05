<?php
session_start();
require_once 'dbh_test.inc.php';

if (!isset($_SESSION['utente_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tipo = $_POST['tipo'] ?? null;
    $id = $_POST['id'] ?? null;
    $utente_id = $_SESSION['utente_id'];

    if (!$tipo || !$id || !in_array($tipo, ['ordine', 'prenotazione'])) {
        header("Location: ../utente.php?errore=parametri");
        exit;
    }

    try {
        if ($tipo === 'ordine') {
            $query = "DELETE FROM ordinazione WHERE ID_Ordinazione = :id AND ID_Utente = :utente";
        } elseif ($tipo === 'prenotazione') {
            $query = "DELETE FROM prenotazione WHERE ID_Prenotazione = :id AND ID_Utente = :utente";
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':id' => $id,
            ':utente' => $utente_id
        ]);

        header("Location: ../utente.php?successo=1");
        exit;

    } catch (PDOException $e) {
        // Se vuoi loggare l'errore: error_log($e->getMessage());
        header("Location: ../utente.php?errore=db");
        exit;
    }
}

header("Location: ../utente.php");
exit;
