<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $allergene = $_POST["allergene"];

    try {
        require_once "dbh.inc.php";

        $query = "INSERT INTO allergene (Nome) VALUES (:allergene);";

        $stmt = $pdo->prepare($query);

        $stmt->bindParam(":allergene", $allergene);

        $stmt->execute();

        $pdo = null;
        $stmt = null;

        header("Location: ../index.php");

        die();
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
}else{
    header("Location: ../index.php");
}