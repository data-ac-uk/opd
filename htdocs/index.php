<?php
date_default_timezone_set( "Europe/London" );
$f3=require('../f3/lib/base.php');

$f3->set('DEBUG',1);
//if ((float)PCRE_VERSION<7.9)
//	trigger_error('PCRE version is out of date');

$f3->config('config.ini');

$f3->route('GET /',
	function($f3) { basicPage( $f3, "Organisation Profile Documents", "welcome.html" ); }
);

$f3->route('GET /docs/feeds',
	function($f3) { basicPage( $f3, "OPD Documentation: Feeds (RSS, iCal, Atom)", "docs-feeds.html" ); }
);
$f3->route('GET /docs/social',
	function($f3) { basicPage( $f3, "OPD Documentation: Social Networks", "docs-social.html" ); }
);

$f3->run();
exit;

function basicPage( $f3, $title, $template )
{
	$f3->set('html_title', $title );
	$f3->set('content', $template );
	echo Template::instance()->render('page-template.html');
}
