<?php
session_start();
require_once 'includes/dbh_test.inc.php';

if (!isset($_SESSION['utente_id'])) {
    header("Location: login.php");
    exit;
}

$utente_id = $_SESSION['utente_id'];

// Recupera nome utente
$stmt = $pdo->prepare("SELECT Nome FROM utente WHERE ID_Utente = :id");
$stmt->execute([':id' => $utente_id]);
$utente = $stmt->fetch();
$nome = $utente['Nome'] ?? 'Utente';

// Recupera gli ordini
$ordini = $pdo->prepare("SELECT * FROM ordinazione WHERE ID_Utente = :id ORDER BY Data DESC");
$ordini->execute([':id' => $utente_id]);
$lista_ordini = $ordini->fetchAll();
?>

<?php include 'header.php'; ?>


<div class="container mt-5">
  <h2 class="text-center mb-4">Benvenuto, <?php echo htmlspecialchars($nome); ?></h2>

  <div class="text-end mb-3">
    <a href="logout.php" class="btn btn-danger">Logout</a>
  </div>

  <h4>Ordini in corso</h4>
  <div class="list-group mb-4">
    <?php
    $trovato = false;
    foreach ($lista_ordini as $ordine) {
        if ($ordine['Stato'] !== 'Consegnato') {
            $trovato = true;
            echo '<div class="list-group-item">';
            echo "<strong>ID:</strong> {$ordine['ID_Ordinazione']} | ";
            echo "<strong>Data:</strong> {$ordine['Data']} | ";
            echo "<strong>Stato:</strong> {$ordine['Stato']} | ";
            echo "<strong>Costo:</strong> €" . number_format($ordine['Costo'], 2, ',', '.');
            echo '</div>';
        }
    }
    if (!$trovato) echo '<div class="list-group-item text-muted">Nessun ordine in sospeso.</div>';
    ?>
  </div>


  <h4>Ordini consegnati</h4>
  <div class="list-group">
    <?php
    $trovato = false;
    foreach ($lista_ordini as $ordine) {
        if ($ordine['Stato'] === 'Consegnato') {
            $trovato = true;
            echo '<div class="list-group-item">';
            echo "<strong>ID:</strong> {$ordine['ID_Ordinazione']} | ";
            echo "<strong>Data:</strong> {$ordine['Data']} | ";
            echo "<strong>Costo:</strong> €" . number_format($ordine['Costo'], 2, ',', '.');
            echo '</div>';
        }
    }
    if (!$trovato) echo '<div class="list-group-item text-muted">Nessun ordine consegnato.</div>';
    ?>
  </div>

  <h4>Prenotazioni tavoli</h4>
  <div class="list-group mb-5">
    <?php
    $prenotazioni = $pdo->prepare("SELECT * FROM prenotazione WHERE ID_Utente = :id ORDER BY Data DESC, Ora DESC");
    $prenotazioni->execute([':id' => $utente_id]);
    $lista_prenotazioni = $prenotazioni->fetchAll();

    if (count($lista_prenotazioni) === 0) {
        echo '<div class="list-group-item text-muted">Nessuna prenotazione trovata.</div>';
    } else {
        foreach ($lista_prenotazioni as $p) {
            echo '<div class="list-group-item">';
            echo "<strong>Data:</strong> " . htmlspecialchars($p['Data']) . " | ";
            echo "<strong>Ora:</strong> " . htmlspecialchars($p['Ora']) . " | ";
            echo "<strong>Persone:</strong> " . htmlspecialchars($p['Persone']) . " | ";
            echo "<strong>Stato:</strong> " . htmlspecialchars($p['Stato']);
            echo '
                <form action="includes/delete_handler.inc.php" method="post" class="d-inline float-end ms-2">
                    <input type="hidden" name="tipo" value="prenotazione">
                    <input type="hidden" name="id" value="' . $p['ID_Prenotazione'] . '">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Annulla ordine" onclick="return confirm(\'Confermi la cancellazione dell\\\'ordine?\')">
                        <i class="fa fa-trash" aria-hidden="true"></i>
                    </button>
                </form>
            ';
            echo '</div>';
        }
    }
    ?>
  </div>

</div>

<?php include 'footer.php'; ?>
