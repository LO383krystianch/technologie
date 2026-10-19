<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */
session_start();

// Juz zalogowany - przejdz od razu do panelu
if (isset($_SESSION['loggedin'])) {
    header('Location: strona.php');
    exit();
}

$blad = isset($_GET['blad']);
$rej  = isset($_GET['rej']);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">  
	<meta name="description" content="Twój Opis">
	<meta name="author" content="Twoje dane">
	<meta name="keywords" content="Twoje słowa kluczowe">
	<title>Logowanie</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" type="text/css" href="twoj_css.css">
</head>

<body class="text-center">
	<main class="form-signin w-100 m-auto">
		<form action="login.php" method="post">
			<i class="bi bi-person-circle" style="font-size:3.5rem; color:#0d6efd;"></i>
			<h1 class="h3 mb-3 fw-normal">Zaloguj się</h1>

			<?php if ($blad): ?>
				<div class="alert alert-danger" role="alert">Niepoprawny login lub hasło.</div>
			<?php endif; ?>

			<?php if ($rej): ?>
				<div class="alert alert-success" role="alert">Rejestracja zakończona pomyślnie. Możesz się zalogować.</div>
			<?php endif; ?>

			<div class="form-floating">
				<input type="text" name="user" class="form-control" id="floatingInput" placeholder="Login" required autofocus>
				<label for="floatingInput">Login</label>
			</div>
			<div class="form-floating">
				<input type="password" name="pass" class="form-control" id="floatingPassword" placeholder="Hasło" required>
				<label for="floatingPassword">Hasło</label>
			</div>

			<div class="checkbox mb-3">
				<label><input type="checkbox" value="remember-me"> Zapamiętaj mnie</label>
			</div>

			<button class="w-100 btn btn-lg btn-primary" type="submit">Zaloguj</button>

			<p class="mt-3 mb-3 text-muted">Nie masz konta? <a href="rejestracja.php">Zarejestruj się</a></p>
		</form>
	</main>
</body>
</html>
