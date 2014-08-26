<?php
class org {


	function getPage( ){
	
		$f3=Base::instance();
			
		$name = $f3->get( "PARAMS.name" );
		$id = $f3->get( "PARAMS.id" );

		$id = preg_replace( '/[^-a-z0-9]/i','',$id );
		
		$datadir = "../var/";
	
		$info = "{$datadir}orgs/{$id}/org.json";
		
		if(!isset($id) || !strlen($id)){
			$f3->error(404);
		}
		
		if(!file_exists($info)){
			$f3->error(404);
		}else{
			$org = json_decode(file_get_contents($info),true);
		}
		

		require_once( "../lib/arc2/ARC2.php" );
		require_once( "../lib/Graphite/Graphite.php" );
		require_once( "../lib/OPDLib/OrgProfileDocument.php" );
		require_once( "../lib/ResourceVerifier.php" );
		
		$topd = @new OrgProfileDocument( "{$datadir}orgs/{$id}/org.ttl" , 'local');

		$graph = $topd->graph;
		
		
		$rv = new ResourceVerifier( "app/org_map.json" );

		$content []= "<p>The following content was identified:</p>";

		$content []= "<h2>Core</h2>";

		$topd->graph->ns( "vcard", "http://www.w3.org/2006/vcard/ns#" );
		$topd->graph->ns( "xtypes", "http://purl.org/xtypes/");
	
		
		$page = array();

		$page[] = "<div class=\"opd_note\">The following is information published in this organisation's OPD plublished here: <a href=\"{$org['opd_url']}\">{$org['opd_url']}</a> and was last checked at {$org['opd_crawled']} </div>";
	
		$page[] = "<div class=\"org_logo\">Offical Logo:<br/>";
		$page[] = "<img src=\"/org/{$org['org_id']}/{$org['org_url_name']}.logo?size=medium\" />";
		$page[] = "</div>";
		
		$page[] = "<div class=\"org_info\">";
		$page[]= $rv->html_report( "core", $topd->org, array('skip'=>true, "id"=>"Organistaion Id") );
		$page[] = "</div>";		
	
		$socialmedias = array(
			"https://www.facebook.com/" => array("Facebook","facebook.com/"),
			"https://twitter.com/" => array("Twitter","@"),
			"https://www.flickr.com/" => array("Flickr","flickr.com/"),
			"https://plus.google.com/" => array("Google+","plus.google.com/"),
			"http://instagram.com/" => array("Instagram","instagram.com/"),
			"http://www.pinterest.com/" => array("Pinterest","pinterest.com/"),
			"http://vimeo.com/" => array("Vimeo","vimeo.com/"),
			"http://vk.com/" => array("VK","vk.com/"),
			"http://www.weibo.com/" => array("Weibo","weibo.com/"),
			"https://www.youtube.com/" => array("YouTube","youtube.com/")
			
		);

		
		$accounts = $topd->org->all( "foaf:account" );
		if(count($accounts)){
			$page []= "<h2>Social Media</h2>";
			$page []= "<ul class='opd_social'>";
	
			foreach( $accounts as $account )
			{
				if ( $account->has( "foaf:accountServiceHomepage" ) && isset($socialmedias[$account->get( "foaf:accountServiceHomepage" )->toString()]))
				{
					$ac = $socialmedias[$account->get( "foaf:accountServiceHomepage" )->toString()];
					$page []= "<li>{$ac[0]}: <a href=\"{$account->toString()}\">".str_replace($account->get( "foaf:accountServiceHomepage" )->toString(),$ac[1],$account->toString())."</a> </li>";
				}

	//			$page []= $rv->html_report( "social", $account );
			}
			$page []= "</ul>";
		}
		
		
		
		
		if($topd->org->has( "foaf:based_near" )){
			$page[]= "<h2>Location</h2>";
			$page[]= $rv->html_map( "foaf:based_near", $topd->org, array() );
		
		
		}
			
		
	
	
		$f3->set('html_title', $org['org_name'] );
		$f3->set('content','content.html');
		$f3->set('html_content', join("\n",$page) );
		print Template::instance()->render( "page-template.html" );
		return;
	
		
		print_r($org);
		
	}

	function getLogo( )
	{
		
		$f3=Base::instance();
			
		$name = $f3->get( "PARAMS.name" );
		$id = $f3->get( "PARAMS.id" );

		$id = preg_replace( '/[^-a-z0-9]/i','',$id );
		
		$sizes = array('small'=>'90x35^>','medium'=>'150x100^>');
		
		$datadir = "../var/";
	
		$info = "{$datadir}orgs/{$id}/org.json";
	
	
	
		if(!isset($id) || !strlen($id)){
			$f3->error(404);
		}
		
		$pic_sub = "{$datadir}orgs/{$id}/logo-";
		$pic_org = "{$datadir}orgs/{$id}/logo-original.png";
		$pic_full = "{$datadir}orgs/{$id}/logo-full.png";
//		$pic_target = "{$datadir}orgs/{$id}/logo-o";
		
		
		if(!file_exists($pic_org) || filemtime($pic_org) < strtotime("-2 Weeks") ){
			@`rm -f {$pic_sub}*`;
		}
		
		
		
		
		if(!file_exists($info)){
			$f3->error(404);
		}else{
			$org = json_decode(file_get_contents($info),true);
		}
		
		if(!strlen($org['org_logo'])){
			$f3->error(404);
		}
	
		if(isset($_REQUEST['size']) && in_array($_REQUEST['size'],array_keys($sizes))){
			$pic_size = "{$pic_sub}{$_REQUEST['size']}.png";
			$goimage = $pic_size;
		}else{
			$goimage = $pic_org;
		}
		
		if(file_exists($goimage)){
			header('Content-Type: image/png');
			readfile($goimage);
			exit();
		
		}
		
		if(!file_exists($pic_org)){
			@`rm -f {$pic_sub}.*`;
			copy($org['org_logo'], $pic_org);
		}
		
	
		if(!file_exists($pic_full)){
			$exec = "convert ".escapeshellarg($pic_org)." ".escapeshellarg("png:{$pic_full}");
			@exec($exec);
		}
		
		if(isset($_REQUEST['size']) && in_array($_REQUEST['size'],array_keys($sizes))){
			$exec = "convert ".escapeshellarg($pic_org)." -resize ".escapeshellarg($sizes[$_REQUEST['size']])." ".escapeshellarg("png:{$pic_size}");
			@exec($exec);
		}
		
		if(file_exists($goimage)){
			header('Content-Type: image/png');
			readfile($goimage);
			exit();
		
		}
	
		$f3->error(404);
		exit();
	
	}
	
	
}

