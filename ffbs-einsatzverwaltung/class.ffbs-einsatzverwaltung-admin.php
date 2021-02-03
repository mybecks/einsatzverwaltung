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

	public function handle_vehicles() { ?>
		<div class="wrap">
			<h2>Fahrzeugverwaltung</h2>
			<div id="message" class="updated">Added new vehicle</div>

            <div>
			    <?php $this->display_vehicles(); ?>
            </div>

			<form method="POST" action="">
                <div class="form-group col-sm-7">
                    <label for="vehicle_radio_id">Funkruf Name</label>
                    <input id="vehicle_radio_id" class="form-control" name="vehicle_radio_id" placeholder="Bsp. 4/42"/>
                </div>

                <div class="form-group col-sm-7">
                    <label for="new_vehicle">Beschreibung</label>
                    <input id="new_vehicle" class="form-control" name="add_new_vehicle" placeholder="Bsp. LF 8/10"/>
                </div>

                <div class="form-group col-sm-7">
                    <label for="vehicle_location">Standort</label>
				    <select class="form-control" id="vehicle_location">
                        <option>Mingolsheim</option>
                        <option>Langenbrücken</option>
                    </select>
                </div>

                <!-- https://wordpress.stackexchange.com/questions/235406/how-do-i-select-an-image-from-media-library-in-my-plugin -->
                <div class="form-group col-sm-7">
                    <label for="vehicle_image">Mediathek Bild</label>
				    <input id="vehicle_image" class="form-control" name="vehicle_image" />
                </div>
                <div class="col-sm-7">
				    <button type="submit" class="btn btn-primary">Add</button>
                </div>
			</form>

		</div>
		<?php
	}

	public function display_vehicles() {
		$vehicles = $this->db_handler->load_vehicles();

		?>
		<table class="tab-vehicle table">
			<thead>
                <tr>
                    <th scope="col">Funkruf Name</th>
                    <th scope="col">Beschreibung</th>
                    <th scope="col">Standort</th>
                    <th scope="col">Edit</th>
                    <th scope="col">Delete</th>
                </tr>
            </thead>
            <tbody>
		<?php
		// http://codex.wordpress.org/AJAX_in_Plugins
		foreach ( $vehicles as $vehicle ) {?>
			<tr>
                <td scope="row">
                    <?php $vehicle->radio_id; ?>
                </td>
                <td>
                    <?php $vehicle->description; ?>
                </td>
                <td>
                    <?php $vehicle->location; ?>
                </td>
                <td>
                    <i class="fas fa-edit"></i>
                </td>
                <td>
                    <i class="fas fa-trash-alt"></i>
                </td>
			</tr>
		<?php } ?>
            </tbody>
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
}
?>
