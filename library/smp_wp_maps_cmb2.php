<?php


class smp_wp_maps_cmb2 {

    private $meta_prefix = '_smp_wp_maps_';


    function __construct() {
        add_action( 'cmb2_admin_init', array( $this, 'smp_wp_maps_metabox' ) );
    }


    public function smp_wp_maps_metabox() {
        $cmb = new_cmb2_box( array(
            'id'            =>  'smp_wp_maps_metabox',
            'title'         =>  'Wordpress Maps Fields',
            'object_types'  =>  array('places'),
            'context'       =>  'normal',
            'priority'      =>  'high',
            'show_names'    =>  true
        ) );

        $this->smp_wp_maps_metafields($cmb);
    }


    private function smp_wp_maps_metafields($cmb) {

        $cmb->add_field( array(
            'name'  =>  'Ville',
            'desc'  =>  'The Name of the city',
            'id'    =>  $this->meta_prefix . 'city',
            'type'  =>  'text'
        ) );

        $cmb->add_field( array(
            'name'  =>  'Calendrier',
            'desc'  =>  '',
            'id'    =>  $this->meta_prefix . 'calendar',
            'type'  =>  'textarea'
        ) );

        $cmb->add_field( array(
            'name'    => 'Couleur du Markeur',
            'id'      => $this->meta_prefix .  'color',
            'type'    => 'colorpicker',
            'default' => '#ffffff',
        ) );

        $cmb->add_field( array(
            'name'  =>  'Partenaire/porteur du projet',
            'desc'  =>  '',
            'id'    =>  $this->meta_prefix . 'partnership',
            'type'  =>  'text'
        ) );

        $cmb->add_field( array(
            'name'  =>  'Statut des candidatures',
            'desc'  =>  '',
            'id'    =>  $this->meta_prefix . 'candidature_status',
            'type'  =>  'checkbox'
        ) );

        $cmb->add_field( array(
            'name'  =>  'Page de candidature',
            'desc'  =>  '',
            'id'    =>  $this->meta_prefix . 'candidature_link',
            'type'  =>  'text_url'
        ) );

        $cmb->add_field( array(
            'name'  =>  'Site web de la formation',
            'desc'  =>  'The Name of the city',
            'id'    =>  $this->meta_prefix . 'place_link',
            'type'  =>  'text_url'
        ) );


        $cmb->add_field( array(
            'name'  =>  'Facebook',
            'desc'  =>  'Lien facebook',
            'id'    =>  $this->meta_prefix . 'facebook_link',
            'type'  =>  'text_url'
        ) );

        $cmb->add_field( array(
            'name'  =>  'Twitter',
            'desc'  =>  'Lien Twitter',
            'id'    =>  $this->meta_prefix . 'twitter_link',
            'type'  =>  'text_url'
        ) );
    }
}