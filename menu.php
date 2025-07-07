<?php
require_once 'includes/dbh.inc.php';
session_start();
include 'header.php';

function getIngredienti(PDO $pdo, int $idProdotto): string {
    $sql = "SELECT i.Nome FROM prodotti_ingredienti pi
            JOIN ingrediente i ON pi.ID_Ingrediente = i.ID_Ingrediente
            WHERE pi.ID_Prodotto = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idProdotto]);
    $ingredienti = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return (count($ingredienti) === 1 && strtolower(trim($ingredienti[0])) === "nessuno") ? '' : implode(', ', $ingredienti);
}

function getAllergeni(PDO $pdo, int $idProdotto): string {
    $sql = "SELECT a.Nome FROM prodotti_allergeni pa
            JOIN allergene a ON pa.ID_Allergene = a.ID_Allergene
            WHERE pa.ID_Prodotto = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idProdotto]);
    $allergeni = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return (count($allergeni) === 1 && strtolower(trim($allergeni[0])) === "nessuno") ? '' : implode(', ', $allergeni);
}

function mostraProdotti(PDO $pdo, ?int $idCategoria = null): void {
    $sql = "SELECT * FROM prodotto";
    $params = [];
    if ($idCategoria !== null) {
        $sql .= " WHERE ID_Categoria = ?";
        $params[] = $idCategoria;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $prodotti = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($prodotti as $row) {
        $img = 'img/default.jpg';
        $categoria = 'altro';
        switch ((int)$row['ID_Categoria']) {
            case 1: $img = 'img/pizza.jpg'; $categoria = 'pizza'; break;
            case 2: $img = 'img/panino.jpg'; $categoria = 'panini'; break;
            case 3: $img = 'img/bevanda.jpg'; $categoria = 'bevande'; break;
            case 4: $img = 'img/fritti.jpg'; $categoria = 'fritti'; break;
        }

        $ingredienti = getIngredienti($pdo, $row['ID_Prodotto']);
        $allergeni = getAllergeni($pdo, $row['ID_Prodotto']);

        echo '<div class="col-sm-6 col-lg-4 all ' . $categoria . '">';
        echo '  <div class="box">';
        echo '    <div>';
        echo '      <div class="img-box">';
        echo '        <img src="' . $img . '" alt="">';
        echo '      </div>';
        echo '      <div class="detail-box">';
        echo '        <h5>' . htmlspecialchars($row['Nome']) . '</h5>';
        echo '        <p>' . htmlspecialchars($row['Descrizione'] ?? 'Nessuna descrizione') . '</p>';
        if (!empty($ingredienti)) echo '<p><strong>Ingredienti:</strong> ' . $ingredienti . '</p>';
        if (!empty($allergeni)) echo '<p><strong>Allergeni:</strong> ' . $allergeni . '</p>';
        echo '        <div class="options">';
        echo '          <h6>€' . number_format($row['Prezzo'], 2) . '</h6>';
        echo '          <a href="#" class="add-to-cart" data-id="' . $row['ID_Prodotto'] . '" data-tipo="prodotto"><i class="fa fa-shopping-cart"></i></a>';
        echo '        </div>';
        echo '      </div>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
}

function mostraMenu(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM menu");
    $menuList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($menuList as $menu) {
        echo '<div class="col-sm-6 col-lg-4 all menu">';
        echo '  <div class="box">';
        echo '    <div>';
        echo '      <div class="img-box">';
        echo '        <img src="img/menu.jpg" alt="">';
        echo '      </div>';
        echo '      <div class="detail-box">';
        echo '        <h5>' . htmlspecialchars($menu['Nome']) . '</h5>';
        echo '        <p>' . htmlspecialchars($menu['Descrizione'] ?? 'Nessuna descrizione') . '</p>';
        $stmtProd = $pdo->prepare("SELECT p.Nome FROM prodotti_menu pm JOIN prodotto p ON pm.ID_Prodotto = p.ID_Prodotto WHERE pm.ID_Menu = ?");
        $stmtProd->execute([$menu['ID_Menu']]);
        $prodotti = $stmtProd->fetchAll(PDO::FETCH_COLUMN);
        echo '        <p><strong>Prodotti:</strong> ' . ($prodotti ? implode(', ', $prodotti) : 'Nessuno') . '</p>';
        echo '        <div class="options">';
        echo '          <h6>€' . number_format((float)($menu['Prezzo'] ?? 0), 2) . '</h6>';
        echo '          <a href="#" class="add-to-cart" data-id="' . $menu['ID_Menu'] . '" data-tipo="menu"><i class="fa fa-shopping-cart"></i></a>';
        echo '        </div>';
        echo '      </div>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
}
?>

<link rel="stylesheet" href="css/menu.css">

<section class="food_section layout_padding">
  <div class="container">
    <div class="heading_container heading_center">
      <h2>Il nostro Menù</h2>
    </div>
    <ul class="filters_menu">
      <li class="active" data-filter="*">Tutto</li>
      <li data-filter=".pizza">Pizza</li>
      <li data-filter=".panini">Panini</li>
      <li data-filter=".bevande">Bevande</li>
      <li data-filter=".fritti">Fritti</li>
      <li data-filter=".menu">Menu</li>
    </ul>
    <div class="filters-content">
      <div class="row grid">
        <?php mostraProdotti($pdo); mostraMenu($pdo); ?>
      </div>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const id = btn.dataset.id;
      const tipo = btn.dataset.tipo;

      fetch('aggiungi_al_carrello.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${id}&tipo=${tipo}`
      })
      .then(res => res.text())
      .then(msg => {
        if (msg.trim() === 'success') {
          alert('Aggiunto al carrello!');
        } else {
          alert('Errore: ' + msg);
        }
      });
    });
  });
});
</script>

<?php include 'footer.php'; ?>
