<?php
// Ottieni il nome del file corrente
$current_page = basename($_SERVER['PHP_SELF']);

require_once 'includes/dbh.inc.php';

if (isset($_SESSION['gruppo']) && $_SESSION['gruppo'] === 'admin') {
  $classeDisabilitata = 'disabilitato';
}else{
  $classeDisabilitata = '';
}

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

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- nice select  -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-nice-select/1.1.0/css/nice-select.min.css" integrity="sha512-CruCP+TD3yXzlvvijET8wV5WxxEh5H8P4cmz0RFbKK6FlZ2sYl3AEsKlLPHbniXKSrDdFewhbmBK5skbdsASbQ==" crossorigin="anonymous" />
  <!-- font awesome style -->
  <link href="css/font-awesome.min.css" rel="stylesheet" />

  <!-- Custom styles for this template -->
  <link href="css/style.css" rel="stylesheet" />
  <!-- responsive style -->
  <link href="css/responsive.css" rel="stylesheet" />

  <link rel="stylesheet" href="css/register_login.css">

  <link rel="stylesheet" href="css/utente.css">
  <style>
    .disabilitato{
      pointer-events: none;   /* Disabilita il click */
      opacity: 0;             /* Rende il bottone invisibile */
      visibility: hidden;     /* Lo nasconde dallo schermo */
      height: 0;
      width: 0;
      overflow: hidden;
      margin: 0;
      padding: 0;
      border: none;
    }
  </style>
</head>
<body>

  <div class="hero_area">
    
    <!-- header section strats -->
    <header class="header_section">
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
              <li class="nav-item <?php echo ($current_page == 'book.php') ? 'active' : ''; ?> <?php echo $classeDisabilitata; ?>">
                <a class="nav-link" href="book.php">Book Table</a>
              </li>
            </ul>
            <div class="user_option">
              <a class="cart_link <?php echo ($current_page == 'cart.php') ? 'active' : ''; ?> <?php echo $classeDisabilitata; ?>" href="cart.php">
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
                    echo '<div id="counter" class="invisible"></div>';
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