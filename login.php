<?php
session_start();


if (isset($_SESSION['utente_id'])) {
  require_once 'includes/dbh_test.inc.php';

  // Recupera il gruppo dell’utente
  $stmt = $pdo->prepare("
      SELECT g.Nome 
      FROM gruppo g
      JOIN utente_gruppo ug ON ug.ID_Gruppo = g.ID_Gruppo
      WHERE ug.ID_Utente = :id
      LIMIT 1
  ");
  $stmt->execute([':id' => $_SESSION['utente_id']]);
  $gruppo = strtolower($stmt->fetchColumn());

  // Reindirizzamento in base al gruppo
  if ($gruppo === 'utenti') {
      header("Location: utente.php");
      exit;
  } elseif ($gruppo === 'admin' || $gruppo === 'cucina') {
      header("Location: dashboard_dinamica.php");
      exit;
  }
}

$prefill_email = '';
$prefill_password = '';

if (isset($_SESSION['temp_user'])) {
    $prefill_email = $_SESSION['temp_user']['email'];
    $prefill_password = $_SESSION['temp_user']['password'];
    unset($_SESSION['temp_user']); 
}

require_once 'includes/dbh.inc.php';

$errore = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    try {
        $stmt = $pdo->prepare("SELECT ID_Utente, Nome, Password FROM utente WHERE Email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $utente = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($password, $utente['Password'])) {
                // Login riuscito, salva i dati in sessione
                $_SESSION['utente_id'] = $utente['ID_Utente'];
                $_SESSION['utente_nome'] = $utente['Nome'];

                
                  // if (isset($_SESSION['prov'])){
                  //   if ($_SESSION['prov'] == 'book'){
                  //     $_SESSION['prov'] = '';
                  //     header("Location: book.php");
                  //     exit;
                  //   }
                  // }

                $stmt = $pdo->prepare("
                  SELECT g.Nome 
                  FROM gruppo g
                  JOIN utente_gruppo ug ON ug.ID_Gruppo = g.ID_Gruppo
                  WHERE ug.ID_Utente = :id
                  LIMIT 1
                ");
                $stmt->execute([':id' => $_SESSION['utente_id']]);
                $gruppo = strtolower($stmt->fetchColumn());

                // Reindirizzamento in base al gruppo
                if ($gruppo === 'utenti') {
                    header("Location: utente.php");
                    exit;
                } elseif ($gruppo === 'admin' || $gruppo === 'cucina') {
                    header("Location: dashboard_dinamica.php");
                    exit;
                }
            } else {
                $errore = "Password errata.";
            }
        } else {
            $errore = "Email non trovata.";
        }
    } catch (PDOException $e) {
        $errore = "Errore del server: " . $e->getMessage();
    }
}
?>


<?php include 'header.php'; ?>

<div class="register-wrapper">
  <div class="register-card">
    <h3 class="text-center mb-4">Login</h3>

    <?php if ($errore): ?>
      <div class="alert alert-danger">
        <?php echo htmlspecialchars($errore); ?>
      </div>
    <?php endif; ?>

    <form method="post" action="login.php">
      <div class="form-group">
        <label for="email" class="form-label">Email</label>
        <input type="email" name="email" class="form-control"
            value="<?php echo htmlspecialchars($prefill_email); ?>" required>
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" class="form-control"
            value="<?php echo htmlspecialchars($prefill_password); ?>" required>
      </div>

      <div class="d-grid mt-3">
        <button type="submit" class="btn btn-warning">Accedi</button>
      </div>

      <p class="mt-3 text-center">
        Non hai un account? <a href="register.php">Registrati qui</a>
      </p>
    </form>
  </div>
</div>

<?php include 'footer.php'; ?>

