<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */
session_start();

// Weryfikacja sesji - dysk sieciowy dostepny tylko dla zalogowanych uzytkownikow
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

$username = (string)($_SESSION['username'] ?? '');

// ============================================================
//  Ustalenie katalogu macierzystego i biezacego podkatalogu
// ============================================================
$home     = ensure_user_home_dir($username);
$homeReal = realpath($home);

// Pobranie podkatalogu (wzgledem katalogu macierzystego) z parametru GET
$sub = isset($_GET['sub']) ? (string)$_GET['sub'] : '';
$sub = str_replace('\\', '/', $sub);
$sub = trim($sub, '/');

// Walidacja nazwy podkatalogu - odrzucenie znakow niebezpiecznych
if ($sub !== '') {
    $czesc = explode('/', $sub);
    $ok = true;
    foreach ($czesc as $c) {
        if ($c === '' || $c === '.' || $c === '..' || strpos($c, "\0") !== false) {
            $ok = false;
            break;
        }
    }
    if (!$ok) {
        $sub = '';
    }
}

// Ustalenie biezacej sciezki z zabezpieczeniem przed wyjsciem poza katalog macierzysty
$current     = $home . ($sub !== '' ? DIRECTORY_SEPARATOR . $sub : '');
$currentReal = realpath($current);
if ($currentReal === false || $homeReal === false || strpos($currentReal, $homeReal) !== 0) {
    $current     = $home;
    $currentReal = $homeReal;
    $sub         = '';
}

$isHome = ($sub === '');

// ============================================================
//  Obsluga akcji (POST): tworzenie katalogu i usuwanie pliku/katalogu
// ============================================================
$komunikat = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';

    // Tworzenie podkatalogu - tylko w katalogu macierzystym
    if ($action === 'mkdir' && $isHome) {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '' || $name === '.' || $name === '..'
            || preg_match('/[\/\\\\]/', $name) || strpos($name, "\0") !== false) {
            $komunikat = 'Nieprawidłowa nazwa katalogu.';
        } else {
            $cel = $home . DIRECTORY_SEPARATOR . $name;
            if (file_exists($cel)) {
                $komunikat = 'Element o takiej nazwie już istnieje.';
            } elseif (@mkdir($cel, 0777, true)) {
                $komunikat = 'Utworzono katalog "' . htmlspecialchars($name) . '".';
            } else {
                $komunikat = 'Nie udało się utworzyć katalogu.';
            }
        }
    }
    // Usuwanie pliku lub podkatalogu
    elseif ($action === 'delete') {
        $name = (string)($_POST['name'] ?? '');
        if ($name === '' || $name === '.' || $name === '..'
            || preg_match('/[\/\\\\]/', $name) || strpos($name, "\0") !== false) {
            $komunikat = 'Nieprawidłowa nazwa elementu.';
        } else {
            $cel     = $current . DIRECTORY_SEPARATOR . $name;
            $celReal = realpath($cel);
            if ($celReal === false || $homeReal === false || strpos($celReal, $homeReal) !== 0) {
                $komunikat = 'Nieprawidłowa ścieżka.';
            } elseif (is_dir($celReal)) {
                // Usuwanie podkatalogu: najpierw usuwamy znajdujace sie w nim pliki
                $zawartosc = @scandir($celReal) ?: [];
                foreach ($zawartosc as $element) {
                    if ($element === '.' || $element === '..') {
                        continue;
                    }
                    $sciezka = $celReal . DIRECTORY_SEPARATOR . $element;
                    if (is_dir($sciezka)) {
                        @rmdir($sciezka);
                    } else {
                        @unlink($sciezka);
                    }
                }
                $komunikat = @rmdir($celReal) ? 'Usunięto katalog "' . htmlspecialchars($name) . '".' : 'Nie udało się usunąć katalogu.';
            } else {
                $komunikat = @unlink($celReal) ? 'Usunięto plik "' . htmlspecialchars($name) . '".' : 'Nie udało się usunąć pliku.';
            }
        }
    }
}

// ============================================================
//  Odczyt zawartosci biezacego katalogu
// ============================================================
$foldery = [];
$pliki   = [];
$elementy = @scandir($current) ?: [];
foreach ($elementy as $element) {
    if ($element === '.' || $element === '..') {
        continue;
    }
    if (is_dir($current . DIRECTORY_SEPARATOR . $element)) {
        $foldery[] = $element;
    } else {
        $pliki[] = $element;
    }
}
sort($foldery, SORT_STRING | SORT_FLAG_CASE);
sort($pliki, SORT_STRING | SORT_FLAG_CASE);

// Okreslenie katalogu nadrzednego (ikona "level up")
$nadrzedny = '';
if ($sub !== '') {
    $poz = strrpos($sub, '/');
    $nadrzedny = ($poz === false) ? '' : substr($sub, 0, $poz);
}

// Ikona pliku w zaleznosci od rozszerzenia
function ikona_pliku(string $nazwa): string
{
    $ext = strtolower(pathinfo($nazwa, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp'], true)) {
        return 'bi-file-image';
    }
    if (in_array($ext, ['mp3', 'wav', 'ogg', 'm4a'], true)) {
        return 'bi-file-music';
    }
    if (in_array($ext, ['mp4', 'webm', 'mov', 'avi'], true)) {
        return 'bi-file-play';
    }
    if ($ext === 'pdf') {
        return 'bi-file-pdf';
    }
    if (in_array($ext, ['doc', 'docx'], true)) {
        return 'bi-file-word';
    }
    if (in_array($ext, ['xls', 'xlsx'], true)) {
        return 'bi-file-excel';
    }
    if (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'], true)) {
        return 'bi-file-zip';
    }
    if (in_array($ext, ['txt', 'csv', 'log'], true)) {
        return 'bi-file-text';
    }
    return 'bi-file-earmark';
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
	<title>Mój dysk sieciowy</title>
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
				<h2><i class="bi bi-cloud-fill"></i> Mój dysk sieciowy</h2>
				<p>Zalogowano jako: <b><?php echo htmlspecialchars($username); ?></b></p>

				<?php if ($komunikat !== ''): ?>
					<div class="alert alert-info" role="alert"><?php echo $komunikat; ?></div>
				<?php endif; ?>

				<?php if (isset($_GET['ok'])): ?>
					<div class="alert alert-success" role="alert">Plik został przesłany na dysk.</div>
				<?php endif; ?>

				<?php if (isset($_GET['blad'])): ?>
					<div class="alert alert-danger" role="alert">Nie udało się przesłać pliku.</div>
				<?php endif; ?>

				<!-- Pasek narzedzi dysku -->
				<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
					<?php if (!$isHome): ?>
						<a class="btn btn-outline-primary btn-sm" href="mycloud.php?sub=<?php echo urlencode($nadrzedny); ?>" title="Katalog nadrzędny">
							<i class="bi bi-arrow-up"></i> W górę
						</a>
					<?php endif; ?>
					<a class="btn btn-outline-primary btn-sm" href="mycloud.php" title="Katalog macierzysty">
						<i class="bi bi-house-door"></i> Katalog macierzysty
					</a>
					<a class="btn btn-primary btn-sm" href="select.php?sub=<?php echo urlencode($sub); ?>" title="Prześlij plik">
						<i class="bi bi-upload"></i> Prześlij plik
					</a>
				</div>

				<!-- Biezaca lokalizacja -->
				<p class="text-muted mb-2">
					Lokalizacja: <i class="bi bi-folder2-open"></i>
					<?php if ($isHome): ?>
						/ (katalog macierzysty)
					<?php else: ?>
						/<?php echo htmlspecialchars($sub); ?>
					<?php endif; ?>
				</p>

				<!-- Formularz tworzenia podkatalogu - tylko w katalogu macierzystym -->
				<?php if ($isHome): ?>
					<form method="post" action="mycloud.php" class="row g-2 align-items-center mb-3">
						<div class="col-auto">
							<label for="nazwaKatalogu" class="visually-hidden">Nazwa nowego katalogu</label>
							<input type="text" class="form-control form-control-sm" id="nazwaKatalogu" name="name" placeholder="Nazwa nowego katalogu" required>
						</div>
						<div class="col-auto">
							<input type="hidden" name="action" value="mkdir">
							<button type="submit" class="btn btn-success btn-sm"><i class="bi bi-folder-plus"></i> Utwórz katalog</button>
						</div>
					</form>
				<?php endif; ?>

				<!-- Lista plikow i podkatalogow -->
				<div class="table-responsive">
					<table class="table table-striped table-hover align-middle">
						<thead>
							<tr>
								<th style="width:40px;"></th>
								<th>Nazwa</th>
								<th style="width:60px;"></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($foldery as $f): ?>
								<?php $sciezka = ($sub !== '' ? $sub . '/' : '') . $f; ?>
								<tr>
									<td class="text-center"><i class="bi bi-folder-fill text-warning" style="font-size:1.2rem;"></i></td>
									<td><a href="mycloud.php?sub=<?php echo urlencode($sciezka); ?>"><?php echo htmlspecialchars($f); ?></a></td>
									<td class="text-center">
										<form method="post" action="mycloud.php?sub=<?php echo urlencode($sub); ?>" style="display:inline;" onsubmit="return confirm('Usunąć ten katalog wraz z całą zawartością?');">
											<input type="hidden" name="action" value="delete">
											<input type="hidden" name="name" value="<?php echo htmlspecialchars($f); ?>">
											<button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń"><i class="bi bi-trash"></i></button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>

							<?php foreach ($pliki as $p): ?>
								<?php $sciezka = ($sub !== '' ? $sub . '/' : '') . $p; ?>
								<tr>
									<td class="text-center"><i class="bi <?php echo ikona_pliku($p); ?> text-secondary" style="font-size:1.2rem;"></i></td>
									<td><a href="download.php?file=<?php echo urlencode($sciezka); ?>"><?php echo htmlspecialchars($p); ?></a></td>
									<td class="text-center">
										<form method="post" action="mycloud.php?sub=<?php echo urlencode($sub); ?>" style="display:inline;" onsubmit="return confirm('Usunąć ten plik?');">
											<input type="hidden" name="action" value="delete">
											<input type="hidden" name="name" value="<?php echo htmlspecialchars($p); ?>">
											<button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń"><i class="bi bi-trash"></i></button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>

							<?php if (count($foldery) === 0 && count($pliki) === 0): ?>
								<tr>
									<td colspan="3" class="text-center text-muted">Katalog jest pusty.</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</section>
	</main>
	<?php require_once 'footer.php'; ?>
</body>
</html>
