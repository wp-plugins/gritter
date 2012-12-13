<?php

//  Plugin Name: Gritter Plugin
//  Plugin URI: http://www.jordan-code.de
//  Description: Dieses Plugin stellt angepasste Gritter Funktionalitaeten bereit.
//  Author: Felix Jordan
//  Version: 0.1
//  License: GPLv2 or later
//  License URI: http://www.gnu.org/licenses/gpl-2.0.html
define('GRITTER_VERSION', '0.1');
define('GRITTER_PLUGIN_URL', WP_PLUGIN_DIR . '/gritter/');
define('GRITTER_REQUIRED_WP_VERSION', '3.4.0');
define('GRITTER_OPTION_NAME', 'gritter-plugin.settings');
if (!class_exists('gritter')) {

    /**
     *  gritter class the main class of the plugin.
     *  
     */
    class gritter {

        private $success = array();
        private $wrap_red = '<div id="setting-error-invalid_home" class="error settings-error"><p>';
        private $wrap_yellow = '<div id="setting-error-settings_updated" class="updated settings-error"><p>';
        private $wrap_end = '</p></div>';

        /**
         * this is the constructor function
         * this is used to call the default actions needed for the plugin
         * @params none
         * @return none
         */
        function gritter() {
            add_action('init', array(&$this, 'init'));
        }

        /**
         * Starts the session if no session_id is set.
         */
        function enable_sessions() {
            if (!session_id())
                session_start();
        }

        /**
         * init is used to call the methods to add the plugin menu in backed and load 
         * required javascripts
         * @params none
         * @return none
         */
        function init() {
            global $wp_version, $gritter_db_version;
            $gritter_db_version = GRITTER_VERSION;
            if (version_compare($wp_version, GRITTER_REQUIRED_WP_VERSION, '>=')) {
//                reloaded_gallery_shortcode();
                if (!session_id())
                    session_start();
                if (!isset($_SESSION['gritter_error']))
                    $_SESSION['gritter_error'] = new WP_Error(null, null, null);
                if (!isset($_SESSION['gritter_data']))
                    $_SESSION['gritter_data'] = false;
                add_action('admin_menu', array(&$this, 'gritter_menu'));
                add_action('wp_enqueue_scripts', array(&$this, 'load_custom_script'));
                add_action('wp_head', array(&$this, 'enable_sessions'));
                add_action('wp_footer', array(&$this, 'script_output'));
                add_action('plugins_loaded', array(&$this, 'gritter_update_db_check'));
                add_filter("plugin_action_links_" . plugin_basename(__FILE__), array(&$this, 'gritter_plugin_settings_link'));
                if (!get_option(GRITTER_OPTION_NAME)) {
                    update_option(GRITTER_OPTION_NAME, array('seed' => 'Ydmh'));
                }
            } else {
                $this->deactivate();
            }
        }

        /**
         * this function will register and load the required javascript file to perform the required
         * functionality
         * @params none
         * @return none
         */
        function load_custom_script() {
            wp_register_script('gritter', '/' . PLUGINDIR . '/' . 'gritter' . '/' . 'js' . '/' . 'gritter.min.js');
            wp_enqueue_script('gritter');
            wp_register_style('gritter', '/' . PLUGINDIR . '/' . 'gritter' . '/' . 'css' . '/' . 'jquery.gritter.css');
            wp_enqueue_style('gritter');
        }

        /**
         * This function will actually add the admin menu and calls the call back menu for creating the 
         * view page which is to be displayed by the plugin
         * @params none
         * @return none
         */
        function gritter_menu() {
            add_options_page('Gritter', 'Gritter', 'administrator', 'gritter', array(&$this, 'settings_page') // function callback
            );
        }

        /**
         * This function will be called when the plugin is activated
         * @params none
         * @return none
         */
        function install() {
            global $wp_version, $wpdb, $gritter_db_version;
            $gritter_db_version = GRITTER_VERSION;
            if (!version_compare($wp_version, GRITTER_REQUIRED_WP_VERSION, '>=')) {
                trigger_error('<strong>Mindestens WordPress Version ' . GRITTER_REQUIRED_WP_VERSION . ' erforderlich.</strong>', E_USER_ERROR);
            }
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $installed_ver = get_option("gritter_db_version");

            if ($installed_ver != $gritter_db_version) {
                $sql_1 =
                        'CREATE TABLE ' . $wpdb->prefix . 'gritter_group (
                        id INT NOT NULL AUTO_INCREMENT ,
                        title LONGTEXT NULL ,
                        logic LONGTEXT NULL ,
                        random VARCHAR(45) NULL ,
                        PRIMARY KEY (id) ,
                        UNIQUE INDEX id_UNIQUE (id ASC))';
                $sql_2 =
                        'CREATE TABLE ' . $wpdb->prefix . 'gritter_layer (
                        id INT NOT NULL AUTO_INCREMENT ,
                        title VARCHAR(255) NULL ,
                        text LONGTEXT NULL ,
                        timeout VARCHAR(45) NULL ,
                        active TINYINT NULL ,
                        group_id INT NULL ,
                        PRIMARY KEY (id) ,
                        UNIQUE INDEX id_UNIQUE (id ASC),
                        INDEX fk_group_id_idx (group_id ASC),
                        CONSTRAINT fk_group_id
                        FOREIGN KEY (group_id)
                        REFERENCES ' . $wpdb->prefix . 'gritter_group (id)
                        ON DELETE NO ACTION
                        ON UPDATE NO ACTION);';

                if (dbDelta($sql_1) && dbDelta($sql_2)) {
                    add_option("gritter_db_version", $gritter_db_version);
                    $wpdb->insert($wpdb->prefix . 'gritter_group', array('title' => 'Default', 'random' => 0, 'logic' => ''));
                } else {
                    trigger_error('<strong>Datenbanktabellen konnten nicht erstellt werden.</strong>', E_USER_ERROR);
                }
            }
        }

        // Add settings link on plugin page
        function gritter_plugin_settings_link($links) {
            $settings_link = '<a href="options-general.php?page=gritter">Einstellungen</a>';
            array_unshift($links, $settings_link);
            return $links;
        }

        /**
         * If there is a new version installed, then update the database tables.
         * @global type $gritter_db_version
         */
        function gritter_update_db_check() {
            global $gritter_db_version;
            if (get_site_option('gritter_db_version') != $gritter_db_version) {
                $this->install();
            }
        }

        /**
         * This function will be called when the plugin is uninstalled
         * @params none
         * @return none
         * @global type $wpdb
         * @global type $gritter_db_version 
         */
        function uninstall() {
            global $wpdb, $gritter_db_version;
            $sql_1 =
                    'DROP TABLE `' . $wpdb->prefix . 'gritter_group`;';
            $sql_2 =
                    'DROP TABLE `' . $wpdb->prefix . 'gritter_layer`;';
            $wpdb->get_results($sql_1);
            $wpdb->get_results($sql_2);
            delete_option("gritter_db_version");
        }

        /**
         * Prints the error messages.
         * @return string
         */
        function error_output() {
            if (is_wp_error($_SESSION['gritter_error']) && count($_SESSION['gritter_error']->errors) > 0) {
                echo $this->wrap_red;


                foreach ($_SESSION['gritter_error']->errors as $key => $value) {
                    foreach ($value as $msg) {
                        echo '<strong>' . $key . ': ' . $msg . '</strong><br />';
                    }
                }
                echo $this->wrap_end;
                unset($_SESSION['gritter_error']);
            }
            return '';
        }

        /**
         * Prints the success messages.
         * @param type $msg
         * @return string
         */
        function success_output($msg = null) {
            if (!empty($msg)) {
                echo $this->wrap_yellow;
                if (is_array($msg)) {
                    foreach ($msg as $value) {
                        echo '<strong>' . $value . '</strong><br />';
                    }
                } else {
                    echo '<strong>' . $msg . '</strong>';
                }
                echo $this->wrap_end;
            }
            return '';
        }

        /**
         * Explode the string delemiter ','.
         * @param string $input
         * @return array
         */
        function explode_string($input = null) {
            if (!empty($input)) {
                $values = explode(',', $input);
                foreach ($values as $value) {
                    $return_array[] = trim($value);
                }
                return $return_array;
            }
            return array();
        }

        /**
         * Entfernt alle Sonderzeichen und Umlaute, wird für die URL-Erstellung genutzt.
         * @param String $zeichenkette
         * @return String $zeichenkette gibt die bereinigte Zeichenkette zurück.
         */
        function replace_specialchars($zeichenkette) {
            $replacePairs = array(
                'ä' => 'ae',
                'ö' => 'oe',
                'ü' => 'ue',
                'Ä' => 'Ae',
                'Ö' => 'Oe',
                'Ü' => 'Ue',
                'ß' => 'ss',
            );
            $zeichenkette = strtr($zeichenkette, $replacePairs);
//            $zeichenkette = preg_replace("/[^a-zA-Z0-9_-]/", "", $zeichenkette);
//            $zeichenkette = strtolower($zeichenkette);
            return $zeichenkette;
        }

        /**
         * Output the header.
         * @param string $h2: The headline for the header.
         * @param boolean $links: Display links or not.
         */
        private function __output_header($h2, $links = false) {
            echo '<div class="wrap">';
            echo '<h2>' . $h2 . '</h2><br />';
            if ($links === TRUE) {
                echo '<a class="" href="./options-general.php?page=gritter">Layer</a>';
                echo ' | ';
                echo '<a class="" href="./options-general.php?page=gritter&plugin_page=groups">Gruppen</a>';
                echo ' | ';
                echo '<a class="" href="./options-general.php?page=gritter&plugin_page=settings">Einstellungen</a>';
            }
        }

        /**
         * Outputs the footer.
         */
        private function __output_footer() {
            echo '<br /><br />';
            echo 'If you find my work useful, please donate:';
            echo '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">';
            echo '<input type="hidden" name="cmd" value="_donations">';
            echo '<input type="hidden" name="business" value="feranx@gmx.de">';
            echo '<input type="hidden" name="lc" value="DE">';
            echo '<input type="hidden" name="item_name" value="Gritter">';
            echo '<input type="hidden" name="no_note" value="0">';
            echo '<input type="hidden" name="currency_code" value="EUR">';
            echo '<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest">';
            echo '<input type="image" src="https://www.paypalobjects.com/de_DE/DE/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="Jetzt einfach, schnell und sicher online bezahlen – mit PayPal.">';
            echo '<img alt="" border="0" src="https://www.paypalobjects.com/de_DE/i/scr/pixel.gif" width="1" height="1">';
            echo '</form>';

            echo "<br /><br />Version " . GRITTER_VERSION;
            echo '</div>';
        }

        /**
         * Sanitize the given data.
         * @param type $send_data
         * @param type $option: Which data is given, of layer data or group data.
         * @return type sanitized data.
         */
        function sanitize_post_data($send_data, $option = 'layer') {

            switch ($option) {
                case 'group':
                    $data = array(
                        'title' => '',
                        'logic' => '',
                        'random' => '',
                    );
                    break;
                default:
                    $data = array(
                        'title' => '',
                        'group_id' => '',
                        'text' => '',
                        'active' => '',
                        'timeout' => '',
                    );
                    break;
            }

            foreach ($send_data as $key => $value) {
                switch ($key) {
                    case 'title':
                        $data['title'] = sanitize_text_field($value);
                        break;
                    case 'group_id':
                        $data['group_id'] = sanitize_text_field($value);
                        break;
                    case 'text':
                        $data['text'] = sanitize_text_field($value);
                        break;
                    case 'active':
                        $data['active'] = sanitize_text_field($value);
                        break;
                    case 'logic':
                        $data['logic'] = sanitize_text_field($value);
                        break;
                    case 'timeout':
                        $data['timeout'] = sanitize_text_field($value);
                        break;
                    case 'random':
                        $data['random'] = sanitize_text_field($value);
                        break;
                    default:
                        break;
                }
            }
            return $data;
        }

        /**
         * Page to create a new group
         */
        function create_new_group() {
            if (isset($_POST['submit'])) {
                $data = $this->sanitize_post_data($_POST, 'group');
                if ($this->setData($data, 'group')) {
                    $this->success = 'Neue Gruppe gespeichert!';
                    $_SESSION['gritter-plugin.success'] = $this->success;
                } else {
                    $_SESSION['gritter_error']->add('Erstellen', 'Fehler beim Erstellen.');
                }
                wp_redirect('./options-general.php?page=gritter&plugin_page=groups');
                exit;
            }

            $this->__output_header('Gritter - Gruppe - Neu <a class="add-new-h2" href="./options-general.php?page=gritter&plugin_page=groups">Zur&uuml;ck</a>');

            $this->form_output_group();

            $this->__output_footer();
        }

        /**
         * Page to create a new layer
         */
        function create_new_layer() {
            if (isset($_POST['submit'])) {
                $data = $this->sanitize_post_data($_POST);
                if ($this->setData($data)) {
                    $this->success = 'Neuer Layer gespeichert!';
                    $_SESSION['gritter-plugin.success'] = $this->success;
                } else {
                    $_SESSION['gritter_error']->add('Erstellen', 'Fehler beim Erstellen.');
                }
                wp_redirect('./options-general.php?page=gritter');
                exit;
            }

            $this->__output_header('Gritter - Neu <a class="add-new-h2" href="./options-general.php?page=gritter">Zur&uuml;ck</a>');

            $this->form_output_layer();

            $this->__output_footer();
        }

        /**
         * Returns alls groups as object.
         * @global type $wpdb
         * @return type
         */
        function getGroupIds() {
            global $wpdb;
            return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'gritter_group;');
        }

        /**
         * Output for the group form (create form or if variable are set the edit form).
         * @param type $title
         * @param type $logic
         * @param type $random
         */
        function form_output_group($title = '', $logic = '', $random = '') {
            echo '<div id="form_container">';
            echo '<form method="post" action="' . $_SERVER['REQUEST_URI'] . '" class="redirection_form" id = "gritter_form" enctype="multipart/form-data">';

            echo '<table class="form-table">';
            echo '<tbody>';
            echo '<tr>';
            echo '<th>';
            echo '<label for="title">Title</label>';
            echo '</th>';
            echo '<td>';
            echo '<input id="title" class="regular-text code" type="text" value="' . $title . '" name="title" kl_virtual_keyboard_secure_input="on">';
            echo '</td>';
            echo '</tr>';

            echo '<tr>';
            echo '<th>';
            echo '<label for="logic">Logik</label>';
            echo '</th>';
            echo '<td>';
            echo '<textarea id="logic" class="regular-text code" name="logic" kl_virtual_keyboard_secure_input="on">' . $logic . '</textarea>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<th>';
            echo '<label for="random">Random</label>';
            echo '</th>';
            echo '<td>';
            echo '<input id="random" class="regular-text code" type="text" value="' . $random . '" name="random" kl_virtual_keyboard_secure_input="on">';
            echo '</td>';
            echo '</tr>';
            echo '</tbody>';
            echo '</table>';
            echo '<p class="submit">';
            echo '<input id="submit" class="button-primary" type="submit" value="Änderungen übernehmen" name="submit">';
            echo '</p>';

            echo '</form>';
        }

        /**
         * Output for the layer form (create form or if variable are set the edit form).
         * @param type $title
         * @param type $group
         * @param type $text
         * @param type $active
         * @param type $timeout
         */
        function form_output_layer($title = '', $group = '', $text = '', $active = 1, $timeout = '') {
            echo '<div id="form_container">';
            echo '<form method="post" action="' . $_SERVER['REQUEST_URI'] . '" class="redirection_form" id = "gritter_form" enctype="multipart/form-data">';

            echo '<table class="form-table">';
            echo '<tbody>';
            echo '<tr>';
            echo '<th>';
            echo '<label for="title">Title</label>';
            echo '</th>';
            echo '<td>';
            echo '<input id="title" class="regular-text code" type="text" value="' . $title . '" name="title" kl_virtual_keyboard_secure_input="on">';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<th>';
            echo '<label for="group">Gruppe</label>';
            echo '</th>';
            echo '<td>';

            $groups = $this->getGroupIds();

            echo '<select name="group_id" id="group_id">';
            foreach ($groups as $group_out) {
                if ($group_out->title == $group) {
                    echo '<option value="' . $group_out->id . '" selected="selected">' . $group_out->title . '</option>';
                } else {
                    echo '<option value="' . $group_out->id . '">' . $group_out->title . '</option>';
                }
            }
            echo '</select>';

            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<th>';
            echo '<label for="group">Text</label>';
            echo '</th>';
            echo '<td>';
            echo '<textarea id="text" class="regular-text code" name="text" kl_virtual_keyboard_secure_input="on">' . $text . '</textarea>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<th>';
            echo '<label for="timeout">Timeout</label>';
            echo '</th>';
            echo '<td>';
            echo '<input id="timeout" class="regular-text code" type="text" value="' . $timeout . '" name="timeout" kl_virtual_keyboard_secure_input="on">';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<th>';
            echo '<label for="users_can_register">Aktiv</label>';
            echo '<td>';
            if ($active == 1) {
                echo '<input id="active" type="checkbox" value="1" name="active" checked="checked">';
            } else {
                echo '<input id="active" type="checkbox" value="1" name="active"';
            }
            echo '</td>';
            echo '</tr>';
            echo '</tbody>';
            echo '</table>';
            echo '<p class="submit">';
            echo '<input id="submit" class="button-primary" type="submit" value="Änderungen übernehmen" name="submit">';
            echo '</p>';

            echo '</form>';
        }

        function search_in_array($needle = null, $array_key = 'ID', $haystack = null) {
            $wildcard = FALSE;
            if (!isset($haystack)) {
                $haystack = &$this->getData();
            }
            if (!isset($array_key) || empty($array_key))
                $wildcard = TRUE;
            if (isset($needle) && is_array($haystack)) {
                foreach ($haystack as $hay_key => $hay_value) {
                    foreach ($hay_value as $key => $value) {
                        if ($key == $array_key || $wildcard === TRUE) {
                            if ($value == $needle) {
                                return $hay_key;
                            }
                        }
                    }
                }
            }
            return false;
        }

        /**
         * To edit a group.
         */
        function edit_group($group_id = null) {
            if (empty($group_id)) {
                $_SESSION['gritter_error']->add('Weiterleitung', 'Keine Group ID gefunden!');
                wp_redirect('./options-general.php?page=gritter&plugin_page=groups');
                exit;
            }

            $option_data = $this->getData('group', $group_id);
            $option_data = $option_data[0];

            if (isset($_POST['submit'])) {
                $data = $this->sanitize_post_data($_POST, 'group');
                if ($this->setData($data, 'group', $group_id)) {
                    $this->success = '&Auml;nderungen gespeichert!';
                    $_SESSION['gritter-plugin.success'] = $this->success;
                } else {
                    $_SESSION['gritter_error']->add('Erstellen', 'Fehler beim &Auml;ndern!');
                }
                wp_redirect('./options-general.php?page=gritter&plugin_page=groups');
                exit;
            }

            $this->__output_header('Gitter - Gruppe - Editieren <a class="add-new-h2" href="./options-general.php?page=gritter&plugin_page=groups">Zur&uuml;ck</a>');


            $this->form_output_group($option_data['title'], $option_data['logic'], $option_data['random']);

            $this->__output_footer();
        }

        /**
         * To edit a layer
         */
        function edit_layer($layer_id = null) {
            if (empty($layer_id)) {
                $_SESSION['gritter_error']->add('Weiterleitung', 'Keine Layer ID gefunden!');
                wp_redirect('./options-general.php?page=gritter');
                exit;
            }

            $option_data = $this->getData('layer', $layer_id);
            $option_data = $option_data[0];

            if (isset($_POST['submit'])) {
                $data = $this->sanitize_post_data($_POST);
                if ($this->setData($data, 'layer', $layer_id)) {
                    $this->success = '&Auml;nderungen gespeichert!';
                    $_SESSION['gritter-plugin.success'] = $this->success;
                } else {
                    $_SESSION['gritter_error']->add('Erstellen', 'Fehler beim &Auml;ndern!');
                }
                wp_redirect('./options-general.php?page=gritter');
                exit;
            }

            $this->__output_header('Gitter - Editieren <a class="add-new-h2" href="./options-general.php?page=gritter">Zur&uuml;ck</a>');


            $this->form_output_layer($option_data['title'], $option_data['group_id'], $option_data['text'], $option_data['active'], $option_data['timeout']);

            $this->__output_footer();
        }

        /**
         * Delete function. For bulk actions, for groups or layers and for single actions.
         * @global type $wpdb
         * @param type $ids
         * @param type $option: layer or group
         * @return boolean
         */
        function delete_bulk($ids, $option) {
            global $wpdb;
            $sql = '';
            switch ($option) {
                case 'group':
                    $group_count = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'gritter_group'));
                    if ($group_count > 1) {
                        $sql = 'DELETE FROM ' . $wpdb->prefix . 'gritter_group';
                    }
                    break;
                case 'layer':
                    $sql = 'DELETE FROM ' . $wpdb->prefix . 'gritter_layer';
                    break;
                default:
                    break;
            }
            $sql .= ' WHERE id  IN(' . implode(',', $ids) . ')';
            if ($wpdb->query($sql)) {
                return true;
            }
        }

        /**
         * Get the data from the database. For layer or group.
         * @global type $wpdb
         * @param type $option: layer or group
         * @param type $id
         * @return boolean
         */
        function getData($option = 'layer', $id = null) {
            global $wpdb;
            switch ($option) {
                case 'group':
                    if (empty($id)) {
                        return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'gritter_group', ARRAY_A);
                    } else {
                        return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'gritter_group WHERE id = ' . $id, ARRAY_A);
                    }
                    break;
                case 'layer':
                    if (empty($id)) {
                        return $wpdb->get_results('SELECT l.id as id, l.title as title, l.text as text, l.timeout as timeout, l.active as active, g.title as group_id FROM ' . $wpdb->prefix . 'gritter_layer l, ' . $wpdb->prefix . 'gritter_group g WHERE l.group_id = g.id;', ARRAY_A);
                    } else {
                        return $wpdb->get_results('SELECT l.id as id, l.title as title, l.text as text, l.timeout as timeout, l.active as active, g.title as group_id FROM ' . $wpdb->prefix . 'gritter_layer l, ' . $wpdb->prefix . 'gritter_group g WHERE l.group_id = g.id AND l.id = ' . $id . ';', ARRAY_A);
                    }
                    break;
                default:
                    break;
            }
            return false;
        }

        /**
         * Sets the data to the database. For group or layer.
         * @global type $wpdb
         * @param type $option: layer or group
         * @param type $id
         * @return boolean
         */
        function setData($data = null, $option = 'layer', $id = null) {
            global $wpdb;
            if (!empty($data)) {
                switch ($option) {
                    case 'group':
                        if (empty($id)) {
                            if ($wpdb->insert($wpdb->prefix . 'gritter_group', $data)) {
                                return true;
                            }
                        } else {
                            if ($wpdb->update($wpdb->prefix . 'gritter_group', $data, array('id' => $id))) {
                                return true;
                            }
                        }
                        return true;
                        break;

                    case 'layer':
                        if (empty($id)) {
                            if ($wpdb->insert($wpdb->prefix . 'gritter_layer', $data))
                                return true;
                        }
                        else {
                            if ($wpdb->update($wpdb->prefix . 'gritter_layer', $data, array('id' => $id)))
                                return true;
                        }
                        breal;

                    default:
                        break;
                }
            }
        }

        /**
         * Function to display the group settings page.
         */
        function groups() {
            if (isset($_POST['action'])) {
                if ($_POST['action'] == 'delete_group' && isset($_POST['group']) && is_array($_POST['group'])) {
                    if ($this->delete_bulk($_POST['group'], 'group')) {
                        $this->success = 'Gruppen wurden entfernt!';
                        $_SESSION['gritter-plugin.success'] = $this->success;
                    } else {
                        $_SESSION['gritter_error']->add('Entfernen', 'Fehler beim L&ouml;schen!');
                    }
                }
            }

            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'edit':
                        if (isset($_GET['group']) && !empty($_GET['group']))
                            $this->edit_group($_GET['group']);
                        die();
                        break;
                    case 'new':
                        $this->create_new_group();
                        die();
                        break;
                    case 'delete':
                        if (isset($_GET['group']) && !empty($_GET['group'])) {
                            if ($this->delete_bulk(array($_GET['group']), 'group')) {
                                $this->success = 'Gruppe wurden entfernt!';
                                $_SESSION['gritter-plugin.success'] = $this->success;
                            } else {
                                $_SESSION['gritter_error']->add('Entfernen', 'Fehler beim L&ouml;schen!');
                            }
                        }
                        break;
                    default:
                        break;
                }
            }

            $sortable_columns = array(
                'title' => array('title', false),
                'logic' => array('logic', false),
                'random' => array('random', false),
            );
            $columns = array(
                'cb' => '<input type="checkbox" />',
                'title' => 'Titel',
                'logic' => 'Logik',
                'random' => 'Zufall',
            );
            $this->__output_header('Gritter - Gruppen  <a class="add-new-h2" href="./options-general.php?page=gritter&plugin_page=groups&action=new">Erstellen</a> <a class="add-new-h2" href="./options-general.php?page=gritter">Zur&uuml;ck</a>', TRUE);
            if (!class_exists('Gritter_Plugin_Table')) {
                require_once( GRITTER_PLUGIN_URL . 'gritter-plugin-table.php' );
            }
            //Prepare Table of elements
            if (!class_exists('Gritter_Plugin_Table')) {
                require_once( GRITTER_PLUGIN_URL . 'gritter-plugin-table.php' );
            }
            $wp_list_table = new Gritter_Plugin_Table();
            $wp_list_table->setOption('group');
            $wp_list_table->setPluginPage('groups');
            $wp_list_table->setColumns($columns);
            $wp_list_table->setSortableColumns($sortable_columns);
            //Get the refrence to the table_data
            $table_data = &$wp_list_table->getTableData();

            //Changing value of table_data
            $table_data = $this->getData('group');

            $wp_list_table->prepare_items();

            if (isset($_SESSION['gritter-plugin.success'])) {
                $this->success = $_SESSION['gritter-plugin.success'];
                unset($_SESSION['gritter-plugin.success']);
            }
            echo $this->success_output($this->success);
            echo $this->error_output();
            echo '<form method="post" action="' . $_SERVER['REQUEST_URI'] . '" class="redirection_form" id = "gritter_form" enctype="multipart/form-data">';

            $wp_list_table->display();

            echo '</form>';
            $this->__output_footer();
        }

        /**
         * Display the page for the settings.
         */
        function settings() {
            $option = get_option(GRITTER_OPTION_NAME);
            if (!$option) {
                $this->init();
                $_SESSION['gritter_error']->add('Fehler', 'Keine Einstellungen gefunden, bitte versuchen Sie es erneut.');
                wp_redirect('./options-general.php?page=gritter');
            }
            if (isset($_POST['submit'])) {
                $option['seed'] = sanitize_text_field($_POST['seed']);

                if (update_option(GRITTER_OPTION_NAME, $option)) {
                    $this->success = 'Einstellungen gespeichert!';
                    $_SESSION['gritter-plugin.success'] = $this->success;
                } else {
                    $_SESSION['gritter_error']->add('Einstellungen', 'Fehler beim Speichern!');
                }
            }

            $this->__output_header('Gritter - Einstellungen  <a class="add-new-h2" href="./options-general.php?page=gritter">Zur&uuml;ck</a>', TRUE);

            //Default seed values:
            $seed = array('Y', 'Yd', 'Ydm', 'Ydmh', 'Ydmhi', 'Ydmhis');

            echo $this->success_output($this->success);
            echo $this->error_output();
            echo '<div id="form_container">';
            echo '<form method="post" action="' . $_SERVER['REQUEST_URI'] . '" class="redirection_form" id = "gritter_form" enctype="multipart/form-data">';

            echo '<table class="form-table">';
            echo '<tbody>';
            echo '<tr>';
            echo '<th>';
            echo '<label for="seed">Seed</label>';
            echo '</th>';
            echo '<td>';
            echo '<select name="seed" id="seed">';
            foreach ($seed as $s) {
                if ($s == $option['seed']) {
                    echo '<option value="' . $s . '" selected="selected">' . $s . '</option>';
                } else {
                    echo '<option value="' . $s . '">' . $s . '</option>';
                }
            }
            echo '</select>';
            echo '</td>';
            echo '</tr>';
            echo '</tbody>';
            echo '</table>';
            echo '<p class="submit">';
            echo '<input id="submit" class="button-primary" type="submit" value="Änderungen übernehmen" name="submit">';
            echo '</p>';

            echo '</form>';

            $this->__output_footer();
        }

        /**
         * This function will display the form to enter data for the user and calls
         * the save function to save the data into the database
         * @params none
         * @return none
         * @global type $wpdb
         * @global type $wpdb 
         */
        function settings_page() {
            if (isset($_GET['plugin_page']) && !empty($_GET['plugin_page'])) {
                switch ($_GET['plugin_page']) {
                    case 'new':
                        $this->create_new_layer();
                        die();
                        break;
                    case 'groups':
                        $this->groups();
                        die();
                        break;
                    case 'settings':
                        $this->settings();
                        die();
                        break;

                    default:
                        break;
                }
            }
            if (isset($_POST['action'])) {
                if ($_POST['action'] == 'delete_layer' && isset($_POST['layer']) && is_array($_POST['layer'])) {
                    if ($this->delete_bulk($_POST['layer'], 'layer')) {
                        $this->success = 'Layer wurden entfernt!';
                        $_SESSION['gritter-plugin.success'] = $this->success;
                    } else {
                        $_SESSION['gritter_error']->add('Entfernen', 'Fehler beim L&ouml;schen!');
                    }
                }
            }

            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'edit':
                        if (isset($_GET['layer']) && !empty($_GET['layer']))
                            $this->edit_layer($_GET['layer']);
                        die();
                        break;
                    case 'delete':
                        if (isset($_GET['layer']) && !empty($_GET['layer'])) {
                            if ($this->delete_bulk(array($_GET['layer']), 'layer')) {
                                $this->success = 'Layer wurden entfernt!';
                                $_SESSION['gritter-plugin.success'] = $this->success;
                            } else {
                                $_SESSION['gritter_error']->add('Entfernen', 'Fehler beim L&ouml;schen!');
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
            if (!class_exists('Gritter_Plugin_Table')) {
                require_once( GRITTER_PLUGIN_URL . 'gritter-plugin-table.php' );
            }
            //Prepare Table of elements
            $wp_list_table = new Gritter_Plugin_Table();

            //Get the refrence to the table_data
            $table_data = &$wp_list_table->getTableData();

            //Changing value of table_data
            $table_data = $this->getData();

            $wp_list_table->prepare_items();

            $html_output = '';

            if (isset($_SESSION['gritter-plugin.success'])) {
                $this->success = $_SESSION['gritter-plugin.success'];
                unset($_SESSION['gritter-plugin.success']);
            }
            echo $this->success_output($this->success);
            echo $this->error_output();
            $this->__output_header('Gritter - Layer <a class="add-new-h2" href="./options-general.php?page=gritter&plugin_page=new">Erstellen</a>', TRUE);

            echo '<form method="post" action="' . $_SERVER['REQUEST_URI'] . '" class="redirection_form" id = "gritter_form" enctype="multipart/form-data">';
            echo '<input type="hidden" name="page" value="" />';


//            
//            $wp_list_table->search_box('Suchen', 'search_id');

            $wp_list_table->display();

            echo '</form>';
            echo $html_output;

            $this->__output_footer();
        }

        /**
         * Deactived the plugin.
         * 
         * @params none
         * @return none
         */
        function deactivate() {
            $option = 'active_plugins';
            $activated_plugins = get_option($option);
            foreach ($activated_plugins as $key => $value) {
                if ($value == 'gritter/gritter-plugin.php') {
                    unset($activated_plugins[$key]);
                }
            }
            update_option($option, $activated_plugins);
        }

        /**
         * Checks if a category logic is set.
         * @param type $logic
         * @return boolean
         */
        function checkCategories($logic = null) {
            if (!empty($logic)) {
                $matches = false;
                if (preg_match('~(category)+(#)+([0-9,])+(#)~', $logic, $matches)) {
                    $categories = explode('#', $matches[0]);
                    return $categories[1];
                }
            }
            return false;
        }

        /**
         * Replace all shortcodes in value.
         * @param type $value
         * @return type $value
         */
        function checkShortcodes($value = null) {
            $this->generateSeed();
            if (!empty($value)) {
                if (preg_match('~(\[RND)+(#)+([0-9,])+(#)+(\])~', $value, $matches)) {
                    $temp_explode = explode('#', $matches[0]);
                    $rand_range = explode(',', $temp_explode[1]);
                    $random = mt_rand($rand_range[0], $rand_range[1]);
                    $value = str_replace($matches[0], $random, $value);
                }
                if (preg_match('~(\[RNDNOSEED)+(#)+([0-9,])+(#)+(\])~', $value, $matches)) {
                    $temp_explode = explode('#', $matches[0]);
                    $rand_range = explode(',', $temp_explode[1]);
                    mt_srand();
                    $random = mt_rand($rand_range[0], $rand_range[1]);
                    $this->generateSeed();
                    $value = str_replace($matches[0], $random, $value);
                }

                if (preg_match('~(\[RNDDAY)+(#)+([0-9,])+(#)+(\])~', $value, $matches)) {
                    $temp_explode = explode('#', $matches[0]);
                    $rand_range = explode(',', $temp_explode[1]);
                    $random = mt_rand($rand_range[0], $rand_range[1]);
                    switch ($random) {
                        case 0:
                            $random = 'heute';
                            break;
                        case 1:
                            $random = 'vor einem Tag';
                            break;
                        default:
                            $random = ' vor ' . $random;
                            $random .= ' Tagen';
                            break;
                    }

                    $value = str_replace($matches[0], $random, $value);
                }
                if (preg_match('~(\[RND)+([A-Za-z])+(#)+([0-9,])+(#)+(\])~', $value, $matches)) {
                    $seed = str_replace($matches[1], '', $matches[0]);
                    $seed = substr($seed, 0, strpos($seed, '#'));
                    $temp_explode = explode('#', $matches[0]);
                    $rand_range = explode(',', $temp_explode[1]);
                    mt_srand(date($seed));
                    $random = mt_rand($rand_range[0], $rand_range[1]);
                    $this->generateSeed();
                    $value = str_replace($matches[0], $random, $value);
                }
                if (preg_match('~(\[CITY\])~', $value, $matches)) {
//                    $tags = get_meta_tags('http://www.geobytes.com/IpLocator.htm?GetLocation&template=php3.txt&IpAddress=' . $_SERVER['REMOVE_ADDR']);
                    if (!class_exists('Gritter_Plugin_Cities')) {
                        require_once( GRITTER_PLUGIN_URL . 'gritter-plugin-cities.php' );
                    }
                    $gritter_city = new Gritter_Plugin_Cities(true);
                    $value = str_replace($matches[0], $gritter_city->city, $value);
                }
            }
            return $value;
        }

        /**
         * Check the session if a seed is set. Otherwise sets a seed.
         */
        function generateSeed() {
            $option = get_option(GRITTER_OPTION_NAME);
            if (!isset($option['seed']) || empty($option['seed'])) {
                $option['seed'] = date('Ymdh');
            }
            $seed = get_the_ID() . date($option['seed']);
            if ((!isset($_SESSION['gritter-plugin.seed'])) || ($_SESSION['gritter-plugin.seed'] != $seed)) {
                $_SESSION['gritter-plugin.seed'] = $seed;
            }
            mt_srand($_SESSION['gritter-plugin.seed']);
        }

        /**
         * Main function to output all javascript to the footer.
         * @global type $wpdb
         */
        function script_output() {
            global $wpdb;
            $group_singles = array();
            if (is_single()) {
                $group_only_for_this = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'gritter_group g, ' . $wpdb->prefix . 'gritter_layer l WHERE g.logic LIKE "%single%' . get_the_ID() . '%" AND g.id = l.group_id AND l.active = 1 GROUP BY l.id', ARRAY_A);
                if ($group_only_for_this) {
                    $group_singles = $group_only_for_this;
                } else {
                    $group_singles = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'gritter_group g, ' . $wpdb->prefix . 'gritter_layer l WHERE g.logic LIKE "%single%" AND g.id = l.group_id  AND l.active = 1 GROUP BY l.id', ARRAY_A);
                }
            }
            if (is_page()) {
                $group_only_for_this = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'gritter_group g, ' . $wpdb->prefix . 'gritter_layer l WHERE g.logic LIKE "%page%' . get_the_ID() . '%" AND g.id = l.group_id  AND l.active = 1 GROUP BY l.id', ARRAY_A);
                if ($group_only_for_this) {
                    $group_singles = $group_only_for_this;
                } else {
                    $group_singles = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'gritter_group g, ' . $wpdb->prefix . 'gritter_layer l WHERE g.logic LIKE "%page%" AND g.id = l.group_id  AND l.active = 1 GROUP BY l.id', ARRAY_A);
                }
            }
            $to_unset = array();

            $this->generateSeed();

            foreach ($group_singles as $key => $item) {
                $category = $this->checkCategories($item['logic']);
                if ($category) {
                    $categories = explode(',', $category);
                    foreach ($categories as $cat) {
                        if (!in_category($cat)) {
                            $to_unset[] = $key;
                        }
                    }
                }
            }
            if (count($to_unset) > 0) {
                foreach ($to_unset as $unset) {
                    unset($group_singles[$unset]);
                }
            }

            if (count($group_singles) > 0) {
                switch ($group_singles[0]['random']) {
                    case '-1':
                        $data = $group_singles;
                        break;
                    case '0':
                        shuffle($group_singles);
                        $data = $group_singles;
                        break;
                    default:
                        if ($group_singles[0]['random'] > 0) {
                            $keys = array();
                            if ($group_singles[0]['random'] > count($group_singles))
                                $group_singles[0]['random'] = count($group_singles);
                            for ($i = 0; $i < $group_singles[0]['random']; $i++) {
                                do {
                                    $random = mt_rand(0, (count($group_singles) - 1));
                                } while (array_search($random, $keys) !== FALSE);
                                $keys[] = $random;
                            }
                            foreach ($keys as $key) {
                                $data[] = $group_singles[$key];
                            }
                        }
                        break;
                }

                foreach ($data as $key => $value) {
                    $data[$key]['title'] = $this->checkShortcodes($value['title']);
                    $data[$key]['text'] = $this->checkShortcodes($value['text']);
                }
            }
            if (count($data) > 0) {
                echo '<script type="text/javascript">';
                $i = 0;
                $output = '';
                $first_timeout = 0;
                foreach ($data as $layer) {
                    if ($i == 0) {
                        $timeout = $layer['timeout'];
                    } else {
                        $timeout = $timeout + $layer['timeout'];
                    }
                    echo 'var $=jQuery;';
                    echo "function message_welcome" . $i . "(){var unique_id=$.gritter.add({title:'" . $layer['title'] . "',text:'" . $layer['text'] . "',sticky:false,time:'',class_name:'my-sticky-class'});}";
                    $output .= "setTimeout(\"message_welcome" . $i . "()\"," . $timeout . ");";
                    $i++;
                }
                echo "$(document).ready(function(){" . $output . "});";
                echo '</script>';
            }
        }

    }

}

// class ends
if (class_exists('gritter')) {
    $gritter = new gritter();
}
if (isset($gritter)) {
    register_activation_hook(__FILE__, array('gritter', 'install'));
    register_deactivation_hook(__FILE__, array('gritter', 'deactivate'));
    register_uninstall_hook(__FILE__, array('gritter', 'uninstall'));
}