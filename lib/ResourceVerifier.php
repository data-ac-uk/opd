<?php 

class ResourceVerifier
{
	var $config;
	var $openspacemapkey = "FFF3760C96CE4469E0430C6CA40A1131";

function __construct( $config_file )
{
	$contents = file_get_contents( $config_file ) ;
	if( !$contents ) { throw new Exception( "Could not read file '$config_file'" ); }
	$this->config = json_decode( $contents, true );
	if( !$this->config )
	{
		throw new Exception( "Error parsing config json: ".json_last_error_msg() );
	}
}

function html_report( $section_id, $resource, $ops = array() )
{
	$graph = $resource->g;

	$section = $this->config[$section_id];
	
	if(!isset($ops['skip']))
		$ops['skip'] = false;
	if(!isset($ops['id']))
		$ops['id'] = false;
	if(!isset($ops['skipid']))
		$ops['skipid'] = false;
	$h = array();
	$h []= "<table class='rv_data'>";
	if(!$ops['skipid']){
		if(!$ops['id']){
			$h []= "<tr><td colspan='2' class='rv_uri'>".$resource->link()."</td></tr>";
		}else{
			$h []= "<tr><th>".$ops['id'].":</th><td class='rv_uri'>".$resource->link()."</td></tr>";
		}
	}

	$n = 0;

	foreach( $section["terms"] as $term )
	{
		
		if( $resource->has( $term["term"] ) )
		{
			$h []= "<tr>";
			$h []= "<th title=\"{$term["term"]}\">".$term["label"].":</th>";
			$h []= "<td>";
			foreach( $resource->all( $term["term"] ) as $value )
			{
				$fn = "render_default";
				if( @$term["render"] )
				{
					$fn = "render_".$term["render"];
				}
			
				$h []= "<div title='$value'>";
				$h []= $this->$fn( $graph, $value, $term ,$resource);
				$h []= $this->verify( $graph, $value, $term );
				$h []= "</div>";
				
				$n++;
				
			}
		}
		elseif(!$ops['skip'])
		{
			$h []= "<tr>";
			$h []= "<th title=\"{$term["term"]}\">".$term["label"].":</th>";
			$h []= "<td>";
			$h []= "<span class='rv_null'>NULL</span>";
			if( @$term["recommended"] )
			{
				$h []= $this->render_problem( "This unset field is recommended" );
			}

			$h []= "</td>";
			$h []= "</tr>";
		}
	}
	$h []= "</table>";

	if($ops['skip'] && !$n ){
		return false;
	}

	return join( "", $h );
}

function verify( $graph, $value, $term )
{
	if( @$term["expect"] )
	{
		if( $term["expect"] == "literal" && get_class( $value ) != "Graphite_Literal" )
		{
			return $this->render_problem( "expected a literal value" );
		}
		if( $term["expect"] == "resource" && get_class( $value ) != "Graphite_Resource" )
		{
			return $this->render_problem( "expected a resource" );
		}
	}
	if( @$term["expect_scheme"] )
	{
		$ok = false;
		list( $scheme, $rest ) = preg_split( "/:/", $value );
		foreach( $term["expect_scheme"] as $a_scheme )
		{
			if( $a_scheme == $scheme )
			{
				$ok = true;
				break;
			}
		}
		if( !$ok )
		{
			return $this->render_problem( "expected URI with scheme ".join( " or ", $term["expect_scheme"] ) );
		}
	}
}

function html_map( $key, $resource, $ops = array() )
{
	$graph = $resource->g;
	
	if(!$resource->has($key)){
		return;
	}
	
	$location = $this->location_find_rdf($resource->get($key), $graph);
	
	if(!isset($ops['size'])){
		$ops['size'] = array(400,300);
	}
	
	$ret = <<<END
<script type="text/javascript" src="http://openspace.ordnancesurvey.co.uk/osmapapi/openspace.js?key=FFF3760C96CE4469E0430C6CA40A1131" ></script>
<script type="text/javascript">
	var osMap;
	var osPos;
	function osInit(){
		extent = new OpenLayers.Bounds(0,0,700000,1300000);
		var options = {
			restrictedExtent: extent,
			resolutions: [500, 200, 100, 50, 25, 10, 5, 4, 2.5, 2, 1]
		};
		osMap = new OpenSpace.Map('map',options);
		osPos = new OpenSpace.MapPoint({$location["loc_easting"]},{$location["loc_northing"]});
        osMap.setCenter(osPos, 0);
	    var markers = new OpenLayers.Layer.Markers("Markers");
        osMap.addLayer(markers);
       	var marker = new OpenLayers.Marker(osPos);
	    markers.addMarker(marker);
	}
	function zoomMap(zoom){
		 osMap.setCenter(osPos, 0);
		 osMap.zoomTo(zoom);
		 return false;
	}
	setTimeout('osInit()',500);
</script>
<div class="rv_mapbox">
<div id="map" style="background: #a0cffe;"></div>
<div class="loc">long/lat: {$location["loc_latlng"]} ( {$location["loc_lat"]}, {$location["loc_lat"]}) OS Grid Reference: {$location["loc_grid"]} ({$location["loc_easting"]},{$location["loc_northing"]})</div>
<nav>Zoom to: <a onclick="zoomMap(0);">Country</a> <a onclick="zoomMap(1);">County</a> <a onclick="zoomMap(3);">District</a> <a onclick="zoomMap(5);">City Area</a> <a onclick="zoomMap(7);">City</a> <a onclick="zoomMap(9);">Street</a> </nav>
</div>
		
END;

	return $ret;


}

function location_find_rdf($loc, $g = NULL){
	if($g===NULL)
		$g = new Graphite();
	$g->load( (string)$loc );
	return $this->location_find($g->resource((string)$loc));
}
	
function location_find($loc){
	
	require_once("../lib/phpLocation/phpLocation.php");
	
	$location = array("	loc_uri"=>(string)$loc);
	if( $loc->has( "http://www.w3.org/2003/01/geo/wgs84_pos#lat" ) )
	{
		$location["loc_lat"] = $loc->getLiteral( "geo:lat" );
		$location["loc_long"] = $loc->getLiteral( "geo:long" );
	}
	if( $loc->has( "http://data.ordnancesurvey.co.uk/ontology/spatialrelations/easting" ) )
	{
		$location["loc_easting"] = (int)$loc->getLiteral( "http://data.ordnancesurvey.co.uk/ontology/spatialrelations/easting" );
		$location["loc_northing"] = (int)$loc->getLiteral( "http://data.ordnancesurvey.co.uk/ontology/spatialrelations/northing" );
	}
	
	$pos = new phpLocation();
	$pos->lat = $location["loc_lat"];
	$pos->lon = $location["loc_long"];
	$pos->toGrid();
	
	if((isset($location["loc_lat"]) && isset($location["loc_long"])) && ( !isset($location["loc_easting"]) || !isset($location["loc_northing"]) )){
		
		$location["loc_easting"] = (int)$pos->east;
		$location["loc_northing"] = (int)$pos->north;
	}
	

	$location["loc_latlng"] = $pos->formatlocation($location["loc_lat"],'dMS','lat')." ".$pos->formatlocation($location["loc_long"],'dMS','long');
	$location["loc_grid"] = $pos->posasgrid(6);

	if(!isset($location["loc_lat"]) || !isset($location["loc_lat"]) ) return false;
	
		
	return $location;
}


function render_default( $graph, $value, $term , $resource)
{
	return $value;	
}

function render_problem( $msg )
{
	return " <span class='rv_message'>WARNING: $msg</span>";
}

function render_uri( $graph, $value, $term, $resource )
{
	return $graph->shrinkURI( $value );
}

function render_map( $graph, $value, $term, $resource )
{
	return $this->html_map( "foaf:based_near", $resource);
}
	
	
function render_uri_values( $graph, $value, $term, $resource )
{
	$svalue = $graph->shrinkURI( $value );

	if( @$term["values"][$svalue] )
	{
		return "<em>".$term["values"][$svalue]."</em>";
	}

	return $svalue;
}

function render_img( $graph, $value, $term, $resource )
{
	return "<img src='$value' />";
}

function render_link( $graph, $value, $term, $resource )
{
	return $value->link();
}

function render_pretty_link( $graph, $value, $term, $resource )
{
	return $value->prettyLink();
}


		

}
		
// for when json_last_error_msg isn't defined
if (!function_exists('json_last_error_msg'))
{
    function json_last_error_msg()
    {
        switch (json_last_error()) {
            default:
                return;
            case JSON_ERROR_DEPTH:
                $error = 'Maximum stack depth exceeded';
            break;
            case JSON_ERROR_STATE_MISMATCH:
                $error = 'Underflow or the modes mismatch';
            break;
            case JSON_ERROR_CTRL_CHAR:
                $error = 'Unexpected control character found';
            break;
            case JSON_ERROR_SYNTAX:
                $error = 'Syntax error, malformed JSON';
            break;
            case JSON_ERROR_UTF8:
                $error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
        }
        throw new Exception($error);
    }
}

