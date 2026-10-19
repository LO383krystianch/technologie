<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */
session_start();

// Weryfikacja sesji - tresc dostepna tylko dla zalogowanych uzytkownikow
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit();
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
	<title>Usuń hosta z monitorowania</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" type="text/css" href="twoj_css.css">
</head>

<body onload="myLoadHeader()">
	<div id='myHeader'> </div>
	<main>
		<section class="sekcja1">
			<div class="container-fluid">
				<h2>Usuń hosta z monitorowania</h2>
				<p>Podaj adres hosta oraz numer portu usługi, którą chcesz usunąć z procesu monitorowania.</p>

				<form action="kasuj2.php" method="post" class="row g-3" style="max-width:520px;">
					<div class="col-12">
						<label for="host" class="form-label">Host (nazwa domenowa lub IP)</label>
						<input type="text" class="form-control" id="host" name="host" placeholder="np. fb.com" required>
					</div>
					<div class="col-12">
						<label for="port" class="form-label">Port</label>
						<input type="number" class="form-control" id="port" name="port" min="1" max="65535" placeholder="np. 80" required>
					</div>
					<div class="col-12">
						<button type="submit" class="btn btn-warning"><i class="bi bi-dash-circle"></i> Usuń</button>
						<a href="index1.php" class="btn btn-secondary">Powrót do monitorowania</a>
					</div>
				</form>
			</div>
		</section>
	</main>
	<?php require_once 'footer.php'; ?>
</body>
</html>
