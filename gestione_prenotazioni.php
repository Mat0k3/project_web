<?php
session_start();
require_once 'includes/dbh_test.inc.php';

if (!isset($_SESSION['utente_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM prenotazione ORDER BY Data DESC, Ora DESC");
$prenotazioni = $stmt->fetchAll();
?>

<?php include 'header.php'; ?>
<link rel="stylesheet" href="css/dashboard.css">

<div class="container mt-5">
  <h2 class="text-center mb-4">Gestione Prenotazioni</h2>

  <!-- PRENOTAZIONI IN SOSPESO -->
  <h4 class="mb-3" style="color: #ffbe33;">Prenotazioni in sospeso</h4>
  <div class="row g-4 mb-5">
    <?php
    $in_sospeso = 0;
    foreach ($prenotazioni as $p):
      if ($p['Stato'] === 'In sospeso'):
        $in_sospeso++;
    ?>
      <div class="col-12">
        <div class="card p-3 shadow-sm">
          <!-- Riga 1: dettagli -->
          <div class="mb-2">
            <strong>ID:</strong> <?= $p['ID_Prenotazione'] ?> |
            <strong>Nome:</strong> <?= $p['Nome_Prenotazione'] ?> |
            <strong>Data:</strong> <?= $p['Data'] ?> |
            <strong>Ora:</strong> <?= $p['Ora'] ?> |
            <strong>Persone:</strong> <?= $p['Persone'] ?>
          </div>

          <!-- Riga 2: pulsanti -->
          <div class="d-flex justify-content-center justify-content-md-end flex-wrap gap-2">
            <form action="includes/update_stato_prenotazione.inc.php" method="post" class="d-inline">
              <input type="hidden" name="id" value="<?= $p['ID_Prenotazione'] ?>">
              <button name="stato" value="Rifiutata" class="btn btn-outline-danger btn-sm">Rifiuta</button>
              <button name="stato" value="Accettata" class="btn btn-outline-success btn-sm">Accetta</button>
            </form>
          </div>
        </div>
      </div>
    <?php endif; endforeach; ?>

    <?php if ($in_sospeso === 0): ?>
      <div class="col-12 text-muted">Nessuna prenotazione in sospeso.</div>
    <?php endif; ?>
  </div>

  <!-- PRENOTAZIONI GESTITE -->
  <h4 class="mb-3" style="color: #ffbe33;">Prenotazioni gestite</h4>
  <div class="row g-4 mb-5">
    <?php
    $gestite = 0;
    foreach ($prenotazioni as $p):
      if ($p['Stato'] !== 'In sospeso'):
        $gestite++;
    ?>
      <div class="col-12">
        <div class="card p-3 bg-light border">
          <div>
            <strong>ID:</strong> <?= $p['ID_Prenotazione'] ?> |
            <strong>Nome:</strong> <?= $p['Nome_Prenotazione'] ?> |
            <strong>Data:</strong> <?= $p['Data'] ?> |
            <strong>Ora:</strong> <?= $p['Ora'] ?> |
            <strong>Persone:</strong> <?= $p['Persone'] ?> |
            <strong>Stato:</strong> <?= $p['Stato'] ?>
          </div>
        </div>
      </div>
    <?php endif; endforeach; ?>

    <?php if ($gestite === 0): ?>
      <div class="col-12 text-muted">Nessuna prenotazione gestita.</div>
    <?php endif; ?>
  </div>
</div>

<?php include 'footer.php'; ?>
