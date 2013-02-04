<?php

/**
 * Widget class
 * 
 * @author Andre Becker
 **/
class Einsatzverwaltung_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
	 		'einsatzverwaltung_widget', // Base ID
			'Einsatzverwaltung', // Name
			array( 'description' => __( 'Anzahl Einsätze im Jahr', 'text_domain' ), ) // Args
		);
	}

 	public function form( $instance ) {
		// outputs the options form on admin
	}

	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
	}

	public function widget( $args, $instance ) {
		// outputs the content of the widget
	}

}

/**
 * Initialize Widget
 * 
 * @author Andre Becker
 **/
function einsatzverwaltung_widget_init() {
  register_widget( 'Einsatzverwaltung_Widget' );
}

?>