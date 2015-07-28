<?php
date_default_timezone_set( "Europe/London" );
require_once( "../lib/arc2/ARC2.php" );
require_once( "../lib/Graphite/Graphite.php" );
require_once( "../lib/OPDLib/OrgProfileDocument.php" );
require_once( "../lib/ResourceVerifier.php" );

try {
    $f3=require('../f3/lib/base.php');
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

$f3->config('config.ini');

if( @!$_GET["opd"] && @!$_GET["homepage"] && @!$_POST["opd_paste"])
{
	$f3->set('html_title', "Organisation Profile Document (OPD) Checker" );
	$f3->set('content','opd-intro.html');
	$f3->set('note','' );
	print Template::instance()->render( "page-template.html" );
	exit;
}

$homepage_url = @$_GET["homepage"];
if (preg_match("#https?://#",$homepage_url) === 0) {
    $homepage_url = 'http://'.$homepage_url;
}

$opd_url = @$_GET["opd"];
$opd_paste = @$_POST["opd_paste"];

$content = array();

try 
{
	if( @$opd_paste )
	{
		$content []= "<p>Attempting to parse OPD pasted form.</p>";
		$opd = OrgProfileDocument::from_string( $opd_paste );
	}
	elseif( @$homepage_url )
	{
		$content []= "<p>Attempting to autodiscover OPD from <a href='$homepage_url'>$homepage_url</a></p>";
		$opd = OrgProfileDocument::discover( $homepage_url );
	}
	else
	{
		$content []= "<p>Attempting to load OPD from <a href='$opd_url'>$opd_url</a></p>";
		$opd = new OrgProfileDocument( $opd_url );
	}
}
catch( OPD_Discover_Exception $e )
{
	$content[] = "<h2>Failed to discover OPD</h2><p>".$e->getMessage()."</p>";
	$f3->set('results', join( "", $content ) );
	serve_results();
	exit;
}
catch( OPD_Load_Exception $e )
{
	$content[] = "<h2>Failed to load OPD</h2><p>".$e->getMessage()."</p>";
	$f3->set('results', join( "", $content ) );
	serve_results();
	exit;
}
catch( OPD_Parse_Exception $e )
{
	$content[] = "<h2>Failed to parse OPD</h2><p>".$e->getMessage()."</p>";
	$content[] = "<div class='code'>".htmlspecialchars(htmlspecialchars( $e->document ))."</div>";
	$f3->set('results', join( "", $content ) );
	serve_results();
	exit;
}
catch( Exception $e ) 
{
	$content[] = "<h2>Error</h2><p>".$e->getMessage()."</p>";
	$f3->set('results', join( "", $content ) );
	serve_results();
	exit;
}



$content []= "<p>OPD Loaded OK!</p>";

#################### #################### ####################
# CORE PROFILE
#################### #################### ####################

$content []= "<p>OPD Located: <a href=\"{$opd->opd_url}\">{$opd->opd_url}</a></p>";

$content []= "<p>Organisation self-assigned URI is $opd->org</p>";

$rv = new ResourceVerifier( "opd_verify.json" );

$content []= "<p>The following content was identified:</p>";

$content []= "<h2>Core</h2>";

$opd->graph->ns( "vcard", "http://www.w3.org/2006/vcard/ns#" );
$opd->graph->ns( "xtypes", "http://purl.org/xtypes/");
$content []= $rv->html_report( "core", $opd->org );




#################### #################### ####################
# DATASETS & FEEDS
#################### #################### ####################

# use verify for additional 
$bits = array(
	array( 
		"name"=>"Facilities",
		"subjects"=>array("http://purl.org/openorg/theme/facilities"),
		"verify"=>array(),
	),
	array( 
		"name"=>"Equipment",
		"subjects"=>array("http://purl.org/openorg/theme/equipment"),
		"verify"=>array(),
	),
	array( 
		"name"=>"Research Outputs",
		"subjects"=>array("http://purl.org/openorg/theme/ResearchOutputs"),
		"verify"=>array(),
	),
	array( 
		"name"=>"Members",
		"subjects"=>array("http://purl.org/openorg/theme/members"),
		"verify"=>array(),
	),
	array( 
		"name"=>"Events",
		"subjects"=>array("http://purl.org/openorg/theme/events"),
		"verify"=>array(),
	),
	array( 
		"name"=>"Places",
		"subjects"=>array("http://purl.org/openorg/theme/places"),
		"verify"=>array(),
	),
	array( 
		"name"=>"News",
		"subjects"=>array("http://purl.org/openorg/theme/news"),
		"verify"=>array(),
	),
	array( 
		"name"=>"Notices",
		"subjects"=>array("http://purl.org/openorg/theme/notices"),
		"verify"=>array(),
	),
);
	

foreach( $bits as $bit )
{
	
	$datasets = $opd->datasets( $bit["subjects"] );
	if( ! sizeof($datasets ) ) { continue; }
	
	$content []= "<h2>".$bit["name"]."</h2>";
	
	$n = 1;
	foreach( $datasets as $dataset )
	{
		if( $dataset->isType( "xtypes:Document-RSS","xtypes:Document-Atom", "xtypes:Document-iCalendar" ) )
		{
			$content []= "<div class='opd_dataset'>";
			$content []= "<h3>Feed: ".$bit["name"]." #$n</h3>";
			$n++;
			$content []= $rv->html_report( "feed", $dataset );
			foreach( $bit["verify"] as $vsection )
			{
				$content []= $rv->html_report( $vsection, $dataset );
			}
			$content []= "</div>";
		}
		else
		{
			$content []= "<div class='opd_dataset'>";
			$content []= "<h3>Dataset: ".$bit["name"]." #$n</h3>";
			$content []= $rv->html_report( "dataset", $dataset );
			$n++;
			foreach( $bit["verify"] as $vsection )
			{
				$content []= $rv->html_report( $vsection, $dataset );
			}
			$content []= "</div>";
		}
	}
}

#################### #################### ####################
# Social Media
#################### #################### ####################

$content []= "<h2>Social Media</h2>";
foreach( $opd->org->all( "foaf:account" ) as $account )
{
	$content []= "<div class='opd_dataset'>";
	$content []= "<h3>Account: '".$account->getLiteral("foaf:accountName")."'";
	if ( $account->has( "foaf:accountServiceHomepage" ) )
	{
		$content []= " on ".$account->get( "foaf:accountServiceHomepage" )->link();
	}

	$content []= "</h3>";
	$content []= $rv->html_report( "social", $account );
	$content []= "</div>";
}

#################### #################### ####################
# LINKING YOU
#################### #################### ####################


$opd->graph->ns( "lyou", "http://purl.org/linkingyou/" );
$content []= "<h2>Linking-you</h2>";
$content []= "<p>These terms link an organisation to web-pages organistations commonly have. See <a href='http://purl.org/linkingyou/'>http://purl.org/linkingyou/</a> for more information on these terms.</p>";
$content []= $rv->html_report( "linking-you", $opd->org );





$f3->set('results', join( "", $content ) );
serve_results();


#vcard
#linkingyou
#adr
#airport
#foaf:based_naer


function serve_results()
{
        $f3=Base::instance();
	$f3->set('html_title', "Organisation Profile Document (OPD) Checker Results" );
	$f3->set('content','opd-results.html');
	$f3->set('note','' );
	print Template::instance()->render( "page-template.html" );
}

 
