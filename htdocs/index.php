<?php
date_default_timezone_set( "Europe/London" );
$f3=require('../f3/lib/base.php');

$f3->set('DEBUG',1);

$f3->set('AUTOLOAD',"app/");

//if ((float)PCRE_VERSION<7.9)
//	trigger_error('PCRE version is out of date');

$f3->config('config.ini');

$f3->route('GET /',
	function($f3) { 
		$opds = json_decode( file_get_contents( '../var/autoopds.json' ), true );
		$f3->set('opds', $opds );
		
		basicPage( $f3, "Organisation Profile Documents", "welcome.html" ); 
	}
);


$f3->route('GET	/org/@id/@name.html', 'org->getPage' );
$f3->route('GET	/org/@id/@name.logo', 'org->getLogo' );



$f3->route('GET /docs/feeds',
	function($f3) { basicPage( $f3, "Documentation: Feeds (RSS, iCal, Atom)", "docs-feeds.html" ); }
);
$f3->route('GET /docs/social',
	function($f3) { basicPage( $f3, "Documentation: Social Networks", "docs-social.html" ); }
);
$f3->route('GET /docs/auto',
	function($f3) { basicPage( $f3, "Documentation: Autodiscovery", "docs-auto.html" ); }
);
$f3->route('GET /docs/datasets',
	function($f3) { basicPage( $f3, "Documentation: Datasets", "docs-datasets.html" ); }
);
$f3->route('GET /workshops/getting_started',
	function($f3) { basicPage( $f3, "Workshop: Getting Started", "workshop-gettingstarted.html" ); }
);
$f3->route('GET /docs/core',
	function($f3) { basicPage( $f3, "Documentation: Core", "docs-core.html" ); }
);
$f3->route('GET /docs/local',
	function($f3) { basicPage( $f3, "Documentation: Locations", "docs-local.html" ); }
);
$f3->route('GET /docs/key-pages',
	function($f3) { basicPage( $f3, " Documentation: Key Pages", "docs-key-pages.html" ); }
);


$f3->route('GET /data/autoopds.json',
	function($f3) { 
		header('Content-type: application/json');
		readfile('../var/autoopds.json');
		exit();
	}	
);


$f3->run();
exit;

function basicPage( $f3, $title, $template )
{
	$f3->set('html_title', $title );
	$f3->set('content', $template );
	echo Template::instance()->render('page-template.html');
}
