<?php
require_once 'includes/dbh.inc.php';


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


<div class="admin-container">
    <div class="admin-header">
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
    <form class="popup-form" method="POST" action="includes/handler_allergene.php">
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
    <form class="popup-form" method="POST" action="includes/handler_ingrediente.php">
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
    <form class="popup-form" method="POST" action="includes/handler_menu.php">
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
    <form class="popup-form" method="POST" action="includes/handler_aggiungi_prodotto.php">
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

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
function openPopup(type) {
    document.getElementById('overlay').style.display = 'block';
    document.getElementById('popup-' + type).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeAllPopups() {
    document.getElementById('overlay').style.display = 'none';
    const popups = document.querySelectorAll('.popup');
    popups.forEach(popup => {
        popup.style.display = 'none';
    });
    document.body.style.overflow = 'auto';
}

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
</script>

<?php require_once 'footer.php'; ?>