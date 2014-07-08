<?php

$f3=require('../f3/lib/base.php');

$f3->set('DEBUG',1);
if ((float)PCRE_VERSION<7.9)
	trigger_error('PCRE version is out of date');

$f3->config('config.ini');

$f3->route('GET /',
	function($f3) {

		$f3->set('html_title', "Welcome to the OPD pages" );
		$f3->set('content','welcome.html');
		echo Template::instance()->render('page-template.html');
	}
);

$f3->run();
