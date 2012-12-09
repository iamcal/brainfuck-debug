<?
	$page = file_get_contents('index.html');

	$nav = capture('/var/www/cal/iamcal.com/templates/universal_nav.txt');
	$track = capture('/var/www/cal/iamcal.com/templates/universal_tracker.txt');

	$page = str_replace('<!-- NAV -->', $nav, $page);
	$page = str_replace('<!-- TRACK -->', $track, $page);

	echo $page;


	function capture($path){
		ob_start();
		include($path);
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}
