<?php
date_default_timezone_set( "Europe/London" );
$f3=require('../f3/lib/base.php');

if(substr($_SERVER['HTTP_HOST'],0,4)=='www.'){
	header("HTTP/1.1 301 Moved Permanently"); 
	header("Location: http".( (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 's' : '' )."://".substr($_SERVER['HTTP_HOST'],4)."/"); 
	exit();
}

if($_SERVER['HTTP_HOST']!='opd.data.ac.uk')
	$f3->set('DEBUG',1);
else{
	$f3->set('DEBUG',0);
}


$f3->set('AUTOLOAD',"app/");
$f3->set('UI',"ui/");

//if ((float)PCRE_VERSION<7.9)
//	trigger_error('PCRE version is out of date');



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
	function($f3) { basicPage( $f3, "Documentation: Basic Structure", "docs-core.html" ); }
);
$f3->route('GET /docs/local',
	function($f3) { basicPage( $f3, "Documentation: Locations", "docs-local.html" ); }
);
$f3->route('GET /docs/key-pages',
	function($f3) { basicPage( $f3, " Documentation: Key Pages", "docs-key-pages.html" ); }
);


$f3->route('GET /data/@name.@type',
	function($f3) { 
		$name = $f3->get( "PARAMS.name" );
		$type =  $f3->get( "PARAMS.type" );
		$fname = "../var/{$name}.{$type}";
		if(!file_exists($fname)){
			$f3->error(404);
			exit();
		}
		switch($type){
			case "json":
				header('Content-type: application/json');
			break;
			case "csv":
				header('Content-type: text/csv');
			break;
		}
		readfile($fname);
		exit();
	}	
);


$f3->route('GET /data/autoopds.json',
	function($f3) { 
		header('Content-type: application/json');
		readfile('../var/autoopds.json');
		exit();
	}	
);


$f3->set('ONERROR',function() use($f3) {
 	$f3=Base::instance();

	$error = $f3->get('ERROR');
	$error_title = constant('Base::HTTP_'.$error['code']);
	
	
   	$f3->set('html_title', "{$error['code']} {$error_title}" );
	$f3->set('content','content.html');
	
	$c[] = "<h2>{$error_title}</h2>";
	
	switch($error['code']){
		case "404":
			$c[] = "<p>The requested URL {$_SERVER['REDIRECT_URL']} was not found on this server.</p>";
		break;
	}
	
	if($f3->get('DEBUG')>0){
		$c[] = "<hr/>";
		$c[] = "<p>{$error['text']}</p>";
		foreach ($error['trace'] as $frame) {
			$line='';
			if (isset($frame['file']) && 
				(empty($frame['class']) || $frame['class']!='Magic') &&
				(empty($frame['function']) || !preg_match('/^(?:(?:trigger|user)_error|__call|call_user_func)/',$frame['function']))
				) {
				
				$addr=$f3->fixslashes($frame['file']).':'.$frame['line'];
				if (isset($frame['class']))
					$line.=$frame['class'].$frame['type'];
				if (isset($frame['function'])) {
					$line.=$frame['function'];
					if (!preg_match('/{.+}/',$frame['function'])) {
						$line.='(';
						if (!empty($frame['args']))
							$line.=$f3->csv($frame['args']);
						$line.=')';
					}
				}
				$str=$addr.' '.$line;
				$c[] = '&bull; '.nl2br($f3->encode($str)).'<br />';
			}
		}
	}
	
	$f3->set('html_content',join("",$c));

	print Template::instance()->render( "page-template.html" );
	exit();
});


$f3->run();
exit;

function basicPage( $f3, $title, $template )
{
	$f3->set('html_title', $title );
	$f3->set('content', $template );
	echo Template::instance()->render('page-template.html');
}
