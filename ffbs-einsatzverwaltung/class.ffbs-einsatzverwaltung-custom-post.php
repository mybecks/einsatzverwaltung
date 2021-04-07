<?php
require_once(MISSIONS_PLUGIN_DIR . 'partials/page.ffbs.settings.php');

/**
 * Custom Post Type for Einsatzverwaltung
 **/
class EinsatzverwaltungCustomPost
{
    private $wpEinsatzverwaltungAdmin;

    public function __construct()
    {
        add_action('init', array($this, 'custom_post_mission'));
        add_action('publish_mission', array($this, 'save_data'));
        // add_action( 'trash_mission', array($this, 'trash_mission') ); //to set a flag that the mission is not shown any more
        add_action('before_delete_post', array($this, 'delete_mission')); //deltes the mission data
        add_action('manage_mission_posts_custom_column', array($this, 'manage_mission_columns'), 10, 2);
        add_filter('post_updated_messages', array($this, 'mission_updated_messages'));
        add_filter('manage_edit-mission_columns', array($this, 'edit_mission_column'));
        add_filter('manage_edit-mission_sortable_columns', array($this, 'mission_sortable_columns'));
        add_filter('post_type_link', array($this, 'mission_permalink'), 10, 3);
        add_action('admin_menu', array($this, 'add_sub_menu_pages'));
        // add_filter('block_editor_settings', 'ffbs_block_editor_settings', 10, 2);
        $this->db_handler = DatabaseHandler::get_instance();
        $this->settings_page =
            FFBSEinsatzverwaltungSettingsPage::get_instance();
    }

    // public function ffbs_block_editor_settings($editor_settings, $post)
    // {
    //     $editor_settings['autosaveInterval'] = 3600;
    //     return $editor_settings;
    // }

    public function custom_post_mission()
    {
        global $wp_rewrite;

        $labels = array(
            'name'               => __('Missions', TEXT_DOMAIN),
            'singular_name'      => __('Mission', TEXT_DOMAIN),
            'add_new'            => __('Add New', TEXT_DOMAIN),
            'add_new_item'       => __('Add New Mission', TEXT_DOMAIN),
            'edit_item'          => __('Edit Mission', TEXT_DOMAIN),
            'new_item'           => __('New Mission', TEXT_DOMAIN),
            'all_items'          => __('All Missions', TEXT_DOMAIN),
            'view_item'          => __('View Mission', TEXT_DOMAIN),
            'search_items'       => __('Search Missions', TEXT_DOMAIN),
            'not_found'          => __('No Missions found', TEXT_DOMAIN),
            'not_found_in_trash' => __('No Missions found in the Trash', TEXT_DOMAIN),
            'parent_item_colon'  => '',
            'menu_name'          => __('Missions', TEXT_DOMAIN)
        );

        $args = array(
            'labels'        => $labels,
            'description'   => __('Holds our missions and specific data', TEXT_DOMAIN),
            'public'        => true,
            'menu_position' => 5,
            'supports'      => array('author', 'editor'),
            'has_archive'   => true,
            'rewrite'       => array('slug' => 'mission', 'with_front' => false),
            'menu_icon'     => plugin_dir_url(__FILE__) . 'img/blaulicht_state_hover.png',
            'register_meta_box_cb' => array($this, 'add_custom_box'),
            'show_in_rest' => false // Gutenberg support
        );
        register_post_type('mission', $args);

        $wp_rewrite->add_rewrite_tag('%mission%', '([^\/]+)', 'index.php?mission=');
        // $wp_rewrite->add_rewrite_tag( '%mission%', '(\d{4}\_\d{2}\_\D+[a-zA-Z])', 'index.php?mission=' );
        $wp_rewrite->add_permastruct('mission', 'mission/%year%/%monthnum%/%mission%/');
    }

    public function mission_updated_messages($messages)
    {
        global $post, $post_ID;
        $messages['einsatz'] = array(
            0 => '',
            1 => sprintf(__('Mission updated. <a href="%s">View mission</a>', TEXT_DOMAIN), esc_url(get_permalink($post_ID))),
            2 => __('Custom field updated.', TEXT_DOMAIN),
            3 => __('Custom field deleted.', TEXT_DOMAIN),
            4 => __('Mission updated.', TEXT_DOMAIN),
            5 => isset($_GET['revision']) ? sprintf(__('Mission restored to revision from %s', TEXT_DOMAIN), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6 => sprintf(__('Mission published. <a href="%s">View mission</a>', TEXT_DOMAIN), esc_url(get_permalink($post_ID))),
            7 => __('Mission saved.', TEXT_DOMAIN),
            8 => sprintf(__('Mission submitted. <a target="_blank" href="%s">Preview mission</a>', TEXT_DOMAIN), esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))),
            9 => sprintf(__('Mission scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview mission</a>', TEXT_DOMAIN), date_i18n(__('M j, Y @ G:i', TEXT_DOMAIN), strtotime($post->post_date)), esc_url(get_permalink($post_ID))),
            10 => sprintf(__('Mission draft updated. <a target="_blank" href="%s">Preview mission</a>', TEXT_DOMAIN), esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))),
        );
        return $messages;
    }

    public function edit_mission_column($columns)
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => __('Title'),
            'alarmdate' => __('Alarm Date'),
            'alarmtime' => __('Alarm Time'),
            'date' => __('Entry Date'),
            'lastedit' => __('Last Edited by User'),
            'author' => __('Author'),
            'id' => __('Mission Id')
        );

        return $columns;
    }

    public function add_sub_menu_pages()
    {
        add_submenu_page(
            'edit.php?post_type=mission',
            __('Manage Vehicles', TEXT_DOMAIN),
            __('Manage Vehicles', TEXT_DOMAIN),
            'manage_options',
            'manage-vehicles',
            array($this, 'vehicle_page_content')
        );

        add_submenu_page(
            'edit.php?post_type=mission',
            __('Settings', TEXT_DOMAIN),
            __('Settings', TEXT_DOMAIN),
            'manage_options',
            'settings',
            array($this, 'ffbs_settings_page_content')
        );
    }

    public function vehicle_page_content()
    {
        $vehicles = $this->db_handler->load_vehicles(True);
?>
        <div class="wrap">
            <h2>Fahrzeugverwaltung</h2>
            <div id="message" class="updated">Added new vehicle</div>

            <div>
                <table class="table tab-vehicle">
                    <thead>
                        <tr>
                            <th scope="col">Funkruf Name</th>
                            <th scope="col">Beschreibung</th>
                            <th scope="col">Standort</th>
                            <th scope="col">Status</th>
                            <th scope="col">Edit</th>
                            <th scope="col">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // http://codex.wordpress.org/AJAX_in_Plugins
                        foreach ($vehicles as $vehicle) { ?>
                            <tr>
                                <th scope="row">
                                    <?php echo $vehicle->radio_id; ?>
                                </th>
                                <td scope="row">
                                    <?php echo $vehicle->description; ?>
                                </td>
                                <td scope="row">
                                    <?php echo $vehicle->location; ?>
                                </td>
                                <td scope="row">
                                    <?php echo $vehicle->status; ?>
                                </td>
                                <td scope="row">
                                    <i class="fas fa-edit action" id="edit"></i>
                                </td>
                                <td scope="row">
                                    <i class="fas fa-trash-alt action" id="delete"></i>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <form method="POST" action="">
                <div class="form-group col-sm-7">
                    <label for="vehicle_radio_id">Funkruf Name</label>
                    <input id="vehicle_radio_id" class="form-control" name="vehicle_radio_id" placeholder="Bsp. BS 2/42" required />
                </div>

                <div class="form-group col-sm-7">
                    <label for="vehicle_description">Beschreibung</label>
                    <input id="vehicle_description" class="form-control" name="vehicle_description" placeholder="Bsp. LF 8/10" required />
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


            <!-- Modal -->
            <div class="modal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Modal title</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Modal body text goes here.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary">Save changes</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    public function ffbs_settings_page_content()
    {
        echo $this->settings_page->display();
    }

    // http://justintadlock.com/archives/2011/06/27/custom-columns-for-custom-post-types
    public function manage_mission_columns($column, $post_id)
    {
        global $post;

        $mission = $this->db_handler->get_mission_details_by_post_id($post->ID);

        switch ($column) {
            case 'id':
                echo $mission[0]->id;
                break;
            case 'alarmdate':
                echo $mission[0]->alarm_date;
                break;
            case 'alarmtime':
                $date = new DateTime($mission[0]->alarm_time);
                echo $date->format('H:i');
                break;
            case 'lastedit':
                the_modified_author();
                break;
            default:
                break;
        }
    }

    public function mission_permalink($permalink, $post_id, $leavename)
    {
        $post = get_post($post_id);

        $rewritecode = array(
            '%year%',
            '%monthnum%',
            '%postname%'
        );

        if ('' != $permalink && !in_array($post->post_status, array('draft', 'pending', 'auto-draft'))) {
            $unixtime = strtotime($post->post_date);

            $date = explode(" ", date('Y m d H i s', $unixtime));

            $rewritereplace = array(
                $date[0],
                $date[1],
                $post->post_name,
            );

            $permalink = str_replace($rewritecode, $rewritereplace, $permalink);
        } else { // if they're not using the fancy permalink option
        }
        return $permalink;
    }

    private function prepare_permalink($permalink)
    {
        // detect position of basis string
        $pos = strrpos($permalink, '/', -2);
        $basis = substr($permalink, 0, $pos) . '/';

        // detect & clear post name
        $postname = substr($permalink, $pos + 1, strlen($permalink));
        list($year, $monath, $postname) = explode('_', $postname);

        // put basis & new post name together
        return $basis . $postname;
    }

    public function mission_sortable_columns($columns)
    {
        $columns['alarmdate'] = 'alarmdate';

        return $columns;
    }

    /**
     * Add Custom Box to Category
     * */
    public function add_custom_box()
    {
        add_meta_box(
            'einsatzverwaltung_sectionid',
            __('Einsatzverwaltung', TEXT_DOMAIN),
            array($this, 'custom_box_content'),
            'mission'
        );
    }

    public function custom_box_content($post)
    {

        // Use nonce for verification
        wp_nonce_field(plugin_basename(__FILE__), 'einsatzverwaltung_noncename');

        $meta_values = get_post_meta($post->ID, MISSION_ID, '');
        $meta_values = array_filter($meta_values);

        if (!empty($meta_values)) {
            $mission = $this->db_handler->load_mission_by_id($meta_values[0]);
            $vehicles_by_mission = $this->db_handler->load_vehicles_by_mission_id($mission->id);
            $vehicles = $this->db_handler->load_vehicles();
        } else {
            $vehicles = $this->db_handler->load_vehicles();

            $mission = new stdClass();
            $mission->id = "";
            $mission->destination = "";
            $mission->alarm_date = date("Y-m-d");
            $mission->alarm_time = "";
            $mission->return_date = date("Y-m-d");
            $mission->return_time = "";
            $mission->link_to_media = "";
            $mission->keyword = "";
            $mission->category = "";
            $mission->article_post_id = "";
            $vehicles_by_mission = array();
        }

        if (0 !== count($vehicles_by_mission)) {
            $this->set_selector_for_checkbox_value($vehicles_by_mission);
        }

        if (0 != $mission->article_post_id) {
            $this->set_selector_for_dropdown_value('#article_post_id', $mission->article_post_id);
        }

        if (!empty($mission->category)) {
            $this->set_selector_for_dropdown_value('#category', $mission->category);
        }
    ?>
        <input type="hidden" id="mission_id" name="mission_id" value="<?php echo $mission->id; ?>">

        <div class="form-group row">
            <label for="category" class="col-sm-2 col-form-label">Kategorie</label>
            <div class="col-sm-10">
                <select class="form-control" id="category" name="category">
                    <option value="BE">Brandeinsatz</option>
                    <option value="TH">Technische Hilfeleistung</option>
                    <option value="S">Sonstiges</option>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <label for="keyword" class="col-sm-2 col-form-label"><?php _e("Alarmstichwort (Freitext)", TEXT_DOMAIN); ?></label>
            <div class="col-sm-10">
                <input class="form-control form-check-input" name="keyword" id="keyword" value="<?php echo $mission->keyword; ?>" />
                <small id="emailHelp" class="form-text text-muted">Beispiel: Auslösung einer BMA</small>
            </div>
        </div>

        <div class="form-group row">
            <label for="destination" class="col-sm-2 col-form-label">
                <?php _e("Einsatzort", TEXT_DOMAIN); ?>
            </label>
            <div class="col-sm-10">
                <input class="form-control" id="destination" name="destination" value="<?php echo $mission->destination; ?>" />
            </div>
        </div>

        <div class="form-group row">
            <label for="alarm_date" class="col-sm-2 col-form-label">
                <?php _e("Alarmierung (Datum)", TEXT_DOMAIN); ?>
            </label>
            <div class="col-sm-10">
                <input class="form-control" id="alarm_date" name="alarm_date" type="date" value="<?php echo $mission->alarm_date; ?>" />
            </div>
        </div>

        <div class="form-group row">
            <label for="alarm_time" class="col-sm-2 col-form-label">
                <?php _e("Alarmierung (Uhrzeit)", TEXT_DOMAIN); ?>
            </label>
            <div class="col-sm-10">
                <input class="form-control" id="alarm_time" name="alarm_time" type="time" value="<?php echo $mission->alarm_time; ?>" />
            </div>
        </div>

        <div class="form-group row">
            <label for="return_date" class="col-sm-2 col-form-label">
                <?php _e("R&uuml;ckkehr (Datum)", TEXT_DOMAIN); ?>
            </label>
            <div class="col-sm-10">
                <input class="form-control" id="return_date" name="return_date" type="date" value="<?php echo $mission->return_date; ?>" />
            </div>
        </div>

        <div class="form-group row">
            <label for="return_time" class="col-sm-2 col-form-label">
                <?php _e("R&uuml;ckkehr (Uhrzeit)", TEXT_DOMAIN); ?>
            </label>
            <div class="col-sm-10">
                <input id="return_time" class="form-control" name="return_time" type="time" value="<?php echo $mission->return_time; ?>" />
            </div>
        </div>

        <div class="form-group row">
            <label for="link_to_media" class="col-sm-2 col-form-label">
                <?php _e("Link zu weiterf&uuml;hrenden Medien", TEXT_DOMAIN); ?>
            </label>
            <div class="col-sm-10">
                <input id="link_to_media" class="form-control" name="link_to_media" type="url" value="<?php echo $mission->link_to_media; ?>" size="50" />
            </div>
        </div>

        <div class="form-group row">
            <label for="article_post_id" class="col-sm-2 col-form-label">
                <?php _e("Link zur News", TEXT_DOMAIN); ?>
            </label>
            <div class="col-sm-10">
                <select class="form-control" id="article_post_id" name="article_post_id">
                    <option value="0">Kein Beitrag</option>
                    <?php echo $this->get_article_options(); ?>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <label for="vehicles" class="col-sm-2 col-form-label">
                <?php _e("Eingesetzte Fahrzeuge", TEXT_DOMAIN); ?>
            </label>
            <div class="col-sm-10">
                <?php
                if (0 < count($vehicles)) {
                    for ($i = 0; $i < count($vehicles); $i++) {

                        // $name = $this->rename_db_vehicle_name($vehicles[$i]->description);
                ?>
                        <div class="form-check" id="vehicles">
                            <input class="form-check-input" type="checkbox" name="<?php echo $vehicles[$i]->id; ?>" value="<?php echo $vehicles[$i]->radio_id; ?>" id="<?php echo $vehicles[$i]->id; ?>">
                            <label class="form-check-label" for="<?php echo $vehicles[$i]->id; ?>">
                                <?php echo $vehicles[$i]->radio_id . ' - ' . $vehicles[$i]->description; ?>
                            </label>
                        </div>
                    <?php }
                } else { ?>
                    <p>
                        <?php _e("Keine Fahrzeuge in der Datenbank gefunden!", TEXT_DOMAIN); ?>
                    </p>
                <?php } ?>
            </div>
        </div>

<?php
    }

    /**
     * Save and Edit Mission Details
     */
    /* When the post is saved, saves our custom data */
    public function save_data($post_id)
    {
        global $wpdb;

        $table_missions = $wpdb->prefix . "ffbs_missions";


        // verify if this is an auto save routine.
        // If it is our form has not been submitted, so we dont want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        // verify this came from the our screen and with proper authorization,
        // because save_post can be triggered at other times
        if (!wp_verify_nonce($_POST['einsatzverwaltung_noncename'], plugin_basename(__FILE__)))
            return;

        // Check permissions
        if ('page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id))
                return;
        } else {
            if (!current_user_can('edit_post', $post_id))
                return;
        }

        $mission_id = $_POST['mission_id'];
        $category = $_POST['category'];
        $keyword = $_POST['keyword'];
        $destination = $_POST['destination'];
        $alarm_date = $_POST['alarm_date'];
        $alarm_time = $_POST['alarm_time'];
        $return_date = $_POST['return_date'];
        $return_time = $_POST['return_time'];
        $link_to_media = $_POST['link_to_media'];
        $article_post_id = $_POST['article_post_id'];
        $db_vehicles = $this->db_handler->load_vehicles();
        $vehicles = array();

        for ($i = 0; $i < count($db_vehicles); $i++) {
            if (isset($_POST[$db_vehicles[$i]->id])) {
                $vehicles[] = $db_vehicles[$i]->id;
            }
        }

        if (empty($mission_id)) {

            //new mission entry
            $wpdb->insert(
                $table_missions,
                array(
                    'category' => $category,
                    'keyword' => $keyword,
                    'destination' => $destination,
                    'alarm_date' => $alarm_date,
                    'alarm_time' => $alarm_time,
                    'return_date' => $return_date,
                    'return_time' => $return_time,
                    'link_to_media' => $link_to_media,
                    'wp_posts_ID' => $post_id,
                    'article_post_id' => $article_post_id
                ),
                array()
            );

            $id = $wpdb->insert_id;

            foreach ($vehicles as $vehicle_id) {
                $this->db_handler->insert_new_vehicle_to_mission($id, $vehicle_id);
            }

            add_post_meta($post_id, MISSION_ID, $id);
        } else {
            //Update
            $wpdb->update(
                $table_missions,
                array(
                    'category' => $category,
                    'keyword' => $keyword,
                    'destination' => $destination,
                    'alarm_date' => $alarm_date,
                    'alarm_time' => $alarm_time,
                    'return_date' => $return_date,
                    'return_time' => $return_time,
                    'link_to_media' => $link_to_media,
                    'article_post_id' => $article_post_id
                ),
                array('id' => $mission_id)
            );

            //remove all vehicles bound to current mission!
            $this->db_handler->remove_vehicles_from_mission($mission_id);

            //insert new values:
            foreach ($vehicles as $vehicle_id) {
                $this->db_handler->insert_new_vehicle_to_mission($mission_id, $vehicle_id);
            }
        }

        $current_post = array(
            'ID' => $post_id,
            'post_title' => $keyword,
            'post_name' => $keyword,
            'post_date' => date($alarm_date . ' ' . $alarm_time),
            'post_date_gmt' => date($alarm_date . ' ' . $alarm_time)
        );

        remove_action('publish_mission', array($this, 'save_data'));

        // Update the post into the database
        wp_update_post($current_post);

        add_action('publish_mission', array($this, 'save_data'));
    }

    /**
     * Delete mission data & associated post
     *
     * @return boolean
     * */
    public function delete_mission($post_id)
    {

        if (current_user_can('delete_posts')) {
            $this->db_handler->delete_mission_by_post_id($post_id);
        }
    }

    /*
     *
     *
     * @author Andre Becker
     **/
    public function set_selector_for_checkbox_value($vehicles_by_mission)
    {
        $script = "<script type='text/javascript'>";

        for ($i = 0; $i < count($vehicles_by_mission); $i++) {
            $script .= "jQuery(document).ready(function($) {
                            $('input[name=" . $vehicles_by_mission[$i]->id . "]').attr('checked', true);
                        });";
        }
        $script .= "</script>";
        echo $script;
    }

    public function set_selector_for_dropdown_value($attribut, $value)
    {
        $script = "
        <script type='text/javascript'>
         jQuery(document).ready(function($) {
            $('" . $attribut . "').val('" . $value . "');
        });
        </script>";
        echo $script;
    }

    public function get_posts_by_category($cat_id)
    {


        $args = array(
            'cat' => $cat_id,
            'numberposts' => -1,
            'orderby'    => 'post_date',
            'sort_order' => 'asc'
        );

        $posts = get_posts($args);
        wp_reset_postdata();
        return $posts;
    }

    public function get_article_options()
    {
        $settings = $this->db_handler->get_settings('cat_id');
        $options = '';
        if (isset($settings)) {
            $posts = $this->get_posts_by_category($settings->value);

            foreach ($posts as $post) {
                $options .= "<option value=" . $post->ID . ">" . $post->post_title . " (" . $post->post_date . ")" . "</option>";
            }
        }

        return $options;
    }
}

$wpEinsatzverwaltungCustomPost = new EinsatzverwaltungCustomPost();
