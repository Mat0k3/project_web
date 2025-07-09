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

require_once 'includes/dbh.inc.php'; // Connessione PDO

$errore = "";
$successo = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST["nome"]);
    $email = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    try {
        // Controlla se l'email esiste già
        $stmt = $pdo->prepare("SELECT ID_Utente FROM utente WHERE Email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $errore = "Email già registrata.";
        } else {
            // Inserisci nuovo utente
            $stmt = $pdo->prepare("INSERT INTO utente (Nome, Email, Password) VALUES (:nome, :email, :password)");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);

            if ($stmt->execute()) {
              // Recupera l'ID dell'utente appena registrato
              $id_utente = $pdo->lastInsertId();
          
              // Recupera l'ID del gruppo 'utente'
              $stmt_gruppo = $pdo->prepare("SELECT ID_Gruppo FROM gruppo WHERE LOWER(Nome) = 'utenti'");
              $stmt_gruppo->execute();
              $id_gruppo = $stmt_gruppo->fetchColumn();
          
              if ($id_gruppo) {
                  // Inserisci nella tabella utente_gruppo
                  $stmt_associa = $pdo->prepare("INSERT INTO utente_gruppo (ID_Utente, ID_Gruppo) VALUES (:id_utente, :id_gruppo)");
                  $stmt_associa->execute([
                      ':id_utente' => $id_utente,
                      ':id_gruppo' => $id_gruppo
                  ]);
              }
          
              $successo = "Registrazione avvenuta con successo!";
              $_SESSION['temp_user'] = [
                  'email' => $_POST['email'],
                  'password' => $_POST['password']
              ];
              $_SESSION['utente_id'] = $id_utente;
              $_SESSION['utente_nome'] = $nome;
              header("Location: utente.php?");
              exit;
          }
        }
    } catch (PDOException $e) {
        $errore = "Errore del server: " . $e->getMessage();
    }
}
?>

<?php include 'header.php'; ?>

<div class="register-wrapper">
  <div class="register-card spazio">
    <h3 class="text-center mb-4">Registrazione</h3>

    <?php if ($errore): ?>
      <div class="alert alert-danger">
        <?php echo htmlspecialchars($errore); ?>
      </div>
    <?php elseif ($successo): ?>
      <div class="alert alert-success">
        <?php echo htmlspecialchars($successo); ?>
      </div>
    <?php endif; ?>

    <form method="post" action="register.php">
      <div class="form-group">
        <label for="nome" class="form-label">Nome</label>
        <input type="text" id="nome" name="nome" class="form-control" required>
      </div>

      <div class="form-group">
        <label for="email" class="form-label">Email</label>
        <input type="email" id="email" name="email" class="form-control" required>
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" name="password" class="form-control" required>
      </div>

      <div class="d-grid mt-3">
        <button type="submit" class="btn btn-warning">Registrati</button>
      </div>

      <p class="mt-3 text-center">
        Hai già un account? <a href="login.php">Accedi qui</a>
      </p>
    </form>
  </div>
</div>

<?php include 'footer.php'; ?>
