<?php
// Test della connessione al database

$dsn = "mysql:host=localhost;dbname=ristorante;charset=utf8mb4";
$dbusername = "root";
$dbpassword = "";

echo "<h2>Test Connessione Database</h2>";

try {
    $pdo = new PDO($dsn, $dbusername, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Connessione al database riuscita!</p>";
    
    // Test se la tabella utente esiste
    $stmt = $pdo->query("SHOW TABLES LIKE 'utente'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Tabella 'utente' trovata!</p>";
        
        // Mostra la struttura della tabella
        $stmt = $pdo->query("DESCRIBE utente");
        $columns = $stmt->fetchAll();
        
        echo "<h3>Struttura tabella 'utente':</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li><strong>" . $column['Field'] . "</strong> - " . $column['Type'] . "</li>";
        }
        echo "</ul>";
        
    } else {
        echo "<p style='color: red;'>✗ Tabella 'utente' non trovata!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Errore: " . $e->getMessage() . "</p>";
}

// Test servizi
echo "<h3>Informazioni Server:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>MySQL disponibile: " . (extension_loaded('pdo_mysql') ? 'Sì' : 'No') . "</p>";
?>

<!-- Elimina questo file dopo aver fatto i test -->