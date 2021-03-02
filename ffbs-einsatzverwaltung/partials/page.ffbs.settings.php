<?php

final class FFBSEinsatzverwaltungSettingsPage
{
    private static $instance = null;

    public static function get_instance(): FFBSEinsatzverwaltungSettingsPage
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    private function __construct()
    {
        $this->db_handler = DatabaseHandler::get_instance();
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    private function __wakeup()
    {
    }

    public function display()
    {
        $args = array(
            'hide_empty' => false
        );

        $categories = get_categories($args);
        $setting = $this->db_handler->get_settings('cat_id');
        $this->set_selector_for_dropdown_value($setting->value);
?>
        <div class="wrap">
            <h2>Settings</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="categorie">Kategorie</label>
                    <select class="custom-select mr-sm-2" id="category">
                        <option value="0" selected>Kategorien...</option>
                        <?php foreach ($categories as $category) { ?>
                            <option value="<?php echo $category->cat_ID; ?>"><?php echo $category->name; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary save-cat">save</button>
                </div>
            </form>
        </div>
<?php
    }

    public function set_selector_for_dropdown_value($value)
    {
        $script = "
        <script type='text/javascript'>
         jQuery(document).ready(function($) {
            $('#category').val(" . $value . ");
        });
        </script>";
        echo $script;
    }
}
