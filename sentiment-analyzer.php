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
function sa_default_keywords (){

    return array(
        'positive' => array( 'good', 'happy', 'excellent', 'amazing' ),
        'negative' => array( 'bad', 'sad', 'terrible', 'awful' ),
        'neutral' => array( 'normal', 'average' )
    );
    
} 


// Sentiment analysis on save posts

function sa_analyze_sentiment_on_save_post($post_id, $post ) {


    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
     return;
    }

    // Condition to process only post
    if($post !== $post -> post_type) {
        return;
    }

    $sa_post_content = strip_tags($post -> post_content);

    $default_selected_sentiment = 'neutral';

    $sa_keywords = sa_default_keywords();

    // Count Positive, Negative and Neutral keywords
    $positive_count = sa_count_keywords($sa_post_content, $sa_keywords['positive']);
    $negative_count = sa_count_keywords($sa_post_content, $sa_keywords['negative']);
    $neutral_count = sa_count_keywords($sa_post_content, $sa_keywords['neutral']);


}

// Call Save Post hook
add_action('save_post','sa_analyze_sentiment_on_save_post');


// keywords Counter function
function sa_count_keywords($content, $keywords){
    $count = 0;

    foreach($keywords as $keyword){
        $count += substr_count($content, strtolower($keyword));
    }

    return $count;

}