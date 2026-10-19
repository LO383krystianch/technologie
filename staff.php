<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */
session_start();

// Weryfikacja sesji - tresc dostepna tylko dla zalogowanych uzytkownikow
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit();
}

// Formularz dostepny wylacznie dla administratora
if ((string)($_SESSION['username'] ?? '') !== 'admin') {
    header('Location: index1.php');
    exit();
}

require_once 'config.php';

$link = db_connect();
db_ensure_users_table($link);
db_ensure_domeny_table($link);

// ============================================================
//  Obsluga formularza (dodanie hosta i przypisanie pracownika)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host      = isset($_POST['host']) ? trim($_POST['host']) : '';
    $port      = isset($_POST['port']) ? (int)$_POST['port'] : 0;
    $worker_id = isset($_POST['worker_id']) ? (int)$_POST['worker_id'] : 0;

    if ($host !== '' && $port >= 1 && $port <= 65535 && $worker_id > 0) {
        $stmt = mysqli_prepare($link, "INSERT INTO domeny (host, port, user_id, errors, total_errors) VALUES (?, ?, ?, 0, 0)");
        mysqli_stmt_bind_param($stmt, 'sii', $host, $port, $worker_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($link);

        header('Location: index1.php');
        exit();
    }
}

// Pobranie listy pracownikow (users z kolumna worker = true)
$pracownicy = [];
$wynik = mysqli_query($link, "SELECT id, username FROM users WHERE worker = 1 ORDER BY username");
while ($r = mysqli_fetch_assoc($wynik)) {
    $pracownicy[] = $r;
}
mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Twój Opis">
	<meta name="author" content="Twoje dane">
	<meta name="keywords" content="Twoje słowa kluczowe">
	<title>Staff - przypisz pracownika do hosta</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" type="text/css" href="twoj_css.css">
</head>

<body onload="myLoadHeader()">
	<div id='myHeader'> </div>
	<main>
		<section class="sekcja1">
			<div class="container-fluid">
				<h2>Staff - dodaj hosta i przypisz pracownika</h2>
				<p>Administrator dodaje adres hosta i numer portu oraz wybiera pracownika odpowiedzialnego za jego monitorowanie.</p>

				<form action="staff.php" method="post" class="row g-3" style="max-width:520px;">
					<div class="col-12">
						<label for="host" class="form-label">Host (nazwa domenowa lub IP)</label>
						<input type="text" class="form-control" id="host" name="host" placeholder="np. fb.com" required>
					</div>
					<div class="col-12">
						<label for="port" class="form-label">Port</label>
						<input type="number" class="form-control" id="port" name="port" min="1" max="65535" placeholder="np. 80" required>
					</div>
					<div class="col-12">
						<label for="worker_id" class="form-label">Pracownik odpowiedzialny za monitorowanie</label>
						<select class="form-select" id="worker_id" name="worker_id" required>
							<option value="" selected disabled>Wybierz pracownika...</option>
							<?php foreach ($pracownicy as $p): ?>
								<option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars((string)$p['username']); ?></option>
							<?php endforeach; ?>
						</select>
						<?php if (count($pracownicy) === 0): ?>
							<div class="form-text text-warning">Brak pracowników (kolumna worker = true w tabeli users).</div>
						<?php endif; ?>
					</div>
					<div class="col-12">
						<button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Dodaj i przypisz</button>
						<a href="index1.php" class="btn btn-secondary">Powrót do monitorowania</a>
					</div>
				</form>
			</div>
		</section>
	</main>
	<?php require_once 'footer.php'; ?>
</body>
</html>
