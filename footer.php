<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */ ?>
	<footer class="page-footer mt-auto bg-light border-primary">
		<div class="container-fluid d-flex align-items-center justify-content-between">
			<span>Zawartość Twojego footera</span>
			<span id="dzwiekInfo" title="Dźwiękowe ostrzeganie o awarii usługi"></span>
		</div>
	</footer>

	<script type="text/javascript">
		// Aktualizacja ikony SVG w footerze - informacja o wlaczonym/wylaczonym
		// dzwiekowym ostrzeganiu o awarii uslug (ustawienie przechowywane w localStorage).
		function aktualizujIkoneDzwieku()
		{
			var el = document.getElementById('dzwiekInfo');
			if (!el) { return; }

			var wl = localStorage.getItem('dzwiek_wl');
			if (wl === null) { wl = '1'; }
			var wlaczony = (wl === '1');

			if (wlaczony)
			{
				el.innerHTML =
					'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#198754" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
					+ '<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>'
					+ '<path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>'
					+ '<path d="M19.07 4.93a10 10 0 0 1 0 14.14"></path>'
					+ '</svg> Dźwięk włączony';
			}
			else
			{
				el.innerHTML =
					'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
					+ '<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>'
					+ '<line x1="23" y1="9" x2="17" y2="15"></line>'
					+ '<line x1="17" y1="9" x2="23" y2="15"></line>'
					+ '</svg> Dźwięk wyłączony';
			}
		}

		if (document.readyState === 'loading')
		{
			document.addEventListener('DOMContentLoaded', aktualizujIkoneDzwieku);
		}
		else
		{
			aktualizujIkoneDzwieku();
		}
	</script>
