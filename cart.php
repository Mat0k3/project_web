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
  .popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.popup-overlay.show {
    opacity: 1;
    visibility: visible;
}

.popup-container {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 20px;
    max-width: 450px;
    width: 90%;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    border: 3px solid #f39c12;
    transform: scale(0.8);
    transition: all 0.3s ease;
    overflow: hidden;
}

.popup-overlay.show .popup-container {
    transform: scale(1);
}

.popup-header {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    color: white;
    padding: 25px;
    text-align: center;
    position: relative;
}

.success-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.checkmark {
    font-size: 2.5rem;
    color: #f39c12;
    font-weight: bold;
}

.popup-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.popup-body {
    padding: 30px;
    text-align: center;
}

.popup-subtitle {
    color: #6c757d;
    font-size: 1.1rem;
    margin-bottom: 25px;
    line-height: 1.5;
}

.order-summary {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    border: 2px solid #e9ecef;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.order-summary h5 {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 15px;
    font-size: 1.2rem;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.order-item:last-child {
    border-bottom: none;
    font-weight: 700;
    color: #f39c12;
    font-size: 1.1rem;
    margin-top: 10px;
    padding-top: 15px;
    border-top: 2px solid #f39c12;
}

.order-item-name {
    font-weight: 600;
    color: #2c3e50;
    flex: 1;
    text-align: left;
}

.order-item-qty {
    color: #6c757d;
    margin: 0 10px;
    font-size: 0.9rem;
}

.order-item-price {
    color: #f39c12;
    font-weight: 600;
    min-width: 60px;
    text-align: right;
}

.popup-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-popup {
    border-radius: 25px;
    padding: 12px 30px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    min-width: 140px;
}

.btn-continue {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
    border: 2px solid #6c757d;
}

.btn-continue:hover {
    background: linear-gradient(135deg, #495057 0%, #343a40 100%);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3);
}

.btn-view-orders {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    color: white;
    border: 2px solid #f39c12;
}

.btn-view-orders:hover {
    background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(243, 156, 18, 0.3);
}

@media (max-width: 768px) {
    .popup-container {
        max-width: 95%;
        margin: 20px;
    }
    
    .popup-header {
        padding: 20px;
    }
    
    .popup-body {
        padding: 20px;
    }
    
    .success-icon {
        width: 60px;
        height: 60px;
    }
    
    .checkmark {
        font-size: 2rem;
    }
    
    .popup-title {
        font-size: 1.5rem;
    }
    
    .popup-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-popup {
        width: 100%;
        max-width: 200px;
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

    <!-- Popup di conferma ordine -->
<div class="popup-overlay" id="orderConfirmationPopup">
    <div class="popup-container">
        <div class="popup-header">
            <div class="success-icon">
                <div class="checkmark">✓</div>
            </div>
            <h3 class="popup-title">Ordine Confermato!</h3>
        </div>
        
        <div class="popup-body">
            <p class="popup-subtitle">
                Il tuo ordine è stato confermato con successo.<br>
                Grazie per aver scelto il nostro ristorante!
            </p>
            
            <div class="order-summary" id="orderSummary">
                <h5>Riepilogo Ordine</h5>
                <div id="orderItems">
                    <!-- Gli elementi dell'ordine verranno inseriti dinamicamente -->
                </div>
            </div>
            
            <div class="popup-buttons">
                <button class="btn btn-popup btn-continue" onclick="continuaShopping()">
                    Continua lo shopping
                </button>
                <button class="btn btn-popup btn-view-orders" onclick="vaiAOrdini()">
                    Visualizza ordini
                </button>
            </div>
        </div>
    </div>
</div>
  </section>
  
  <script>

document.getElementById('checkout-btn')?.addEventListener('click', () => {
    // Raccogli i dati dell'ordine prima di inviarlo
    const orderData = {
        prodotti: [],
        menu: []
    };
    
    // Raccogli i prodotti dal DOM
    document.querySelectorAll('tr[data-type="prodotto"]').forEach(tr => {
        const nome = tr.querySelector('.item-name').textContent;
        const prezzoText = tr.querySelector('.prezzo').textContent;
        const prezzo = parseFloat(prezzoText.replace(/[^\d.]/g, ''));
        const quantita = parseInt(tr.querySelector('.qty-input').value);
        
        orderData.prodotti.push({
            nome: nome,
            prezzo: prezzo / quantita, // Prezzo unitario
            quantita: quantita
        });
    });
    
    // Raccogli i menu dal DOM
    document.querySelectorAll('tr[data-type="menu"]').forEach(tr => {
        const nome = tr.querySelector('.item-name').textContent;
        const prezzoText = tr.querySelector('.prezzo').textContent;
        const prezzo = parseFloat(prezzoText.replace(/[^\d.]/g, ''));
        const quantita = parseInt(tr.querySelector('.qty-input').value);
        
        orderData.menu.push({
            nome: nome,
            prezzo: prezzo / quantita, // Prezzo unitario
            quantita: quantita
        });
    });
    
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'checkout' })
    }).then(r => r.json()).then(data => {
        if (data.success) {
            // Mostra il popup invece dell'alert
            showOrderConfirmationPopup(orderData);
        } else {
            alert('Errore durante la conferma ordine: ' + (data.error || 'Errore sconosciuto'));
        }
    });
});

// Aggiungi queste funzioni JavaScript alla fine dello script esistente:

function showOrderConfirmationPopup(orderData) {
    const popup = document.getElementById('orderConfirmationPopup');
    const orderItemsContainer = document.getElementById('orderItems');
    
    // Pulisci il container precedente
    orderItemsContainer.innerHTML = '';
    
    let totalPrice = 0;
    
    // Aggiungi i prodotti
    if (orderData.prodotti && orderData.prodotti.length > 0) {
        orderData.prodotti.forEach(prodotto => {
            const itemTotal = prodotto.prezzo * prodotto.quantita;
            totalPrice += itemTotal;
            
            const itemDiv = document.createElement('div');
            itemDiv.className = 'order-item';
            itemDiv.innerHTML = `
                <span class="order-item-name">${prodotto.nome}</span>
                <span class="order-item-qty">x${prodotto.quantita}</span>
                <span class="order-item-price">€${itemTotal.toFixed(2)}</span>
            `;
            orderItemsContainer.appendChild(itemDiv);
        });
    }
    
    // Aggiungi i menu
    if (orderData.menu && orderData.menu.length > 0) {
        orderData.menu.forEach(menu => {
            const itemTotal = menu.prezzo * menu.quantita;
            totalPrice += itemTotal;
            
            const itemDiv = document.createElement('div');
            itemDiv.className = 'order-item';
            itemDiv.innerHTML = `
                <span class="order-item-name">${menu.nome}</span>
                <span class="order-item-qty">x${menu.quantita}</span>
                <span class="order-item-price">€${itemTotal.toFixed(2)}</span>
            `;
            orderItemsContainer.appendChild(itemDiv);
        });
    }
    
    // Aggiungi il totale
    const totalDiv = document.createElement('div');
    totalDiv.className = 'order-item';
    totalDiv.innerHTML = `
        <span class="order-item-name">TOTALE</span>
        <span class="order-item-qty"></span>
        <span class="order-item-price">€${totalPrice.toFixed(2)}</span>
    `;
    orderItemsContainer.appendChild(totalDiv);
    
    // Mostra il popup
    popup.classList.add('show');
    
    // Blocca lo scroll della pagina
    document.body.style.overflow = 'hidden';
}

function hideOrderConfirmationPopup() {
    const popup = document.getElementById('orderConfirmationPopup');
    popup.classList.remove('show');
    document.body.style.overflow = 'auto';
}

function continuaShopping() {
    hideOrderConfirmationPopup();
    window.location.href = 'menu.php';
}

function vaiAOrdini() {
    hideOrderConfirmationPopup();
    window.location.href = 'login.php'; // Modifica con il percorso corretto
}

// Chiudi il popup cliccando sull'overlay
document.getElementById('orderConfirmationPopup').addEventListener('click', function(e) {
    if (e.target === this) {
        hideOrderConfirmationPopup();
    }
});

// Chiudi il popup con il tasto ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideOrderConfirmationPopup();
    }
});
  </script>
  
  <?php include 'footer.php'; ?>