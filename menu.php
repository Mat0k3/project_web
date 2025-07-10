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

        

        switch ((int)$row['ID_Prodotto']) {

          case 1: $img = 'images/f1.png'; break;

          case 2: $img = 'img/panino.jpg'; break;

          case 3: $img = 'img/bevanda.jpg'; break;

          case 4: $img = 'img/fritti.jpg'; break;

      }



        switch ((int)$row['ID_Categoria']) {

            case 1: $categoria = 'pizza'; break;

            case 2: $categoria = 'panini'; break;

            case 3: $categoria = 'bevande'; break;

            case 4: $categoria = 'fritti'; break;

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

        echo '        <div class="content-wrapper">';

        echo '          <h5>' . htmlspecialchars($row['Nome']) . '</h5>';

        echo '          <p>' . htmlspecialchars($row['Descrizione'] ?? 'Nessuna descrizione') . '</p>';

        if (!empty($ingredienti)) echo '<p><strong>Ingredienti:</strong> ' . $ingredienti . '</p>';

        if (!empty($allergeni)) echo '<p><strong>Allergeni:</strong> ' . $allergeni . '</p>';

        echo '        </div>';

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

        echo '        <div class="content-wrapper">';

        echo '          <h5>' . htmlspecialchars($menu['Nome']) . '</h5>';

        echo '          <p>' . htmlspecialchars($menu['Descrizione'] ?? 'Nessuna descrizione') . '</p>';

        $stmtProd = $pdo->prepare("SELECT p.Nome FROM prodotti_menu pm JOIN prodotto p ON pm.ID_Prodotto = p.ID_Prodotto WHERE pm.ID_Menu = ?");

        $stmtProd->execute([$menu['ID_Menu']]);

        $prodotti = $stmtProd->fetchAll(PDO::FETCH_COLUMN);

        echo '          <p><strong>Prodotti:</strong> ' . ($prodotti ? implode(', ', $prodotti) : 'Nessuno') . '</p>';

        echo '        </div>';

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


<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/menu.css">



<section class="food_section layout_padding ">

  <div class="container food">

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



<!-- Popup personalizzato per il carrello -->

<div id="cart-popup" class="cart-popup">

  <div class="popup-content">

    <div class="success-icon">
      <i class="fas fa-check-circle"></i>
    </div>

    <h3 class="popup-title">Aggiunto al carrello!</h3>

    <p class="popup-message">Il prodotto è stato aggiunto con successo al tuo carrello.</p>

    <div class="popup-actions">

      <button class="popup-btn popup-continue popup-sium">Continua lo shopping</button>

      <button class="popup-btn popup-view-cart">Visualizza carrello</button>

    </div>

  </div>

</div>



<!-- Popup per errori -->

<div id="error-popup" class="cart-popup error-popup">

  <div class="popup-content popup-content-error">

    <div class="popup-icon error">

      <i class="fa fa-exclamation-triangle"></i>

    </div>

    <h3 class="popup-title">Errore</h3>

    <p class="popup-message error-message"></p>

    <div class="popup-actions">

      <button class="popup-btn popup-continue popup-error">Chiudi</button>

    </div>

  </div>

</div>



<script>

document.addEventListener('DOMContentLoaded', () => {

  const cartPopup = document.getElementById('cart-popup');

  const errorPopup = document.getElementById('error-popup');

  

  // Funzione per mostrare popup

  function showPopup(popup) {

    popup.style.display = 'flex';

    setTimeout(() => {

      popup.classList.add('show');

    }, 10);

  }

  

  // Funzione per nascondere popup

  function hidePopup(popup) {

    popup.classList.remove('show');

    setTimeout(() => {

      popup.style.display = 'none';

    }, 300);

  }

  

  // Event listeners per i bottoni di chiusura

  document.querySelectorAll('.popup-continue').forEach(btn => {

    btn.addEventListener('click', () => {

      hidePopup(cartPopup);

      hidePopup(errorPopup);

    });

  });

  

  document.querySelector('.popup-view-cart').addEventListener('click', () => {

    hidePopup(cartPopup);

    // Reindirizza al carrello (modifica l'URL secondo le tue necessità)

    window.location.href = 'cart.php';

  });

  

  // Chiudi popup cliccando fuori

  [cartPopup, errorPopup].forEach(popup => {

    popup.addEventListener('click', (e) => {

      if (e.target === popup) {

        hidePopup(popup);

      }

    });

  });

  

  // Gestione bottoni aggiungi al carrello

  document.querySelectorAll('.add-to-cart').forEach(btn => {

    btn.addEventListener('click', e => {

      e.preventDefault();

      

      // Aggiungi classe di loading al bottone

      btn.classList.add('loading');

      

      const id = btn.dataset.id;

      const tipo = btn.dataset.tipo;

      const counter = document.getElementById('counter');
      




      fetch('aggiungi_al_carrello.php', {

        method: 'POST',

        headers: {'Content-Type': 'application/x-www-form-urlencoded'},

        body: `id=${id}&tipo=${tipo}`

      })

      .then(res => res.text())

      .then(msg => {

        // Rimuovi classe di loading

        btn.classList.remove('loading');

        

        if (msg.trim() === 'success') {
          if (counter.classList.contains('invisible')) {
            counter.classList.remove('invisible')
            counter.textContent =  1;
          } else {
            let current = parseInt(counter.textContent) || 0;
            counter.textContent = current +1;
          }
          showPopup(cartPopup);

        } else {

          document.querySelector('.error-message').textContent = msg;

          showPopup(errorPopup);

        }

      })

      .catch(error => {

        btn.classList.remove('loading');

        document.querySelector('.error-message').textContent = 'Si è verificato un errore. Riprova.';

        showPopup(errorPopup);

      });

    });

  });

});

</script>



<?php include 'footer.php'; ?>