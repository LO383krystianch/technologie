<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */
session_start();

// Juz zalogowany - przejdz do panelu
if (isset($_SESSION['loggedin'])) {
    header('Location: strona.php');
    exit();
}

$blad = isset($_GET['blad']) ? (int)$_GET['blad'] : 0;
$komunikat = '';
switch ($blad) {
    case 1: $komunikat = 'Wypełnij wszystkie pola formularza.'; break;
    case 2: $komunikat = 'Login musi mieć od 3 do 50 znaków.'; break;
    case 3: $komunikat = 'Hasło musi mieć co najmniej 4 znaki.'; break;
    case 4: $komunikat = 'Hasła nie są identyczne.'; break;
    case 5: $komunikat = 'Podany login jest już zajęty.'; break;
    case 6: $komunikat = 'Błąd zapisu do bazy danych.'; break;
    case 7: $komunikat = 'Login może zawierać tylko litery, cyfry i podkreślenie.'; break;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">  
	<meta name="description" content="Twój Opis">
	<meta name="author" content="Twoje dane">
	<meta name="keywords" content="Twoje słowa kluczowe">
	<title>Rejestracja</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" type="text/css" href="twoj_css.css">
</head>

<body class="text-center">
	<main class="form-register w-100 m-auto">
		<form action="register.php" method="post" enctype="multipart/form-data">
			<i class="bi bi-person-plus-fill" style="font-size:3rem; color:#0d6efd;"></i>
			<h1 class="h3 mb-3 fw-normal">Rejestracja</h1>

			<?php if ($komunikat !== ''): ?>
				<div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($komunikat); ?></div>
			<?php endif; ?>

			<div class="form-floating">
				<input type="text" name="user" class="form-control" id="floatingUser" placeholder="Login" required autofocus>
				<label for="floatingUser">Login</label>
			</div>
			<div class="form-floating">
				<input type="password" name="pass" class="form-control" id="floatingPass" placeholder="Hasło" required>
				<label for="floatingPass">Hasło</label>
			</div>
			<div class="form-floating">
				<input type="password" name="pass2" class="form-control" id="floatingPass2" placeholder="Powtórz hasło" required>
				<label for="floatingPass2">Powtórz hasło</label>
			</div>

			<div class="mb-3 text-start">
				<label for="formFile" class="form-label">Avatar (opcjonalnie)</label>
				<input class="form-control" type="file" id="formFile" name="avatar" accept="image/*">
				<div class="form-text">Jeśli nie dodasz avatara, zostanie użyta domyślna ikona użytkownika.</div>
			</div>

			<button class="w-100 btn btn-lg btn-primary" type="submit">Zarejestruj</button>

			<p class="mt-3 mb-3 text-muted">Masz już konto? <a href="index.php">Zaloguj się</a></p>
		</form>
	</main>
</body>
</html>
