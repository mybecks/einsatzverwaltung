<?php

/**
 * Handler for all database related requests
 */

//http://wordpress.stackexchange.com/questions/73868/queries-inside-of-a-class
class DatabaseHandler
{
    /** Refers to a single instance of this class. */
    private static $instance = null;
    private $table;

    /**
     * Creates or returns an instance of this class.
     *
     * @return A single instance of this class.
     * @author Andre Becker
     */
    public static function get_instance()
    {

        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = (object) array(
            "missions" => $this->db->prefix . "ffbs_missions",
            "moved_out_vehicles" => $this->db->prefix . "ffbs_moved_out_vehicles",
            "vehicles" => $this->db->prefix . "ffbs_vehicles",
            "settings" => $this->db->prefix . "ffbs_settings"
        );
    }

    /**
     *
     * Mission Related Requests
     *
     **/


    /**
     * Delete mission by post id
     *
     * @param  int $post_id
     * @author Andre Becker
     */
    public function delete_mission_by_post_id($post_id)
    {
        $mission = $this->load_mission_by_post_id($post_id);
        $this->db->delete(
            $this->table->missions,
            array('wp_posts_ID' => $post_id),
            array('%d')
        );

        $this->remove_vehicles_from_mission($mission->id);
    }


    /**
     * Update mission
     * @return [type] [description]
     * @author Andre Becker
     */
    public function update_mission()
    {
    }

    /**
     * Returns array with all mission years
     *
     * @return array()
     * @author Andre Becker
     */
    public function get_mission_years()
    {
        $array = array();

        $query = "SELECT YEAR(alarm_date) AS Year FROM " . $this->table->missions . " GROUP BY Year DESC";
        $years = $this->db->get_results($query);

        foreach ($years as $year) {
            if (1970 != $year->Year)
                $array[] = $year->Year;
        }

        if (empty($array)) {
            array_push($array, CURRENT_YEAR);
        }

        return $array;
    }

    /**
     * Get mission details by post id
     *
     * @param  int $post_id
     * @return mission
     * @author Andre Becker
     */
    public function get_mission_details_by_post_id($post_id)
    {
        $query = "SELECT id, category, keyword, destination, alarm_date, alarm_time, return_date, return_time, link_to_media FROM "
            . $this->table->missions . " WHERE wp_posts_ID = %d";
        return $this->db->get_results($this->db->prepare($query, $post_id));
    }

    /**
     * Load missions by mission_id
     *
     * @param int id
     * @return array()
     * @author Andre Becker
     */
    public function load_mission_by_id($id)
    {
        $query = "SELECT * FROM " . $this->table->missions . " WHERE id = %d";
        return $this->db->get_row($this->db->prepare($query, $id));
    }

    /**
     * Load vehicles bound to mission
     *
     * @param int mission id
     * @return array()
     * @author Andre Becker
     */
    public function load_vehicles_by_mission_id($mission_id)
    {
        $query = "SELECT v.id, v.description, v.location FROM " . $this->table->vehicles .
            " as v, " . $this->table->moved_out_vehicles . " as mv WHERE v.id = mv.vehicle_id AND mv.mission_id = %d
                ORDER BY v.location DESC, v.radio_id ASC";

        return $this->db->get_results($this->db->prepare($query, $mission_id));
    }

    /**
     * Load missions bound to post id
     *
     * @param post    id
     * @return mission
     * @author Andre Becker
     */
    public function load_mission_by_post_id($id)
    {
        $query = "SELECT * FROM " . $this->table->missions . " WHERE wp_posts_ID = %d";
        $mission_details = $this->db->get_row($this->db->prepare($query, $id));

        return $mission_details;
    }

    /**
     * Collect missions by year DESC
     *
     * @return array()
     * @author Andre Becker
     */
    public function get_missions_by_year($year)
    {
        $arr_months = array();

        $query = "SELECT id, category, keyword, destination, alarm_date, alarm_time, return_date, return_time, link_to_media, wp_posts_ID, MONTH(alarm_date) AS Month, article_post_id " .
            "FROM " . $this->table->missions .
            " WHERE YEAR(alarm_date) = %d" .
            " ORDER BY alarm_date DESC, alarm_time DESC";

        $missions = $this->db->get_results($this->db->prepare($query, $year));

        foreach ($missions as $mission) {

            //http://stackoverflow.com/questions/1195549/php-arrays-and-solution-to-undefined-index-errors

            if (!array_key_exists($mission->Month, $arr_months)) {
                $arr_months[$mission->Month] = array();
            }

            foreach ($arr_months as $key => $value) {
                if ($key == $mission->Month) {
                    $tmp_arr = $arr_months[$key];

                    $post = get_post($mission->wp_posts_ID);
                    // var_dump($mission->article_post_id);
                    if (0 != strlen($post->post_content) || $mission->article_post_id) {
                        $description = "Bericht";
                    } else {
                        $description = "Kurzinfo";
                    }

                    // if (false !== strpos($mission->art_alarmierung, 'Brandeinsatz')) {
                    //     $alarm_short = 'BE';
                    // } else if (false !== strpos($mission->art_alarmierung, 'Technischer Einsatz')) {
                    //     $alarm_short = 'TE';
                    // } else {
                    //     $alarm_short = 'SE';
                    // }

                    $arr_content = array(
                        "keyword" => $mission->keyword,
                        "category" => $mission->category,
                        "location" => $mission->destination,
                        "alarm_date" => strftime("%d.%m.%Y", strtotime($mission->alarm_date)),
                        "alarm_time" => strftime("%H:%M", strtotime($mission->alarm_time)),
                        "return_date" => strftime("%d.%m.%Y", strtotime($mission->return_date)),
                        "return_time" => strftime("%H:%M", strtotime($mission->return_time)),
                        "link_to_media" =>  $mission->link_to_media,
                        "description" => $description,
                        "linked_post_id" => get_permalink($mission->wp_posts_ID),
                        "article_post" => $mission->article_post_id ? get_permalink($mission->article_post_id) : null,
                        "mission_id" => $mission->id,
                        "post_content" => do_shortcode($post->post_content)
                    );

                    array_push($tmp_arr, $arr_content);

                    $arr_months[$key] = $tmp_arr;
                }
            }
        }

        // reverse sort of months (12,11,10,...,1)
        krsort($arr_months);

        return $arr_months;
    }

    /**
     *
     * Vehicle Related Requests
     *
     **/

    /**
     * Load all vehicles
     *
     * @return array()
     * @author Andre Becker
     */
    public function load_vehicles($all = False)
    {
        $query = "SELECT id, radio_id, description, location, status, media_link FROM " . $this->table->vehicles;

        if (!$all) {
            $query .= " WHERE status = 'S2'";
        }

        $vehicles = $this->db->get_results($query);

        return $vehicles;
    }

    /**
     * Remove vehicle from mission
     * @param  int $mission_id
     * @author Andre Becker
     */
    public function remove_vehicles_from_mission($mission_id)
    {
        $this->db->delete(
            $this->table->moved_out_vehicles,
            array('mission_id' => $mission_id),
            array('%d')
        );
    }

    /**
     * Insert new vehicles to mission
     *
     * @param int $mission_id
     * @param string $vehicle_id
     * @author Andre Becker
     */
    public function insert_new_vehicle_to_mission($mission_id, $vehicle_id)
    {
        $this->db->insert(
            $this->table->moved_out_vehicles,
            array(
                'mission_id' => $mission_id,
                'vehicle_id' => $vehicle_id
            ),
            array(
                '%d',
                '%s'
            )
        );
    }

    /**
     * Insert a new vehicle in the database
     *
     * @param  array $vehicle
     * @author Andre Becker
     */
    public function admin_insert_vehicle($vehicle)
    {
        $sanitized_radio_id = str_replace(' ', '', $vehicle['radioId']);
        $sanitized_radio_id = str_replace('/', '', $sanitized_radio_id);
        $sanitized_radio_id = str_replace('-', '', $sanitized_radio_id);
        $sanitized_radio_id = strtolower($sanitized_radio_id);


        $result = $this->db->insert(
            $this->table->vehicles,
            array(
                'id' => $sanitized_radio_id,
                'description' => $vehicle['description'],
                'radio_id' => $vehicle['radioId'],
                'location' => $vehicle['location'],
                // 'status' => $vehicle['status'],
                'media_link' => $vehicle['mediaLink'],
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s',
                // '%s',
                '%s',
            )
        );

        if (empty($result)) {
            return $this->get_last_error();
        }

        return $result;
    }

    public function delete_vehicle($id)
    {
        $result = $this->db->delete(
            $this->table->vehicles,
            array('id' => $id),
            array('%s')
        );

        if (empty($result)) {
            return $this->get_last_error();
        }

        return $result;
    }

    public function get_last_insert_id()
    {
        return $this->db->insert_id;
    }

    public function get_last_error()
    {
        return $this->db->last_error;
    }

    public function get_settings($id)
    {
        $query = "SELECT id, value FROM " . $this->table->settings;
        $settings = null;

        if (!empty($id)) {
            $query .= " WHERE id = %s";
            $settings = $this->db->get_row($this->db->prepare($query, $id));
        } else {
            $settings = $this->db->get_results($query);
        }

        return $settings;
    }

    public function add_setting($id, $value)
    {
        $result = $this->db->insert(
            $this->table->settings,
            array(
                'id' => $id,
                'value' => $value
            ),
            array(
                '%s',
                '%s'
            )
        );

        if (empty($result)) {
            return $this->get_last_error();
        }

        return $result;
    }

    public function list_last_missions($count)
    {
        $query = "SELECT category, keyword, alarm_date FROM " . $this->table->missions . " WHERE YEAR (alarm_date) = YEAR(CURDATE()) ORDER BY alarm_date DESC LIMIT " . $count;
        $missions = $this->db->get_results($query);

        return $missions;
    }
}
