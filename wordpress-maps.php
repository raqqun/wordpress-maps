<?php

/*
 * Plugin Name: Wordpress Maps
 * Plugin URI:  http://prod.simplon.co/
 * Description: This plugin provides the ability to pin point custom post types content as maps descriptions
 * Version:     0.1
 * Author:      Alexandros Nikiforidis
 * Author URI:  http://raqqun0101.net
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 2    as published by
 * the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */


if ( file_exists( dirname( __FILE__ ) . '/library/CMB2/init.php' ) ) {
    require_once 'library/CMB2/init.php';
    require_once 'library/smp_wp_maps_cmb2.php';
    new smp_wp_maps_cmb2();
} else {
    add_action( 'admin_notices', 'cmb2_example_plugin_missing_cmb2' );
}


function cmb2_example_plugin_missing_cmb2() { ?>
<div class="error">
    <p><?php echo 'CMB2 Example Plugin is missing CMB2!'; ?></p>
</div>
<?php }


class smp_wordpress_maps {

    private $access_token;

    private $mapbox_map_id;

    private $map_element_id;

    function __construct() {

        $this->access_token = get_option('mapbox_api_token');
        $this->mapbox_map_id = get_option('mapbox_map_id');
        $this->map_element_id = get_option('map_element_id');
        register_activation_hook( __FILE__, array( $this, 'smp_wordpress_maps_activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'smp_wordpress_maps_deactivate' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'map_box_js' ), 10 );
        add_shortcode( 'smp-maps', array ( $this, 'smp_wordpress_maps_shortcode' ) );
        add_action( 'init', array( $this, 'create_places_post_type' ) );
        add_action( 'admin_menu', array( $this, 'smp_wordpress_maps_menu' ) );
        add_action( 'add_meta_boxes_places', array( $this, 'smp_wordpress_maps_add_meta_box' ) );
        add_action( 'save_post', array( $this, 'smp_wordpress_maps_places_save' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_map_box_js' ) );
    }

    public function smp_wordpress_maps_places_save( $post_id, $post ) {
        if ( 'places' != $post->post_type ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! isset( $_POST['map_box_address'] ) &&
             ! isset( $_POST['map_box_geolocation_lat'] ) &&
             ! isset( $_POST['map_box_geolocation_lng'] )
           )
        {
            return;
        }

        update_post_meta( $post_id, 'map_box_address', sanitize_text_field( $_POST['map_box_address'] ) );
        update_post_meta( $post_id, 'map_box_geolocation_lat', sanitize_text_field( $_POST['map_box_geolocation_lat'] ) );
        update_post_meta( $post_id, 'map_box_geolocation_lng', sanitize_text_field( $_POST['map_box_geolocation_lng'] ) );
    }

    public function smp_wordpress_maps_add_meta_box() {
        add_meta_box(
            'smp_wordpress_maps_geolocation',
            'Wordpress Maps Geolocation',
            array( $this, 'smp_wordpress_maps_geolocation_metabox_callback'),
            'places'
        );
    }

    public function smp_wordpress_maps_geolocation_metabox_callback( $post ) { ?>
        <style>
            #map-box-geolocation-list {
                background-color: #cccccc;
                width: 400px;
                /*height: 150px;*/
                position: relative;
                top: -15px;
                left: 137px;
                /*display: none;*/
            }
            .map-box-geolocation-item {
                border-bottom: 1px solid #ffffff;
                margin: 0;
                padding: 5px;
                cursor: pointer;
            }
            .map-box-geolocation-item:hover {
                background-color: rgba(255, 255, 255, 0.8);
            }
            .map-box-geolocation-item > p {
                margin: 0;
            }
            .map-box-geolocation-item > span {
                display: none;
            }
            #map_box_address {
                width: 400px;
            }
        </style>
        <label for="map_box_address">What's your address ?</label>
        <input type="text" id="map_box_address" name="map_box_address" value="<?php echo get_post_meta( $post->ID, 'map_box_address', true ) ?>" placeholder="What's your address ?">
        <input type="hidden" id="map_box_geolocation_lat" name="map_box_geolocation_lat" value="<?php echo get_post_meta( $post->ID, 'map_box_geolocation_lat', true ) ?>">
        <input type="hidden" id="map_box_geolocation_lng" name="map_box_geolocation_lng" value="<?php echo get_post_meta( $post->ID, 'map_box_geolocation_lng', true ) ?>">
        <ul id="map-box-geolocation-list">

        </ul>
        <script>
            L.mapbox.accessToken = '<?php echo $this->access_token ?>';

            var geocoder = L.mapbox.geocoder('mapbox.places');

            jQuery('#map_box_address').on('keypress', function(e) {
                if (e.keyCode == 13) {
                    e.preventDefault();
                    var place = jQuery(this).val();
                    if ( place.length != 0 ) {
                        geocoder.query(place, geoHandler);
                    }
                }
            });


            function geoHandler(error, data) {
                var placeslist = "";
                for (var i = 0; i < data.results.features.length; i++) {
                    var place = data.results.features[i].place_name;
                    placeslist += "<li class='map-box-geolocation-item'>";
                    placeslist += "<p>" + place + "</p>";
                    placeslist += "<span id='lat'>" + data.results.features[i].geometry.coordinates[1] + "</span>";
                    placeslist += "<span id='lng'>" + data.results.features[i].geometry.coordinates[0] + "</span>";
                    placeslist += "</li>";

                };

                jQuery('#map-box-geolocation-list').html(placeslist);
                jQuery('.map-box-geolocation-item').on('click', function (e) {
                    var place = jQuery(this).find('p').text();
                    var lat = jQuery(this).find('#lat').text();
                    var lng = jQuery(this).find('#lng').text();
                    jQuery('#map_box_address').val(place);
                    jQuery('#map-box-geolocation-list').html('');
                    jQuery('#map_box_geolocation_lat').val(lat);
                    jQuery('#map_box_geolocation_lng').val(lng);
                });
            }
        </script>
    <?php
    }

    public function smp_wordpress_maps_menu() {
        $hook_suffix = add_options_page(
            'Wordpress Maps',
            'Wordpress Maps Setup',
            'manage_options',
            'smp_wordpress_maps',
            array (
                $this,
                'smp_wordpress_maps_menu_options'
            )
        );
        add_action( 'load-' . $hook_suffix , array( $this, 'smp_wordpress_maps_menu_handler' ) );
    }

    public function smp_wordpress_maps_menu_options() {

        echo '<div class="wrap">';
        echo '<h2>Wordpress Maps Setup Page</h2>';
        echo '</div>';

        if (!get_option('mapbox_api_token')) {
            echo "<div id='notice' class='updated fade'><p>Wordpress Maps is not configured yet. Please do it now.</p></div>\n";
        }
        ?>

        <form id='smp_wordpress_maps_form' method='POST' action=''>
            <table class='form-table'>
                <tr>
                    <th scope='row'>
                        <label for='mapbox_api_token'>MapBox.js api token</label>
                    </th>
                    <td>
                        <input id='mapbox_api_token' type='text' name='mapbox_api_token' value='<?php echo get_option('mapbox_api_token') ?>' placeholder='MapBox.js api token' />
                        <p class="description">You api access token. You can to get this from <a target="_blank" href="https://www.mapbox.com/projects/">www.mapbox.com</a></p>
                    </td>
                </tr>
                <tr>
                    <th scope='row'>
                        <label for='mapbox_map_id'>MapBox.js map id</label>
                    </th>
                    <td>
                        <input id='mapbox_map_id' type='text' name='mapbox_map_id' value='<?php echo get_option('mapbox_map_id') ?>' placeholder='MapBox.js map id' />
                        <p class="description">The id of your mapbox project. You can get this from <a target="_blank" href="https://www.mapbox.com/projects/">www.mapbox.com</a></p>
                    </td>
                </tr>
                <tr>
                    <th scope='row'>
                        <label for='map_element_id'>MapBox.js element id</label>
                    </th>
                    <td>
                        <input id='map_element_id' type='text' name='map_element_id' value='<?php echo get_option('map_element_id') ?>' placeholder='MapBox.js element id' />
                        <p class="description">The id attribute of your html element to transform in to an actual map.</p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="">Map ShortCode to place in page or post content.</label>
                    </th>
                    <td>
                        <p><?php echo "[smp-maps width='100%' height='600px']" ?></p>
                        <p class="description">You can change width and height attributes to your own needs.</p>
                    </td>
                </tr>
            </table>
            <p>
                <?php submit_button() ?>
            </p>
        </form>
        <?php

    }

    public function smp_wordpress_maps_menu_handler() {
        if ( !empty( $_POST['mapbox_api_token'] ) &&
             !empty( $_POST['mapbox_map_id'] ) &&
             !empty( $_POST['map_element_id'] ) )
        {
            update_option('mapbox_api_token', $_POST['mapbox_api_token'], true);
            update_option('mapbox_map_id', $_POST['mapbox_map_id'], true);
            update_option('map_element_id', $_POST['map_element_id'], true);
        }
    }

    public function smp_wordpress_maps_shortcode( $atts ) {
        $map_atts = shortcode_atts( array(
            'width'     =>  '100%',
            'height'    =>  '600px'
        ), $atts );

        $places = get_posts( array( 'post_type' => 'places', 'nopaging' => true ) );
        $places_lat_lng = array();
        $places_content = array();

        foreach ( $places as $place ) {
            global $post;
            $post = $place;
            setup_postdata( $post );
            $place_lat_lng = array(
                get_post_meta( get_the_ID(), 'map_box_geolocation_lat', true ),
                get_post_meta( get_the_ID(), 'map_box_geolocation_lng', true ),
                get_post_meta( get_the_ID(), '_smp_wp_maps_color', true )
            );
            $place_content_map = array(
                'title'     =>  get_the_title(),
                'thumbnail' =>  get_the_post_thumbnail(),
                'content'   =>  get_the_content(),
                'excerpt'   =>  get_the_excerpt(),
                'metas'     =>  array (

                )
            );
            wp_reset_postdata();
            $place_content = apply_filters( 'smp_map_places_content', $place_content_map, $place->ID );
            array_push($places_lat_lng, $place_lat_lng);
            array_push($places_content, $place_content);
        }

        return
            "<div id='" . $this->map_element_id . "' style='position:relative; width:" . $map_atts['width'] . "; height:" . $map_atts['height'] . ";'></div>
            <script>
                L.mapbox.accessToken = '" . $this->access_token . "';
                var map = L.mapbox.map(
                    'smp-maps',
                    'simplonprod.o2j20p28'
                );
                var placesLatLng = " . json_encode($places_lat_lng) . ";
                var placesContent = " . json_encode($places_content) . ";
            </script>
            ";
    }

    public function create_places_post_type() {
        register_post_type(
            'places',
            array(
                'labels'            => array(
                    'name'          => __( 'Places' ),
                    'singular_name' => __( 'Place' )
                ),
                'public'            => true,
                'has_archive'       => true,
                'taxonomies'        => array('regions', 'marker_colors'),
                'supports'          => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields'),
                'capability_type'   => array('place', 'places'),
                'map_meta_cap'      => true
            )
        );
        register_taxonomy(
            'place_category',
            'places',
            array(
                'label'             =>  'Places Categories',
                'public'            =>  false,
                'hierarchical'      =>  true,
                'show_ui'           =>  true,
                'show_admin_column' =>  true,
                'rewrite'           =>  false,
                'capabilities'      =>  array(
                    'manage_terms'  =>  'manage_place_category',
                    'edit_terms'    =>  'manage_place_category',
                    'delete_terms'  =>  'manage_place_category',
                    'assign_terms'  =>  'edit_places'
                )
            )
        );
    }

    public function admin_map_box_js( $hook ) {
        if ( $hook == 'post.php' || $hook == 'post-new.php') {
            $this->map_box_js();
        }
        return;
    }

    public function map_box_js() {
        if ( !is_admin() ) {
            wp_enqueue_script(
                'smp_wordpress_maps_js',
                plugins_url( '/js/smp-wordpress-maps.js', __FILE__ ),
                array('mapboxjs'),
                '0.1',
                true
            );
            wp_enqueue_style(
                'mapboxcss',
                'https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.css',
                array(),
                '2.2.3',
                'all'
            );
            wp_enqueue_style(
                'smpwpmapscss',
                plugins_url( '/css/smp-wordpress-maps.css', __FILE__ ),
                array(),
                '0.1',
                'all'
            );
        }
        wp_enqueue_script(
            'mapboxjs',
            'https://api.mapbox.com/mapbox.js/v2.2.3/mapbox.js',
            array(),
            '2.2.3',
            false
        );
    }

    public function smp_wordpress_maps_add_role_caps() {

        // Add the roles you'd like to administer the custom post types
        $roles = array('smp_map_manager','editor','administrator');

        // Loop through each role and assign capabilities
        foreach($roles as $the_role) {

            $role = get_role($the_role);

            if ( $the_role == 'smp_map_manager' ) {
                $role->add_cap( 'read' );
            }

            $role->add_cap( 'manage_place_category' );
            $role->add_cap( 'read_place');
            $role->add_cap( 'read_private_places' );
            $role->add_cap( 'edit_place' );
            $role->add_cap( 'edit_places' );
            $role->add_cap( 'edit_others_places' );
            $role->add_cap( 'edit_published_places' );
            $role->add_cap( 'publish_places' );
            $role->add_cap( 'delete_others_places' );
            $role->add_cap( 'delete_private_places' );
            $role->add_cap( 'delete_published_places' );
        }
    }

    public function smp_wordpress_maps_remove_role_caps() {

        // Add the roles you'd like to administer the custom post types
        $roles = array('smp_map_manager','editor','administrator');

        // Loop through each role and assign capabilities
        foreach($roles as $the_role) {

            $role = get_role($the_role);

            if ( $the_role == 'smp_map_manager' ) {
                $role->remove_cap( 'read' );
            }

            $role->remove_cap( 'manage_place_category' );
            $role->remove_cap( 'read_place');
            $role->remove_cap( 'read_private_places' );
            $role->remove_cap( 'edit_place' );
            $role->remove_cap( 'edit_places' );
            $role->remove_cap( 'edit_others_places' );
            $role->remove_cap( 'edit_published_places' );
            $role->remove_cap( 'publish_places' );
            $role->remove_cap( 'delete_others_places' );
            $role->remove_cap( 'delete_private_places' );
            $role->remove_cap( 'delete_published_places' );
        }
    }

    public function smp_wordpress_maps_activate() {
        add_role(
            'smp_map_manager',
            'Map Manager',
                array(
                    'read' => true,
                    'edit_places' => false,
                    'delete_places' => false,
                    'publish_places' => false,
                    'upload_files' => true
                )
        );
        $this->create_places_post_type();
        $this->smp_wordpress_maps_add_role_caps();
        flush_rewrite_rules();
    }

    public function smp_wordpress_maps_deactivate() {
        $this->smp_wordpress_maps_remove_role_caps();
        remove_role('smp_map_manager');
    }

}

global $smp_wordpress_maps;
$smp_wordpress_maps = new smp_wordpress_maps();



add_filter( 'smp_map_places_content', 'change_place_content', 10, 2 );
function change_place_content( $place_content, $place_id ) {


    $html = "
        <div>
            <p>{$place_content['title']}</p>
            <p>{$place_content['thumbnail']}</p>
            <p>{$place_content['excerpt']}</p>
            <p>{$place_content['content']}</p>
            <p>Calendrier : ".
                get_post_meta($place_id, '_smp_wp_maps_calendar', true) ."</p>
            <p>Partenaire/porteur du projet : ".
                get_post_meta($place_id, '_smp_wp_maps_partnership', true) ."</p>
            <p>Statut des candidatures : ".
                (get_post_meta($place_id, '_smp_wp_maps_candidature_status', true) == 'on'
                    ? '<span class="_smp_wp_maps_candidature_status on"></span>'
                    : '<span class="_smp_wp_maps_candidature_status off"></span>'
                )
            ."</p>
            <p><a target='_blank' href='".
                get_post_meta($place_id, '_smp_wp_maps_candidature_link', true) ."'>Lien candidatures</a></p>
            <p><a target='_blank' href='".
                get_post_meta($place_id, '_smp_wp_maps_place_link', true) ."'>Site web de la formation</a></p>
        </div>
    ";

    return $html;
}
