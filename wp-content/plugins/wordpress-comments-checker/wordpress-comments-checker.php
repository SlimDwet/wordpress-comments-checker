<?php

/**
 * Plugin Name: Wordpress comments checker
 * Plugin URI: https://codex.wordpress.org/Plugins
 * Author: Evans RINVILLE
 * Version: 1.0
 * Description: A plugin that verify any new comments and check if it doesn't contain specified words
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

global $wcc_version;
$wcc_version = '1.0';

function wordpress_comments_checker_init() {
    wcc_install();
}

function wcc_install() {
    global $wpdb;

    $table_name = $wpdb->prefix.'comments_checker';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name IF NOT EXISTS(
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        name tinytext NOT NULL,
        text text NOT NULL,
        url varchar(55) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    add_option('wcc_version', $wcc_version);
}

// Initialize the plugin on his activation
// register_activation_hook( __FILE__, 'wordpress_comments_checker_init' );
register_activation_hook( __FILE__, 'wcc_install' );
