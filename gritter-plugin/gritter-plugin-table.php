<?php

//Our class extends the WP_List_Table class, so we need to make sure that it's there
if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * To display all tables in the admin area.
 */
class Gritter_Plugin_Table extends WP_List_Table {

    private $table_data = array(),
            $sortable_columns = array(
                'title' => array('title', false),
                'text' => array('text', false),
                'group_id' => array('group_id', false),
                'active' => array('active', false)
                    ),
            $columns = array(
                'cb' => '<input type="checkbox" />',
                'title' => 'Titel',
                'text' => 'Text',
                'group_id' => 'Gruppe',
                'active' => 'Aktiv'
                    ),
            $option = 'layer',
            $plugin_page = '';

    /**
     * Constructor, we override the parent to pass our own arguments
     * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
     */
    function __construct() {
        parent::__construct(array(
            'singular' => 'wp_list_text_link', //Singular label
            'plural' => 'wp_list_test_links', //plural label, also this well be one of the table css class
            'ajax' => false //We won't support Ajax for this table
        ));
    }

    /**
     * Add extra markup in the toolbars before or after the list
     * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
     */
    function extra_tablenav($which) {
        if ($which == "top") {
//The code that goes before the table is here
//            echo"Hello, I'm before the table";
        }
        if ($which == "bottom") {
//The code that goes after the table is there
//            echo"Hi, I'm after the table";
        }
    }

    /**
     * Decide which columns to activate the sorting functionality on
     * @return array $sortable, the array of columns that can be sorted by the user
     */
    function get_sortable_columns() {
        return $this->sortable_columns;
    }

    /**
     * Define the columns that are going to be used in the table
     * @return array $columns, the array of columns to use with the table
     */
    function get_columns() {
        return $this->columns;
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     */
    function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        usort($this->table_data, array(&$this, 'usort_reorder'));

        $per_page = 5;
        $current_page = $this->get_pagenum();
        $total_items = count($this->table_data);

        $this->found_data = array_slice($this->table_data, (($current_page - 1) * $per_page), $per_page);
        $this->set_pagination_args(array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page' => $per_page //WE have to determine how many items to show on a page
        ));
        $this->items = $this->found_data;
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'title':
            case 'group_id':
            case 'text':
            case 'active':
            case 'logic':
            case 'random':
                return $item[$column_name];
//            default:
//                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function usort_reorder($a, $b) {
// If no sort, default to title
        $orderby = (!empty($_GET['orderby']) ) ? $_GET['orderby'] : 'title';
// If no order, default to asc
        $order = (!empty($_GET['order']) ) ? $_GET['order'] : 'asc';
// Determine sort order
        $result = strcmp($a[$orderby], $b[$orderby]);
// Send final sort direction to usort
        return ( $order === 'asc' ) ? $result : -$result;
    }

    function column_title($item) {
        $actions = array(
            'edit' => sprintf('<a href="?page=%s&action=%s&' . $this->option . '=%s&plugin_page=' . $this->plugin_page . '">Editieren</a>', $_REQUEST['page'], 'edit', $item['id']),
            'delete' => sprintf('<a href="?page=%s&action=%s&' . $this->option . '=%s&plugin_page=' . $this->plugin_page . '">L&ouml;schen</a>', $_REQUEST['page'], 'delete', $item['id']),
        );
        return sprintf('%1$s %2$s', $item['title'], $this->row_actions($actions));
    }

    function get_bulk_actions() {
        $actions = array(
            'delete_' . $this->option => 'L&ouml;schen'
        );
        return $actions;
    }

    function column_cb($item) {
        return sprintf(
                        '<input type="checkbox" name="' . $this->option . '[]" value="%s" />', $item['id']
        );
    }

    function setTableData($data = array()) {
        if (is_array($data)) {
            $this->table_data = $data;
        }
    }

    function &getTableData() {
        return $this->table_data;
    }

    function setColumns($data = array()) {
        if (is_array($data)) {
            $this->columns = $data;
        }
    }

    function &getColumns() {
        return $this->columns;
    }

    function setSortableColumns($data = array()) {
        if (is_array($data)) {
            $this->sortable_columns = $data;
        }
    }

    function &getSortableColumns() {
        return $this->sortable_columns;
    }

    function setOption($option = 'layer') {
        if (!empty($option)) {
            $this->option = $option;
        }
    }

    function &getOption() {
        return $this->option;
    }

    function setPluginPage($option = '') {
        if (!empty($option)) {
            $this->plugin_page = $option;
        }
    }

    function &getPluginPage() {
        return $this->plugin_page;
    }

}

?>
