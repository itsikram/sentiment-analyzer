<?php
/**
 * Plugin Name: Sentiment Analyzer
 * Description: Analyze post content for sentiment and display a sentiment badge.
 * Version: 1.0
 * Author: Md Ikram
 * Author Uri: http://programmerikram.com
 * Text Domain: sentiment-analyzer
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Plugin Directory
define( 'SA_PLUGIN_DIR', value: plugin_dir_path( __FILE__ ) );

define( 'SA_PLUGIN_DIR_URL', value: plugin_dir_url( __FILE__ ) );

// Includes Sentiment Functions

if(file_exists(SA_PLUGIN_DIR . 'includes/sentiment-functions.php')) {
    require_once(SA_PLUGIN_DIR . 'includes/sentiment-functions.php');
}

// Call Save Post hook
add_action('save_post', 'sa_analyze_sentiment_on_save_post');

// Display Sentiment Badge with post title
add_filter( 'the_title', 'sa_display_sentiment_badge' );

// Call add_shortcode hook to run shortcode functions
add_shortcode( 'sentiment_filter', 'sa_filter_shortcode' );

// Enqueue Stylesheet
add_action('wp_enqueue_scripts', 'sa_enqueue_stylesheet');