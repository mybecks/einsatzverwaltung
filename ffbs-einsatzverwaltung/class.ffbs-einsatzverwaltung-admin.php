<?php

/**
 * Initializing Admin Menu
 * */


// todo refactor to WP_REST_Controller
// https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/#examples
class EinsatzverwaltungAdmin
{
    protected $pluginPath;
    private $db_handler;

    const VENDOR = 'ffbs';
    const ROUTE_VEHICLES = '/vehicles';
    const ROUTE_SETTINGS = '/settings';

    public function __construct()
    {
        $this->pluginPath = dirname(__FILE__);
        $this->db_handler = DatabaseHandler::get_instance();

        add_action('rest_api_init', [$this, 'ffbs_register_routes']);
    }

    public function ffbs_register_routes()
    {
        /**
         * Vehicle Route
         **/
        // Here we are registering our route for a collection of products.
        register_rest_route(self::VENDOR . '/v1', self::ROUTE_VEHICLES, array(
            // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
            'methods'  => WP_REST_Server::READABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => [$this, 'get_vehicles'],
        ));

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
                'detailsLink' => array(),
            ),
        ));

        register_rest_route(self::VENDOR . '/v1', self::ROUTE_VEHICLES . '/(?P<vehicle_id>\w+\/?\d+)', array(
            // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
            'methods'  => WP_REST_Server::DELETABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => [$this, 'delete_vehicle'],
        ));

        /**
         * Settings Route
         **/

        // Here we are registering our route for a collection of products.
        register_rest_route(self::VENDOR . '/v1', self::ROUTE_SETTINGS, array(
            // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
            'methods'  => WP_REST_Server::READABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => [$this, 'get_settings'],
        ));

        register_rest_route(self::VENDOR . '/v1', self::ROUTE_SETTINGS, array(
            // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
            'methods' => WP_REST_Server::CREATABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => [$this, 'add_setting'],
            'args' => array(
                'id' => array(),
                'value' => array(),
            ),
        ));
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
            'mediaLink' => $body['mediaLink'],
            'detailsLink' => $body['detailsLink']
        ];


        $result = $this->db_handler->admin_insert_vehicle($vehicle);

        if ($result == 1) {
            return new WP_REST_Response(null, 201);
        } else {
            return new WP_Error('cant-create', $result, array('status' => 400));
        }
    }

    public function delete_vehicle($request)
    {
        $params = $request->get_params();

        $result = $this->db_handler->delete_vehicle($params['vehicle_id']);
        if ($result == 1) {
            return new WP_REST_Response(null, 204);
        } else {
            return new WP_Error('cant-create', $result, array('status' => 400));
        }
    }

    public function add_setting($request)
    {
        $body = $request->get_json_params();

        $result = $this->db_handler->add_setting($body['id'], $body['value']);

        if ($result == 1) {
            return new WP_REST_Response(null, 201);
        } else {
            return new WP_Error('cant-create', $result, array('status' => 400));
        }
    }

    public function get_settings($request)
    {
        $settings = $this->db_handler->get_settings();
        return rest_ensure_response($settings);
    }
}
$wpEinsatzverwaltungAdmin = new EinsatzverwaltungAdmin();
