<?php
/**
 * Initializing Admin Menu
 *
 * @author Andre Becker
 * */

// http://wp.tutsplus.com/articles/tips-articles/quick-tip-conditionally-including-js-and-css-with-get_current_screen/?search_index=6
class EinsatzverwaltungAdmin {
	protected $pluginPath;
    private $db_handler;

	public function __construct() {
		$this->pluginPath = dirname(__FILE__);
		$this->db_handler = DatabaseHandler::get_instance();

		add_action( 'admin_print_styles', array( $this, 'add_admin_styles') );
        add_action( 'admin_enqueue_scripts', array( $this,'add_admin_scripts') );
        add_action( 'wp_ajax_add_vehicle', array( $this, 'add_vehicle' ) );
		add_action( 'wp_ajax_nopriv_add_vehicle', array( $this, 'add_vehicle' ) );
	}

	public function add_admin_styles() {
		wp_register_style( 'admin_styles', plugins_url( 'css/admin.css', __FILE__ ) );
        wp_register_style( 'admin_bootstrap', plugins_url( 'css/bootstrap.css', __FILE__ ) );
        wp_register_style( 'admin_fa', plugins_url( 'css/all.css', __FILE__ ) );

        wp_enqueue_style( 'admin_styles' );
        wp_enqueue_style( 'admin_bootstrap' );
        wp_enqueue_style( 'admin_fa' );
	}

    public function add_admin_scripts( ) {
        wp_enqueue_script( 'admin_scripts', plugins_url( 'js/functions.admin.js', __FILE__ ), array('jquery') );
        wp_localize_script('admin_scripts', 'ajax_var', array( 'nonce' => wp_create_nonce( 'ajax-nonce' ) ) );
    }

	public function handle_vehicles() {
		?>
		<div class="wrap">
			<h2>Fahrzeugverwaltung</h2>
			<div id="message" class="updated">Added new vehicle</div>

			<?php $this->display_vehicles(); ?>

			<form method="POST" action="">

				<label for="new_vehicle">
				    <?php _e( "Neues Fahrzeug hinzuf&uuml;gen", 'einsatzverwaltung_textdomain' ); ?>
                </label>
                <input id="new_vehicle" name="add_new_vehicle" />


                <label for="vehicle_radio_id">Funkruf Name</label>
				<input id="vehicle_radio_id" name="vehicle_radio_id" />

                <label for="vehicle_location">Abteilung</label>
				<input id="vehicle_location" name="vehicle_location" />

				<input type="submit" value="add" class="add-vehicle button-primary">
			</form>
		</div>
		<?php
	}

	public function display_vehicles() {
		$vehicles = $this->db_handler->load_vehicles();

		?>
		<table class="tab-vehicle" border="1">
			<tr>
				<th>ID</th>
				<th>Beschreibung</th>
				<th>Funkruf Name</th>
				<th>Abteilung</th>
				<th>Edit</th>
				<th>Delete</th>
			</tr>

		<?php
		// http://codex.wordpress.org/AJAX_in_Plugins
		foreach ( $vehicles as $vehicle ) {
			echo '	<tr>';
			echo '		<td>';
			echo            $vehicle->id;
			echo '		</td>';
			echo '		<td>';
			echo            $vehicle->description;
			echo '		</td>';
			echo '		<td>';
			echo            $vehicle->radio_id;
			echo '		</td>';
			echo '		<td>';
			echo            $vehicle->location;
			echo '		</td>';
			echo '		<td>';
			echo '			<i class="fas fa-edit"></i>';
			echo '		</td>';
			echo '		<td>';
			echo '			<i class="fas fa-trash-alt"></i>';
			echo '		</td>';
			echo '	</tr>';
		}
		?>
		</table>
		<?php
	}

	public function add_vehicle() {
		$nonce = $_POST['nonce'];

		if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ) {
			die ( 'Busted!');
		}

		//add new vehicle to database
		$this->db_handler->admin_insert_vehicle( $_POST['vehicle'] );
		$id = $this->db_handler->get_last_insert_id();
		$vehicle = (object) array( 'id' => $id,
								'description' => $_POST['vehicle'] );

		$response = json_encode( $vehicle );

		// response output -> sent back to javascript file
		header( "Content-Type: application/json" );
		echo $response;

		wp_die();
	}

	public function import_missions() {
	   //http://html5demos.com/dnd-upload#view-source
    	?>
    	<div class="wrap">
    	    <h2>Mass Importer ALPHA</h2>
    	    <style>

    		</style>
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
                    <div class="holder"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
                    <p>Upload progress: <progress id="uploadprogress" min="0" max="100" value="0">0</progress></p>
                </div>
            </div>

            <p id="upload" class="hidden"><label>Drag & drop not supported, but you can still upload via this input field:<br><input type="file"></label></p>
            <p id="filereader">File API & FileReader API not supported</p>
            <p id="formdata">XHR2's FormData is not supported</p>
            <p id="progress">XHR2's upload progress isn't supported</p>
    		<p>Drag an image from your desktop on to the drop zone above to see the browser both render the preview, but also upload automatically to this server.</p>
    		<!-- </article> -->

    	</div>
    	<?php
    }
}

$wpEinsatzverwaltungAdmin = new EinsatzverwaltungAdmin();


/*
 * End Admin Menu
 */

?>
