<?php
session_start();

if (isset($_SESSION['utente_id'])) {
    header("Location: utente.php"); // o un'altra pagina dopo il login
    exit;
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
                $successo = "Registrazione avvenuta con successo!";
                $_SESSION['temp_user'] = [
                    'email' => $_POST['email'],
                    'password' => $_POST['password']
                ];

                header("Location: login.php?success=1");
                exit;

            } else {
                $errore = "Errore durante la registrazione.";
            }
        }
    } catch (PDOException $e) {
        $errore = "Errore del server: " . $e->getMessage();
    }
}
?>

<?php include 'header.php'; ?>

<div class="register-wrapper">
  <div class="register-card">
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
