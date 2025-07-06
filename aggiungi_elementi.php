<?php
require_once 'includes/dbh_test.inc.php';
include 'header.php';

// Precarica dati per select
function getProdotti($categorie, $pdo) {
  $in = str_repeat('?,', count($categorie) - 1) . '?';
  $sql = "SELECT p.ID_Prodotto, p.Nome, c.Nome AS Categoria FROM prodotto p
          JOIN categoria c ON p.ID_Categoria = c.ID_Categoria
          WHERE LOWER(c.Nome) IN ($in)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(array_map('strtolower', $categorie));
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCategorie($pdo) {
  $stmt = $pdo->query("SELECT ID_Categoria, Nome FROM categoria");
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllergeni($pdo) {
  $stmt = $pdo->query("SELECT ID_Allergene, Nome FROM allergene");
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$pizza_panino = getProdotti(['pizza', 'panino'], $pdo);
$bevande = getProdotti(['bevanda'], $pdo);
$fritti = getProdotti(['fritti'], $pdo);
$categorie = getCategorie($pdo);
$allergeni = getAllergeni($pdo);
?>

<link rel="stylesheet" href="css/aggiungi_elementi.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container my-5">
  <h2 class="text-center mb-4">Pannello Aggiunte</h2>
  <div class="row row-cols-1 row-cols-md-2 g-4">
    <div class="col"><button class="btn-action" onclick="addAllergene()">Aggiungi allergene</button></div>
    <div class="col"><button class="btn-action" onclick="addIngrediente()">Aggiungi ingrediente</button></div>
    <div class="col"><button class="btn-action" onclick="addMenu()">Aggiungi menu</button></div>
    <div class="col"><button class="btn-action" onclick="addProdotto()">Aggiungi prodotto</button></div>
  </div>
</div>

<script>
function addAllergene() {
  Swal.fire({
    title: 'Nuovo Allergene',
    input: 'text',
    inputLabel: 'Nome allergene',
    showCancelButton: true,
    confirmButtonText: 'Aggiungi'
  }).then(result => {
    if (result.isConfirmed) {
      $.post('includes/aggiunta_prodotto_handler.inc.php', { tipo: 'allergene', nome: result.value }, res => Swal.fire(res));
    }
  });
}

function addIngrediente() {
  Swal.fire({
    title: 'Nuovo Ingrediente',
    input: 'text',
    inputLabel: 'Nome ingrediente',
    showCancelButton: true,
    confirmButtonText: 'Aggiungi'
  }).then(result => {
    if (result.isConfirmed) {
      $.post('includes/aggiunta_prodotto_handler.inc.php', { tipo: 'ingrediente', nome: result.value }, res => Swal.fire(res));
    }
  });
}

function addMenu() {
  Swal.fire({
    title: 'Nuovo Menu',
    html: `
      <input type='text' id='menu-nome' class='swal2-input' placeholder='Nome'>
      <textarea id='menu-desc' class='swal2-textarea' placeholder='Descrizione'></textarea>
      <select id='menu-panino' class='swal2-select'>
        <option value=''>Panino/Pizza</option>
        <?php foreach ($pizza_panino as $p): ?>
          <option value='<?= $p['ID_Prodotto'] ?>'><?= $p['Nome'] ?></option>
        <?php endforeach; ?>
      </select>
      <select id='menu-bevanda' class='swal2-select'>
        <option value=''>Bevanda</option>
        <?php foreach ($bevande as $p): ?>
          <option value='<?= $p['ID_Prodotto'] ?>'><?= $p['Nome'] ?></option>
        <?php endforeach; ?>
      </select>
      <select id='menu-fritto' class='swal2-select'>
        <option value=''>Fritto</option>
        <?php foreach ($fritti as $p): ?>
          <option value='<?= $p['ID_Prodotto'] ?>'><?= $p['Nome'] ?></option>
        <?php endforeach; ?>
      </select>
    `,
    preConfirm: () => {
      return {
        nome: document.getElementById('menu-nome').value,
        descrizione: document.getElementById('menu-desc').value,
        panino: document.getElementById('menu-panino').value,
        bevanda: document.getElementById('menu-bevanda').value,
        fritto: document.getElementById('menu-fritto').value
      }
    },
    showCancelButton: true
  }).then(result => {
    if (result.isConfirmed) {
      $.post('includes/aggiunta_prodotto_handler.inc.php', { tipo: 'menu', dati: result.value }, res => Swal.fire(res));
    }
  });
}

function addProdotto() {
  Swal.fire({
    title: 'Nuovo Prodotto',
    html: `
      <input type='text' id='prod-nome' class='swal2-input' placeholder='Nome'>
      <input type='number' id='prod-prezzo' class='swal2-input' placeholder='Prezzo'>
      <textarea id='prod-desc' class='swal2-textarea' placeholder='Descrizione'></textarea>
      <select id='prod-cat' class='swal2-select'>
        <option value=''>Categoria</option>
        <?php foreach ($categorie as $c): ?>
          <option value='<?= $c['ID_Categoria'] ?>'><?= $c['Nome'] ?></option>
        <?php endforeach; ?>
      </select>
      <select id='prod-allergene' class='swal2-select'>
        <option value=''>Allergene</option>
        <?php foreach ($allergeni as $a): ?>
          <option value='<?= $a['ID_Allergene'] ?>'><?= $a['Nome'] ?></option>
        <?php endforeach; ?>
      </select>
    `,
    preConfirm: () => {
      return {
        nome: document.getElementById('prod-nome').value,
        prezzo: document.getElementById('prod-prezzo').value,
        descrizione: document.getElementById('prod-desc').value,
        categoria: document.getElementById('prod-cat').value,
        allergene: document.getElementById('prod-allergene').value
      }
    },
    showCancelButton: true
  }).then(result => {
    if (result.isConfirmed) {
      $.post('includes/aggiunta_prodotto_handler.inc.php', { tipo: 'prodotto', dati: result.value }, res => Swal.fire(res));
    }
  });
}
</script>

<?php include 'footer.php'; ?>
