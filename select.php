<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */
session_start();

// Weryfikacja sesji - przesyłanie plikow tylko dla zalogowanych uzytkownikow
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit();
}

// Pobranie biezacego podkatalogu (wzgledem katalogu macierzystego)
$sub = isset($_GET['sub']) ? (string)$_GET['sub'] : '';
$sub = str_replace('\\', '/', $sub);
$sub = trim($sub, '/');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Twój Opis">
	<meta name="author" content="Twoje dane">
	<meta name="keywords" content="Twoje słowa kluczowe">
	<title>Prześlij plik</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" type="text/css" href="twoj_css.css">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
	<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
	<script type="text/javascript" src="twoj_js.js"></script>
</head>

<body onload="myLoadHeader()">
	<div id='myHeader'> </div>
	<main>
		<section class="sekcja1">
			<div class="container-fluid">
				<h2><i class="bi bi-upload"></i> Przesyłanie pliku na dysk</h2>
				<p>
					Katalog docelowy:
					<?php if ($sub === ''): ?>
						katalog macierzysty
					<?php else: ?>
						/<?php echo htmlspecialchars($sub); ?>
					<?php endif; ?>
				</p>

				<form action="upload.php" method="post" enctype="multipart/form-data" class="row g-3" style="max-width:560px;">
					<input type="hidden" name="sub" value="<?php echo htmlspecialchars($sub); ?>">
					<div class="col-12">
						<label for="fileToUpload" class="form-label">Wybierz plik do przesłania</label>
						<input class="form-control" type="file" name="fileToUpload" id="fileToUpload" required>
					</div>
					<div class="col-12">
						<button class="btn btn-primary" type="submit" name="submit"><i class="bi bi-cloud-arrow-up"></i> Prześlij</button>
						<a class="btn btn-secondary" href="mycloud.php?sub=<?php echo urlencode($sub); ?>">Anuluj</a>
					</div>
				</form>
			</div>
		</section>
	</main>
	<?php require_once 'footer.php'; ?>
</body>
</html>
