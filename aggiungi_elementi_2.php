<?php
require_once 'includes/dbh.inc.php';

// Gestione delle aggiunte via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $pdo->beginTransaction();
        
        switch ($_POST['action']) {
            case 'aggiungi_allergene':
                $nome = trim($_POST['nome']);
                
                // Verifica se l'allergene esiste già
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM allergene WHERE LOWER(Nome) = LOWER(?)");
                $stmt->execute([$nome]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Allergene già esistente');
                }
                
                $stmt = $pdo->prepare("INSERT INTO allergene (Nome) VALUES (?)");
                $stmt->execute([$nome]);
                break;
                
            case 'aggiungi_ingrediente':
                $nome = trim($_POST['nome']);
                
                // Verifica se l'ingrediente esiste già
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM ingrediente WHERE LOWER(Nome) = LOWER(?)");
                $stmt->execute([$nome]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Ingrediente già esistente');
                }
                
                $stmt = $pdo->prepare("INSERT INTO ingrediente (Nome) VALUES (?)");
                $stmt->execute([$nome]);
                break;
                
            case 'aggiungi_menu':
                $nome = trim($_POST['nome']);
                $descrizione = trim($_POST['descrizione']);
                $prodotto_panino = !empty($_POST['prodotto_panino']) ? $_POST['prodotto_panino'] : null;
                $prodotto_bevanda = !empty($_POST['prodotto_bevanda']) ? $_POST['prodotto_bevanda'] : null;
                $prodotto_fritti = !empty($_POST['prodotto_fritti']) ? $_POST['prodotto_fritti'] : null;
                
                // Verifica se il menu esiste già
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu WHERE LOWER(Nome) = LOWER(?)");
                $stmt->execute([$nome]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Menu già esistente');
                }
                
                // Inserisci il menu
                $stmt = $pdo->prepare("INSERT INTO menu (Nome, Descrizione) VALUES (?, ?)");
                $stmt->execute([$nome, $descrizione]);
                $menu_id = $pdo->lastInsertId();
                
                // Collega i prodotti al menu
                if ($prodotto_panino) {
                    $stmt = $pdo->prepare("INSERT INTO prodotti_menu (ID_Menu, ID_Prodotto) VALUES (?, ?)");
                    $stmt->execute([$menu_id, $prodotto_panino]);
                }
                if ($prodotto_bevanda) {
                    $stmt = $pdo->prepare("INSERT INTO prodotti_menu (ID_Menu, ID_Prodotto) VALUES (?, ?)");
                    $stmt->execute([$menu_id, $prodotto_bevanda]);
                }
                if ($prodotto_fritti) {
                    $stmt = $pdo->prepare("INSERT INTO prodotti_menu (ID_Menu, ID_Prodotto) VALUES (?, ?)");
                    $stmt->execute([$menu_id, $prodotto_fritti]);
                }
                break;
                
            case 'aggiungi_prodotto':
                $nome = trim($_POST['nome']);
                $prezzo = floatval($_POST['prezzo']);
                $descrizione = trim($_POST['descrizione']);
                $categoria_id = $_POST['categoria_id'];
                $ingredienti = isset($_POST['ingredienti']) ? array_filter($_POST['ingredienti']) : [];
                $allergeni = isset($_POST['allergeni']) ? array_filter($_POST['allergeni']) : [];
                
                // Verifica se il prodotto esiste già
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM prodotto WHERE LOWER(Nome) = LOWER(?)");
                $stmt->execute([$nome]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Prodotto già esistente');
                }
                
                // Inserisci il prodotto
                $stmt = $pdo->prepare("INSERT INTO prodotto (Nome, Prezzo, Descrizione, ID_Categoria) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $prezzo, $descrizione, $categoria_id]);
                $prodotto_id = $pdo->lastInsertId();
                
                // Collega ingredienti
                foreach ($ingredienti as $ingrediente_id) {
                    $stmt = $pdo->prepare("INSERT INTO prodotti_ingredienti (ID_Prodotto, ID_Ingrediente) VALUES (?, ?)");
                    $stmt->execute([$prodotto_id, $ingrediente_id]);
                }
                
                // Collega allergeni
                foreach ($allergeni as $allergene_id) {
                    $stmt = $pdo->prepare("INSERT INTO prodotti_allergeni (ID_Prodotto, ID_Allergene) VALUES (?, ?)");
                    $stmt->execute([$prodotto_id, $allergene_id]);
                }
                break;
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Elemento aggiunto con successo']);
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiunta: ' . $e->getMessage()]);
    }
    exit;
}

// Carica dati per i form
$categorie = $pdo->query("SELECT ID_Categoria, Nome FROM categoria")->fetchAll(PDO::FETCH_ASSOC);
$ingredienti = $pdo->query("SELECT ID_Ingrediente, Nome FROM ingrediente")->fetchAll(PDO::FETCH_ASSOC);
$allergeni = $pdo->query("SELECT ID_Allergene, Nome FROM allergene")->fetchAll(PDO::FETCH_ASSOC);

// Prodotti per categoria per il menu
$prodotti_panino = $pdo->query("SELECT ID_Prodotto, Nome FROM prodotto WHERE ID_Categoria = (SELECT ID_Categoria FROM categoria WHERE Nome = 'panino')")->fetchAll(PDO::FETCH_ASSOC);
$prodotti_pizza = $pdo->query("SELECT ID_Prodotto, Nome FROM prodotto WHERE ID_Categoria = (SELECT ID_Categoria FROM categoria WHERE Nome = 'pizza')")->fetchAll(PDO::FETCH_ASSOC);
$prodotti_bevanda = $pdo->query("SELECT ID_Prodotto, Nome FROM prodotto WHERE ID_Categoria = (SELECT ID_Categoria FROM categoria WHERE Nome = 'bevanda')")->fetchAll(PDO::FETCH_ASSOC);
$prodotti_fritti = $pdo->query("SELECT ID_Prodotto, Nome FROM prodotto WHERE ID_Categoria = (SELECT ID_Categoria FROM categoria WHERE Nome = 'fritti')")->fetchAll(PDO::FETCH_ASSOC);
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
        <h2>Gestione Prodotti</h2>
    </div>

    <div class="buttons-container">
        <button class="admin-btn" onclick="openPopup('allergene')">
            <i class="fas fa-exclamation-triangle"></i>
            Aggiungi Allergene
        </button>
        <button class="admin-btn" onclick="openPopup('ingrediente')">
            <i class="fas fa-leaf"></i>
            Aggiungi Ingrediente
        </button>
        <button class="admin-btn" onclick="openPopup('menu')">
            <i class="fas fa-utensils"></i>
            Aggiungi Menu
        </button>
        <button class="admin-btn" onclick="openPopup('prodotto')">
            <i class="fas fa-plus-circle"></i>
            Aggiungi Prodotto
        </button>
    </div>
</div>

<!-- Overlay -->
<div class="overlay" id="overlay" onclick="closeAllPopups()"></div>

<!-- Popup Allergene -->
<div class="popup" id="popup-allergene">
    <div class="popup-header">
        <h2>Aggiungi Allergene</h2>
        <button class="close-btn" onclick="closeAllPopups()">&times;</button>
    </div>
    <form class="popup-form" id="form-allergene">
        <div class="form-group">
            <label for="nome-allergene">Nome Allergene</label>
            <input type="text" id="nome-allergene" name="nome" class="form-control" required>
        </div>
        <div class="form-buttons">
            <button type="submit" class="btn-primary-custom">Salva</button>
            <button type="button" class="btn-secondary-custom" onclick="closeAllPopups()">Annulla</button>
        </div>
    </form>
</div>

<!-- Popup Ingrediente -->
<div class="popup" id="popup-ingrediente">
    <div class="popup-header">
        <h2>Aggiungi Ingrediente</h2>
        <button class="close-btn" onclick="closeAllPopups()">&times;</button>
    </div>
    <form class="popup-form" id="form-ingrediente">
        <div class="form-group">
            <label for="nome-ingrediente">Nome Ingrediente</label>
            <input type="text" id="nome-ingrediente" name="nome" class="form-control" required>
        </div>
        <div class="form-buttons">
            <button type="submit" class="btn-primary-custom">Salva</button>
            <button type="button" class="btn-secondary-custom" onclick="closeAllPopups()">Annulla</button>
        </div>
    </form>
</div>

<!-- Popup Menu -->
<div class="popup" id="popup-menu">
    <div class="popup-header">
        <h2>Aggiungi Menu</h2>
        <button class="close-btn" onclick="closeAllPopups()">&times;</button>
    </div>
    <form class="popup-form" id="form-menu">
        <div class="form-group">
            <label for="nome-menu">Nome Menu</label>
            <input type="text" id="nome-menu" name="nome" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="descrizione-menu">Descrizione</label>
            <textarea id="descrizione-menu" name="descrizione" class="form-control" rows="3"></textarea>
        </div>
        
        <div class="menu-row">
            <div class="form-group">
                <label for="prodotto-panino">Prodotto Panino/Pizza</label>
                <select id="prodotto-panino" name="prodotto_panino" class="form-control">
                    <option value="">Seleziona...</option>
                    <?php foreach (array_merge($prodotti_panino, $prodotti_pizza) as $prod): ?>
                        <option value="<?= $prod['ID_Prodotto'] ?>"><?= htmlspecialchars($prod['Nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="prodotto-bevanda">Prodotto Bevanda</label>
                <select id="prodotto-bevanda" name="prodotto_bevanda" class="form-control">
                    <option value="">Seleziona...</option>
                    <?php foreach ($prodotti_bevanda as $prod): ?>
                        <option value="<?= $prod['ID_Prodotto'] ?>"><?= htmlspecialchars($prod['Nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="prodotto-fritti">Prodotto Fritti</label>
            <select id="prodotto-fritti" name="prodotto_fritti" class="form-control">
                <option value="">Seleziona...</option>
                <?php foreach ($prodotti_fritti as $prod): ?>
                    <option value="<?= $prod['ID_Prodotto'] ?>"><?= htmlspecialchars($prod['Nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-buttons">
            <button type="submit" class="btn-primary-custom">Salva</button>
            <button type="button" class="btn-secondary-custom" onclick="closeAllPopups()">Annulla</button>
        </div>
    </form>
</div>

<!-- Popup Prodotto -->
<div class="popup" id="popup-prodotto">
    <div class="popup-header">
        <h2>Aggiungi Prodotto</h2>
        <button class="close-btn" onclick="closeAllPopups()">&times;</button>
    </div>
    <form class="popup-form" id="form-prodotto">
        <div class="form-group">
            <label for="nome-prodotto">Nome Prodotto</label>
            <input type="text" id="nome-prodotto" name="nome" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="prezzo-prodotto">Prezzo (€)</label>
            <input type="number" id="prezzo-prodotto" step="0.01" name="prezzo" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="descrizione-prodotto">Descrizione</label>
            <textarea id="descrizione-prodotto" name="descrizione" class="form-control" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label for="categoria-prodotto">Categoria</label>
            <select id="categoria-prodotto" name="categoria_id" class="form-control" required>
                <option value="">Seleziona...</option>
                <?php foreach ($categorie as $cat): ?>
                    <option value="<?= $cat['ID_Categoria'] ?>"><?= htmlspecialchars($cat['Nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="select-section">
            <h5><i class="fas fa-leaf"></i> Ingredienti</h5>
            <div id="ingredienti-wrapper">
                <div class="select-item">
                    <select name="ingredienti[]" class="form-control">
                        <option value="">Seleziona ingrediente...</option>
                        <?php foreach ($ingredienti as $ing): ?>
                            <option value="<?= $ing['ID_Ingrediente'] ?>"><?= htmlspecialchars($ing['Nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn-add" onclick="aggiungiIngrediente()">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="select-section">
            <h5><i class="fas fa-exclamation-triangle"></i> Allergeni</h5>
            <div id="allergeni-wrapper">
                <div class="select-item">
                    <select name="allergeni[]" class="form-control">
                        <option value="">Seleziona allergene...</option>
                        <?php foreach ($allergeni as $all): ?>
                            <option value="<?= $all['ID_Allergene'] ?>"><?= htmlspecialchars($all['Nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn-add" onclick="aggiungiAllergene()">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="form-buttons">
            <button type="submit" class="btn-primary-custom">Salva</button>
            <button type="button" class="btn-secondary-custom" onclick="closeAllPopups()">Annulla</button>
        </div>
    </form>
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
    <div class="success-icon">
        <i class="fas fa-plus-circle"></i>
    </div>
    <h3>Conferma Aggiunta</h3>
    <p id="confirm-message">Sei sicuro di voler aggiungere questo elemento?</p>
    <div class="confirm-buttons">
        <button class="btn-confirm" onclick="confirmAdd()">
            <i class="fas fa-check"></i> Aggiungi
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
let pendingAddition = null;

function openPopup(type) {
    currentType = type;
    document.getElementById('overlay').style.display = 'block';
    document.getElementById('popup-' + type).style.display = 'block';
    document.body.style.overflow = 'hidden';
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
    pendingAddition = null;
    
    // Reset dei form
    document.querySelectorAll('form').forEach(form => {
        form.reset();
    });
}

function closeConfirmPopup() {
    document.getElementById('confirm-overlay').style.display = 'none';
    document.getElementById('confirm-popup').style.display = 'none';
    pendingAddition = null;
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

function showConfirmPopup(type, formData) {
    const confirmMessage = document.getElementById('confirm-message');
    
    let message = '';
    switch (type) {
        case 'allergene':
            message = `Sei sicuro di voler aggiungere l'allergene "${formData.get('nome')}"?`;
            break;
        case 'ingrediente':
            message = `Sei sicuro di voler aggiungere l'ingrediente "${formData.get('nome')}"?`;
            break;
        case 'menu':
            message = `Sei sicuro di voler aggiungere il menu "${formData.get('nome')}"?`;
            break;
        case 'prodotto':
            message = `Sei sicuro di voler aggiungere il prodotto "${formData.get('nome')}" al prezzo di €${formData.get('prezzo')}?`;
            break;
    }
    
    confirmMessage.textContent = message;
    document.getElementById('confirm-overlay').style.display = 'block';
    document.getElementById('confirm-popup').style.display = 'block';
}

function confirmAdd() {
    if (!pendingAddition) return;
    
    const { type, formData } = pendingAddition;
    
    // Chiudi il popup del form e mostra il feedback di caricamento
    document.getElementById('popup-' + type).style.display = 'none';
    closeConfirmPopup();
    showFeedback('loading', 'Aggiunta in corso', 'Attendere prego, stiamo processando la richiesta...', false);
    
    // Aggiungi l'action al FormData
    formData.append('action', 'aggiungi_' + type);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showFeedback('success', 'Operazione Completata', data.message);
        } else {
            showFeedback('error', 'Errore nell\'aggiunta', data.message);
        }
        pendingAddition = null;
    })
    .catch(error => {
        console.error('Error:', error);
        showFeedback('error', 'Errore di connessione', 'Si è verificato un errore durante l\'aggiunta. Riprova più tardi.');
        pendingAddition = null;
    });
}

// Event listeners per i form
document.getElementById('form-allergene').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    // Validazione
    if (!formData.get('nome').trim()) {
        showFeedback('error', 'Errore Validazione', 'Il nome dell\'allergene è obbligatorio');
        return;
    }
    
    pendingAddition = {
        type: 'allergene',
        formData: formData
    };
    
    showConfirmPopup('allergene', formData);
});

document.getElementById('form-ingrediente').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    // Validazione
    if (!formData.get('nome').trim()) {
        showFeedback('error', 'Errore Validazione', 'Il nome dell\'ingrediente è obbligatorio');
        return;
    }
    
    pendingAddition = {
        type: 'ingrediente',
        formData: formData
    };
    
    showConfirmPopup('ingrediente', formData);
});

document.getElementById('form-menu').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    // Validazione
    if (!formData.get('nome').trim()) {
        showFeedback('error', 'Errore Validazione', 'Il nome del menu è obbligatorio');
        return;
    }
    
    pendingAddition = {
        type: 'menu',
        formData: formData
    };
    
    showConfirmPopup('menu', formData);
});

document.getElementById('form-prodotto').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    // Validazione
    if (!formData.get('nome').trim()) {
        showFeedback('error', 'Errore Validazione', 'Il nome del prodotto è obbligatorio');
        return;
    }
    
    if (!formData.get('prezzo') || parseFloat(formData.get('prezzo')) <= 0) {
        showFeedback('error', 'Errore Validazione', 'Il prezzo deve essere maggiore di 0');
        return;
    }
    
    if (!formData.get('categoria_id')) {
        showFeedback('error', 'Errore Validazione', 'La categoria è obbligatoria');
        return;
    }
    
    pendingAddition = {
        type: 'prodotto',
        formData: formData
    };
    
    showConfirmPopup('prodotto', formData);
});

function aggiungiIngrediente() {
    const wrapper = document.getElementById('ingredienti-wrapper');
    const firstSelect = wrapper.querySelector('select');
    const newDiv = document.createElement('div');
    newDiv.className = 'select-item';
    newDiv.innerHTML = `
        <select name="ingredienti[]" class="form-control">
            ${firstSelect.innerHTML}
        </select>
        <button type="button" class="btn-remove" onclick="rimuoviSelect(this)">
            <i class="fas fa-minus"></i>
        </button>
    `;
    wrapper.appendChild(newDiv);
}

function aggiungiAllergene() {
    const wrapper = document.getElementById('allergeni-wrapper');
    const firstSelect = wrapper.querySelector('select');
    const newDiv = document.createElement('div');
    newDiv.className = 'select-item';
    newDiv.innerHTML = `
        <select name="allergeni[]" class="form-control">
            ${firstSelect.innerHTML}
        </select>
        <button type="button" class="btn-remove" onclick="rimuoviSelect(this)">
            <i class="fas fa-minus"></i>
        </button>
    `;
    wrapper.appendChild(newDiv);
}

function rimuoviSelect(button) {
    button.parentElement.remove();
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