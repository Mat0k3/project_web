<?php
session_start();

require_once 'includes/dbh_test.inc.php';
if (!isset($_SESSION['utente_id'])) {
    $_SESSION['prov'] = 'book';
    header("Location: login.php");
    exit;
  }
  
  function getOrCreateCartId($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT ID_Carrello FROM carrello WHERE ID_Utente = ?");
    $stmt->execute([$userId]);
    $id = $stmt->fetchColumn();
    if (!$id) {
      $pdo->prepare("INSERT INTO carrello (ID_Utente) VALUES (?)")->execute([$userId]);
      return $pdo->lastInsertId();
    }
    return $id;
  }
  
  $userId = $_SESSION['utente_id'];
  $cartId = getOrCreateCartId($pdo, $userId);
  
  // Gestione AJAX
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $tipo = $_POST['tipo'] ?? null;
    $id = (int)($_POST['id'] ?? 0);
    $qty = max(1, (int)($_POST['quantita'] ?? 1));
  
    if ($tipo === 'prodotto') {
      if ($action === 'update') {
        $pdo->prepare("UPDATE prodotti_carrello SET Quantità = ? WHERE ID_Carrello = ? AND ID_Prodotto = ?")
            ->execute([$qty, $cartId, $id]);
      } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM prodotti_carrello WHERE ID_Carrello = ? AND ID_Prodotto = ?")
            ->execute([$cartId, $id]);
      }
    } elseif ($tipo === 'menu') {
      if ($action === 'update') {
        $pdo->prepare("UPDATE menu_carrello SET Quantità = ? WHERE ID_Carrello = ? AND ID_Menu = ?")
            ->execute([$qty, $cartId, $id]);
      } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM menu_carrello WHERE ID_Carrello = ? AND ID_Menu = ?")
            ->execute([$cartId, $id]);
      }
    } elseif ($action === 'checkout') {
      try {
          $pdo->beginTransaction();
  
          // Calcolo costo totale
          $tot = 0;
          $stmt = $pdo->prepare("SELECT p.Prezzo, pc.Quantità FROM prodotti_carrello pc JOIN prodotto p USING(ID_Prodotto) WHERE pc.ID_Carrello = ?");
          $stmt->execute([$cartId]);
          foreach ($stmt->fetchAll() as $r) $tot += $r['Prezzo'] * $r['Quantità'];
  
          $stmt = $pdo->prepare("SELECT m.Prezzo, mc.Quantità FROM menu_carrello mc JOIN menu m USING(ID_Menu) WHERE mc.ID_Carrello = ?");
          $stmt->execute([$cartId]);
          foreach ($stmt->fetchAll() as $r) $tot += $r['Prezzo'] * $r['Quantità'];
  
          // Inserisci nella tabella ordinazione
          $stmt = $pdo->prepare("INSERT INTO ordinazione (ID_Utente, Data, Stato, Costo) VALUES (?, NOW(), 'In preparazione', ?)");
          $stmt->execute([$userId, $tot]);
          $id_ordinazione = $pdo->lastInsertId();
  
          // Se hai tabelle `ordinazione_prodotto` e `ordinazione_menu`, inseriscile qui (solo se esistono)
          // altrimenti puoi saltare queste righe.
  
          // Svuota carrello
          $pdo->prepare("DELETE FROM prodotti_carrello WHERE ID_Carrello = ?")->execute([$cartId]);
          $pdo->prepare("DELETE FROM menu_carrello WHERE ID_Carrello = ?")->execute([$cartId]);
  
          $pdo->commit();
  
          echo json_encode(['success' => true]);
      } catch (Exception $e) {
          $pdo->rollBack();
          echo json_encode(['success' => false, 'error' => $e->getMessage()]);
      }
      exit;
    }
  
    // Ricalcola totale
    $tot = 0;
    $stmt = $pdo->prepare("SELECT p.Prezzo, pc.Quantità FROM prodotti_carrello pc JOIN prodotto p USING(ID_Prodotto) WHERE pc.ID_Carrello = ?");
    $stmt->execute([$cartId]);
    foreach ($stmt->fetchAll() as $r) $tot += $r['Prezzo'] * $r['Quantità'];
  
    $stmt = $pdo->prepare("SELECT m.Prezzo, mc.Quantità FROM menu_carrello mc JOIN menu m USING(ID_Menu) WHERE mc.ID_Carrello = ?");
    $stmt->execute([$cartId]);
    foreach ($stmt->fetchAll() as $r) $tot += $r['Prezzo'] * $r['Quantità'];

    // Calcolo quantità totale
    $stmt = $pdo->prepare("SELECT SUM(Quantità) FROM prodotti_carrello WHERE ID_Carrello = ?");
    $stmt->execute([$cartId]);
    $quantitaProdotti = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT SUM(Quantità) FROM menu_carrello WHERE ID_Carrello = ?");
    $stmt->execute([$cartId]);
    $quantitaMenu = (int) $stmt->fetchColumn();

    $quantitaTotale = $quantitaProdotti + $quantitaMenu;

  
    echo json_encode([
      'success' => true,
      'totale' => number_format($tot, 2),
      'quantita_totale' => $quantitaTotale
    ]);
    
    exit;

  }
  
  // Caricamento dati carrello
  $stmtProdotti = $pdo->prepare("SELECT p.ID_Prodotto, p.Nome, p.Descrizione, p.Prezzo, pc.Quantità FROM prodotti_carrello pc JOIN prodotto p USING(ID_Prodotto) WHERE pc.ID_Carrello = ?");
  $stmtProdotti->execute([$cartId]);
  $prodotti = $stmtProdotti->fetchAll(PDO::FETCH_ASSOC);
  
  $stmtMenu = $pdo->prepare("SELECT m.ID_Menu, m.Nome, m.Descrizione, m.Prezzo, mc.Quantità FROM menu_carrello mc JOIN menu m USING(ID_Menu) WHERE mc.ID_Carrello = ?");
  $stmtMenu->execute([$cartId]);
  $menu = $stmtMenu->fetchAll(PDO::FETCH_ASSOC);
  
  $totale = 0;
  foreach ($prodotti as $p) $totale += $p['Prezzo'] * $p['Quantità'];
  foreach ($menu as $m)     $totale += $m['Prezzo'] * $m['Quantità'];
  $totaleFormatted = number_format($totale, 2);
  
  include 'header.php';
  ?>
<style>
  body {
    background-color: #f8f9fa;
    color: #2c3e50;
  }
  
  .carrello-table {
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border-radius: 12px;
    overflow: hidden;
    background-color: #ffffff;
    border: 1px solid #e9ecef;
  }
  
  .carrello-table thead th {
    background: linear-gradient(to right, #2a2e37 0%, #262e31 100%);
    color: #f39c12;
    font-weight: 600;
    border: none;
    padding: 18px;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 0.9em;
  }
  
  .carrello-table tbody tr {
    transition: all 0.3s ease;
    background-color: #ffffff;
    border-bottom: 1px solid #e9ecef;
  }
  
  
  .carrello-table td {
    vertical-align: middle;
    padding: 18px;
    border-bottom: 1px solid #e9ecef;
    color: #2c3e50;
    text-align: center;
  }
  
  .item-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  
  .item-name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 1.1em;
  }
  
  .item-description {
    color: #6c757d;
    font-size: 0.9em;
    line-height: 1.4;
  }
  
  .item-badge {
    display: inline-block;
    margin-top: 5px;
    background-color: #f39c12;
    color: #ffffff;
    font-weight: 600;
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 0.8em;
  }
  
  .qty-input {
    width: 80px;
    text-align: center;
    font-weight: 600;
    background-color: #ffffff;
    color: #2c3e50;
    border: 2px solid #f39c12;
    border-radius: 8px;
    padding: 8px;
  }
  
  .qty-input:focus {
    background-color: #ffffff;
    color: #2c3e50;
    border-color: #e67e22;
    box-shadow: 0 0 0 0.2rem rgba(243, 156, 18, 0.25);
    outline: none;
  }
  
  .price-cell {
    text-align: right;
    font-weight: 700;
    font-size: 1.2em;
    color: #f39c12;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
  }
  
  .remove-btn {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    background-color: #e74c3c;
    border: none;
    color: white;
  }
  
  .remove-btn:hover {
    transform: scale(1.1);
    background-color: #c0392b;
    box-shadow: 0 6px 12px rgba(231, 76, 60, 0.3);
  }
  
  .total-section {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    color: #2c3e50;
    padding: 0px 20px 20px 20px;
    border-radius: 12px;
    margin-top: 25px;
    border: 2px solid #f39c12;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  }
  
  .total-amount {
    font-size: 1.8em;
    font-weight: 700;
    color: #f39c12;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
  }
  
  .empty-cart {
    text-align: center;
    padding: 80px 20px;
    color: #6c757d;
    background-color: #ffffff;
    border-radius: 12px;
    border: 2px solid #e9ecef;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  }
  
  .empty-cart-icon {
    font-size: 5em;
    margin-bottom: 20px;
    opacity: 0.4;
    color: #f39c12;
  }
  
  .empty-cart h4 {
    color: #2c3e50;
    margin-bottom: 15px;
  }
  
  .btn-primary {
    background-color: #f39c12;
    border-color: #f39c12;
    color: #ffffff;
    font-weight: 600;
    transition: all 0.3s ease;
    border-radius: 8px;
    padding: 12px 24px;
  }
  
  .btn-primary:hover {
    background-color: #e67e22;
    border-color: #e67e22;
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(243, 156, 18, 0.3);
  }
  
  .btn-light {
    background-color: #f39c12;
    border-color: #f39c12;
    color: #ffffff;
    font-weight: 600;
    transition: all 0.3s ease;
    border-radius: 8px;
  }
  
  .btn-light:hover {
    background-color: #e67e22;
    border-color: #e67e22;
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(243, 156, 18, 0.3);
  }
  
  .container {
    max-width: 1200px;
  }
  
  h2 {
    color: #2c3e50;
    font-weight: 700;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
    margin-bottom: 30px;
  }
  
  .fas {
    color: #f39c12;
  }
  
  section {
    background-color: #f8f9fa;
    min-height: 100vh;
  }

  small {
    color:rgb(255, 255, 255);
  }

  @media (max-width: 768px) {
    .carrello-table {
      font-size: 0.9em;
    }
    
    .carrello-table th,
    .carrello-table td {
      padding: 12px 10px;
    }
    
    .item-name {
      font-size: 1em;
    }
    
    .qty-input {
      width: 60px;
      padding: 6px;
    }
    
    .total-section {
      padding: 20px;
    }
    
    .total-amount {
      font-size: 1.5em;
    }

  }
  </style>
  
  <section class="py-5">
    <div class="container spazio">
      <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
          <h2 class="mb-4 text-center">
            Il tuo carrello
          </h2>
          
          <?php if (empty($prodotti) && empty($menu)): ?>
            <div class="empty-cart">
              <div class="empty-cart-icon">🛒</div>
              <h4>Il tuo carrello è vuoto</h4>
              <p>Aggiungi alcuni prodotti per iniziare!</p>
              <a href="menu.php" class="btn btn-primary">Vai al Menu</a>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table carrello-table">
                <thead>
                  <tr>
                    <th style="width: 45%;" ><div class='centered'>Prodotto</div></th>
                    <th style="width: 15%;" ><div class='centered'>Quantità</div></th>
                    <th style="width: 20%;" ><div class='centered'>Prezzo</div></th>
                    <th style="width: 20%;" ><div class='centered'>Rimuovi</div></th>
                  </tr>
                </thead>
                <tbody id="carrello-body">
                  <?php foreach ($prodotti as $item): ?>
                  <tr data-type="prodotto" data-id="<?= $item['ID_Prodotto'] ?>">
                    <td>
                      <div class="item-info">
                        <div class="item-name"><?= htmlspecialchars($item['Nome']) ?></div>
                    </td>
                    <td class="text-center">
                      <div class='centered'><input type="number" min="1" class="form-control qty-input" value="<?= $item['Quantità'] ?>"></div>
                    </td>
                    <td class="price-cell prezzo">€ <?= number_format($item['Prezzo'] * $item['Quantità'], 2) ?></td>
                    <td class="text-center">
                      <div class='centered'>
                        <button class="btn btn-danger rimuovi" title="Rimuovi dal carrello">
                        X
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
          
                  <?php foreach ($menu as $m): ?>
                  <tr data-type="menu" data-id="<?= $m['ID_Menu'] ?>">
                    <td>
                      <div class="item-info">
                        <div class="item-name"><?= htmlspecialchars($m['Nome']) ?></div>
                    </td>
                    <td class="text-center">
                      <input type="number" min="1" class="form-control qty-input" value="<?= $m['Quantità'] ?>">
                    </td>
                    <td class="price-cell prezzo">€ <?= number_format($m['Prezzo'] * $m['Quantità'], 2) ?></td>
                    <td class="text-center">
                      <div class='centered'>
                        <button class="btn btn-danger rimuovi" title="Rimuovi dal carrello">
                        X
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            
            <div class="total-section">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h4 class="mb-0">Totale Carrello:</h4>
                  <small>Small</small>
                </div>
                <div class="total-amount">
                € <span id="totale"><?= $totaleFormatted ?></span>
                </div>
              </div>
              <div class="mt-3">
                <button class="btn btn-light btn-lg w-100" id="checkout-btn">
                  Procedi al Pagamento
                </button>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
  
  <script>

function aggiornaContatore(qty) {
  const counter = document.getElementById('counter');
  if (qty > 0) {
    counter.textContent = qty;
    counter.style.display = 'flex';
  } else {
    counter.textContent = '';
    counter.style.display = 'none';
  }
}

  document.querySelectorAll('input[type=number]').forEach(input => {
    input.addEventListener('change', e => {
      const tr = e.target.closest('tr');
      const id = tr.dataset.id;
      const tipo = tr.dataset.type;
      const qty = e.target.value;
  
      fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'update', tipo: tipo, id: id, quantita: qty })
      }).then(r => r.json()).then(data => {
        if (data.success) {
            const prezzoBase = parseFloat(tr.querySelector('.prezzo').textContent.replace(/[^\d.]/g, '')) / e.target.defaultValue;
            tr.querySelector('.prezzo').textContent = '€ ' + (prezzoBase * qty).toFixed(2);
            const counter = document.getElementById('counter');
            document.getElementById('totale').textContent = data.totale;
            aggiornaContatore(data.quantita_totale);

            e.target.defaultValue = qty;
        }
      });
    });
  });
  
  document.querySelectorAll('.rimuovi').forEach(btn => {
    btn.addEventListener('click', e => {
      const tr = e.target.closest('tr');
      const id = tr.dataset.id;
      const tipo = tr.dataset.type;
      fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'delete', tipo: tipo, id: id })
      }).then(r => r.json()).then(data => {
        if (data.success) {
  location.reload();
}

      });
    });
  });

    document.getElementById('checkout-btn')?.addEventListener('click', () => {
    fetch('', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ action: 'checkout' })
    }).then(r => r.json()).then(data => {
      if (data.success) {
        alert('Ordine confermato!');
        location.reload();
      } else {
        alert('Errore durante la conferma ordine: ' + (data.error || 'Errore sconosciuto'));
      }
    });
  });
  </script>
  
  <?php include 'footer.php'; ?>