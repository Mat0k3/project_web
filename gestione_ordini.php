<?php
session_start();
require_once 'includes/dbh_test.inc.php';

if (!isset($_SESSION['utente_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM ordinazione ORDER BY Data DESC");
$ordini = $stmt->fetchAll();
?>

<?php include 'header.php'; ?>
<link rel="stylesheet" href="css/dashboard.css">

<div class="container mt-5">
  <h2 class="text-center mb-4 spazio">Gestione Ordini</h2>

  <!-- ORDINI NON CONSEGNATI -->
  <h4 class="mb-3" style="color: #ffbe33;">Ordini Attivi</h4>
  <div class="row g-4 mb-5">
    <?php
    $attivi = 0;
    foreach ($ordini as $ordine):
      if ($ordine['Stato'] !== 'Consegnato'):
        $attivi++;
    ?>
      <div class="col-12">
        <div class="card p-3 shadow-sm">
            <!-- Riga dettagli ordine -->
            <div class="mb-2">
                <strong>ID:</strong> <?= $ordine['ID_Ordinazione'] ?> |
                <strong>Data:</strong> <?= $ordine['Data'] ?> |
                <strong>Stato:</strong> <?= $ordine['Stato']=='In consegna'?'Pronto':'In preparazione' ?> |
                <strong>Costo:</strong> €<?= number_format($ordine['Costo'], 2, ',', '.') ?>
            </div>

            <!-- Riga pulsanti -->
            <div class="d-flex justify-content-center justify-content-md-end flex-wrap gap-2">
                <form action="includes/update_stato_ordine.inc.php" method="post" class="d-inline">
                <input type="hidden" name="id" value="<?= $ordine['ID_Ordinazione'] ?>">
                <?php if ($ordine['Stato'] === 'In preparazione'): ?>
                    <button name="stato" value="In consegna" class="btn btn-outline-dark btn-sm">
                    Pronto
                    </button>
                <?php endif; ?>
                <?php if (in_array($ordine['Stato'], ['In preparazione', 'In consegna'])): ?>
                    <button name="stato" value="Consegnato" class="btn btn-outline-success btn-sm">
                    Ritirato
                    </button>
                <?php endif; ?>
                </form>
            </div>
        </div>


      </div>
    <?php endif; endforeach; ?>

    <?php if ($attivi === 0): ?>
      <div class="col-12 text-muted">Nessun ordine attivo al momento.</div>
    <?php endif; ?>
  </div>

  <!-- ORDINI CONSEGNATI -->
  <h4 class="mb-3" style="color: #ffbe33;">Ordini Ritirati</h4>
  <div class="row g-4 mb-5">
    <?php
    $consegnati = 0;
    foreach ($ordini as $ordine):
      if ($ordine['Stato'] === 'Consegnato'):
        $consegnati++;
    ?>
      <div class="col-12">
        <div class="card p-3 bg-light border">
          <div><strong>ID:</strong> <?= $ordine['ID_Ordinazione'] ?> |
               <strong>Data:</strong> <?= $ordine['Data'] ?> |
               <strong>Costo:</strong> €<?= number_format($ordine['Costo'], 2, ',', '.') ?></div>
        </div>
      </div>
    <?php endif; endforeach; ?>

    <?php if ($consegnati === 0): ?>
      <div class="col-12 text-muted">Nessun ordine consegnato ancora.</div>
    <?php endif; ?>
  </div>
</div>

<?php include 'footer.php'; ?>
