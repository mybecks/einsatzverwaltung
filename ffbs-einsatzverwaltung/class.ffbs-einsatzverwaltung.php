<?php

/**
 * Main Class
 */
class Einsatzverwaltung
{
    protected $pluginPath;

    public function __construct()
    {
        $this->plugin_path = dirname(__FILE__);
        $this->db_handler = DatabaseHandler::get_instance();

        add_action('wp_enqueue_scripts', array($this, 'add_styles'));
        add_action('wp_enqueue_scripts', array($this, 'add_scripts'));

        add_action('plugins_loaded', array($this, 'plugin_textdomain'));
        add_action('wp_footer', array($this, 'postinfo_head'));

        // Backend Styles & Scripts
        add_action('admin_print_styles', array($this, 'add_admin_styles'));
        add_action('admin_enqueue_scripts', array($this, 'add_admin_scripts'));

        // Shortcodes
        add_shortcode('einsatzverwaltung', array($this, 'my_einsatzverwaltung_handler'));
    }

    public function add_styles()
    {
        // Custom CSS styling
        wp_register_style('einsatzverwaltung-style', plugins_url('/css/styles.css', __FILE__));
        wp_enqueue_style('einsatzverwaltung-style');

        // Bootstrap CSS styling
        wp_register_style('bootstrap-style', plugins_url('/css/bootstrap.css', __FILE__));
        wp_enqueue_style('bootstrap-style');
    }

    public function add_scripts()
    {
        wp_enqueue_script('widget_script', plugins_url('js/functions.widget.js', __FILE__), array('jquery'));
    }

    public function add_admin_styles()
    {
        // Custom CSS styling
        wp_register_style('admin_styles', plugins_url('css/admin.css', __FILE__));
        wp_enqueue_style('admin_styles');

        // Custom Bootstrap styling
        wp_register_style('bootstrap-style', plugins_url('/css/bootstrap.css', __FILE__));
        wp_enqueue_style('bootstrap-style');

        // FontAwesome Styles
        wp_register_style('admin_fa', plugins_url('css/all.css', __FILE__));
        wp_enqueue_style('admin_fa');
    }

    public function add_admin_scripts()
    {
        // Load JS Script for backend functions
        wp_enqueue_script('admin_scripts', plugins_url('js/functions.admin.js', __FILE__), array('jquery', 'wp-api'));
        wp_enqueue_script('bootstrap-js', plugins_url('js/bootstrap.min.js', __FILE__));
        wp_localize_script(
            'admin_scripts',
            'bootstrap-js',
            'wpApiSettings',
            array(
                'root' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest')
            )
        );
    }

    public function my_einsatzverwaltung_handler($atts, $content = null, $code = "")
    {

        ob_start();

        //code 4 displaying
        $this->display_missions();

        $output_string = ob_get_contents();
        ob_end_clean();

        return $output_string;
    }

    public static function plugin_textdomain()
    {
        load_plugin_textdomain('einsatzverwaltung_textdomain', false, dirname(plugin_basename(__FILE__)) . '/lang/');
    }

    public function display_missions()
    {

        $permalink = get_permalink();
        $years = $this->db_handler->get_mission_years();

        echo "<div style='margin-bottom: 2.5rem;'>";
        echo    "<form action=\"$permalink\" method=\"post\">";
        echo        "<table>";
        echo            "<tr>Einsatzjahr:&nbsp;</tr>";
        echo            "<tr><select name=\"einsatzjahr\" onchange=\"this.form.submit()\">";

        foreach ($years as $year) {
            if(isset($_POST['einsatzjahr']) && $_POST['einsatzjahr'] == $year) {
                $selected = " selected=\"selected\"";
            } else {
                $selected = "";
            }
            echo "  <option value=\"" . $year . "\"" . $selected . ">" . $year . "</option>";
        }

        echo                "</select>";
        echo            "</tr>";
        echo        "</table>";
        echo    "</form>";
        echo "</div>";

        if (!isset($_POST['einsatzjahr'])) {
            $missions = $this->db_handler->get_missions_by_year(CURRENT_YEAR);
        } else {
            $missions = $this->db_handler->get_missions_by_year($_POST['einsatzjahr']);
        }

        $this->print_missions_month_overview($missions);
        $this->print_missions_by_year($missions);
    }


    /**
     * Returns missions grouped by month for current year.
     *
     * @return array()
     * */
    public function print_missions_by_year($arr_months)
    {
        // Paths
        $arrow_up_path = plugin_dir_url(__FILE__) . 'img/mini-nav-top.gif';

        // Ausgabe der Einsätze im aktuellen Jahr
        foreach ($arr_months as $monthKey => $monthValue) {

            $german_month = $this->get_german_month($monthKey);
            $count = count($arr_months[$monthKey]);
?>

            <br />
            <div>
                <a name='<?php echo $german_month; ?>'></a>
                <h2>
                    <?php echo $german_month; ?>
                </h2>
                <div>
                    <table class='table table-striped' summary='Einsatzliste im Monat <?= $german_month ?>' border='0'>
                        <thead>
                            <tr class="ffbs-hideOnMobile">
                                <th scope='col' width="100">Datum</th>
                                <th scope='col' width="70">Zeit</th>
                                <th scope='col'>Alarmstichwort</th>
                                <th scope='col' width="140">Einsatzort</th>
                                <th scope='col' width="75"></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <td colspan='5'>Anzahl der Eins&auml;tze im Monat: <b><?php echo $count; ?></b></td>
                            </tr>
                        </tfoot>
                        <tbody>

                            <?php foreach ($arr_months[$monthKey] as $key => $value) {
                                $vehicles = "";
                                foreach ($this->db_handler->load_vehicles_by_mission_id($value['mission_id']) as $vehicle) {
                                    if ($vehicles) {
                                        $vehicles .= "";
                                    }
                                    $vehicles .= "<img src=\"" . $vehicle->media_link . "\" alt=\"" . $vehicle->description . " " . $vehicle->location . "\" title=\"" . $vehicle->description . " " . $vehicle->location . "\" class=\"mr-3 mt-1 mb-2 ffbs-vehicle\">";
                                }
                                if (!$vehicles) {
                                    $vehicles = "-";
                                }
                                switch ($value['category']) {
                                    case "BE":
                                        $category = "<div class=\"d-inline-block mr-1\" style=\"width:22px;\"><i class=\"fas fa-fire\"></i></div>B - ";
                                        break;
                                    case "TH":
                                        $category = "<div class=\"d-inline-block mr-1\" style=\"width:22px;\"><i class=\"fas fa-tools\"></i></div>TH - ";
                                        break;
                                    case "S":
                                        $category = "<div class=\"d-inline-block mr-1\" style=\"width:22px;\"><i class=\"fas fa-siren\"></i></div>S - ";
                                        break;
                                }
                            ?>
                                <tr onclick="jQuery('#missionDetails<?= $value['mission_id'] ?>').toggle();return false;" style="cursor:pointer;">
                                    <td class="ffbs-hideOnMobile"><?php echo $value['alarm_date']; ?></td>
                                    <td class="ffbs-hideOnMobile"><?php echo $value['alarm_time']; ?></td>
                                    <td><?php echo $category . $value['keyword']; ?></td>
                                    <td class="ffbs-hideOnMobile"><?php echo $value['location']; ?></td>
                                    <td class="ffbs-hideOnMobile"><a href="javascript:jQuery('#missionDetails<?= $value['mission_id'] ?>').toggle();return false;">Details</a></td>
                                </tr>
                                <tr id="missionDetails<?= $value['mission_id'] ?>" style="display:none;">
                                    <td colspan="5">
                                        <div>
                                            <div class="row mt-3 mb-3">
                                                <div class="col">
                                                    <strong>Alarmierung:</strong> <?php echo $value['alarm_date']; ?>, <?php echo $value['alarm_time']; ?> Uhr
                                                </div>
                                                <div class="col">
                                                    <strong>Rückkehr:</strong> <?php echo $value['return_date']; ?>, <?php echo $value['return_time']; ?> Uhr
                                                </div>
                                            </div>
                                            <div class="row mt-3 mb-3 ffbs-showOnMobile">
                                                <div class="col">
                                                    <strong>Einsatzort:</strong> <?php echo $value['location']; ?>
                                                </div>
                                            </div>
                                            <?php
                                            if ($value['post_content']) {
                                            ?>
                                                <div class="row mt-1 mb-3">
                                                    <div class="col">
                                                        <?php echo $value['post_content']; ?>
                                                    </div>
                                                </div>
                                            <?php
                                            }
                                            if ($value['article_post']) {
                                            ?>
                                                <div class="row mt-1 mb-3">
                                                    <div class="col">
                                                        <strong>Einsatz-News:</strong> <a href="<?= $value['article_post'] ?>"><?= $value['article_title'] ?></a>
                                                    </div>
                                                </div>
                                            <?php
                                            }
                                            ?>
                                            <div class="row mt-1 mb-3">
                                                <div class="col">
                                                    <strong>Fahrzeuge:</strong>
                                                    <br/>
                                                    <?= $vehicles ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php
        }
        ?>
        <div>
            <i class="fas fa-fire"></i> B: Brandeinsatz - <i class="fas fa-tools"></i> TH: Technischer Einsatz - <i class="fas fa-siren"></i> S: Sonstiger Einsatz
        </div>
        <?php
    }

    /**
     * Transfers the english months to german
     *
     * @return array()
     * */
    public function get_german_month($english_month_2number)
    {


        // $dateFormat = new LocaleDateFormat('MMMM'); # Long Month Names
        // $date = new DateTime(); # Now
        //    $month = $dateFormat->localeFormat(LOCALE, $date);
        //    wp_die($month. ' # '. $english_month_2number);

        $german_months = array(
            1 => "Januar",
            2 => "Februar",
            3 => "M&auml;rz",
            4 => "April",
            5 => "Mai",
            6 => "Juni",
            7 => "Juli",
            8 => "August",
            9 => "September",
            10 => "Oktober",
            11 => "November",
            12 => "Dezember"
        );
        $english_month_2number = ltrim($english_month_2number, "0");

        return $german_months[$english_month_2number];
    }

    /**
     * Print overview of missions grouped by month
     *
     * */
    public function print_missions_month_overview($arr_months)
    {
        // START Attributes
        $mission_year = CURRENT_YEAR;

        if (isset($_POST['einsatzjahr']))
            $mission_year = $_POST['einsatzjahr'];

        $mission_year_count = 0;

        foreach ($arr_months as $key => $value) {
            foreach ($arr_months[$key] as $key => $value) {
                $mission_year_count++;
            }
        }

        ?>
        <a name="Übersicht"></a>
        <h2>Monatsübersicht für <?php echo $mission_year; ?></h2>
        <div>
            <table class="table table-striped" summary="Übersicht über die Anzahl der Einsätze im Jahr <?php echo $mission_year; ?>">
                <thead>
                    <tr>
                        <th>Monat</th>
                        <th>Einsätze</th>
                        <th class="ffbs-hideOnMobile"><i class="fas fa-fire"></i> B / <i class="fas fa-tools"></i> TH / <i class="fas fa-siren"></i> S</th>
                        <th></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <td colspan="4">Anzahl der Einsätze im Jahr: <b> <?php echo $mission_year_count; ?></b></td>
                    </tr>
                </tfoot>
                <tbody>
                    <?php
                    foreach ($arr_months as $key => $value) {
                        // START Amount of missions in the month
                        $count_missions_in_month = count($arr_months[$key]);
                        // END

                        $count_brandeinsatz = 0;
                        $count_technischereinsatz = 0;
                        $count_sonstiges = 0;

                        foreach ($value as $mission_key => $mission_value) {
                            if ($mission_value['category'] == 'BE') {
                                $count_brandeinsatz++;
                            } elseif ($mission_value['category'] == 'TH') {
                                $count_technischereinsatz++;
                            } else {
                                $count_sonstiges++;
                            }
                        }

                        // OUTPUT
                        $german_month = $this->get_german_month($key);
                    ?>
                        <tr>
                            <td><?php echo $german_month; ?></td>
                            <td><?php echo $count_missions_in_month; ?></td>
                            <td class="ffbs-hideOnMobile"><?= $count_brandeinsatz ?>/<?= $count_technischereinsatz ?>/<?= $count_sonstiges ?></td>
                            <td><a href="#<?php echo $german_month; ?>">Einsätze</a></td>
                        </tr>
                    <?php
                    } ?>

                </tbody>
            </table>
        </div>
<?php
    }
    /*
     * Begin Postinfo
     */

    /**
     * Add JavaScript for postinfo to the footer
     * */
    public function postinfo_head()
    {
        global $post;

        //Check if mission category
        if ('mission' !== $post->post_type)
            return;

        $script = <<< EOF
<script type='text/javascript'>
    jQuery(document).ready(function($){
        $('.post-info').prependTo('.entry-content');
        $('.open-post-info').prependTo('.entry-content');


        $('.post-info').show();
        $('.open-post-info').click(function() {
            var id = $(this).attr('id');

            $('.post-info-' + id).slideToggle("medium", function() {
                $(this).prev().toggleClass("toggled");
            });

            return false;
        });

        $('.included-article').prependTo('.entry-content');
    });
</script>
EOF;
        echo $script;
        $this->postinfo();
    }

    /**
     * Ausgabe der Detailinformationen zu einem Einsatz
     * */
    public function postinfo()
    {
        global $post;

        $mission = $this->db_handler->load_mission_by_post_id($post->ID);
        $vehicles = $this->db_handler->load_vehicles_by_mission_id($mission->id);

        $used_vehicles = "";

        for ($i = 0; $i < count($vehicles); $i++) {
            if (count($vehicles) - 1 === $i) {
                $used_vehicles .= $vehicles[$i]->description;
            } else {
                $used_vehicles .= $vehicles[$i]->description . " &#x95 ";
            }
        }

        if (("Freitext" === $mission->alarmstichwort) || ("Sonstiger Brand" === $mission->alarmstichwort)) {
            $alarmstichwort = $mission->freitext;
        } else {
            $alarmstichwort = $mission->alarmstichwort;
        }

        echo '<p class="open-post-info" id="' . $post->post_name . '">Details</p>';
        echo '<div class="post-info post-info-' . $post->post_name . '">';
        echo '<ul>';
        echo    '<li class="alarmstichwort">';
        echo        "<b>Alarmstichwort:</b> " . $alarmstichwort;
        echo    '</li>';
        echo    '<li class="art_der_alarmierung">';
        echo        "<b>Art der Alarmierung:</b> " . $mission->art_alarmierung;
        echo    '</li>';
        echo    '<li class="alarmierung">';
        echo        "<b>Alarmierung:</b> " . strftime("%d.%m.%Y", strtotime($mission->alarm_date)) . " " . strftime("%H:%M", strtotime($mission->alarm_time));
        echo    '</li>';
        echo    '<li class="rueckkehr">';
        echo        "<b>R&uuml;ckkehr:</b> " . strftime("%d.%m.%Y", strtotime($mission->rueckkehr_date)) . " " . strftime("%H:%M", strtotime($mission->rueckkehr_time));
        echo    '</li>';
        echo    '<li class="einsatzort">';
        echo        "<b>Einsatzort:</b> " . $mission->einsatzort;
        echo    '</li>';
        echo    '<li class="eingesetzte_fahrzeuge">';
        echo        "<b>Eingesetzte Fahrzeuge:</b> " . $used_vehicles;
        echo    '</li>';
        echo    '<li class="link">';
        if (empty($mission->link_to_media)) {
            echo "<b>Quelle:</b> Nicht verf&uuml;gbar";
        } else {
            echo "<b>Quelle:</b> <a href='$link' target='_blank'>" . $mission->link_to_media . "</a>";
        }
        echo    '</li>';
        echo '</ul>';
        echo '</div>';


        echo "<div class='included-article'>" . get_the_content(null, false, $mission->article_post_id) . "</div>";

        // // get content of article if linked
        // if ( !isset( $mission->article_post_id ) ) {

        // } else {
        //     echo "<p>No Article linked</p>";
        // }
    }
}

function plugin_activation()
{
    global $wpdb;
    // https://codex.wordpress.org/Creating_Tables_with_Plugins#Adding_an_Upgrade_Function

    flush_rewrite_rules();

    // $wpdb->show_errors();
    $table_vehicles = $wpdb->prefix . "ffbs_vehicles";
    $table_missions = $wpdb->prefix . "ffbs_missions";
    $table_moved_out_vehicles = $wpdb->prefix . "ffbs_moved_out_vehicles";
    $table_settings = $wpdb->prefix . "ffbs_settings";
    $table_wp_posts = $wpdb->prefix . "posts";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    /*
    * SQL Create Tables
    *
    * No Foreign Keys: http://wordpress.stackexchange.com/questions/52783/dbdelta-support-for-foreign-key
    */

    $sql_vehicles = "CREATE TABLE IF NOT EXISTS " . $table_vehicles . "
    (
        id                  VARCHAR(12) NOT NULL,
        radio_id            VARCHAR(12) NOT NULL,
        description         VARCHAR(25) NOT NULL,
        location            VARCHAR(14) NOT NULL,
        status              VARCHAR(2) DEFAULT 'S2' NOT NULL,
        media_link          VARCHAR(2083),
        PRIMARY KEY  (id)
    )
    CHARACTER SET utf8
    COLLATE utf8_general_ci;
    ";
    dbDelta($sql_vehicles);

    $sql_missions = "CREATE TABLE IF NOT EXISTS $table_missions
    (
        id                  INT UNSIGNED NOT NULL AUTO_INCREMENT ,
        category            VARCHAR(25) NOT NULL,
        keyword             VARCHAR(255) NOT NULL ,
        destination         VARCHAR(45) NOT NULL ,
        alarm_date          DATE NOT NULL ,
        alarm_time          TIME NOT NULL ,
        return_date         DATE NULL ,
        return_time         VARCHAR(45) NULL ,
        link_to_media       VARCHAR(255) NULL ,
        wp_posts_ID         INT UNSIGNED NOT NULL ,
        article_post_id     INT UNSIGNED,
        PRIMARY KEY  (id)
    )
    CHARACTER SET utf8
    COLLATE utf8_general_ci;
    ";
    // KEY fk_einsaetze_wp_posts1 (wp_posts_ID ASC),
    // CONSTRAINT fk_einsaetze_wp_posts1
    //     FOREIGN KEY (wp_posts_ID)
    //     REFERENCES  $table_wp_posts (ID)
    //     ON DELETE NO ACTION
    //     ON UPDATE NO ACTION

    dbDelta($sql_missions);

    $sql_missions_has_vehicles = "CREATE TABLE IF NOT EXISTS $table_moved_out_vehicles
    (
        mission_id        INT NOT NULL ,
        vehicle_id        VARCHAR(12) NOT NULL ,
        PRIMARY KEY  (mission_id, vehicle_id)
    )
    CHARACTER SET utf8
    COLLATE utf8_general_ci;
    ";
    // KEY fk_einsaetze_has_fahrzeuge_fahrzeuge1 (fahrzeuge_id ASC) ,
    // KEY fk_einsaetze_has_fahrzeuge_einsaetze (einsaetze_id ASC) ,
    //    CONSTRAINT fk_einsaetze_has_fahrzeuge_einsaetze
    //      FOREIGN KEY  (einsaetze_id)
    //      REFERENCES $table_missions (id)
    //      ON DELETE NO ACTION
    //      ON UPDATE NO ACTION,
    //    CONSTRAINT fk_einsaetze_has_fahrzeuge_fahrzeuge1
    //      FOREIGN KEY  (fahrzeuge_id)
    //      REFERENCES $table_vehicles (id)
    //      ON DELETE NO ACTION
    //      ON UPDATE NO ACTION
    dbDelta($sql_missions_has_vehicles);

    // $wpdb->print_error();

    $sql_settings = "CREATE TABLE IF NOT EXISTS $table_settings
    (
        id VARCHAR(255) NOT NULL ,
        value VARCHAR(2083) NOT NULL ,
        PRIMARY KEY  (id)
    )
    CHARACTER SET utf8
    COLLATE utf8_general_ci;
    ";
    dbDelta($sql_settings);
}

function plugin_deactivation()
{
    unregister_post_type('mission');
    flush_rewrite_rules();
}
