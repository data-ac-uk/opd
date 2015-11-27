<?php
class dataset {

	
	var $mapping = array(
		'linkingyou' => array(
			'title' => 'Linking You',
			'keys'=>array(
			)
		),
			
		'core' => array(
			'title' => 'Core',
			'keys'=>array(
				'org_name' => 'Official Name',
				'org_type' => 'Type',
				'org_hiddenlabel' => 'Hidden Name',
				'org_homepage' => 'Home Page',
				'org_phone' => 'Phone',
				'org_email' => 'Email',
				'org_logo' => 'Logo',
				'org_sparql'  => 'Sparql endpoint',
				'org_sameas' => 'SameAs'
			)
		),
			
		'social' => array(
			'title' => 'Social',
			'keys'=>array(
				'org_social_facebook' => 'Facebook',
				'org_social_twitter' => 'Twitter',
				'org_social_flickr' => 'Flickr',
				'org_social_googleplus' => 'Google Plus',
				'org_social_instagram' => 'Instargram',
				'org_social_pinterest' => 'Pinterest',
				'org_social_vimeo' => 'Vimeo',
				'org_social_vk' => 'VK',
				'org_social_weibo' => 'Weibo',
				'org_social_youtube' => 'Youtube'
			)
		)
	);
	
	function getDateset( ){
	
		$f3=Base::instance();
			
		$name = $f3->get( "PARAMS.name" );
		$id = $f3->get( "PARAMS.id" );

		$id = preg_replace( '/[^-a-z0-9]/i','',$id );
		
		$datadir = "../var/";
		
		$loaddata = json_decode(file_get_contents("{$datadir}exportopds.json"));
		
		if(!isset($this->mapping[$id])){
			$f3->error(404);
			exit();
		}
		
		$type = $this->mapping[$id];
		$count_array = array();
		
		
		switch($id){
			
			case "core":
			case "social":
				foreach($type['keys'] as $k=>$v){
					$count_array[$k] = array('count'=>0, 'title'=>$k,'desc'=>$v);
				}
				
				foreach($loaddata as $org){
					foreach($count_array as $k=>$v){
						if(isset($org->{$k}) && !empty($org->{$k})){
							$count_array[$k]['count'] ++;
						}
					}
				}
			break;
			case "liningyou":
			
				$lyoukeys = json_decode(file_get_contents("{$datadir}lyoumap.json"),true);
				$liy = json_decode(file_get_contents("opd_verify.json"),true);
		
				$desc_array = array();
				foreach($liy["linking-you"]['terms'] as $terms){
					$desc_array[$terms['term']] = $terms['label'];
				}
			
				foreach($lyoukeys as $k=>$v){
					$count_array[$k] = array('count'=>0, 'title'=>$v,'desc'=>$desc_array[$v]);
				}
		
	
				foreach($loaddata as $org){
					foreach($count_array as $k=>$v){
						if(isset($org->{$k}) && !empty($org->{$k})){
							$count_array[$k]['count'] ++;
						}
					}
				}
			
				break;
			
		
		}	
		
		$page = array();

		$page []= "<table class='bordered_table'>";
		$page []= "<thead>";
		$page []= "<tr>";
			$page []= "<th>Term</th>";
			$page []= "<th>Description</th>";
			$page []= "<th>Number of organisations</th>";
		$page []= "</tr>";
		$page []= "</thead>";
		

		$page []= "<tbody>";
		foreach($count_array as $k=>$v){
			$page []= "<tr onclick=\"location.href='/dataset/{$id}/{$v['title']}.html'; return false;\" class=\"hover\">";
				$page []= "<td>{$v['title']}</td>";
				$page []= "<td>{$v['desc']}</td>";
				$page []= "<td class=\"center\">{$v['count']}</td>";
			$page []= "</tr>";
		}

		$page []= "</tbody>";

		$page []= "</table>";
		
	
		$f3->set('html_title', $type['title'] );
		$f3->set('content','content.html');
		$f3->set('html_content', join("\n",$page) );
		print Template::instance()->render( "page-template.html" );
		
		
	}
	
	function getDatesetItem( ){
		$f3=Base::instance();
			
		$name = $f3->get( "PARAMS.name" );
		$id = $f3->get( "PARAMS.id" );
		$format = $f3->get( "PARAMS.format" );

		$id = preg_replace( '/[^-a-z0-9]/i','',$id );
		
		$datadir = "../var/";
		
		$loaddata = json_decode(file_get_contents("{$datadir}exportopds.json"));

		$opddata = json_decode(file_get_contents("{$datadir}autoopds.json"),true);
		
		if(!isset($this->mapping[$id])){
			$f3->error(404);
			exit();
		}
		
		$type = $this->mapping[$id];
		$count_array = array();
		
		
		
		
		
		switch($id){
			
			case "core":
			case "social":
				
				$key = $name;
				foreach($loaddata as $org){
					if(isset($org->{$key}) && !empty($org->{$key})){
						$count_array[$org->org_id] = array('id'=>$org->org_id, 'title'=>$org->org_name, 'value'=>$org->{$key}) ;
					}
				}
				
				$desc = $type['keys'][$key];
				
				$csv_titles = array('org_id','org_name', 'page');
				
				
				$page_titles = array('Organisation', $desc);
				
				
				$intro = "<p>A list of an organisations with a $desc</p>";
				
			break;
			case "liningyou":
			
				$lyoukeys = json_decode(file_get_contents("{$datadir}lyoumap.json"),true);
				$liy = json_decode(file_get_contents("opd_verify.json"),true);
		
		
				$desc_array = array();
				foreach($liy["linking-you"]['terms'] as $terms){
					if($terms['term']==$name){
						$desc = $terms['label'];
					}
				}
			
				foreach($lyoukeys as $k=>$v){
					if($v == $name){
						$key = $k;
					}

				}
		
		
	
				foreach($loaddata as $org){
					if(isset($org->{$key}) && !empty($org->{$key})){
						$count_array[$org->org_id] = array('id'=>$org->org_id, 'title'=>$org->org_name, 'value'=>$org->{$key}) ;
					}
				}
			
				
				$csv_titles = array('org_id','org_name', 'page');
				
				$page_titles = array('Organisation', 'Linking You Page');
				
				$intro = "<p>A list of an organisations with a linking you $desc</p>";
		}	
		
		
		
		switch($format) {
			case "csv";
			$filename = "{$name}.csv";
			
			$this-> print_csv($csv_titles, $count_array, $filename);
		

			break;
			
			case "json";
			$filename = "{$name}.json";
			
			$this-> print_json($count_array, $filename);
		

			break;
		default:
			$page = array();
		
			$page []=  $intro;

			$page []= "<table class='bordered_table'>";
			$page []= "<thead>";
			$page []= "<tr>";
				$page []= "<th>{$page_titles[0]}</th>";
				$page []= "<th>{$page_titles[1]}</th>";
			$page []= "</tr>";
			$page []= "</thead>";
		

			$page []= "<tbody>";
			foreach($count_array as $k=>$v){
				$page []= "<tr>";
					$page []= "<td><a href=\"/org/{$v['id']}/".$opddata[$v['id']]['org_url_name'].".html\">{$v['title']}</a></td>";
					if(substr($v['value'],0,4) == 'http') {
						$page []= "<td><a href=\"{$v['value']}\" target=\"_blank\">{$v['value']}</td>";
					} else {
						$page []= "<td>{$v['value']}</td>";
					}
					
				$page []= "</tr>";
			}

			$page []= "</tbody>";

			$page []= "</table>";
		
			$page[] ="<div class=\"download_as\">";
				$page[] = "Download as: ";
				$page[] = "<a href=\"/dataset/$id/$name.csv\" class=\"fileicon csv\">CSV</a>";
				$page[] = " | <a href=\"/dataset/$id/$name.json\" class=\"fileicon json\">JSON</a>";
			$page[] ="</div>";
	
			$f3->set('text_title', $type['title'] . ' - '. $name .' - '. $desc);
			$f3->set('html_title', "<a href=\"/dataset/$id\">".$type['title'] . '</a> - '. $name .' - '. $desc);
			$f3->set('content','content.html');
			$f3->set('html_content', join("\n",$page) );
			print Template::instance()->render( "page-template.html" );
		
			
		
		}
	}
	
	function print_json( $count_array, $filename) {
	    header( 'Content-Type: application/json' );
        header( 'Content-Disposition: attachment;filename='.$filename);
        print json_encode($count_array);
	}
	function print_csv($titles, $count_array, $filename) {
	    header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment;filename='.$filename);
        $fp = fopen('php://output', 'w');
		
		fputcsv($fp, $titles);
		
		foreach($count_array as $k=>$v){
			fputcsv($fp, $v);
		}

    
        fclose($fp);
	}
}

