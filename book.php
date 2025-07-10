<?php 
session_start(); 

if (!isset($_SESSION['utente_id'])) {
  $_SESSION['prov'] = 'book';
  header("Location: login.php");
  exit;
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

  <title> Pane & Pixel  </title>

  <!-- bootstrap core css -->
  <link rel="stylesheet" type="text/css" href="css/bootstrap.css" />

  <!--owl slider stylesheet -->
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
  <!-- nice select  -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-nice-select/1.1.0/css/nice-select.min.css" integrity="sha512-CruCP+TD3yXzlvvijET8wV5WxxEh5H8P4cmz0RFbKK6FlZ2sYl3AEsKlLPHbniXKSrDdFewhbmBK5skbdsASbQ==" crossorigin="anonymous" />
  <!-- font awesome style -->
  <link href="css/font-awesome.min.css" rel="stylesheet" />

  <!-- Custom styles for this template -->
  <link href="css/style.css" rel="stylesheet" />
  <!-- responsive style -->
  <link href="css/responsive.css" rel="stylesheet" />

</head>

<body class="sub_page">
  <style>
    .spazio{
      margin-top: 50px;
    }
  </style>
  <div class="hero_area">
    <?php include 'header.php'; ?>
  </div>

  <!-- book section -->
  <section class="book_section layout_padding">
    <div class="container spazio">
      <div class="heading_container">
        <h2>
          Prenota un tavolo
        </h2>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form_container">
            <form action="includes/form_prenotazioni_handler.inc.php" method="post">
              <div>
                <input type="text" name="nome" class="form-control" placeholder="Nome" required />
              </div>
              <div>
                <select name="persone" class="form-control nice-select wide" required>
                  <option value="" disabled selected>
                    Numero di persone
                  </option>
                  <option value="1">
                    1
                  </option>
                  <option value="2">
                    2
                  </option>
                  <option value="3">
                    3
                  </option>
                  <option value="4">
                    4
                  </option>
                  <option value="6">
                    6
                  </option>
                  <option value="7">
                    7
                  </option>
                  <option value="8">
                    8
                  </option>
                  <option value="9">
                    9
                  </option>
                  <option value="10">
                    10
                  </option>
                </select>
              </div>
              <div>
                <input type="date" name="data" class="form-control" required>
              </div>
              <div>
                <input type="time" name="ora" class="form-control" required>
              </div>
              <div class="btn_box">
                <button type="submit">
                  Prenota
                </button>
              </div>
            </form>
          </div>
        </div>
        <div class="col-md-6">
          <div class="map_container ">
            <div id="googleMap">
              <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d11793.560713535804!2d13.380958787158205!3d42.35552330000001!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x132fd2f32395d82b%3A0x40ed6780ff6559a2!2sUniversit%C3%A0%20degli%20Studi%20dell&#39;Aquila%20-%20Dipartimento%20di%20Scienze%20Umane!5e0!3m2!1sit!2sit!4v1752134254246!5m2!1sit!2sit" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- end book section -->

  <!-- footer section -->
  <?php include 'footer.php'; ?>

  <!-- jQery -->
  <script src="js/jquery-3.4.1.min.js"></script>
  <!-- popper js -->
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous">
  </script>
  <!-- bootstrap js -->
  <script src="js/bootstrap.js"></script>
  <!-- owl slider -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js">
  </script>
  <!-- isotope js -->
  <script src="https://unpkg.com/isotope-layout@3.0.4/dist/isotope.pkgd.min.js"></script>
  <!-- nice select -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-nice-select/1.1.0/js/jquery.nice-select.min.js"></script>
  <!-- custom js -->
  <script src="js/custom.js"></script>
  <!-- Google Map  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCh39n5U-4IoWpsVGUHWdqB6puEkhRLdmI&callback=myMap"> -->
  
  </script>
  <!-- End Google Map -->

</body>

</html>