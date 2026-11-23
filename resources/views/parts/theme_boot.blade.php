<script>
(function () {
	try {
		var theme = localStorage.getItem('odds_theme');
		if (theme !== 'dark' && theme !== 'light') {
			theme = 'light';
		}
		document.documentElement.setAttribute('data-theme', theme);
	} catch (e) {
		document.documentElement.setAttribute('data-theme', 'light');
	}
})();
</script>
