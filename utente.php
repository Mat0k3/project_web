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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testo'], $_POST['voto']) && isset($_SESSION['utente_id'])) {
    $utenteId = $_SESSION['utente_id'];
    $testo = trim($_POST['testo']);
    $voto = (int)$_POST['voto'];

    if ($testo !== '' && $voto >= 1 && $voto <= 5) {
        try {
            $stmt = $pdo->prepare("INSERT INTO recensione (ID_Utente, Testo, Voto, Data) VALUES (?, ?, ?, CURDATE())");
            $stmt->execute([$utenteId, $testo, $voto]);
            header("Location: utente.php?recensione=ok");
            exit;
        } catch (PDOException $e) {
            header("Location: utente.php?recensione=errore");
            exit;
        }
    } else {
        header("Location: utente.php?recensione=errore");
        exit;
    }
}
?>

<?php include 'header.php'; ?>


<div class="container mt-5">
  <h2 class="text-center mb-4 spazio">Benvenuto, <?php echo htmlspecialchars($nome); ?></h2>

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
            echo "<strong>Nome:</strong> " . htmlspecialchars($p['Nome_Prenotazione']) . " | ";
            echo "<strong>Data:</strong> " . htmlspecialchars($p['Data']) . " | ";
            echo "<strong>Ora:</strong> " . htmlspecialchars($p['Ora']) . " | ";
            echo "<strong>Persone:</strong> " . htmlspecialchars($p['Persone']) . " | ";
            echo "<strong>Stato:</strong> " . htmlspecialchars($p['Stato']);
            echo '
                <form action="includes/delete_handler.inc.php" method="post" class="d-inline float-end ms-2">
                    <input type="hidden" name="tipo" value="prenotazione">
                    <input type="hidden" name="id" value="' . $p['ID_Prenotazione'] . '">
                    <button type="button" class="btn btn-sm btn-outline-danger delete-btn" title="Annulla prenotazione">
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

<section class="py-5 bg-light">
    <div class="container">
      <h3 class="text-center mb-4">Lascia una recensione</h3>
      <form method="POST" class="mx-auto" style="max-width: 600px;">
        <div class="mb-3">
          <label for="testo" class="form-label">Recensione</label>
          <textarea class="form-control" id="testo" name="testo" rows="4" required></textarea>
        </div>
        <div class="mb-3 d-flex align-items-end gap-3">
          <div class="flex-grow-1">
            <label for="voto" class="form-label d-block">Voto</label>
            <select class="" id="voto" name="voto" required>
              <option value="" selected disabled>Seleziona un voto</option>
              <option value="1">1 - Pessimo</option>
              <option value="2">2 - Scarso</option>
              <option value="3">3 - Discreto</option>
              <option value="4">4 - Buono</option>
              <option value="5">5 - Eccellente</option>
            </select>
          </div>
          <div>
            <button type="submit" class="btn btn-warning px-4">Invia</button>
          </div>
        </div>
      </form>
    </div>
  </section>

  <!-- Modal per conferma eliminazione -->
<div id="deleteModal" class="custom-modal">
    <div class="modal-content-custom">
        <div class="icon-circle">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="modal-title-custom">Conferma eliminazione</div>
        <div class="modal-text-custom">
            Sei sicuro di voler annullare questa prenotazione?<br>
            <strong>Questa azione non può essere annullata.</strong>
        </div>
        <div>
    <button type="button" class="btn btn-custom-confirm" id="confirmDelete">
        Elimina
    </button>
    <button type="button" class="btn btn-custom-cancel" id="cancelDelete">
        Annulla
    </button>
</div>
    </div>
</div>

<script>
const modal = document.getElementById('deleteModal');
const confirmBtn = document.getElementById('confirmDelete');
const cancelBtn = document.getElementById('cancelDelete');
let currentForm = null;

document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        currentForm = this.closest('form');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    });
});

confirmBtn.addEventListener('click', function() {
    if (currentForm) {
        currentForm.submit();
    }
});

cancelBtn.addEventListener('click', function() {
    modal.classList.remove('show');
    document.body.style.overflow = '';
    currentForm = null;
});

modal.addEventListener('click', function(e) {
    if (e.target === modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        currentForm = null;
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal.classList.contains('show')) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        currentForm = null;
    }
});
</script>
  
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<?php include 'footer.php'; ?>
