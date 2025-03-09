<?php

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

    $sa_sentiment = 'neutral'; // Default Sentiment

    $sa_keywords = sa_default_keywords();

    // Count Positive, Negative and Neutral keywords
    $positive_count = sa_count_keywords($sa_post_content, $sa_keywords['positive']);
    $negative_count = sa_count_keywords($sa_post_content, $sa_keywords['negative']);
    $neutral_count = sa_count_keywords($sa_post_content, $sa_keywords['neutral']);

    if($positive_count > 0 && $negative_count > 0) {
        if( $positive_count > $neutral_count) {
            $sa_sentiment = 'positive';
        }else {
            $sa_sentiment = 'negative';
        }
    }

    // store sentiment as post meta
    update_post_meta($post_id, '_post_sentiment', $sa_sentiment);

}

// keywords Counter function
function sa_count_keywords($content, $keywords){
    $count = 0;

    foreach($keywords as $keyword){
        $count += substr_count($content, strtolower($keyword));
    }

    return $count;

}


// Display Sentiment Badge Function
function sa_display_sentiment_badge($title){

    global $post;

    $sentiment = get_post_meta($post->ID, '_post_sentiment', true);

    if (is_single()) {
        if($sentiment == 'positive') {
            $sentiment_badge = '<span class="sentiment-badge positive">Positive</span>';
        } elseif($sentiment == 'negative') {
            $sentiment_badge = '<span class="sentiment-badge negative">Negative</span>';
        } else {
            $sentiment_badge = '<span class="sentiment-badge neutral">Neutral</span>';
        }
    
        return $title.' '. $sentiment_badge;
    }

    return $title;


}
