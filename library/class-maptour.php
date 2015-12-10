<?php

class Maptour {

    private
	$plugin_name	= 'maptour',
	$secret		= 'RAJmIEgoEQATUufRPDov',
	$post_type	= 'map_place';

    /**
     * initialize
     */
    public function __construct() {
	add_action( 'init', array( $this, 'register_post_place' ) );
	add_action( 'init', array( $this, 'register_taxonomy_place' ) );
	add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
	add_shortcode( 'get_map', array( $this, 'get_map' ) );
	if ( is_admin() ) {
	    $this->admin_setup();
	}
    }


    public function admin_setup() {
	add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
	add_action( 'save_post', array( $this, 'save_meta_box' ) );
    }

    public function enqueue_frontend_scripts() {
	wp_enqueue_script( 'maps', 'http://maps.google.com/maps/api/js', array(), true );
	wp_enqueue_style( 'maptour',  MAPTOUR_PLUGIN_URL . 'css/maptour.css' );
	wp_enqueue_style( 'font-awesome',  'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css' );
	$this->_enqueue_map_styles_js();
	wp_enqueue_script( 'maptour.app', MAPTOUR_PLUGIN_URL . 'js/frontend-map.min.js' , array( 'jquery', 'maps' ), MAPTOUR_VERSION, true );
    }

    public function admin_enqueue_scripts() {
	wp_enqueue_script( 'maps', 'https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&sensor=false' );
	wp_enqueue_script( 'field-map', plugins_url( '../js/backend-map.min.js', __FILE__ ), array( 'jquery' ), MAPTOUR_VERSION );
	wp_localize_script( 'field-map', 'geocode_notfound', __( 'No results were found for the search criteria', 'mt' ) );
    }

    public function register_post_place() {
	$labels = array(
	    'name'		    => __( 'Places', 'mt' ),
	    'singular_name'	    => __( 'Place', 'mt' ),
	    'add_new'		    => __( 'Add New', 'mt' ),
	    'add_new_item'	    => __( 'Add New place', 'mt' ),
	    'edit_item'		    => __( 'Edit place', 'mt' ),
	    'new_item'		    => __( 'New place', 'mt' ),
	    'view_item'		    => __( 'View place', 'mt' ),
	    'search_items'	    => __( 'Search places', 'mt' ),
	    'not_found'		    => __( 'No placess found', 'mt' ),
	    'not_found_in_trash'    => __( 'No places found in Trash', 'mt' ),
	    'parent_item_colon'	    => __( 'Parent place:', 'mt' ),
	    'menu_name'		    => __( 'Map places', 'mt' ),
	);
	$args = array(
	    'labels'		    => $labels,
	    'hierarchical'	    => false,
	    'description'	    => __( 'places', 'mt' ),
	    'supports'		    => array( 'title', 'editor','custom-fields', 'thumbnail' ),
	    'public'		    => true,
	    'show_ui'		    => true,
	    'show_in_menu'	    => true,
	    'menu_position'	    => 6,
	    'show_in_nav_menus'	    => true,
	    'publicly_queryable'    => true,
	    'exclude_from_search'   => true,
	    'has_archive'	    => false,
	    'query_var'		    => true,
	    'can_export'	    => true,
	    'rewrite'		    => true,
	    'capability_type'	    => 'post'
	);
	register_post_type( $this->post_type, $args );
    }

    public function register_taxonomy_place() {
	$labels = array(
		'name'                       => _x( 'Groups', 'mt' ),
		'singular_name'              => _x( 'Group', 'mt' ),
		'search_items'               => __( 'Search Groupss', 'mt' ),
		'popular_items'              => __( 'Popular Groups', 'mt' ),
		'all_items'                  => __( 'All Groupss', 'mt' ),
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => __( 'Edit Group', 'mt' ),
		'update_item'                => __( 'Update Group', 'mt' ),
		'add_new_item'               => __( 'Add New Group', 'mt' ),
		'new_item_name'              => __( 'New Group Name', 'mt' ),
		'separate_items_with_commas' => __( 'Separate groupss with commas', 'mt' ),
		'add_or_remove_items'        => __( 'Add or remove groups', 'mt' ),
		'choose_from_most_used'      => __( 'Choose from the most used groups', 'mt' ),
		'not_found'                  => __( 'No groups found.', 'mt' ),
		'menu_name'                  => __( 'Groups', 'mt' ),
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

    private function get_place_types() {
	return get_terms( 'place_type' );
    }

    private function get_map_places() {
	$map_args  = array(
	    'post_type'		=> 'map_place',
	    'post_status'	=> 'publish',
	    'posts_per_page'	=> 100,
	    'meta_query'	=> array(
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
		$image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'thumbnail' );
		$map_markers[$i]['image'] = $image[0];
		$map_markers[$i]['url'] = get_permalink();
		$type = wp_get_post_terms( get_the_ID(), 'place_type', array( 'fields' => 'slugs') );
		$map_markers[$i]['type'] = $type[0];
		$i++;
	    }
	    return $map_markers;
	}
    }

    private function _enqueue_map_places_js() {
	$map_markers = $this->get_map_places();
	$markers_json = array(
	    'l10n_print_after' => 'var markers = ' . json_encode( $map_markers ) . ';'
	);
	wp_localize_script( 'maptour.app', 'markers', $markers_json );
    }

    private function _enqueue_map_styles_js() {
	if ( file_exists(get_template_directory() . '/map-style.js' ) ) {
	    wp_enqueue_script( 'maptour.mapstyle', get_bloginfo( 'template_directory' ) . '/map-style.js' , array( 'jquery', 'maps' ), MAPTOUR_VERSION, true );
	}
    }

    function get_map() {
	$place_types = get_terms( 'place_type' );
	$this->_enqueue_map_places_js();
	$html = '<div id="map-pums" class="container-map">'
		. '<a href="" class="resize-small" data-map-container="map-pums"><i class="fa fa-times"></i></a>'
		. '<a href="" class="resize-big" data-map-container="map-pums">' . __('Bigger map ', 'mt') . '<i class="fa fa-arrows-alt"></i></a>'
		. '<div id="map"></div>'
		. '<div class="siderbarmap">'
		. '<ul id="map-legend">';
	foreach ( $place_types as $type ) {
	    $types[$type->slug]['markers'] = array();
	    $type_description = explode( ',', $type->description );
	    $types[$type->slug]['color'] = $type_description[0];
	    $types[$type->slug]['default'] = isset( $type_description[1] ) ? 1 : 0;
	    $hidden = 'hidden';
	    if ( isset( $type_description[1] ) && $type_description[1] == 'default' ) {
		$hidden = '';
	    }
	    $html .= '<li><a id="' . $type->slug . '" class="' . $hidden . '" href="#' . $type->slug . '" data-type="' . $type->slug . '">'
		    . '<svg version="1.2" baseProfile="tiny" xmlns="http://www.w3.org/2000/svg" fill="'.$types[$type->slug]['color'].'" viewBox="0 0 50 50" overflow="inherit"><path d="M25.015 2.4c-7.8 0-14.121 6.204-14.121 13.854 0 7.652 14.121 32.746 14.121 32.746s14.122-25.094 14.122-32.746c0-7.65-6.325-13.854-14.122-13.854z"/></svg>'
		    . '<span>' . $type->name . '</span></a></li>';
	}
	$html .= '</ul></div></div>';
	$markerGroups_json = array(
	    'l10n_print_after' => 'markerGroups = ' . json_encode( $types  ) . ';'
	);
	wp_localize_script( 'maptour.app', 'markerGroups', $markerGroups_json );
	return $html;
    }
}