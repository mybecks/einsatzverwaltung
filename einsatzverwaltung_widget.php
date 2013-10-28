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
			array( 'description' => __('Anzahl Eins&auml;tze im Jahr', 'einsatzverwaltung_textdomain'), ) // Args
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
		
		global $wpdb;

		// $wpdb->show_errors();

		$table_missions = $wpdb->prefix . "einsaetze";
		
		$sql = "SELECT count(id) FROM ".$table_missions." WHERE YEAR(alarmierung_date) = Year(CURDATE())";
		$count = $wpdb->get_var($sql);

		// $wpdb->print_error();

		$html = "<aside id='einsatzverwaltungs_widget' class='widget'>".
					"<h3 class='widget-title'>Einsätze im laufenden Jahr</h3>".
					"<p style='margin-left:3em; font-size: 2em; font-style:bold;'>".$count."</p>".
				"</aside>";
		print($html);
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

add_action( 'widgets_init', 'einsatzverwaltung_widget_init' );
?>
