<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */
session_start();

// Brak zalogowania - przekierowanie do formularza logowania
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit();
}

$nazwa  = (string)($_SESSION['username'] ?? '');
$avatar = (string)($_SESSION['avatar'] ?? '');
if ($avatar === '') { $avatar = 'media/avatars/default.svg'; }
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">  
	<meta name="description" content="Twój Opis">
	<meta name="author" content="Twoje dane">
	<meta name="keywords" content="Twoje słowa kluczowe">
	<title>Strona powitalna</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap5.min.css">
	<style type="text/css" class="init"></style>
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
				<h2>Strona powitalna</h2>
				<p>Jesteś zalogowany jako: <b><?php echo htmlspecialchars($nazwa); ?></b></p>
				<p>
					<img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="rounded-circle" width="96" height="96" style="object-fit:cover;">
				</p>
				<p>Wybierz jedno z poleceń w menu, aby wyświetlić jego treść.</p>
			</div>	
		</section>
	</main>	
	<?php require_once 'footer.php'; ?>	
</body>
</html>
