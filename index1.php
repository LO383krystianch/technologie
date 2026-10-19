<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */
session_start();

// Weryfikacja sesji - tresc dostepna tylko dla zalogowanych uzytkownikow
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

// ============================================================
//  Funkcje pomocnicze
// ============================================================

// Formatuje liczbe sekund na postac GG:MM:SS
function format_czasu(int $sekundy): string
{
    $sekundy = max(0, $sekundy);
    $g = intdiv($sekundy, 3600);
    $m = intdiv($sekundy % 3600, 60);
    $s = $sekundy % 60;
    return sprintf('%02d:%02d:%02d', $g, $m, $s);
}

// Zwraca ikone SVG reprezentujaca stan monitorowanej uslugi
function svg_uslugi(string $stan, int $czas_awarii_s): string
{
    if ($stan === 'ok') {
        $kolor = '#198754';   // zielony - poprawne dzialanie
        $grubosc = 2;
        $klasa = '';
    } elseif ($czas_awarii_s > 20) {
        $kolor = '#dc3545';   // czerwony, grubsza linia, pulsowanie
        $grubosc = 3.5;
        $klasa = ' class="pulsuj"';
    } else {
        $kolor = '#dc3545';   // czerwony - awaria krotkotrwala
        $grubosc = 2;
        $klasa = '';
    }

    return '<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="' . $kolor . '" stroke-width="' . $grubosc . '" stroke-linecap="round" stroke-linejoin="round"' . $klasa . '>'
        . '<rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>'
        . '<rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>'
        . '<line x1="6" y1="6" x2="6.01" y2="6"></line>'
        . '<line x1="6" y1="18" x2="6.01" y2="18"></line>'
        . '</svg>';
}

// ============================================================
//  Polaczenie z baza i przygotowanie struktury
// ============================================================
$link = db_connect();
db_ensure_users_table($link);
db_ensure_domeny_table($link);
db_ensure_admin_user($link);

$user_id  = (int)($_SESSION['user_id'] ?? 0);
$username = (string)($_SESSION['username'] ?? '');
$is_admin = ($username === 'admin');

// ============================================================
//  Pobranie monitorowanych hostow
//  Administrator widzi wszystkie, zwykly uzytkownik tylko swoje
// ============================================================
if ($is_admin) {
    $stmt = mysqli_prepare($link, "SELECT id, host, port, errors, failure, total_errors FROM domeny ORDER BY id");
} else {
    $stmt = mysqli_prepare($link, "SELECT id, host, port, errors, failure, total_errors FROM domeny WHERE user_id = ? ORDER BY id");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
}
mysqli_stmt_execute($stmt);
$rezultat = mysqli_stmt_get_result($stmt);

$wiersze = [];
while ($r = mysqli_fetch_assoc($rezultat)) {
    $wiersze[] = $r;
}
mysqli_stmt_close($stmt);

// ============================================================
//  Badanie odpowiedzi hostow na zadanych portach (fsockopen)
//  oraz aktualizacja kolumn errors / failure / total_errors
// ============================================================
$czas_refresh = 10;   // sekundy - tyle trwa cykl monitorowania
$timeout      = 5;    // sekundy - maksymalny czas oczekiwania na polaczenie

foreach ($wiersze as $i => $w) {
    $id     = (int)$w['id'];
    $host   = (string)$w['host'];
    $port   = (int)$w['port'];
    $errors = (int)($w['errors'] ?? 0);
    $total  = (int)($w['total_errors'] ?? 0);
    $failure = ($w['failure'] !== null) ? (string)$w['failure'] : null;

    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);

    if ($fp) {
        fclose($fp);
        // Host odpowiada - zerujemy licznik kolejnych bledow i czas awarii
        $wiersze[$i]['status']       = 'ok';
        $wiersze[$i]['errstr']       = '';
        $wiersze[$i]['errors']       = 0;
        $wiersze[$i]['total_errors'] = $total;
        $wiersze[$i]['failure']      = null;

        $upd = mysqli_prepare($link, "UPDATE domeny SET errors = 0, failure = NULL WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'i', $id);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    } else {
        // Host nie odpowiada - zwiekszamy liczniki bledow
        $wiersze[$i]['status']       = 'awaria';
        $wiersze[$i]['errstr']       = trim($errstr . ' (' . $errno . ')');
        $wiersze[$i]['errors']       = $errors + 1;
        $wiersze[$i]['total_errors'] = $total + 1;
        $wiersze[$i]['failure']      = ($failure !== null) ? $failure : date('Y-m-d H:i:s');

        $upd = mysqli_prepare($link, "UPDATE domeny SET errors = errors + 1, total_errors = total_errors + 1, failure = COALESCE(failure, NOW()) WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'i', $id);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    }
}
mysqli_close($link);

// ============================================================
//  Okreslenie, czy jakas awaria trwa powyzej 40 sekund (dzwiek)
// ============================================================
$alarm = false;
foreach ($wiersze as $w) {
    if (($w['status'] ?? '') === 'awaria' && ((int)$w['errors'] * $czas_refresh) > 40) {
        $alarm = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<meta http-equiv="refresh" content="<?php echo $czas_refresh; ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Twój Opis">
	<meta name="author" content="Twoje dane">
	<meta name="keywords" content="Twoje słowa kluczowe">
	<title>Monitorowanie usług w sieciach IP</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap5.min.css">
	<link rel="stylesheet" type="text/css" href="twoj_css.css">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
	<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap5.min.js"></script>
	<script type="text/javascript" src="twoj_js.js"></script>
</head>

<body onload="myLoadHeader()">
	<div id='myHeader'> </div>
	<main>
		<section class="sekcja1">
			<div class="container-fluid">
				<h2>Monitorowanie usług w sieciach IP</h2>
				<p>
					Zalogowano jako: <b><?php echo htmlspecialchars($username); ?></b>
					<?php if ($is_admin): ?> (administrator &mdash; widoczne są wszystkie hosty)<?php endif; ?>
				</p>

				<p>
					<a class="btn btn-primary btn-sm" href="dodaj1.php"><i class="bi bi-plus-circle"></i> Dodaj hosta</a>
					<a class="btn btn-warning btn-sm" href="kasuj1.php"><i class="bi bi-dash-circle"></i> Usuń hosta</a>
					<?php if ($is_admin): ?>
						<a class="btn btn-secondary btn-sm" href="staff.php"><i class="bi bi-people"></i> Przypisz pracownika (staff)</a>
					<?php endif; ?>
				</p>

				<p>
					<label for="typDzwieku" class="form-label me-1">Rodzaj dźwięku ostrzegawczego:</label>
					<select id="typDzwieku" class="form-select form-select-sm d-inline-block" style="width:auto;">
						<option value="1">Dźwięk 1</option>
						<option value="2">Dźwięk 2</option>
						<option value="3">Dźwięk 3</option>
					</select>
					<span class="ms-2">
						<input class="form-check-input" type="checkbox" id="dzwiekWl" checked>
						<label class="form-check-label" for="dzwiekWl">Włącz dźwiękowe ostrzeganie o awarii</label>
					</span>
				</p>

				<div class="table-responsive">
					<table id="tabelaUslug" class="table table-striped table-bordered table-hover w-100">
						<thead>
							<tr>
								<th>id</th>
								<th>Host</th>
								<th>Port</th>
								<th>Stan</th>
								<th>Kolejne błędy</th>
								<th>Łączna liczba błędów</th>
								<th>Łączny czas awarii</th>
								<th>Data/czas pierwszej awarii</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($wiersze as $w): ?>
								<?php
								$status    = (string)($w['status'] ?? 'awaria');
								$errors    = (int)($w['errors'] ?? 0);
								$total     = (int)($w['total_errors'] ?? 0);
								$failure   = $w['failure'] ?? null;
								$czas_awarii = $errors * $czas_refresh;
								?>
								<tr>
									<td><?php echo (int)$w['id']; ?></td>
									<td><?php echo htmlspecialchars((string)$w['host']); ?></td>
									<td><?php echo (int)$w['port']; ?></td>
									<td>
										<?php echo svg_uslugi($status, $czas_awarii); ?>
										<?php if ($status === 'ok'): ?>
											<span class="text-success">OK</span>
										<?php else: ?>
											<span class="text-danger" title="<?php echo htmlspecialchars((string)$w['errstr']); ?>">Awaria</span>
										<?php endif; ?>
									</td>
									<td><?php echo $errors; ?></td>
									<td><?php echo $total; ?></td>
									<td><?php echo format_czasu($total * $czas_refresh); ?></td>
									<td><?php echo ($failure !== null) ? htmlspecialchars($failure) : '-'; ?></td>
								</tr>
							<?php endforeach; ?>
							<?php if (count($wiersze) === 0): ?>
								<tr><td colspan="8" class="text-center text-muted">Brak monitorowanych hostów.</td></tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</section>
	</main>
	<?php require_once 'footer.php'; ?>

	<script type="text/javascript">
		// ===== Konfiguracja dzwieku ostrzegawczego (localStorage) =====
		var alarmAktywny = <?php echo $alarm ? 'true' : 'false'; ?>;

		function odczytajUstawienia() {
			var typ = localStorage.getItem('dzwiek_typ') || '1';
			var wl  = localStorage.getItem('dzwiek_wl');
			if (wl === null) { wl = '1'; }
			return { typ: typ, wl: (wl === '1') };
		}

		function zapiszUstawienia() {
			localStorage.setItem('dzwiek_typ', document.getElementById('typDzwieku').value);
			localStorage.setItem('dzwiek_wl', document.getElementById('dzwiekWl').checked ? '1' : '0');
		}

		// Odtworzenie alarmu (Web Audio API - 3 rodzaje dzwiekow)
		function odtworzAlarm(typ) {
			try {
				var ctx = new (window.AudioContext || window.webkitAudioContext)();
				var wzorce = {
					'1': [[880, 0.15, 0], [880, 0.15, 0.2], [880, 0.15, 0.4]],
					'2': [[440, 0.4, 0], [440, 0.4, 0.6]],
					'3': [[660, 0.2, 0], [440, 0.2, 0.25], [660, 0.2, 0.5]]
				};
				var sekwencja = wzorce[typ] || wzorce['1'];
				var t0 = ctx.currentTime;
				sekwencja.forEach(function(k) {
					var os = ctx.createOscillator();
					var g = ctx.createGain();
					os.frequency.value = k[0];
					os.type = 'square';
					g.gain.setValueAtTime(0.001, t0 + k[2]);
					g.gain.exponentialRampToValueAtTime(0.2, t0 + k[2] + 0.01);
					g.gain.exponentialRampToValueAtTime(0.001, t0 + k[2] + k[1]);
					os.connect(g);
					g.connect(ctx.destination);
					os.start(t0 + k[2]);
					os.stop(t0 + k[2] + k[1] + 0.05);
				});
			} catch (e) {}
		}

		$(document).ready(function() {
			// Tabela Bootstrap/DataTables
			$('#tabelaUslug').DataTable({
				"order": [[0, "asc"]],
				"pageLength": 10,
				"lengthMenu": [5, 10, 25, 50, 100],
				"language": {
					"search": "Filtruj:",
					"lengthMenu": "Pokaż _MENU_ rekordów",
					"info": "Rekordy _START_ - _END_ z _TOTAL_",
					"infoEmpty": "Brak rekordów",
					"infoFiltered": "(odfiltrowano z _MAX_)",
					"zeroRecords": "Brak pasujących rekordów",
					"emptyTable": "Brak danych w tabeli",
					"paginate": {
						"first": "Pierwsza",
						"last": "Ostatnia",
						"next": "Następna",
						"previous": "Poprzednia"
					}
				}
			});

			// Przywrocenie zapamietanych ustawien dzwieku
			var u = odczytajUstawienia();
			document.getElementById('typDzwieku').value = u.typ;
			document.getElementById('dzwiekWl').checked = u.wl;

			document.getElementById('typDzwieku').addEventListener('change', zapiszUstawienia);
			document.getElementById('dzwiekWl').addEventListener('change', function() {
				zapiszUstawienia();
				aktualizujIkoneDzwieku();
			});

			// Dzwiek ostrzegawczy, gdy awaria trwa powyzej 40 s i jest wlaczony
			if (alarmAktywny && u.wl) {
				odtworzAlarm(u.typ);
			}

			aktualizujIkoneDzwieku();
		});
	</script>
</body>
</html>
