<?php
session_start();
require_once 'dbh.inc.php';

if (!isset($_SESSION['utente_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_SESSION['utente_id'])) {

    // Recupera il gruppo dell’utente
    $stmt = $pdo->prepare("
        SELECT g.Nome 
        FROM gruppo g
        JOIN utente_gruppo ug ON ug.ID_Gruppo = g.ID_Gruppo
        WHERE ug.ID_Utente = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $_SESSION['utente_id']]);
    $gruppo = strtolower($stmt->fetchColumn());

    // Reindirizzamento in base al gruppo
    if ($gruppo === 'admin' || $gruppo === 'cucina') {
        header("Location: ../errore_permessi.php");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idUtente = $_SESSION['utente_id'];

    // Recupera i dati
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $data = $_POST['data'] ?? null;
    $ora = $_POST['ora'] ?? null;
    $persone = $_POST['persone'] ?? null;

    // Validazioni
    if (
        empty($nome) ||
        empty($email) ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !$data || !$ora || !$persone || (int)$persone <= 0
    ) {
        header("Location: ../book.php?errore=input_non_valido");
        exit;
    }

    // Inserisci nel database
    try {
        $stmt = $pdo->prepare("
            INSERT INTO prenotazione (ID_Utente, Nome_Prenotazione, Data, Ora, Persone, Stato)
            VALUES (:id, :nome, :data, :ora, :persone, 'In sospeso')
        ");
        $stmt->execute([
            ':id' => $idUtente,
            ':nome' => $nome,
            ':data' => $data,
            ':ora' => $ora,
            ':persone' => $persone
        ]);

        // Eventualmente: salva nome/email in sessione temporanea
        //$_SESSION['ultimo_nome_prenotazione'] = $nome;
        //$_SESSION['ultima_email_prenotazione'] = $email;

        header("Location: ../utente.php?prenotazione=ok");
        exit;

    } catch (PDOException $e) {
        header("Location: ../book.php?errore=db");
        exit;
    }
} else {
    header("Location: ../book.php");
    exit;
}
