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


// Call Save Post hook
add_action('save_post','sa_analyze_sentiment_on_save_post');

// Display Sentiment Badge with post title
add_filter( 'the_title', 'sa_display_sentiment_badge' );

