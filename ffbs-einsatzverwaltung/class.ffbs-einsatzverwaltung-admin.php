<?php

/**
 * Initializing Admin Menu
 *
 * @author Andre Becker
 * */

// http://wp.tutsplus.com/articles/tips-articles/quick-tip-conditionally-including-js-and-css-with-get_current_screen/?search_index=6
class EinsatzverwaltungAdmin
{
    protected $pluginPath;
    private $db_handler;

    const VENDOR = 'ffbs';
    const ROUTE_VEHICLES = '/vehicles';

    public function __construct()
    {
        $this->pluginPath = dirname(__FILE__);
        $this->db_handler = DatabaseHandler::get_instance();

        add_action('admin_print_styles', array($this, 'add_admin_styles'));
        add_action('admin_enqueue_scripts', array($this, 'add_admin_scripts'));

        add_action('rest_api_init', [$this, 'ffbs_register_vehicle_routes']);

        // add_action('wp_ajax_add_vehicle', array($this, 'add_vehicle'));
        // add_action('wp_ajax_nopriv_add_vehicle', array($this, 'add_vehicle'));
    }

    public function add_admin_styles()
    {
        wp_register_style('admin_styles', plugins_url('css/admin.css', __FILE__));
        wp_register_style('admin_bootstrap', plugins_url('css/bootstrap.css', __FILE__));
        wp_register_style('admin_fa', plugins_url('css/all.css', __FILE__));

        wp_enqueue_style('admin_styles');
        wp_enqueue_style('admin_bootstrap');
        wp_enqueue_style('admin_fa');
    }

    public function add_admin_scripts()
    {
        wp_enqueue_script('admin_scripts', plugins_url('js/functions.admin.js', __FILE__), array('jquery', 'wp-api'));
        wp_localize_script(
            'admin_scripts',
            'wpApiSettings',
            array(
                'root' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest')
            )
        );
    }

    public function ffbs_register_vehicle_routes()
    {
        // Here we are registering our route for a collection of products.
        register_rest_route(self::VENDOR . '/v1', self::ROUTE_VEHICLES, array(
            // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
            'methods'  => WP_REST_Server::READABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => [$this, 'get_vehicles'],
        ));
        // // Here we are registering our route for single products. The (?P<id>[\d]+) is our path variable for the ID, which, in this example, can only be some form of positive number.
        // register_rest_route(self::VENDOR . '/v1', self::ROUTE_VEHICLES . '/(?P<id>[\d]+)', array(
        //     // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
        //     'methods'  => WP_REST_Server::READABLE,
        //     // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
        //     'callback' => 'get_product',
        // ));

        register_rest_route(self::VENDOR . '/v1', self::ROUTE_VEHICLES, array(
            // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
            'methods' => WP_REST_Server::CREATABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => [$this, 'add_vehicle'],
            'args' => array(
                'radioId' => array(),
                'description' => array(),
                'location' => array(),
                'mediaLink' => array(),
            ),
        ));
    }

    public function handle_vehicles()
    { ?>
        <div class="wrap">
            <h2>Fahrzeugverwaltung</h2>
            <div id="message" class="updated">Added new vehicle</div>

            <div>
                <?php $this->display_vehicles(); ?>
            </div>

            <form method="POST" action="">
                <div class="form-group col-sm-7">
                    <label for="vehicle_radio_id">Funkruf Name</label>
                    <input id="vehicle_radio_id" class="form-control" name="vehicle_radio_id" placeholder="Bsp. 4/42" />
                </div>

                <div class="form-group col-sm-7">
                    <label for="vehicle_description">Beschreibung</label>
                    <input id="vehicle_description" class="form-control" name="vehicle_description" placeholder="Bsp. LF 8/10" />
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
                    <button type="submit" class="btn btn-primary add-vehicle">Add</button>
                </div>
            </form>

        </div>
<?php
    }

    public function get_vehicles()
    {
        $vehicles = $this->db_handler->load_vehicles();
        return rest_ensure_response($vehicles);
    }

    public function add_vehicle($request)
    {
        $body = $request->get_json_params();

        $vehicle = [
            'radioId' => $body['radioId'],
            'description' => $body['description'],
            'location' => $body['location'],
            'mediaLink' => $body['mediaLink']
        ];


        $result = $this->db_handler->admin_insert_vehicle($vehicle);

        if ($result == 1) {
            return new WP_REST_Response(null, 201);
        } else {
            return new WP_Error('cant-create', $result, array('status' => 400));
        }

        // $nonce = $_POST['nonce'];

        // if (!wp_verify_nonce($nonce, 'ajax-nonce')) {
        //     die('Busted!');
        // }


        // //add new vehicle to database
        // $this->db_handler->admin_insert_vehicle($_POST['vehicle']);
        // $id = $this->db_handler->get_last_insert_id();
        // $vehicle = array(
        //     'id' => $id,
        //     'description' => $_POST['vehicle']
        // );

        // // $response = json_encode($vehicle);

        // // response output -> sent back to javascript file
        // // header("Content-Type: application/json");
        // wp_send_json($vehicle);
    }
}
$wpEinsatzverwaltungAdmin = new EinsatzverwaltungAdmin();
?>
