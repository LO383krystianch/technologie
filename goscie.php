<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */
session_start();

// Weryfikacja sesji - tresc dostepna tylko dla zalogowanych uzytkownikow
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

// ============================================================
//  Funkcje pomocnicze (geolokalizacja IP + identyfikacja przegladarki)
// ============================================================

function pobierz_url(string $url): string
{
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $dane = curl_exec($ch);
        curl_close($ch);
        if (is_string($dane) && $dane !== '') {
            return $dane;
        }
    }
    $ctx = stream_context_create(['http' => ['timeout' => 5], 'https' => ['timeout' => 5]]);
    $dane = @file_get_contents($url, false, $ctx);
    return is_string($dane) ? $dane : '';
}

// Geolokalizacja po IP - zwraca znormalizowany obiekt {country, region, city, loc} lub null
function ip_details(string $ip): ?object
{
    // 1) ipinfo.io/geo - zgodnie z instrukcją zadania
    $json = pobierz_url('https://ipinfo.io/' . $ip . '/geo');
    if ($json !== '') {
        $dane = json_decode($json);
        if (is_object($dane) && isset($dane->loc)) {
            return (object)[
                'country' => (string)($dane->country ?? ''),
                'region'  => (string)($dane->region ?? ''),
                'city'    => (string)($dane->city ?? ''),
                'loc'     => (string)($dane->loc ?? ''),
            ];
        }
    }

    // 2) ipinfo.io/json - zapasowy format odpowiedzi
    $json = pobierz_url('https://ipinfo.io/' . $ip . '/json');
    if ($json !== '') {
        $dane = json_decode($json);
        if (is_object($dane) && isset($dane->loc)) {
            return (object)[
                'country' => (string)($dane->country ?? ''),
                'region'  => (string)($dane->region ?? ''),
                'city'    => (string)($dane->city ?? ''),
                'loc'     => (string)($dane->loc ?? ''),
            ];
        }
    }

    // 3) ip-api.com - awaryjny, bezkluczowy serwis geolokalizacji
    $json = pobierz_url('http://ip-api.com/json/' . $ip . '?fields=status,country,regionName,city,lat,lon');
    if ($json !== '') {
        $dane = json_decode($json);
        if (is_object($dane) && ($dane->status ?? '') === 'success') {
            $loc = (isset($dane->lat) && isset($dane->lon)) ? ((string)$dane->lat . ',' . (string)$dane->lon) : '';
            return (object)[
                'country' => (string)($dane->country ?? ''),
                'region'  => (string)($dane->regionName ?? ''),
                'city'    => (string)($dane->city ?? ''),
                'loc'     => $loc,
            ];
        }
    }

    return null;
}

// Identyfikacja przegladarki gościa portalu
function pobierz_przegladarke(): string
{
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');

    // Probujemy get_browser (wymaga skonfigurowanego browscap w php.ini)
    if (function_exists('get_browser')) {
        $b = @get_browser(null, true);
        if (is_array($b) && !empty($b['browser']) && $b['browser'] !== 'Default Browser') {
            return trim((string)($b['browser'] ?? '') . ' ' . (string)($b['version'] ?? '') . ' (' . (string)($b['platform'] ?? '') . ')');
        }
    }

    // Fallback - parsowanie nagłówka User-Agent
    $przegladarka = 'Nieznana';
    $wersja = '';
    if (preg_match('/Edg(?:e|A|iOS)?\/([\d.]+)/i', $ua, $m)) {
        $przegladarka = 'Microsoft Edge'; $wersja = $m[1];
    } elseif (preg_match('/OPR\/([\d.]+)/i', $ua, $m)) {
        $przegladarka = 'Opera'; $wersja = $m[1];
    } elseif (preg_match('/Firefox\/([\d.]+)/i', $ua, $m)) {
        $przegladarka = 'Mozilla Firefox'; $wersja = $m[1];
    } elseif (preg_match('/Chrome\/([\d.]+)/i', $ua, $m)) {
        $przegladarka = 'Google Chrome'; $wersja = $m[1];
    } elseif (preg_match('/Version\/([\d.]+).*Safari/i', $ua, $m)) {
        $przegladarka = 'Safari'; $wersja = $m[1];
    } elseif (preg_match('/MSIE ([\d.]+)/i', $ua, $m) || preg_match('/Trident\/.*rv:([\d.]+)/i', $ua, $m)) {
        $przegladarka = 'Internet Explorer'; $wersja = $m[1];
    }

    $platforma = '';
    if (preg_match('/Windows NT 10\.0/i', $ua)) { $platforma = 'Windows 10/11'; }
    elseif (preg_match('/Windows NT 6\.3/i', $ua)) { $platforma = 'Windows 8.1'; }
    elseif (preg_match('/Windows NT 6\.1/i', $ua)) { $platforma = 'Windows 7'; }
    elseif (preg_match('/Windows/i', $ua)) { $platforma = 'Windows'; }
    elseif (preg_match('/iPhone/i', $ua)) { $platforma = 'iOS (iPhone)'; }
    elseif (preg_match('/iPad/i', $ua)) { $platforma = 'iOS (iPad)'; }
    elseif (preg_match('/Android/i', $ua)) { $platforma = 'Android'; }
    elseif (preg_match('/Mac OS X|Macintosh/i', $ua)) { $platforma = 'macOS'; }
    elseif (preg_match('/Linux/i', $ua)) { $platforma = 'Linux'; }

    return trim($przegladarka . ($wersja !== '' ? ' ' . $wersja : '') . ($platforma !== '' ? ' (' . $platforma . ')' : ''));
}

// ============================================================
//  Obsluga AJAX - zapis danych o urzadzeniu gościa (JS -> PHP)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $link = db_connect();
    db_ensure_goscieportalu_table($link);

    $id      = (int)$_POST['update_id'];
    $screen  = (string)($_POST['screen_res'] ?? '');
    $window  = (string)($_POST['window_res'] ?? '');
    $colors  = (string)($_POST['color_depth'] ?? '');
    $cookies = (string)($_POST['cookies'] ?? '');
    $java    = (string)($_POST['java'] ?? '');
    $jezyk   = (string)($_POST['jezyk'] ?? '');

    $stmt = mysqli_prepare($link, "UPDATE goscieportalu SET screen_res=?, window_res=?, color_depth=?, cookies=?, java=?, jezyk=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssssssi', $screen, $window, $colors, $cookies, $java, $jezyk, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($link);

    echo 'ok';
    exit();
}

// ============================================================
//  Rejestracja wizyty gościa oraz pobranie listy gości
// ============================================================
$link = db_connect();
db_ensure_goscieportalu_table($link);

$ip       = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$datetime = date('Y-m-d H:i:s');
$browser  = pobierz_przegladarke();

$stmt = mysqli_prepare($link, "INSERT INTO goscieportalu (ipaddress, `datetime`, browser) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'sss', $ip, $datetime, $browser);
mysqli_stmt_execute($stmt);
$nowy_id = (int)mysqli_insert_id($link);
mysqli_stmt_close($stmt);

$wynik = mysqli_query($link, "SELECT id, ipaddress, `datetime`, browser, screen_res, window_res, color_depth, cookies, java, jezyk FROM goscieportalu ORDER BY id DESC");

$wiersze = [];
while ($r = mysqli_fetch_assoc($wynik)) {
    $wiersze[] = $r;
}
mysqli_close($link);

// Cache geolokalizacji - dla danego IP pobieramy dane tylko raz na strone
$geo_cache = [];
function pobierz_dane_ip(string $ip, array &$cache): ?object
{
    if (array_key_exists($ip, $cache)) {
        return $cache[$ip];
    }
    $dane = ip_details($ip);
    $cache[$ip] = $dane;
    return $dane;
}

function pokaz($wartosc): string
{
    $s = ($wartosc === null) ? '' : (string)$wartosc;
    return ($s === '') ? '-' : htmlspecialchars($s);
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
	<title>Rejestracja wizyt gości portalu</title>
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
				<h2>Rejestracja wizyt gości portalu (geolokalizacja)</h2>
				<p>Twoje IP: <b><?php echo htmlspecialchars($ip); ?></b> &mdash; data wejścia: <b><?php echo htmlspecialchars($datetime); ?></b></p>

				<div class="table-responsive">
					<table id="tabelaGosci" class="table table-striped table-bordered table-hover w-100">
						<thead>
							<tr>
								<th>IP</th>
								<th>Data i godzina</th>
								<th>Kraj</th>
								<th>Region</th>
								<th>Miasto</th>
								<th>Współrzędne</th>
								<th>Mapa</th>
								<th>Przeglądarka</th>
								<th>Ekran</th>
								<th>Okno</th>
								<th>Kolory</th>
								<th>Cookies</th>
								<th>Java</th>
								<th>Język</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($wiersze as $w): ?>
								<?php
								$geo = pobierz_dane_ip((string)$w['ipaddress'], $geo_cache);
								$country = $geo->country ?? '';
								$region  = $geo->region ?? '';
								$city    = $geo->city ?? '';
								$loc     = $geo->loc ?? '';
								?>
								<tr id="wiersz-<?php echo (int)$w['id']; ?>">
									<td><?php echo pokaz($w['ipaddress']); ?></td>
									<td><?php echo pokaz($w['datetime']); ?></td>
									<td><?php echo pokaz($country); ?></td>
									<td><?php echo pokaz($region); ?></td>
									<td><?php echo pokaz($city); ?></td>
									<td><?php echo pokaz($loc); ?></td>
									<td>
										<?php if ($loc !== ''): ?>
											<a href="https://www.google.pl/maps/place/<?php echo urlencode($loc); ?>" target="_blank" rel="noopener">LINK</a>
										<?php else: ?>
											-
										<?php endif; ?>
									</td>
									<td><?php echo pokaz($w['browser']); ?></td>
									<td class="c-ekran"><?php echo pokaz($w['screen_res']); ?></td>
									<td class="c-okno"><?php echo pokaz($w['window_res']); ?></td>
									<td class="c-kolory"><?php echo pokaz($w['color_depth']); ?></td>
									<td class="c-cookies"><?php echo pokaz($w['cookies']); ?></td>
									<td class="c-java"><?php echo pokaz($w['java']); ?></td>
									<td class="c-jezyk"><?php echo pokaz($w['jezyk']); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>	
		</section>
	</main>	
	<?php require_once 'footer.php'; ?>

	<script type="text/javascript">
		$(document).ready(function()
		{
			// Tabela Bootstrap/DataTables - sortowanie, filtrowanie, paginacja
			$('#tabelaGosci').DataTable({
				"order": [[1, "desc"]],
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

			// Zebranie danych o urzadzeniu gościa (po stronie JS) i zapis do rekordu biezacej wizyty
			var nowyId = <?php echo $nowy_id; ?>;
			if (nowyId > 0) {
				var dane = {
					screen_res: screen.width + 'x' + screen.height,
					window_res: window.innerWidth + 'x' + window.innerHeight,
					color_depth: screen.colorDepth + '-bit',
					cookies: navigator.cookieEnabled ? 'Tak' : 'Nie',
					java: (typeof navigator.javaEnabled === 'function' && navigator.javaEnabled()) ? 'Tak' : 'Nie',
					jezyk: navigator.language || navigator.userLanguage || ''
				};
				$.ajax({
					type: 'POST',
					url: 'goscie.php',
					data: $.extend({ update_id: nowyId }, dane),
					dataType: 'text',
					success: function()
					{
						var wiersz = $('#wiersz-' + nowyId);
						wiersz.find('.c-ekran').text(dane.screen_res);
						wiersz.find('.c-okno').text(dane.window_res);
						wiersz.find('.c-kolory').text(dane.color_depth);
						wiersz.find('.c-cookies').text(dane.cookies);
						wiersz.find('.c-java').text(dane.java);
						wiersz.find('.c-jezyk').text(dane.jezyk);
					}
				});
			}
		});
	</script>
</body>
</html>