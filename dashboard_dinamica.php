<?php
session_start();
require_once 'includes/dbh_test.inc.php';

if (!isset($_SESSION['utente_id'])) {
    header("Location: login.php");
    exit;
}

$utente_id = $_SESSION['utente_id'];

// Recupera il nome dell’utente
$stmtNome = $pdo->prepare("SELECT Nome FROM utente WHERE ID_Utente = :id");
$stmtNome->execute([':id' => $utente_id]);
$nome_utente = $stmtNome->fetchColumn();

// Recupera i servizi dell’utente tramite i gruppi
$query = "
  SELECT s.Nome 
  FROM servizio s
  JOIN gruppo_servizio gs ON gs.ID_Servizio = s.ID_Servizio
  JOIN utente_gruppo ug ON ug.ID_Gruppo = gs.ID_Gruppo
  WHERE ug.ID_Utente = :id
";
$stmt = $pdo->prepare($query);
$stmt->execute([':id' => $utente_id]);
$servizi = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Mappa: nome servizio → link
$link_servizi = [
    "gestione ordini" => "gestione_ordini.php",
    "gestione prenotazioni" => "gestione_prenotazioni.php",
    "aggiunta prodotti" => "aggiungi_prodotto.php",
    "modifica prodotti" => "modifica_prodotto.php",
    "aggiunta offerte" => "aggiungi_offerta.php",
    "modifica offerte" => "modifica_offerta.php"
];

// Calcola saldo se ha accesso
$mostra_saldo = in_array("saldo", array_map('strtolower', $servizi));
$saldo_totale = 0;
$saldo_giornaliero = 0;

if ($mostra_saldo) {
    $stmtTot = $pdo->query("SELECT SUM(Costo) FROM ordinazione");
    $saldo_totale = $stmtTot->fetchColumn() ?? 0;

    $oggi = date('Y-m-d');
    $stmtGiorno = $pdo->prepare("SELECT SUM(Costo) FROM ordinazione WHERE DATE(Data) = :oggi");
    $stmtGiorno->execute([':oggi' => $oggi]);
    $saldo_giornaliero = $stmtGiorno->fetchColumn() ?? 0;
}
?>

<?php include 'header.php'; ?>
<link rel="stylesheet" href="css/dashboard.css">

<div class="container mt-5">
  <h2 class="text-center mb-4">Dashboard <?php echo htmlspecialchars($nome_utente); ?></h2>

  <div class="text-end mb-3">
    <a href="logout.php" class="btn btn-danger">Logout</a>
  </div>

  <?php if ($mostra_saldo): ?>
  <div class="row mb-5">
    <div class="col-12 col-md-6 mb-4 mb-md-0">
      <div class="card stat-card">
        <h5 class="card-title">Guadagno giornaliero</h5>
        <p class="card-text">€ <?php echo number_format($saldo_giornaliero, 2, ',', '.'); ?></p>
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="card stat-card">
        <h5 class="card-title">Guadagno totale</h5>
        <p class="card-text">€ <?php echo number_format($saldo_totale, 2, ',', '.'); ?></p>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (empty($servizi)): ?>
    <div class="alert alert-warning text-center">Nessun servizio abilitato per il tuo account.</div>
  <?php else: ?>
    <?php
        $servizi_utili = array_filter($servizi, function ($s) use ($link_servizi) {
            return strtolower($s) !== "saldo" && isset($link_servizi[strtolower($s)]);
        });

        $num_servizi = count($servizi_utili);
        $aggiungi_spazio_extra = $num_servizi <= 2;
    ?>
    <div class="row g-4 margine_basso <?php echo $aggiungi_spazio_extra ? 'extra-bottom-space' : ''; ?>">
      <?php
        
        $index = 0;

        foreach ($servizi_utili as $nome_servizio):
            $key = strtolower($nome_servizio);
            $link = $link_servizi[$key];
            $index++;

            // Se è l’ultimo ed è dispari, centriamolo
            $is_last_odd = ($num_servizi % 2 === 1) && ($index === $num_servizi);
            $col_class = $is_last_odd ? 'col-12 col-md-6 offset-md-3' : 'col-12 col-md-6';
        ?>
        <div class="<?php echo $col_class; ?>">
            <a href="<?php echo $link; ?>" class="dashboard-btn-block text-center">
            <?php echo ucfirst($nome_servizio); ?>
            </a>
        </div>
        <?php endforeach; ?>

    </div>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
