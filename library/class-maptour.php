<?php

class Maptour {

    private
	    $plugin_name = 'maptour',
	    $secret = 'RAJmIEgoEQATUufRPDov',
	    $post_type = 'map_place';

    public function __construct() {
	add_action( 'init', array( $this, 'register_post_place' ) );
	add_action( 'init', array( $this, 'register_taxonomy_place' ) );
	add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
	add_shortcode( 'get_map', array( $this, 'get_map' ) );
	if(is_admin()){
	    $this->admin_setup();
	}
    }

    public function admin_setup() {
	
	
	add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	add_action( 'add_meta_boxes', array( $this, 'add_meta_box') );
	add_action( 'save_post', array( $this, 'save_meta_box' ) );
    }

    public function enqueue_frontend_scripts() {
	wp_enqueue_style( 'maptour',  MAPTOUR_PLUGIN_URL . 'css/maptour.css');
	wp_enqueue_style( 'font-avesome',  'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css');
	wp_enqueue_script( 'maptour.app', MAPTOUR_PLUGIN_URL . 'js/frontend-map.js' , array( 'jquery' ), MAPTOUR_VERSION, true );
    }

    public function admin_enqueue_scripts() {
	wp_enqueue_script( 'maps', 'https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&sensor=false' );
	wp_enqueue_script( 'field-map', plugins_url( '../js/backend-map.js', __FILE__ ), array( 'jquery' ), MAPTOUR_VERSION );
	wp_localize_script( 'field-map', 'geocode_notfound', __( 'No results were found for the search criteria', 'mt' ) );
    }

    public function register_post_place() {
	$labels = array(
	    'name' => __( 'Places', 'mt' ),
	    'singular_name' => __( 'Place', 'mt' ),
	    'add_new' => __( 'Add New', 'mt' ),
	    'add_new_item' => __( 'Add New place', 'mt' ),
	    'edit_item' => __( 'Edit place', 'mt' ),
	    'new_item' => __( 'New place', 'mt' ),
	    'view_item' => __( 'View place', 'mt' ),
	    'search_items' => __( 'Search places', 'mt' ),
	    'not_found' => __( 'No placess found', 'mt' ),
	    'not_found_in_trash' => __( 'No places found in Trash', 'mt' ),
	    'parent_item_colon' => __( 'Parent place:', 'mt' ),
	    'menu_name' => __( 'Map places', 'mt' ),
	);
	$args = array(
	    'labels' => $labels,
	    'hierarchical' => false,
	    'description' => __( 'places', 'mt' ),
	    'supports' => array( 'title', 'editor','custom-fields', 'thumbnail' ),
	    'public' => true,
	    'show_ui' => true,
	    'show_in_menu' => true,
	    'menu_position' => 6,
	    'show_in_nav_menus' => true,
	    'publicly_queryable' => true,
	    'exclude_from_search' => true,
	    'has_archive' => false,
	    'query_var' => true,
	    'can_export' => true,
	    'rewrite' => true,
	    'capability_type' => 'post'
	);
	register_post_type( $this->post_type, $args );
    }

    public function register_taxonomy_place() {
	$labels = array(
		'name'                       => _x( 'Writers', 'taxonomy general name' ),
		'singular_name'              => _x( 'Writer', 'taxonomy singular name' ),
		'search_items'               => __( 'Search Writers' ),
		'popular_items'              => __( 'Popular Writers' ),
		'all_items'                  => __( 'All Writers' ),
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => __( 'Edit Writer' ),
		'update_item'                => __( 'Update Writer' ),
		'add_new_item'               => __( 'Add New Writer' ),
		'new_item_name'              => __( 'New Writer Name' ),
		'separate_items_with_commas' => __( 'Separate writers with commas' ),
		'add_or_remove_items'        => __( 'Add or remove writers' ),
		'choose_from_most_used'      => __( 'Choose from the most used writers' ),
		'not_found'                  => __( 'No writers found.' ),
		'menu_name'                  => __( 'Writers' ),
	);

	$args = array(
		'hierarchical'          => true,
		'labels'                => $labels,
		'show_ui'               => true,
		'show_admin_column'     => true,
		'update_count_callback' => '_update_post_term_count',
		'query_var'             => true,
		'rewrite'               => array( 'slug' => 'place_type' ),
	);

	register_taxonomy( 'place_type', $this->post_type, $args );
	
    }


    public function add_meta_box() {
	add_meta_box( 'metabox_' . $this->plugin_name, __( 'Post place', 'mt' ), array( $this, 'render_metabox' ), $this->post_type );
    }

    public function render_metabox( $post ) {
	wp_nonce_field( $this->secret . '_save', $this->plugin_name . '_nonce' );
	$value = get_post_meta( $post->ID, 'place_data', true );
	$type = 'hidden';
	if ( MAPTOUR_DEBUG ) {
	    $type = 'text';
	}
	$html = '<style>.map-box {height: 250px;}</style>'
		. '<div id="field_map" class="map-field box">'
		. '<input onkeydown="if (event.keyCode == 13){ codeAddress(); return false;}" id="geocode_field_map" class="controls" type="text" placeholder="' . __( 'Type location', 'mt' ) . '">'
		. '<input type="button" class="code-address button button-small" value="' . __( 'Show on map', 'mt' ) . '" >'
		. '<div id="map_field_map" class="map-box" ></div>'
		. '<input id="map" class="geodata"  type="' . $type . '"  name="place_data" value="' . esc_attr( $value ) . '" />'
		. '</div>';
	echo $html;
    }

    public function save_meta_box( $post_id ) {
	if ( !filter_input( INPUT_POST, $this->plugin_name . '_nonce' ) ) {
	    return;
	}
	if ( !wp_verify_nonce( filter_input( INPUT_POST, $this->plugin_name . '_nonce' ), $this->secret . '_save' ) ) {
	    return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
	    return;
	}
	if ( filter_input( INPUT_POST, 'post_type' ) && 'page' == filter_input( INPUT_POST, 'post_type' ) ) {
	    if ( ! current_user_can( 'edit_page', $post_id ) ) {
		    return;
	    }
	} else {
	    if ( ! current_user_can( 'edit_post', $post_id ) ) {
		    return;
	    }
	}
	if ( !filter_input( INPUT_POST, 'place_data' ) ) {
	    return;
	}
	$my_data = sanitize_text_field( filter_input( INPUT_POST, 'place_data' ) );
	update_post_meta( $post_id, 'place_data', $my_data );
    }


    
function get_map(  ) {
    if ( !is_single() && !is_page() ) {
	return;
    }
    global $post;
    $content = $post->content;

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
	    $map_markers[$i]['id'] = get_the_ID();
	    $map_markers[$i]['lat'] = $page_location[0];
	    $map_markers[$i]['lng'] = $page_location[1];
	    $map_markers[$i]['title'] = get_the_title();
	    $map_markers[$i]['description'] = get_the_content();
	    $image = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'thumbnail' );
	    $map_markers[$i]['image'] = $image[0];
	    //$map_markers[$i]['image'] = wp_get_attachment_url( get_post_thumbnail_id( get_the_ID() ) );
	    $map_markers[$i]['url'] = get_permalink();
	    $type = wp_get_post_terms( get_the_ID(), 'place_type', array( 'fields' => 'slugs') );
	    $map_markers[$i]['type'] = $type[0];

	    $i++;
	    //$page_location[] = '<div id="content"><h3><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h3>' . get_the_content() . '</div>';
	    //$markers[] = $page_location;
	}
    }
    //dump($map_markers);
    //dump($markers);
    //dump($place_types);
    ?>


    <div id="map-pums" class="container-map">
	<a href="" class="resize-small" data-map-container="map-pums"><i class="fa fa-times"></i></a>
	<a href="" class="resize-big" data-map-container="map-pums"><?php _e('Bigger map ', 'mt') ?> <i class="fa fa-arrows-alt"></i></a>
    <div id="map"></div>

    <div class="siderbarmap">

        <ul id="map-legend">
            <?php
	    $html = '';
    foreach( $place_types as $type ) {
		$types[$type->slug]['markers'] = array();
		$type_description = explode(',', $type->description);
		$types[$type->slug]['color'] = $type_description[0];
		$types[$type->slug]['default'] = isset($type_description[1])?1:0;
		$hidden = 'hidden';
		if($type_description[1] == 'default'){
		    $hidden = '';
		}
		$html .= '<li><a id="'.$type->slug.'" class="' . $hidden . '" href="#' . $type->slug . '" data-type="' . $type->slug . '">'
			. '<svg version="1.2" baseProfile="tiny" xmlns="http://www.w3.org/2000/svg" fill="'.$types[$type->slug]['color'].'" viewBox="0 0 50 50" overflow="inherit"><path d="M25.015 2.4c-7.8 0-14.121 6.204-14.121 13.854 0 7.652 14.121 32.746 14.121 32.746s14.122-25.094 14.122-32.746c0-7.65-6.325-13.854-14.122-13.854z"/></svg>'
			. '<span>' . $type->name . '</span></a></li>';
	    }
	    echo $html;
	?>

        </ul>
    </div>
    </div>
<?php




?>


    <script>



var markerGroups = <?php echo json_encode( $types, JSON_HEX_QUOT | JSON_HEX_TAG  ); ?>;

var map_markers = [];

var markers = <?php echo json_encode( $map_markers, JSON_HEX_QUOT | JSON_HEX_TAG ); ?>;

var bounds;
var map;



var map_style = [
    {
        "featureType": "landscape",
        "elementType": "all",
        "stylers": [
            {
                "weight": "1.71"
            },
            {
                "gamma": "1.48"
            },
            {
                "lightness": "-17"
            },
            {
                "saturation": "40"
            },
            {
                "hue": "#00ffdb"
            }
        ]
    },
    {
        "featureType": "landscape.man_made",
        "elementType": "all",
        "stylers": [
            {
                "saturation": "-50"
            },
            {
                "lightness": "-10"
            },
            {
                "gamma": "3.26"
            },
            {
                "weight": "3.00"
            }
        ]
    },
    {
        "featureType": "landscape.natural",
        "elementType": "geometry.fill",
        "stylers": [
            {
                "visibility": "on"
            },
            {
                "color": "#e0efef"
            }
        ]
    },
    {
        "featureType": "poi",
        "elementType": "geometry.fill",
        "stylers": [
            {
                "visibility": "on"
            },
            {
                "hue": "#1900ff"
            },
            {
                "color": "#c0e8e8"
            }
        ]
    },
    {
        "featureType": "road",
        "elementType": "all",
        "stylers": [
            {
                "saturation": "27"
            }
        ]
    },
    {
        "featureType": "road",
        "elementType": "geometry",
        "stylers": [
            {
                "lightness": 200
            },
            {
                "visibility": "simplified"
            }
        ]
    },
    {
        "featureType": "road",
        "elementType": "labels",
        "stylers": [
            {
                "visibility": "on"
            }
        ]
    },
    {
        "featureType": "transit.line",
        "elementType": "geometry",
        "stylers": [
            {
                "visibility": "on"
            },
            {
                "lightness": 700
            }
        ]
    },
    {
        "featureType": "water",
        "elementType": "all",
        "stylers": [
            {
                "color": "#77c1bc"
            },
            {
                "visibility": "on"
            },
            {
                "lightness": "13"
            }
        ]
    },
    {
        "featureType": "water",
        "elementType": "geometry",
        "stylers": [
            {
                "saturation": "19"
            },
            {
                "hue": "#00b2ff"
            },
            {
                "lightness": "-4"
            }
        ]
    },
    {
        "featureType": "water",
        "elementType": "geometry.fill",
        "stylers": [
            {
                "lightness": "31"
            }
        ]
    },
    {
        "featureType": "water",
        "elementType": "labels.text",
        "stylers": [
            {
                "color": "#3d3b38"
            },
            {
                "visibility": "simplified"
            },
            {
                "lightness": "20"
            }
        ]
    }
];



	window.onload = function() {

	    bounds = new google.maps.LatLngBounds();

     map = new google.maps.Map(document.getElementById('map'), {

		mapTypeId: google.maps.MapTypeId.roadmap,
		styles: map_style
	    });


function create_infowindow(i){
    var html = '<div class="infowindow">'+
            '<h2>'+markers[i].title+'</h2>'+infowindowContent(i)+infowindowNavigation(i)+
            '</div>';
    return html;
}

function infowindowContent(i){
    var html = '<div class="content">';
    if(markers[i].image){
	    html += '<img src="'+markers[i].image+'" class="post-thumbnail" >';
	}
	    html += markers[i].description+'</div>';


return html;
}


function infowindowNavigation(i){
    var next = '<a class="btn-next" href="javascript:myclick('+(i+1)+');"><i class="fa fa-long-arrow-right"></i></a>';
    var previous = '<a class="btn-previous" href="javascript:myclick('+(i-1)+');"><i class="fa fa-long-arrow-left"></i></a>';
     if( i > 0 && i < markers.length ){
	links = previous + next;
    }
    if( i == markers.length-1 ){
	links = previous;
    }
   
    if( i == 0  ){
	links = next;
    }
var html = '<div class="infowindow-navigation"><span class="infowindow-navigation-header">Places navigation </span>'+links+'</div>';
    return html;


}



	    var infowindow;
	    for (var i = 0; i < markers.length; i++) {
		var marker = new google.maps.Marker({
		    position: new google.maps.LatLng(markers[i].lat,markers[i].lng ),
		    map: map,
		    title: markers[i].title,
		    info: create_infowindow(i),
//                            '<div class="infowindow">'+
//      '<h2>'+markers[i].title+'</h2>'+
//      ''+
//      '<div class="content">'+
//      '<img src="'+markers[i].image+'" class="post-thumbnail" >'+markers[i].description+
//      '</div>'+
//      '<a href="javascript:myclick('+(i-1)+');">Poprzedni</a> <a class="btn_next" href="javascript:myclick('+(i+1)+');">Następny</a></div>',

		    icon: pinSymbol(markerGroups[markers[i].type].color),
		    type: markers[i].type
		});

		console.log(markers[i].type);

if(markerGroups[markers[i].type].default != 1){
    marker.setOptions({'opacity': 0.3,'strokeWeight':.1});
}

 bounds.extend(marker.position);

map_markers.push(marker);

markerGroups[markers[i].type].markers.push(marker);

		(function(marker, i) {




		    google.maps.event.addListener(marker, 'click', function(e) {

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

console.log(map_markers);

var toggle = (function toggle_markers(b){
var toggle_buttons = document.querySelectorAll(b);

for ( var i=0; i < toggle_buttons.length; i++ ) {

	    toggle_buttons[i].addEventListener( 'click', function(e){
e.preventDefault();

	var type = this.getAttribute('data-type');


for ( var i=0; i < toggle_buttons.length; i++ ) {
toggle_buttons[i].setAttribute('class', 'hidden');
  if(toggle_buttons[i].getAttribute('data-type') == type){
      toggle_buttons[i].setAttribute('class', '');
        }else{

    }

}









    for (var i = 0; i < map_markers.length; i++) {

        var marker = map_markers[i];

if(marker.type == type) {
     marker.setVisible(true);
     marker.setOptions({'opacity': 1, 'strokeWeight':1});

}else{
     //marker.setVisible(false);
    marker.setOptions({'opacity': 0.3,'strokeWeight':.1});
}


//	    if (!marker.getVisible()) {
//            marker.setVisible(true);
//        } else {
//            marker.setVisible(false);
//        }

	}

}, false);
};
})('#map-legend a');



var resizeBig = (function resizeBig(btn_class){
    var buttons = document.getElementsByClassName(btn_class);
    for ( var i = 0, length = buttons.length; i < length; i++ ) {
	buttons[i].addEventListener( 'click', function(e){
	    e.preventDefault();
	    //var container = this.getAttribute('data-map-container');
	    var c = document.getElementById(this.getAttribute('data-map-container'));
	    c.addClass('full');
	    google.maps.event.trigger( map, "resize" );
	    map.fitBounds(bounds);
	    this.style.display = 'none';
	    //c.firstElementChild.style.display = 'block';
		    c.children[0].style.display = 'block';

	}, false);
    }
})('resize-big');

var resizeSmall = (function resizeSmall(btn_class){
    var buttons = document.getElementsByClassName(btn_class);
    for ( var i = 0, length = buttons.length; i < length; i++ ) {
	buttons[i].addEventListener( 'click', function(e){
	    e.preventDefault();
	    var c = document.getElementById(this.getAttribute('data-map-container'));
	    c.removeClass('full');
	    google.maps.event.trigger( map, "resize" );
	    map.fitBounds(bounds);
	    this.style.display = 'none';
	    c.children[1].style.display = 'block';
	}, false);
    }
})('resize-small');

//
//var buttons = document.getElementsByClassName('btn_next');
//for ( var i = 0, length = buttons.length; i < length; i++ ) {
//    buttons[i].addEventListener( 'click', function(e){
//        console.log(this);
//    });
//
//    }


function myclick(index) {

   google.maps.event.trigger(map_markers[index],"click");
}


function pinSymbol(color) {

    return {
        path: 'M25 0c-8.284 0-15 6.656-15 14.866 0 8.211 15 35.135 15 35.135s15-26.924 15-35.135c0-8.21-6.716-14.866-15-14.866zm-.049 19.312c-2.557 0-4.629-2.055-4.629-4.588 0-2.535 2.072-4.589 4.629-4.589 2.559 0 4.631 2.054 4.631 4.589 0 2.533-2.072 4.588-4.631 4.588z',
        fillColor: color,
        fillOpacity: 1,
        strokeColor: '#fff',
        strokeWeight: 2,
        scale: .9,
   };
}


var showhide = (function showhide_markers(b){
var toggle_buttons = document.querySelectorAll(b);
for ( var i=0; i < toggle_buttons.length; i++ ) {

	    toggle_buttons[i].addEventListener( 'click', function(e){

    var type = this.getAttribute('data-type');
if(this.classList.contains('hidden')){
    this.classList.remove('hidden');
}else{
    this.classList.add('hidden');
    }


    for (var i = 0; i < markerGroups[type].markers.length; i++) {

        var marker = markerGroups[type].markers[i];

	    if (!marker.getVisible()) {
            marker.setVisible(true);
        } else {
            marker.setVisible(false);
        }

	}

}, false);
};
})('s#map-legend a');


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
 

}