<?php
/*
 * Plugin Name: Map Tour
 * Plugin URI: http://
 * Description:
 * Version: 1.0.0
 * Author:
 * Author URI: http://webkowski.com
 * Text Domain: mt
*/

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'MAPTOUR_VERSION', '1.0.0');
define( 'MAPTOUR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAPTOUR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MAPTOUR_DEBUG', true );

require_once 'library/class-maptour.php';

add_action( 'plugins_loaded', function() {
    new Maptour();
});





add_action( 'the_content', 'get_pages_map' );
function get_pages_map( $content ) {
    if ( !is_single() && !is_page() ) {
	return;
    }
    $place_types = get_terms( 'place_type' );



    $map_args  = array(
	'post_type'	=> 'map_place',
	'post_status'	=> 'publish',
	'meta_query' => array(
	    array(
		'key'	    => 'place_data',
		'compare'   => 'EXIST',
	    )
	)
    );
    $map_query = new WP_Query( $map_args );
    if ( $map_query->have_posts() ) {
	$i = 0;
	while ( $map_query->have_posts() ) {
	    $map_query->the_post();

	    $page_location = explode( ',', get_post_meta( get_the_ID(), 'place_data', true ) );

	    $map_markers[$i]['lat'] = $page_location[0];
	    $map_markers[$i]['lng'] = $page_location[1];
	    $map_markers[$i]['title'] = get_the_title();
	    $map_markers[$i]['description'] = get_the_content();
	    $map_markers[$i]['image'] = wp_get_attachment_url( get_post_thumbnail_id( get_the_ID() ) );
	    $map_markers[$i]['url'] = get_permalink();
	    $type = wp_get_post_terms( get_the_ID(), 'place_type', array( 'fields' => 'slugs') );
	    $map_markers[$i]['type'] = $type[0];

	    $i++;
	    //$page_location[] = '<div id="content"><h3><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h3>' . get_the_content() . '</div>';
	    //$markers[] = $page_location;
	}
    }
    dump($map_markers);
    //dump($markers);
    //dump($place_types);
    ?>
    <style>
	.entry-content img, .comment-content img, .widget img {
	    max-width: inherit;
	}
	#map {
	    height: 600px;
	    border: 0px solid #000;
	}
	#map-legend .hidden{
	    opacity: .2;
	}
    </style>
    <script>
	
	
	
    </script>
    <div id="map"></div>

    <div class="siderbarmap">
        <ul id="map-legend">
            <?php
	    $html = '';
    foreach( $place_types as $type ) {
		$html .= '<li><a href="#' . $type->slug . '" data-type="' . $type->slug . '">' . $type->name . '</a></li>';
	    }
	    echo $html;
	?>
            
        </ul>
    </div>
    <script>

//	var markerGroups = {
//
//        "a": [],
//        "b": [],
//        "c": [],
//	"d": []
//};
//console.log(markerGroups);


var markerGroups = <?php

foreach ($place_types as $type){
    $types[$type->slug] = array();
}

echo json_encode( $types, JSON_HEX_QUOT | JSON_HEX_TAG  );


?>;



var markers = <?php echo json_encode( $map_markers, JSON_HEX_QUOT | JSON_HEX_TAG ); ?>;
//var markerGroups = [];

	window.onload = function() {

	    var bounds = new google.maps.LatLngBounds();
	    
    var map = new google.maps.Map(document.getElementById('map'), {

		mapTypeId: google.maps.MapTypeId.roadmap
	    });

	    var infowindow;
	    for (var i = 0; i < markers.length; i++) {
		var marker = new google.maps.Marker({
		    position: new google.maps.LatLng(markers[i].lat,markers[i].lng ),
		    map: map,
		    title: markers[i].title,
		    info: markers[i].title,
		    icon: 'http://google.com/mapfiles/ms/micons/green-dot.png',
		    type: markers[i].type
		});
		console.log(markers[i].type);

		
 bounds.extend(marker.position);

markerGroups[markers[i].type].push(marker);

		(function(marker, i) {

		    

		    
		    google.maps.event.addListener(marker, 'click', function() {
			if (infowindow) infowindow.close();
			infowindow = new google.maps.InfoWindow({
			    content: this.info
			});
			infowindow.open(map, marker);
		    });
		})(marker, i);
	    }

	    map.fitBounds(bounds);
	};


var toggle = (function toggle_markers(b){
var toggle_buttons = document.querySelectorAll(b);
for ( var i=0; i < toggle_buttons.length; i++ ) {
    
	    toggle_buttons[i].addEventListener( 'click', function(e){

    var type = this.getAttribute('data-type');
if(this.classList.contains('hidden')){
    this.classList.remove('hidden');
}else{
    this.classList.add('hidden');
    }


    for (var i = 0; i < markerGroups[type].length; i++) {
        var marker = markerGroups[type][i];
        if (!marker.getVisible()) {
            marker.setVisible(true);
        } else {
            marker.setVisible(false);
        }
    }
}, false);
};
})('#map-legend a');


//	function toggleGroup(type) {
//
//
//    for (var i = 0; i < markerGroups[type].length; i++) {
//        var marker = markerGroups[type][i];
//        if (!marker.getVisible()) {
//            marker.setVisible(true);
//        } else {
//            marker.setVisible(false);
//        }
//    }
//}

    </script>
    <?php
    wp_enqueue_script( 'maps', 'http://maps.google.com/maps/api/js', array(), true );

    return $content;
}
