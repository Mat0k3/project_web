<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome"];
    $telefono = $_POST["telefono"];
    $email = $_POST["email"];
    $persone = $_POST["persone"];
    $data = $_POST["data"];
    $ora = $_POST["ora"];

    try {
        require_once "dbh.inc.php";

        // Prima dobbiamo verificare se l'utente esiste già o crearlo
        $queryUser = "SELECT ID_Utente FROM utente WHERE Email = :email";
        $stmtUser = $pdo->prepare($queryUser);
        $stmtUser->bindParam(":email", $email);
        $stmtUser->execute();
        
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // L'utente esiste già, usa il suo ID
            $userId = $user['ID_Utente'];
        } else {
            // Crea un nuovo utente
            $queryNewUser = "INSERT INTO utente (Nome, Email, Password) VALUES (:nome, :email, :password)";
            $stmtNewUser = $pdo->prepare($queryNewUser);
            
            // Password temporanea per utenti che fanno solo prenotazioni
            $tempPassword = password_hash("temp_" . time(), PASSWORD_DEFAULT);
            
            $stmtNewUser->bindParam(":nome", $nome);
            $stmtNewUser->bindParam(":email", $email);
            $stmtNewUser->bindParam(":password", $tempPassword);
            $stmtNewUser->execute();
            
            $userId = $pdo->lastInsertId();
        }

        // Inserisci la prenotazione
        $query = "INSERT INTO prenotazione (ID_Utente, Data, Ora, Persone, Stato) VALUES (:id_utente, :data, :ora, :persone, 'In sospeso')";

        $stmt = $pdo->prepare($query);

        $stmt->bindParam(":id_utente", $userId);
        $stmt->bindParam(":data", $data);
        $stmt->bindParam(":ora", $ora);
        $stmt->bindParam(":persone", $persone);

        $stmt->execute();

        $pdo = null;
        $stmt = null;
        $stmtUser = null;

        header("Location: ../index.php?booking=success");

        die();
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
} else {
    header("Location: ../index.php");
}