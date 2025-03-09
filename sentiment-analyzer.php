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

// sa Default Keywords
$sa_dafault_keywords = array(
    'positive' => array( 'good', 'happy', 'excellent', 'amazing' ),
    'negative' => array( 'bad', 'sad', 'terrible', 'awful' ),
    'neutral' => array( 'normal', 'average' )
);

// Sentiment analysis on save posts

function sa_analyze_sentiment_on_save_post($post_id, $post ) {

    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
     return;
    }

}


// Call Save Post hook
add_action('save_post','sa_analyze_sentiment_on_save_post');