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
/* Miglioramenti per la selezione degli elementi */
.item-checkbox {
    cursor: pointer;
    padding: 10px;
    border-radius: 5px;
    transition: background-color 0.3s ease;
    user-select: none;
}

.item-checkbox:hover {
    background-color: #f8f9fa;
}

.item-checkbox input[type="checkbox"] {
    margin-right: 10px;
    pointer-events: none;
}

.item-checkbox label {
    cursor: pointer;
    width: 100%;
    margin: 0;
    pointer-events: none;
}

/* Popup per il feedback */
.feedback-popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 15px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    z-index: 10001;
    min-width: 350px;
    max-width: 450px;
    padding: 30px;
    text-align: center;
    display: none;
    border: 2px solid #f39c12;
}

.feedback-popup .spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(243, 156, 18, 0.3);
    border-top: 4px solid #f39c12;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.feedback-popup .success-icon {
    color: #27ae60;
    font-size: 60px;
    margin-bottom: 20px;
    animation: checkmark 0.6s ease-in-out;
}

.feedback-popup .error-icon {
    color: #e74c3c;
    font-size: 60px;
    margin-bottom: 20px;
    animation: shake 0.6s ease-in-out;
}

@keyframes checkmark {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.feedback-popup h3 {
    margin-bottom: 15px;
    color: #fff;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    font-size: 1.5rem;
}

.feedback-popup p {
    margin-bottom: 25px;
    color: #ecf0f1;
    font-family: 'Poppins', sans-serif;
    font-size: 1rem;
    line-height: 1.5;
}

.feedback-popup .btn {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 25px;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
}

.feedback-popup .btn:hover {
    background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(243, 156, 18, 0.4);
}

/* Popup di conferma personalizzato */
.confirm-popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    border-radius: 15px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    z-index: 10002;
    min-width: 400px;
    max-width: 500px;
    padding: 30px;
    text-align: center;
    display: none;
    border: 2px solid #e74c3c;
}

.confirm-popup .warning-icon {
    color: #f39c12;
    font-size: 60px;
    margin-bottom: 20px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.confirm-popup h3 {
    margin-bottom: 15px;
    color: #fff;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    font-size: 1.5rem;
}

.confirm-popup p {
    margin-bottom: 25px;
    color: #ecf0f1;
    font-family: 'Poppins', sans-serif;
    font-size: 1rem;
    line-height: 1.5;
}

.confirm-popup .confirm-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.confirm-popup .btn-confirm {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 25px;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
}

.confirm-popup .btn-confirm:hover {
    background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
}

.confirm-popup .btn-cancel {
    background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 25px;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
}

.confirm-popup .btn-cancel:hover {
    background: linear-gradient(135deg, #7f8c8d 0%, #6c7b7d 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(149, 165, 166, 0.4);
}

/* Overlay per popup di conferma */
.confirm-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 10001;
    display: none;
    backdrop-filter: blur(5px);
}
</style>

<div class="admin-container">
    <div class="admin-header spazio">
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
            <div class="form-buttons">
                <button class="btn-primary-custom" onclick="eliminaSelezionati('prodotti')">Elimina Selezionati</button>
                <button class="btn-secondary-custom" onclick="closeAllPopups()">Annulla</button>
            </div>
        </div>
    </div>
</div>

<!-- Popup Feedback -->
<div class="feedback-popup" id="feedback-popup">
    <div id="feedback-content">
        <!-- Content will be dynamically loaded here -->
    </div>
</div>

<!-- Popup di Conferma Personalizzato -->
<div class="confirm-overlay" id="confirm-overlay"></div>
<div class="confirm-popup" id="confirm-popup">
    <div class="warning-icon">
        <i class="fas fa-exclamation-triangle"></i>
    </div>
    <h3>Conferma Eliminazione</h3>
    <p id="confirm-message">Sei sicuro di voler eliminare gli elementi selezionati?</p>
    <div class="confirm-buttons">
        <button class="btn-confirm" onclick="confirmDelete()">
            <i class="fas fa-trash-alt"></i> Elimina
        </button>
        <button class="btn-cancel" onclick="closeConfirmPopup()">
            <i class="fas fa-times"></i> Annulla
        </button>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
let currentType = '';
let pendingDeletion = null;

function openPopup(type) {
    currentType = type;
    document.getElementById('overlay').style.display = 'block';
    document.getElementById('popup-' + type).style.display = 'block';
    document.body.style.overflow = 'hidden';
    
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
            showFeedback('error', 'Errore', 'Errore nel caricamento dei dati');
            closeAllPopups();
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
        
        // Aggiungi event listener per il click sull'intero elemento
        div.addEventListener('click', function(e) {
            const checkbox = div.querySelector('input[type="checkbox"]');
            if (e.target !== checkbox) {
                checkbox.checked = !checkbox.checked;
            }
        });
        
        itemList.appendChild(div);
    });
}

function closeAllPopups() {
    document.getElementById('overlay').style.display = 'none';
    document.getElementById('feedback-popup').style.display = 'none';
    document.getElementById('confirm-overlay').style.display = 'none';
    document.getElementById('confirm-popup').style.display = 'none';
    const popups = document.querySelectorAll('.popup');
    popups.forEach(popup => {
        popup.style.display = 'none';
    });
    document.body.style.overflow = 'auto';
    currentType = '';
    pendingDeletion = null;
}

function closeConfirmPopup() {
    document.getElementById('confirm-overlay').style.display = 'none';
    document.getElementById('confirm-popup').style.display = 'none';
    pendingDeletion = null;
}

function showFeedback(type, title, message, showButton = true) {
    const feedbackPopup = document.getElementById('feedback-popup');
    const feedbackContent = document.getElementById('feedback-content');
    
    let content = '';
    
    if (type === 'loading') {
        content = `
            <div class="spinner"></div>
            <h3>${title}</h3>
            <p>${message}</p>
        `;
    } else if (type === 'success') {
        content = `
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>${title}</h3>
            <p>${message}</p>
            ${showButton ? '<button class="btn" onclick="closeAllPopups()">Perfetto!</button>' : ''}
        `;
    } else if (type === 'error') {
        content = `
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h3>${title}</h3>
            <p>${message}</p>
            ${showButton ? '<button class="btn" onclick="closeAllPopups()">Ho capito</button>' : ''}
        `;
    }
    
    feedbackContent.innerHTML = content;
    feedbackPopup.style.display = 'block';
}

function showConfirmPopup(type, selectedIds) {
    const confirmMessage = document.getElementById('confirm-message');
    
    let message = '';
    switch (type) {
        case 'allergeni':
            message = `Sei sicuro di voler eliminare ${selectedIds.length} allergene/i? Questa azione rimuoverà anche tutti i collegamenti con i prodotti.`;
            break;
        case 'ingredienti':
            message = `Sei sicuro di voler eliminare ${selectedIds.length} ingrediente/i? Questa azione potrebbe eliminare anche i prodotti collegati e i relativi menu.`;
            break;
        case 'menu':
            message = `Sei sicuro di voler eliminare ${selectedIds.length} menu? Questa azione non può essere annullata.`;
            break;
        case 'prodotti':
            message = `Sei sicuro di voler eliminare ${selectedIds.length} prodotto/i? Questa azione potrebbe eliminare anche i menu collegati.`;
            break;
    }
    
    confirmMessage.textContent = message;
    document.getElementById('confirm-overlay').style.display = 'block';
    document.getElementById('confirm-popup').style.display = 'block';
}

function eliminaSelezionati(type) {
    const checkboxes = document.querySelectorAll(`#list-${type} input[type="checkbox"]:checked`);
    
    if (checkboxes.length === 0) {
        showFeedback('error', 'Attenzione', 'Seleziona almeno un elemento da eliminare');
        return;
    }
    
    const selectedIds = Array.from(checkboxes).map(cb => cb.value);
    
    // Salva i dati per l'eliminazione e mostra il popup di conferma
    pendingDeletion = {
        type: type,
        ids: selectedIds
    };
    
    showConfirmPopup(type, selectedIds);
}

function confirmDelete() {
    if (!pendingDeletion) return;
    
    const { type, ids } = pendingDeletion;
    
    // Chiudi tutti i popup e mostra il feedback di caricamento
    document.getElementById('popup-' + type).style.display = 'none';
    closeConfirmPopup();
    showFeedback('loading', 'Eliminazione in corso', 'Attendere prego, stiamo processando la richiesta...', false);
    
    const formData = new FormData();
    formData.append('action', `elimina_${type}`);
    formData.append('ids', JSON.stringify(ids));
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showFeedback('success', 'Operazione Completata', data.message);
        } else {
            showFeedback('error', 'Errore nell\'eliminazione', data.message);
        }
        pendingDeletion = null;
    })
    .catch(error => {
        console.error('Error:', error);
        showFeedback('error', 'Errore di connessione', 'Si è verificato un errore durante l\'eliminazione. Riprova più tardi.');
        pendingDeletion = null;
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
    const confirmOverlay = document.getElementById('confirm-overlay');
    if (event.target === overlay || event.target === confirmOverlay) {
        closeAllPopups();
    }
}
</script>
<?php require_once 'footer.php'; ?>