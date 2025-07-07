<?php
session_start();
include 'includes/dbh.inc.php';

// Gestione delle eliminazioni via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $pdo->beginTransaction();
        
        switch ($_POST['action']) {
            case 'elimina_allergeni':
                $ids = json_decode($_POST['ids'], true);
                foreach ($ids as $id) {
                    // Elimina dalle tabelle correlate
                    $stmt = $pdo->prepare("DELETE FROM prodotti_allergeni WHERE ID_Allergene = ?");
                    $stmt->execute([$id]);
                    
                    // Elimina l'allergene
                    $stmt = $pdo->prepare("DELETE FROM allergene WHERE ID_Allergene = ?");
                    $stmt->execute([$id]);
                }
                break;
                
            case 'elimina_ingredienti':
                $ids = json_decode($_POST['ids'], true);
                foreach ($ids as $id) {
                    // Trova i prodotti legati SOLO a questo ingrediente
                    $stmt = $pdo->prepare("
                        SELECT pi.ID_Prodotto 
                        FROM prodotti_ingredienti pi 
                        WHERE pi.ID_Ingrediente = ? 
                        AND pi.ID_Prodotto NOT IN (
                            SELECT DISTINCT pi2.ID_Prodotto 
                            FROM prodotti_ingredienti pi2 
                            WHERE pi2.ID_Ingrediente != ?
                        )
                    ");
                    $stmt->execute([$id, $id]);
                    $prodotti_da_eliminare = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Elimina prima tutte le relazioni dell'ingrediente
                    $stmt = $pdo->prepare("DELETE FROM prodotti_ingredienti WHERE ID_Ingrediente = ?");
                    $stmt->execute([$id]);
                    
                    // Ora elimina i prodotti che erano legati SOLO a questo ingrediente
                    foreach ($prodotti_da_eliminare as $prodotto_id) {
                        // Trova i menu legati al prodotto
                        $stmt = $pdo->prepare("SELECT ID_Menu FROM prodotti_menu WHERE ID_Prodotto = ?");
                        $stmt->execute([$prodotto_id]);
                        $menu_da_eliminare = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        // Elimina tutte le relazioni del prodotto
                        $stmt = $pdo->prepare("DELETE FROM prodotti_allergeni WHERE ID_Prodotto = ?");
                        $stmt->execute([$prodotto_id]);
                        
                        $stmt = $pdo->prepare("DELETE FROM prodotti_carrello WHERE ID_Prodotto = ?");
                        $stmt->execute([$prodotto_id]);
                        
                        $stmt = $pdo->prepare("DELETE FROM prodotti_menu WHERE ID_Prodotto = ?");
                        $stmt->execute([$prodotto_id]);
                        
                        $stmt = $pdo->prepare("DELETE FROM prodotti_ordinazione WHERE ID_Prodotto = ?");
                        $stmt->execute([$prodotto_id]);
                        
                        $stmt = $pdo->prepare("DELETE FROM recensione WHERE ID_Prodotto = ?");
                        $stmt->execute([$prodotto_id]);
                        
                        // Elimina il prodotto
                        $stmt = $pdo->prepare("DELETE FROM prodotto WHERE ID_Prodotto = ?");
                        $stmt->execute([$prodotto_id]);
                        
                        // Elimina i menu che erano legati SOLO a questo prodotto
                        foreach ($menu_da_eliminare as $menu_id) {
                            // Verifica se il menu ha altri prodotti
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM prodotti_menu WHERE ID_Menu = ?");
                            $stmt->execute([$menu_id]);
                            $count = $stmt->fetchColumn();
                            
                            if ($count == 0) {
                                $stmt = $pdo->prepare("DELETE FROM menu_ordinazione WHERE ID_Menu = ?");
                                $stmt->execute([$menu_id]);
                                
                                $stmt = $pdo->prepare("DELETE FROM menu_carrello WHERE ID_Menu = ?");
                                $stmt->execute([$menu_id]);
                                
                                $stmt = $pdo->prepare("DELETE FROM menu WHERE ID_Menu = ?");
                                $stmt->execute([$menu_id]);
                            }
                        }
                    }
                    
                    // Elimina l'ingrediente
                    $stmt = $pdo->prepare("DELETE FROM ingrediente WHERE ID_Ingrediente = ?");
                    $stmt->execute([$id]);
                }
                break;
                
            case 'elimina_menu':
                $ids = json_decode($_POST['ids'], true);
                foreach ($ids as $id) {
                    // Elimina dalle tabelle correlate
                    $stmt = $pdo->prepare("DELETE FROM menu_ordinazione WHERE ID_Menu = ?");
                    $stmt->execute([$id]);
                    
                    $stmt = $pdo->prepare("DELETE FROM menu_carrello WHERE ID_Menu = ?");
                    $stmt->execute([$id]);
                    
                    $stmt = $pdo->prepare("DELETE FROM prodotti_menu WHERE ID_Menu = ?");
                    $stmt->execute([$id]);
                    
                    // Elimina il menu
                    $stmt = $pdo->prepare("DELETE FROM menu WHERE ID_Menu = ?");
                    $stmt->execute([$id]);
                }
                break;
                
            case 'elimina_prodotti':
                $ids = json_decode($_POST['ids'], true);
                foreach ($ids as $id) {
                    // Trova i menu legati al prodotto
                    $stmt = $pdo->prepare("SELECT ID_Menu FROM prodotti_menu WHERE ID_Prodotto = ?");
                    $stmt->execute([$id]);
                    $menu_collegati = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Elimina prima tutte le relazioni del prodotto
                    $stmt = $pdo->prepare("DELETE FROM prodotti_allergeni WHERE ID_Prodotto = ?");
                    $stmt->execute([$id]);
                    
                    $stmt = $pdo->prepare("DELETE FROM prodotti_carrello WHERE ID_Prodotto = ?");
                    $stmt->execute([$id]);
                    
                    $stmt = $pdo->prepare("DELETE FROM prodotti_ingredienti WHERE ID_Prodotto = ?");
                    $stmt->execute([$id]);
                    
                    $stmt = $pdo->prepare("DELETE FROM prodotti_menu WHERE ID_Prodotto = ?");
                    $stmt->execute([$id]);
                    
                    $stmt = $pdo->prepare("DELETE FROM prodotti_ordinazione WHERE ID_Prodotto = ?");
                    $stmt->execute([$id]);
                    
                    $stmt = $pdo->prepare("DELETE FROM recensione WHERE ID_Prodotto = ?");
                    $stmt->execute([$id]);
                    
                    // Elimina il prodotto
                    $stmt = $pdo->prepare("DELETE FROM prodotto WHERE ID_Prodotto = ?");
                    $stmt->execute([$id]);
                    
                    // Ora verifica ed elimina i menu che non hanno più prodotti associati
                    foreach ($menu_collegati as $menu_id) {
                        // Verifica se il menu ha ancora altri prodotti
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM prodotti_menu WHERE ID_Menu = ?");
                        $stmt->execute([$menu_id]);
                        $count = $stmt->fetchColumn();
                        
                        // Se il menu non ha più prodotti, lo eliminiamo
                        if ($count == 0) {
                            $stmt = $pdo->prepare("DELETE FROM menu_ordinazione WHERE ID_Menu = ?");
                            $stmt->execute([$menu_id]);
                            
                            $stmt = $pdo->prepare("DELETE FROM menu_carrello WHERE ID_Menu = ?");
                            $stmt->execute([$menu_id]);
                            
                            $stmt = $pdo->prepare("DELETE FROM menu WHERE ID_Menu = ?");
                            $stmt->execute([$menu_id]);
                        }
                    }
                }
                break;
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Eliminazione completata con successo']);
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()]);
    }
    exit;
}

// Recupera dati per i popup
if (isset($_GET['get_data'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_GET['get_data']) {
            case 'allergeni':
                $stmt = $pdo->query("SELECT ID_Allergene, Nome FROM allergene ORDER BY Nome");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'ingredienti':
                $stmt = $pdo->query("SELECT ID_Ingrediente, Nome FROM ingrediente ORDER BY Nome");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'menu':
                $stmt = $pdo->query("SELECT ID_Menu, Nome FROM menu ORDER BY Nome");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'prodotti':
                $stmt = $pdo->query("SELECT p.ID_Prodotto, p.Nome, c.Nome as Categoria 
                                   FROM prodotto p 
                                   JOIN categoria c ON p.ID_Categoria = c.ID_Categoria 
                                   ORDER BY p.Nome");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            default:
                $data = [];
        }
        
        echo json_encode($data);
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
?>
<!-- Bootstrap CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- CSS Personalizzato -->
<?php require_once 'header.php'; ?>
<link rel="stylesheet" href="css/gestione.css">

<style>
/* Stili aggiuntivi per migliorare l'interfaccia */
.item-checkbox {
    padding: 12px;
    margin-bottom: 8px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background-color: #f9f9f9;
    transition: all 0.3s ease;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 12px;
}

.item-checkbox:hover {
    background-color: #f0f0f0;
    border-color: #ccc;
}

.item-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin: 0;
    cursor: pointer;
}

.item-checkbox label {
    margin: 0;
    font-weight: 500;
    color: #333;
    cursor: pointer;
    flex: 1;
}

.item-checkbox.selected {
    background-color: #e7f3ff;
    border-color: #007bff;
}

.result-message {
    margin-top: 20px;
    padding: 15px;
    border-radius: 8px;
    font-weight: 500;
    text-align: center;
    display: none;
}

.result-message.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.result-message.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.popup-form {
    max-height: 70vh;
    overflow-y: auto;
}

.item-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 20px;
}
</style>

<div class="admin-container">
    <div class="admin-header">
        <h2>Gestione Eliminazioni</h2>
    </div>

    <div class="buttons-container">
        <button class="admin-btn" onclick="openPopup('allergeni')">
            <i class="fas fa-exclamation-triangle"></i>
            Elimina Allergeni
        </button>
        <button class="admin-btn" onclick="openPopup('ingredienti')">
            <i class="fas fa-leaf"></i>
            Elimina Ingredienti
        </button>
        <button class="admin-btn" onclick="openPopup('menu')">
            <i class="fas fa-utensils"></i>
            Elimina Menu
        </button>
        <button class="admin-btn" onclick="openPopup('prodotti')">
            <i class="fas fa-plus-circle"></i>
            Elimina Prodotti
        </button>
    </div>
</div>

<!-- Overlay -->
<div class="overlay" id="overlay" onclick="closeAllPopups()"></div>

<!-- Popup Allergeni -->
<div class="popup" id="popup-allergeni">
    <div class="popup-header">
        <h2>Elimina Allergeni</h2>
        <button class="close-btn" onclick="closeAllPopups()">&times;</button>
    </div>
    <div class="popup-form">
        <div class="loading" id="loading-allergeni" style="display: none;">
            <div class="spinner"></div>
            <p>Caricamento...</p>
        </div>
        <div id="content-allergeni" style="display: none;">
            <div class="item-list" id="list-allergeni">
                <!-- Items will be loaded here -->
            </div>
            <div class="result-message" id="result-allergeni"></div>
            <div class="form-buttons">
                <button class="btn-primary-custom" onclick="eliminaSelezionati('allergeni')">Elimina Selezionati</button>
                <button class="btn-secondary-custom" onclick="closeAllPopups()">Annulla</button>
            </div>
        </div>
    </div>
</div>

<!-- Popup Ingredienti -->
<div class="popup" id="popup-ingredienti">
    <div class="popup-header">
        <h2>Elimina Ingredienti</h2>
        <button class="close-btn" onclick="closeAllPopups()">&times;</button>
    </div>
    <div class="popup-form">
        <div class="loading" id="loading-ingredienti" style="display: none;">
            <div class="spinner"></div>
            <p>Caricamento...</p>
        </div>
        <div id="content-ingredienti" style="display: none;">
            <div class="item-list" id="list-ingredienti">
                <!-- Items will be loaded here -->
            </div>
            <div class="result-message" id="result-ingredienti"></div>
            <div class="form-buttons">
                <button class="btn-primary-custom" onclick="eliminaSelezionati('ingredienti')">Elimina Selezionati</button>
                <button class="btn-secondary-custom" onclick="closeAllPopups()">Annulla</button>
            </div>
        </div>
    </div>
</div>

<!-- Popup Menu -->
<div class="popup" id="popup-menu">
    <div class="popup-header">
        <h2>Elimina Menu</h2>
        <button class="close-btn" onclick="closeAllPopups()">&times;</button>
    </div>
    <div class="popup-form">
        <div class="loading" id="loading-menu" style="display: none;">
            <div class="spinner"></div>
            <p>Caricamento...</p>
        </div>
        <div id="content-menu" style="display: none;">
            <div class="item-list" id="list-menu">
                <!-- Items will be loaded here -->
            </div>
            <div class="result-message" id="result-menu"></div>
            <div class="form-buttons">
                <button class="btn-primary-custom" onclick="eliminaSelezionati('menu')">Elimina Selezionati</button>
                <button class="btn-secondary-custom" onclick="closeAllPopups()">Annulla</button>
            </div>
        </div>
    </div>
</div>

<!-- Popup Prodotti -->
<div class="popup" id="popup-prodotti">
    <div class="popup-header">
        <h2>Elimina Prodotti</h2>
        <button class="close-btn" onclick="closeAllPopups()">&times;</button>
    </div>
    <div class="popup-form">
        <div class="loading" id="loading-prodotti" style="display: none;">
            <div class="spinner"></div>
            <p>Caricamento...</p>
        </div>
        <div id="content-prodotti" style="display: none;">
            <div class="item-list" id="list-prodotti">
                <!-- Items will be loaded here -->
            </div>
            <div class="result-message" id="result-prodotti"></div>
            <div class="form-buttons">
                <button class="btn-primary-custom" onclick="eliminaSelezionati('prodotti')">Elimina Selezionati</button>
                <button class="btn-secondary-custom" onclick="closeAllPopups()">Annulla</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
let currentType = '';

function openPopup(type) {
    currentType = type;
    document.getElementById('overlay').style.display = 'block';
    document.getElementById('popup-' + type).style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Nascondi il messaggio di risultato precedente
    const resultDiv = document.getElementById('result-' + type);
    resultDiv.style.display = 'none';
    resultDiv.className = 'result-message';
    
    // Show loading
    document.getElementById('loading-' + type).style.display = 'block';
    document.getElementById('content-' + type).style.display = 'none';
    
    // Load data
    fetch(`?get_data=${type}`)
        .then(response => response.json())
        .then(data => {
            loadItems(data, type);
            document.getElementById('loading-' + type).style.display = 'none';
            document.getElementById('content-' + type).style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            showResult(type, 'Errore nel caricamento dei dati', false);
            document.getElementById('loading-' + type).style.display = 'none';
            document.getElementById('content-' + type).style.display = 'block';
        });
}

function loadItems(items, type) {
    const itemList = document.getElementById('list-' + type);
    itemList.innerHTML = '';
    
    if (items.length === 0) {
        itemList.innerHTML = '<p style="text-align: center; padding: 20px; color: #666;">Nessun elemento disponibile</p>';
        return;
    }
    
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'item-checkbox';
        
        let displayText = '';
        let itemId = '';
        
        switch (type) {
            case 'allergeni':
                displayText = item.Nome;
                itemId = item.ID_Allergene;
                break;
            case 'ingredienti':
                displayText = item.Nome;
                itemId = item.ID_Ingrediente;
                break;
            case 'menu':
                displayText = item.Nome;
                itemId = item.ID_Menu;
                break;
            case 'prodotti':
                displayText = `${item.Nome} (${item.Categoria})`;
                itemId = item.ID_Prodotto;
                break;
        }
        
        div.innerHTML = `
            <input type="checkbox" id="item_${itemId}" value="${itemId}">
            <label for="item_${itemId}">${displayText}</label>
        `;
        
        // Aggiungi event listener per l'intera area cliccabile
        div.addEventListener('click', function(e) {
            if (e.target.tagName !== 'INPUT') {
                const checkbox = div.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
                updateItemSelection(div, checkbox.checked);
            } else {
                updateItemSelection(div, e.target.checked);
            }
        });
        
        // Event listener per il checkbox
        const checkbox = div.querySelector('input[type="checkbox"]');
        checkbox.addEventListener('change', function() {
            updateItemSelection(div, this.checked);
        });
        
        itemList.appendChild(div);
    });
}

function updateItemSelection(div, isChecked) {
    if (isChecked) {
        div.classList.add('selected');
    } else {
        div.classList.remove('selected');
    }
}

function showResult(type, message, isSuccess) {
    const resultDiv = document.getElementById('result-' + type);
    resultDiv.textContent = message;
    resultDiv.className = `result-message ${isSuccess ? 'success' : 'error'}`;
    resultDiv.style.display = 'block';
    
    // Scorri automaticamente per mostrare il messaggio
    resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function closeAllPopups() {
    document.getElementById('overlay').style.display = 'none';
    const popups = document.querySelectorAll('.popup');
    popups.forEach(popup => {
        popup.style.display = 'none';
    });
    document.body.style.overflow = 'auto';
    currentType = '';
}

function eliminaSelezionati(type) {
    const checkboxes = document.querySelectorAll(`#list-${type} input[type="checkbox"]:checked`);
    
    if (checkboxes.length === 0) {
        showResult(type, 'Seleziona almeno un elemento da eliminare', false);
        return;
    }
    
    const selectedIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (!confirm(`Sei sicuro di voler eliminare ${selectedIds.length} elemento/i? Questa azione non può essere annullata.`)) {
        return;
    }
    
    const loading = document.getElementById('loading-' + type);
    const content = document.getElementById('content-' + type);
    
    loading.style.display = 'block';
    content.style.display = 'none';
    
    const formData = new FormData();
    formData.append('action', `elimina_${type}`);
    formData.append('ids', JSON.stringify(selectedIds));
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        loading.style.display = 'none';
        content.style.display = 'block';
        
        showResult(type, data.message, data.success);
        
        if (data.success) {
            // Ricarica la lista dopo l'eliminazione
            setTimeout(() => {
                fetch(`?get_data=${type}`)
                    .then(response => response.json())
                    .then(data => {
                        loadItems(data, type);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }, 2000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        loading.style.display = 'none';
        content.style.display = 'block';
        showResult(type, 'Errore durante l\'eliminazione', false);
    });
}

// Chiudi popup con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAllPopups();
    }
});

// Close popup when clicking outside
window.onclick = function(event) {
    const overlay = document.getElementById('overlay');
    if (event.target === overlay) {
        closeAllPopups();
    }
}
</script>
<?php require_once 'footer.php'; ?>