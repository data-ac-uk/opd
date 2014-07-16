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
$f3->route('GET /docs/auto',
	function($f3) { basicPage( $f3, "OPD Documentation: Autodiscovery", "docs-auto.html" ); }
);
$f3->route('GET /docs/datasets',
	function($f3) { basicPage( $f3, "OPD Documentation: Datasets", "docs-datasets.html" ); }
);
$f3->route('GET /workshops/getting_started',
	function($f3) { basicPage( $f3, "Workshop: OPD Getting Started", "workshop-gettingstarted.html" ); }
);
$f3->route('GET /docs/core',
	function($f3) { basicPage( $f3, "OPD Documentation: Core", "docs-core.html" ); }
);
$f3->route('GET /docs/key-pages',
	function($f3) { basicPage( $f3, "OPD Documentation: Key Pages", "docs-key-pages.html" ); }
);

$f3->run();
exit;

function basicPage( $f3, $title, $template )
{
	$f3->set('html_title', $title );
	$f3->set('content', $template );
	echo Template::instance()->render('page-template.html');
}
