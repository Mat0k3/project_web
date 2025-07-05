<?php

$dsn = "mysql:host=localhost;dbname=ristorante;charset=utf8mb4";
$dbusername = "root";
$dbpassword = "";

try {
    $pdo = new PDO($dsn, $dbusername, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log l'errore invece di mostrarlo all'utente
    error_log("Database connection failed: " . $e->getMessage());
    die("Errore di connessione al database. Contatta l'amministratore.");
}

// Test della connessione (rimuovi dopo aver verificato)
// echo "Connessione al database riuscita!";
