<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */
session_start();
$zalogowany = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$nazwa = $zalogowany ? (string)($_SESSION['username'] ?? '') : '';
$avatar = $zalogowany ? (string)($_SESSION['avatar'] ?? '') : '';
if ($avatar === '') { $avatar = 'media/avatars/default.svg'; }
$is_admin = $zalogowany && $nazwa === 'admin';
?>
<header>
<div class="container">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top mt-0 mb-0 ms-0 me-0 pt-0 pb-0 ps-0 pe-0">
	<div class="container-fluid">
	
		<div class="collapse navbar-collapse" id="main_nav">
			<ul class="navbar-nav me-auto mb-2 mb-lg-0">
				<?php if ($zalogowany): ?>
					<li class="nav-item">
						<a class="nav-link" href="mycloud.php"><i class="bi bi-cloud-fill"></i> Mój dysk</a>
					</li>
				<?php endif; ?>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Polecenia 1.x</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="polecenie1_1.php"> <img src="media/menu_icons/info.svg" height="18"> Polecenie 1.1 </a></li>
						<li><a class="dropdown-item" href="polecenie1_2.php"> <img src="media/menu_icons/help.svg" height="18"> Polecenie 1.2 </a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Polecenia 2.x</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="polecenie2_1.php"> Polecenie 2.1 </a></li>
						<li><a class="dropdown-item" href="polecenie2_2.php"> Polecenie 2.2 </a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Polecenia 3.x</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="polecenie3_1.php"> Polecenie 3.1 </a></li>
						<li><a class="dropdown-item" href="polecenie3_2.php"> Polecenie 3.2 </a></li>
						<li><a class="dropdown-item" href="polecenie3_3.php"> Polecenie 3.3 </a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Geolokalizacja</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="goscie.php"> <img src="media/menu_icons/info.svg" height="18"> Rejestracja wizyt gości portalu </a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Monitorowanie</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="index1.php"> <img src="media/menu_icons/info.svg" height="18"> Monitorowanie hostów </a></li>
						<li><a class="dropdown-item" href="dodaj1.php"> <img src="media/menu_icons/help.svg" height="18"> Dodaj hosta </a></li>
						<li><a class="dropdown-item" href="kasuj1.php"> <img src="media/menu_icons/help.svg" height="18"> Usuń hosta </a></li>
						<?php if ($is_admin): ?>
							<li><a class="dropdown-item" href="staff.php"> <img src="media/menu_icons/help.svg" height="18"> Staff (przypisz pracownika) </a></li>
						<?php endif; ?>
					</ul>
				</li>
			</ul>
			
			<ul class="navbar-nav ms-auto mb-2 mb-lg-0">
				<?php if ($zalogowany): ?>
					<li class="nav-item d-flex align-items-center">
						<span class="navbar-text text-white me-3">
							<img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="rounded-circle avatar-navbar">
							<?php echo htmlspecialchars($nazwa); ?>
						</span>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Wyloguj</a>
					</li>
				<?php else: ?>
					<li class="nav-item">
						<a class="nav-link" href="index.php"><i class="bi bi-box-arrow-in-right"></i> Zaloguj</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="rejestracja.php"><i class="bi bi-person-plus"></i> Rejestracja</a>
					</li>
				<?php endif; ?>
			</ul>
		</div> 
		
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main_nav"  aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		
	</div> 
</nav>		
	
</div>
</header>
