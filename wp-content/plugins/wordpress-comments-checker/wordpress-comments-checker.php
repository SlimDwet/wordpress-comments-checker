<?php

/**
 * Plugin Name: Wordpress comments checker
 * Plugin URI: https://codex.wordpress.org/Plugins
 * Author: Evans RINVILLE
 * Version: 1.0
 * Description: A plugin that verify any new comments and check if it doesn't contain specified words
 * Text Domain: wcc-domain
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define('WCC_VERSION', '1.0');
define('WCC_TABLE_NAME', 'comments_checker');

$errors_tab = array(); // List of errors

// New terms to save
if(!empty($_POST) && isset($_POST['term'])) {
    wcc_save_terms();
}

/**
 * Actions to do on plugin activation
 * @return [type] [description]
 */
function wordpress_comments_checker_activate() {
    wcc_install();
}

/**
 * Create the table that contains the unwanted words
 * @return [type] [description]
 */
function wcc_install() {
    global $wpdb;

    $table_name = $wpdb->prefix.WCC_TABLE_NAME;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        term varchar(255) NOT NULL,
        created datetime NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // Add the plugin version in wordpress options
    add_option('wcc_version', WCC_VERSION);
}

/**
 * Add plugin menu item
 * @return [type] [description]
 */
function wcc_admin_menu() {
    add_menu_page(__('Wordpress comments checker administration', 'wcc-domain'), __('Comments checker', 'wcc-domain'), 'moderate_comments', 'comments_checker', 'wcc_admin_theme', 'dashicons-format-chat');
}

/**
 * Get the list of unwanted terms stored in database
 * @return [type] [description]
 */
function wcc_get_unwanted_terms() {
    global $wpdb;
    return $wpdb->get_results('SELECT term FROM '.$wpdb->prefix.WCC_TABLE_NAME);
}

/**
 * Save new term(s)
 * @return [type] [description]
 */
function wcc_save_terms() {
    global $wpdb;
    global $errors_tab;

    if(!empty($_POST) && isset($_POST['term'])) {
        extract($_POST);
        // Remove duplicated terms
        $term = array_map('trim', array_unique($term));

        // Remove old stored terms
        $result = $wpdb->query('TRUNCATE TABLE '.$wpdb->prefix.WCC_TABLE_NAME);
        if($result) {
            // Let's store the new terms
            foreach ($term as $key => $new_term) {
                $insert_result = $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO ".$wpdb->prefix.WCC_TABLE_NAME." (term, created) VALUES(%s, %s)",
                        esc_html($new_term),
                        date('Y-m-d H:i:s')
                    )
                );
                if(!$insert_result) {
                    // Error while insert new terms
                    $errors_tab = array('type' => 'error', 'message' => 'An error occured while terms adding');
                } else {
                    // New terms added
                    $errors_tab = array('type' => 'success', 'message' => 'Terms added with success');
                }
            }
        } else {
            // Can't erase old terms
            $errors_tab = array('type' => 'error', 'message' => 'An error occured while remove old unauthorized terms');
        }
    }
}

/**
 * Check new comment and delete it if contain unwanted term(s)
 * @param  [type] $comment_id [description]
 * @return [type]             [description]
 */
function wcc_check_new_comment($comment_id) {
    // Get the new comment
    $comment = get_comment($comment_id);

    // Get all unwanted terms
    $all_non_valid_terms = wcc_get_unwanted_terms();

    // Check if new comment content contain unwanted terms
    foreach ($all_non_valid_terms as $key => $term_obj) {
        if (strpos($comment->comment_content, $term_obj->term) !== false) {
            // Unwanted term found so delete the comment
            if(wp_delete_comment($comment_id, true)) {
                // Add to post URL a parameter to inform user that his comment is invalid
                $post_permalink = get_permalink($comment->comment_post_ID).'?wcc_invalid_comment='.$term_obj->term;
                // Redirect to post URL
                if(wp_redirect($post_permalink)) {
                    exit;
                }
            }
        }
    }
}

/**
 * Inform the user that his comment contain invalid term
 * @return [type] [description]
 */
function wcc_deleted_comment_info() {
    if(isset($_GET['wcc_invalid_comment'])) {
        echo '<script type="text/javascript">alert("'.__("Your comment contain an unauthorized term", 'wcc-domain').' : '.$_GET['wcc_invalid_comment'].'");</script>';
    }
}

/**
 * Display the plugin admin page
 * @return [type] [description]
 */
function wcc_admin_theme() {
    global $errors_tab;

    // Get all unwanted terms
    $all_terms = wcc_get_unwanted_terms();

    $wcc_admin_theme_html = '<div class="wrap">';
    $wcc_admin_theme_html .= '<h1>'.__('Wordpress comments checker', 'wcc-domain').'</h1>';
    $wcc_admin_theme_html .= '<div class="errors_container">';
    // Errors
    if(!empty($errors_tab)) {
        $wcc_admin_theme_html .= '<div class="notice notice-'.$errors_tab['type'].' is-dismissible"><p>'.__($errors_tab['message'], 'wcc-domain').'</p>';
        $wcc_admin_theme_html .= '<button type="button" class="notice-dismiss"><span class="screen-reader-text">'.__('Dismiss this notice', 'wcc-domain').'</span></button>';
        $wcc_admin_theme_html .= '</div>';
    }
    $wcc_admin_theme_html .= '</div>';
    $wcc_admin_theme_html .= '<p>'.__('Please list above the unwanted terms in comments', 'wcc-domain').'</p>';
    $wcc_admin_theme_html .= '<form action="#" method="POST">';
    $wcc_admin_theme_html .= '<div class="terms_container">';

    if(empty($all_terms)) {
        // New term (no term stored)
        $wcc_admin_theme_html .= '<p><label for="term0">'.__('Term', 'wcc-domain').'</label>&nbsp;';
        $wcc_admin_theme_html .= '<input type="text" id="term0" class="term_field" name="term[]">&nbsp;<a href="#" class="remove_term">'.__('Remove term', 'wcc-domain').'</a>';
        $wcc_admin_theme_html .= '</p>';
    } else {
        // Get terms from database
        foreach ($all_terms as $key => $term_obj) {
            $wcc_admin_theme_html .= '<p><label for="term'.$key.'">'.__('Term', 'wcc-domain').'</label>&nbsp;';
            $wcc_admin_theme_html .= '<input type="text" id="term'.$key.'" class="term_field" name="term[]" value="'.$term_obj->term.'">&nbsp;';
            $wcc_admin_theme_html .= '<a href="#" class="remove_term">'.__('Remove term', 'wcc-domain').'</a>';
            $wcc_admin_theme_html .= '</p>';
        }
    }
    $wcc_admin_theme_html .= '</div>'; // div.terms_container
    $wcc_admin_theme_html .= '<button id="add_term_field" class="button button-primary">'.__('Add term', 'wcc-domain').'</button>&nbsp';
    $wcc_admin_theme_html .= '<button type="submit" class="button button-primary">'.__('Submit', 'wcc-domain').'</button>';
    $wcc_admin_theme_html .= '</form>';
    $wcc_admin_theme_html .= '</div>'; // div.wrap

    echo $wcc_admin_theme_html;
}

// Register all scripts and styles
function wcc_assets() {
    wp_enqueue_script('wcc-script', plugin_dir_url(__FILE__).'/assets/wcc_scripts.js', array('jquery'), false, false);
    wp_localize_script(
        'wcc-script',
        'objectL10n',
        array(
            'term'  => esc_html__( 'Term', 'wcc-domain' ),
            'remove_term'  => esc_html__( 'Remove term', 'wcc-domain' ),
        )
    );
    wp_enqueue_style('wcc-style', plugin_dir_url(__FILE__).'/assets/wcc_styles.css', array(), false, false);
}

// Initialize the plugin on his activation
register_activation_hook( __FILE__, 'wordpress_comments_checker_activate' );
// Add an item to the menu for plugin management
add_action('admin_menu', 'wcc_admin_menu');
// Add the plugin's scripts and styles
add_action('admin_enqueue_scripts', 'wcc_assets');
// Check validity of every new comment
add_action('wp_insert_comment', 'wcc_check_new_comment', 90);
// Inform the user that his comment contains invalid terms
add_action('wp', 'wcc_deleted_comment_info');
