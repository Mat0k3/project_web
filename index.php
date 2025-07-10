<?php
session_start();
require_once 'includes/dbh_test.inc.php';

$current_page = basename($_SERVER['PHP_SELF']);

// Controlla se l'utente è loggato (assumendo che usi le sessioni)
$user_logged_in = isset($_SESSION['utente_id']);
$user_name = $user_logged_in ? $_SESSION['utente_nome'] : null;
$sql_carrello = "SELECT ID_Carrello FROM carrello WHERE ID_Utente = :id_utente";
$stmt = $pdo->prepare($sql_carrello);
$stmt->bindParam(':id_utente',$_SESSION['utente_id'], PDO::PARAM_INT);
$stmt->execute();

$carrello = $stmt->fetch(PDO::FETCH_ASSOC);
$quantita_totale = 0;
if ($carrello) {
    $id_carrello = $carrello['ID_Carrello'];

    // Ora somma le quantità nel carrello
    $sql_quantita = "SELECT SUM(Quantità) AS totale_quantita FROM prodotti_carrello WHERE ID_Carrello = :id_carrello";
    $stmt = $pdo->prepare($sql_quantita);
    $stmt->bindParam(':id_carrello', $id_carrello, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $quantita_totale1 = $row['totale_quantita'] ?? 0;

    $sql_quantita = "SELECT SUM(Quantità) AS totale_quantita FROM menu_carrello WHERE ID_Carrello = :id_carrello";
    $stmt = $pdo->prepare($sql_quantita);
    $stmt->bindParam(':id_carrello', $id_carrello, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $quantita_totale2 = $row['totale_quantita'] ?? 0;
    $quantita_totale= $quantita_totale1 +$quantita_totale2;
}else{
  echo "";
}

// Funzioni per ottenere ingredienti e allergeni (come in menu.php)
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

// Funzione per mostrare solo 3 prodotti per la preview
function mostraPreviewProdotti(PDO $pdo): void {
    $sql = "SELECT * FROM prodotto LIMIT 3";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testo'], $_POST['voto']) && isset($_SESSION['utente_id'])) {
    $utenteId = $_SESSION['utente_id'];
    $testo = trim($_POST['testo']);
    $voto = (int)$_POST['voto'];

    if ($testo !== '' && $voto >= 1 && $voto <= 5) {
        try {
            $stmt = $pdo->prepare("INSERT INTO recensione (ID_Utente, Testo, Voto, Data) VALUES (?, ?, ?, CURDATE())");
            $stmt->execute([$utenteId, $testo, $voto]);
            header("Location: index.php?recensione=ok");
            exit;
        } catch (PDOException $e) {
            header("Location: index.php?recensione=errore");
            exit;
        }
    } else {
        header("Location: index.php?recensione=errore");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
  <!-- Basic -->
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <!-- Mobile Metas -->
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <!-- Site Metas -->
  <meta name="keywords" content="" />
  <meta name="description" content="" />
  <meta name="author" content="" />
  <link rel="shortcut icon" href="images/favicon.png" type="">

  <title> Feane </title>

  <!-- bootstrap core css -->
  <link rel="stylesheet" type="text/css" href="css/bootstrap.css" />

  <!--owl slider stylesheet -->
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
  <!-- nice select  -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-nice-select/1.1.0/css/nice-select.min.css" integrity="sha512-CruCP+TD3yXzlvvijET8wV5WxxEh5H8P4cmz0RFbKK6FlZ2sYl3AEsKlLPHbniXKSrDdFewhbmBK5skbdsASbQ==" crossorigin="anonymous" />
  <!-- font awesome style -->
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="css/font-awesome.min.css" rel="stylesheet" />

  <!-- Custom styles for this template -->
  <link href="css/style.css" rel="stylesheet" />
  <!-- responsive style -->
  <link href="css/responsive.css" rel="stylesheet" />
  <!-- Menu CSS per la preview -->

</head>

<body>

<!-- Sostituisci il tag <style> esistente con questo -->
<style>
  

.carousel-wrap {
  position: relative;
  margin-bottom: 100px; /* spazio sotto per evitare sovrapposizione footer */
}

.owl-carousel {
  transition: height 0s ease;
}

.owl-carousel .item {
  min-height: 150px; /* o quello che ti serve */
}

.owl-nav {
  position: absolute;
  bottom: -80px; /* distanza dal carosello */
  left: 50%;
  transform: translateX(-50%);
}
  .food{
    margin-top: 50px;
  }
  .header_personalizzato{
    background: transparent;
  }
  
  /* Stili specifici per la preview nella homepage */
  .preview-menu .filters_menu {
    display: none; /* Nascondi i filtri nella preview */
  }
  
  .preview-menu .box {
    position: relative;
    margin-top: 25px;
    background-color: #ffffff;
    border-radius: 15px;
    overflow: hidden;
    background: linear-gradient(to bottom, #f1f2f3 25px, #222831 25px);
    color: #ffffff;
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 470px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: 0.3s;
  }
  
  .preview-menu .box:hover {
    transform: translateY(-5px);
  }
  
  .preview-menu .img-box {
    background: #f1f2f3;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 215px;
    border-radius: 0 0 0 45px;
    padding: 25px;
  }
  
  .preview-menu .img-box img {
    max-width: 100%;
    max-height: 145px;
    transition: all 0.2s;
  }
  
  .preview-menu .box:hover .img-box img {
    transform: scale(1.1);
  }
  
  .preview-menu .detail-box {
    padding: 25px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background-color: transparent;
    color: white;
    height: 255px;
  }
  
  .preview-menu .detail-box h5 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 10px;
    color: #ffbe33;
  }
  
  .preview-menu .detail-box p {
    font-size: 14px;
    margin: 5px 0;
    line-height: 1.4;
  }
  
  .preview-menu .content-wrapper {
    flex-grow: 1;
    overflow-y: auto;
    padding-right: 5px;
    margin-bottom: 10px;
  }
  
  .preview-menu .content-wrapper::-webkit-scrollbar {
    width: 6px;
  }
  
  .preview-menu .content-wrapper::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
  }
  
  .preview-menu .content-wrapper::-webkit-scrollbar-thumb {
    background: rgba(255, 190, 51, 0.6);
    border-radius: 3px;
  }
  
  .preview-menu .content-wrapper::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 190, 51, 0.8);
  }
  
  .preview-menu .content-wrapper {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 190, 51, 0.6) rgba(255, 255, 255, 0.1);
  }
  
  .preview-menu .options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
    padding-top: 10px;
    border-top: 1px solid #333;
    visibility: visible;
    opacity: 1;
    min-height: 40px;
  }
  
  .preview-menu .options h6 {
    font-size: 16px;
    font-weight: 600;
    color: #fff;
    margin: 0;
  }
  
  .preview-menu .options a {
    width: 40px;
    height: 40px;
    border-radius: 100%;
    background: #ffbe33;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }
  
  .preview-menu .options a::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: all 0.3s ease;
  }
  
  .preview-menu .options a:hover::before {
    width: 100%;
    height: 100%;
  }
  
  .preview-menu .options a i {
    color: #ffffff;
    font-size: 18px;
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
  }
  
  .preview-menu .options a:hover {
    background: #e69c00;
    transform: scale(1.1);
    box-shadow: 0 4px 15px rgba(255, 190, 51, 0.4);
  }
  
  .preview-menu .options a:hover i {
    transform: scale(1.2);
    animation: cartBounce 0.6s ease;
  }
  
  .preview-menu .options a:active {
    transform: scale(0.95);
  }
  
  @keyframes cartBounce {
    0% { transform: scale(1.2); }
    50% { transform: scale(1.4) rotate(6deg); }
    100% { transform: scale(1.2) rotate(0deg); }
  }
  
  .view-more-btn {
    display: inline-block;
    background: #ffbe33;
    color: #ffffff;
    padding: 12px 30px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    margin-top: 30px;
  }
  
  .view-more-btn:hover {
    background: #e69c00;
    color: #ffffff;
    text-decoration: none;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 190, 51, 0.4);
  }
  
  /* Popup personalizzato per il carrello */
  .cart-popup {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  
  .cart-popup.show {
    opacity: 1;
  }
  
  .popup-content {
    background: rgb(227, 227, 227);
    border-radius: 20px;
    padding: 40px 30px;
    text-align: center;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    transform: scale(0.7);
    border: 2px solid #f39c12;
    transition: transform 0.3s ease;
  }
  
  .cart-popup.show .popup-content {
    transform: scale(1);
  }
  
  .popup-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: #4CAF50;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: popupSuccess 0.6s ease;
  }
  
  .popup-icon.error {
    background: #f44336;
  }
  
  .popup-icon i {
    font-size: 40px;
    color: white;
  }
  
  @keyframes popupSuccess {
    0% {
      transform: scale(0);
      opacity: 0;
    }
    50% {
      transform: scale(1.2);
    }
    100% {
      transform: scale(1);
      opacity: 1;
    }
  }
  
  .popup-title {
    font-size: 24px;
    font-weight: 600;
    color: #333;
    margin-bottom: 15px;
  }
  
  .popup-message {
    font-size: 16px;
    color: #666;
    margin-bottom: 25px;
    line-height: 1.4;
  }
  
  .popup-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
  }
  
  .popup-btn {
    padding: 12px 25px;
    border: none;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 120px;
  }
  
  .popup-continue {
    background: #f5f5f5;
    color: #333;
  }
  
  .popup-continue:hover {
    background: #e0e0e0;
  }
  
  .popup-view-cart {
    background: #ffbe33;
    color: white;
  }
  
  .popup-view-cart:hover {
    background: #e69c00;
    transform: translateY(-2px);
  }
  
  /* Stato loading del bottone carrello */
  .add-to-cart.loading {
    animation: buttonLoading 1s infinite;
    pointer-events: none;
  }
  
  @keyframes buttonLoading {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
  }
  
  /* Responsive per popup */
  @media (max-width: 768px) {
    .popup-content {
      padding: 30px 20px;
      max-width: 300px;
    }
    
    .popup-actions {
      flex-direction: column;
      gap: 10px;
    }
    
    .popup-btn {
      width: 100%;
    }

    
  }

  h4{
    color: #ffbe33;
  }

  .success-icon {
    color:#f39c12; 
    font-size: 80px; 
    margin-bottom: 20px;
    animation: checkmark 0.6s ease-in-out;
    /*text-shadow: 0 0 20px rgba(39, 174, 96, 0.5);*/ /* Effetto glow */
  }

  </style>

  <div class="hero_area">
    <div class="bg-box">
      <img src="images/hero-bg-gpt.png" alt="">
    </div>
    <!-- header section strats -->
    <header class="header_section header_personalizzato">
      <div class="container">
        <nav class="navbar navbar-expand-lg custom_nav-container ">
          <a class="navbar-brand" href="index.php">
            <span>
              Feane
            </span>
          </a>

          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class=""> </span>
          </button>

          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav  mx-auto ">
              <li class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php">Home <span class="sr-only">(current)</span></a>
              </li>
              <li class="nav-item <?php echo ($current_page == 'menu.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="menu.php">Menu</a>
              </li>
              <li class="nav-item <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="about.php">About</a>
              </li>
              <li class="nav-item <?php echo ($current_page == 'book.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="book.php">Book Table</a>
              </li>
            </ul>
            <div class="user_option">
              <a class="cart_link <?php echo ($current_page == 'cart.php') ? 'active' : ''; ?>" href="cart.php">
                <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 456.029 456.029" style="enable-background:new 0 0 456.029 456.029;" xml:space="preserve">
                  <g>
                    <g>
                      <path d="M345.6,338.862c-29.184,0-53.248,23.552-53.248,53.248c0,29.184,23.552,53.248,53.248,53.248
                   c29.184,0,53.248-23.552,53.248-53.248C398.336,362.926,374.784,338.862,345.6,338.862z" />
                    </g>
                  </g>
                  <g>
                    <g>
                      <path d="M439.296,84.91c-1.024,0-2.56-0.512-4.096-0.512H112.64l-5.12-34.304C104.448,27.566,84.992,10.67,61.952,10.67H20.48
                   C9.216,10.67,0,19.886,0,31.15c0,11.264,9.216,20.48,20.48,20.48h41.472c2.56,0,4.608,2.048,5.12,4.608l31.744,216.064
                   c4.096,27.136,27.648,47.616,55.296,47.616h212.992c26.624,0,49.664-18.944,55.296-45.056l33.28-166.4
                   C457.728,97.71,450.56,86.958,439.296,84.91z" />
                    </g>
                  </g>
                  <g>
                    <g>
                      <path d="M215.04,389.55c-1.024-28.16-24.576-50.688-52.736-50.688c-29.696,1.536-52.224,26.112-51.2,55.296
                   c1.024,28.16,24.064,50.688,52.224,50.688h1.024C193.536,443.31,216.576,418.734,215.04,389.55z" />
                    </g>
                  </g>
                  <g>
                  </g>
                  <g>
                  </g>
                  <g>
                  </g>
                  <g>
                  </g>
                  <g>
                  </g>
                  <g>
                  </g>
                  <g>
                  </g>
                  <g>
                  </g>
                  <g>
                  </g>
                  <g>
                  </g>
                  <g>
                  </g>
                  <g>
                  </g>
                  <g>
                  </g>
                  <g>
                  </g>
                  <g>
                  </g>
                </svg>
                <?php
                if ($quantita_totale > 0) {
                    echo '<div id="counter">' . $quantita_totale . '</div>';
                } else {
                    echo '<div id="counter"  class="invisible"></div>';
                }
                ?>
              </a>
              
              <!-- Sezione utente con nome se loggato -->
              <?php if ($user_logged_in): ?>
                <div class="user_profile">
                  <a href="login.php" class="user_link <?php echo ($current_page == 'utente.php'||$current_page == 'dashboard_dinamica.php'||$current_page == 'login.php') ? 'active' : ''; ?>">
                    <i class="fa fa-user" aria-hidden="true"></i>
                    <span class="user_name"><?php echo htmlspecialchars($user_name); ?></span>
                  </a>
                </div>
              <?php else: ?>
                <div class="user_auth">
                  <a href="login.php" class="user_link <?php echo ($current_page == 'login.php' || $current_page == 'register.php') ? 'active' : ''; ?>">
                    <i class="fa fa-user" aria-hidden="true"></i>
                    <span class="auth_text">Accedi</span>
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </nav>
      </div>
    </header>
    <!-- end header section -->
    <!-- slider section -->
    <section class="intro_section d-flex align-items-center" style="min-height: 100vh; position: relative;">
  <div class="bg-box">
  </div>
  <div class="container position-relative text-white text-center">
    <div class="row justify-content-center">
      <div class="col-md-10 col-lg-8">
        <h1 class="display-4 fw-bold" style="font-family: 'Dancing Script', cursive;">
          Benvenuto da <span style="color: #ffbe33;">Feane</span>
        </h1>
        <p class="lead mt-3 mb-4">
          Dove la passione per la cucina italiana incontra la qualità e la tradizione.
          Vieni a scoprire i nostri piatti unici preparati con ingredienti freschi e amore.
        </p>
        <a href="book.php" class="btn btn-warning btn-lg px-5 py-2 rounded-pill">
          Mangia da noi
        </a>
      </div>
    </div>
  </div>
</section>

    <!-- end slider section -->
  </div>

  <!-- offer section -->
  <!-- end offer section -->

  <!-- food section -->
  <section class="food_section layout_padding-bottom food preview-menu">
    <div class="container">
      <div class="heading_container heading_center">
        <h2>
          Il nostro Menù
        </h2>
      </div>

      <div class="filters-content">
        <div class="row grid">
          <?php mostraPreviewProdotti($pdo); ?>
        </div>
      </div>
      <div class="btn-box text-center">
        <a href="menu.php" class="view-more-btn">
          <i class="fa fa-cutlery" style="margin-right: 8px;"></i>
          Visualizza Menu Completo
        </a>
      </div>
    </div>
  </section>

  <!-- end food section -->

  <!-- about section -->
  <section class="about_section layout_padding">
    <div class="container  ">
      <div class="row">
        <div class="col-md-6 ">
          <div class="img-box">
            <img src="images/about-img.png" alt="">
          </div>
        </div>
        <div class="col-md-6">
          <div class="detail-box">
            <div class="heading_container">
              <h2>
                We Are Feane
              </h2>
            </div>
            <p>
              There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration
              in some form, by injected humour, or randomised words which don't look even slightly believable. If you
              are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in
              the middle of text. All
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- end about section -->

  <!-- book section -->
  <!-- end book section -->

  <!-- client section -->
  <section class="client_section layout_padding-bottom food">
  <div class="container">
    <div class="heading_container heading_center psudo_white_primary mb_45">
      <h2>What Says Our Customers</h2>
    </div>

    <div class="carousel-wrap row">
      <div class="owl-carousel client_owl-carousel">
        <?php
        $stmt = $pdo->prepare("
          SELECT r.Testo, r.Voto, u.Nome 
          FROM recensione r 
          JOIN utente u ON r.ID_Utente = u.ID_Utente 
          ORDER BY r.Data DESC 
          LIMIT 10
        ");
        $stmt->execute();
        $recensioni = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($recensioni as $i => $rec) {
          $img = $i % 2 == 0 ? 'images/client1.jpg' : 'images/client2.jpg';
          $testo_completo = htmlspecialchars($rec['Testo']);
          $testo_troncato = strlen($testo_completo) > 150 ? substr($testo_completo, 0, 150) . '...' : $testo_completo;
          $mostra_espandi = strlen($testo_completo) > 150;

          echo '<div class="item">
                  <div class="box">
                    <div class="detail-box">
                      <p class="recensione-testo">' . $testo_troncato . '</p>';
                      if ($mostra_espandi) {
                        echo '<a href="#" class="espandi-link text-warning" data-completo="' . htmlspecialchars($testo_completo) . '" data-troncato="' . htmlspecialchars($testo_troncato) . '">Espandi</a>';
                      }
          echo '<h6>' . htmlspecialchars($rec['Nome']) . '</h6>
                      <p>';
          for ($v = 1; $v <= 5; $v++) {
            echo $v <= $rec['Voto'] ? '⭐' : '☆';
          }
          echo     '</p>
                    </div>
                  </div>
                </div>';
        }
        ?>
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
        <button class="popup-btn popup-continue">Continua lo shopping</button>
        <button class="popup-btn popup-view-cart">Visualizza carrello</button>
      </div>
    </div>
  </div>

  <!-- Popup per errori -->
  <div id="error-popup" class="cart-popup error-popup">
    <div class="popup-content">
      <div class="popup-icon error">
        <i class="fa fa-exclamation-triangle"></i>
      </div>
      <h3 class="popup-title">Errore</h3>
      <p class="popup-message error-message"></p>
      <div class="popup-actions">
        <button class="popup-btn popup-continue">Chiudi</button>
      </div>
    </div>
  </div>

  <!-- end client section -->
  <script>
    window.addEventListener('scroll', function () {
      const header = document.querySelector('header');
      if (window.scrollY > 100) {
        header.classList.remove('header_personalizzato');
      } else {
        header.classList.add('header_personalizzato');
      }
    });

    // Script per il carrello (stesso di menu.php)
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
          
          btn.classList.add('loading');
          
          const id = btn.dataset.id;
          const tipo = btn.dataset.tipo;

          const counter = document.getElementById('counter');
          if (counter.classList.contains('invisible')) {
        counter.classList.remove('invisible')
        
        counter.textContent =  1;
      } else {
        let current = parseInt(counter.textContent) || 0;
        counter.textContent = current +1;
      }


          fetch('aggiungi_al_carrello.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}&tipo=${tipo}`
          })
          .then(res => res.text())
          .then(msg => {
            btn.classList.remove('loading');
            
            if (msg.trim() === 'success') {
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

    document.addEventListener('DOMContentLoaded', function () {
  function aggiornaAltezzaCarousel() {
    const itemsAttivi = document.querySelectorAll('.owl-carousel .owl-item.active .item');
    const carousel = document.querySelector('.owl-carousel');
    if (itemsAttivi.length > 0 && carousel) {
      let maxHeight = 0;
      itemsAttivi.forEach(item => {
        maxHeight = Math.max(maxHeight, item.offsetHeight);
      });
      carousel.style.height = maxHeight + 'px';
    }
  }

  document.querySelectorAll('.espandi-link').forEach(link => {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      this.blur();

      const paragrafo = this.previousElementSibling;
      const testoCompleto = this.getAttribute('data-completo');
      const testoTroncato = this.getAttribute('data-troncato');

      if (this.textContent.toLowerCase() === 'espandi') {
        paragrafo.textContent = testoCompleto;
        this.textContent = 'Comprimi';
      } else {
        paragrafo.textContent = testoTroncato;
        this.textContent = 'Espandi';
      }

      aggiornaAltezzaCarousel();
    });
  });

  $('.client_owl-carousel').on('changed.owl.carousel', function () {
    setTimeout(aggiornaAltezzaCarousel, 100);
  });

  setTimeout(aggiornaAltezzaCarousel, 500);
});
  </script>

  <?php include 'footer.php'; ?>
