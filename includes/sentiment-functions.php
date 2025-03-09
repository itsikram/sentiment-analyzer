<?php

// sa Default Keywords
function sa_default_keywords()
{

    return array(
        'positive' => array('good', 'happy', 'excellent', 'amazing'),
        'negative' => array('bad', 'sad', 'terrible', 'awful'),
        'neutral' => array('normal', 'average')
    );
}


// Sentiment analysis on save posts
function sa_analyze_sentiment_on_save_post($post_id)
{
    $post = get_post( $post_id );
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Condition to process only post
    if ('post' !== $post->post_type) {
        return;
    }

    $sa_post_content = strip_tags($post->post_content);

    $sa_sentiment = 'neutral'; // Default Sentiment

    $sa_keywords = sa_default_keywords();

    // Count Positive, Negative and Neutral keywords
    $positive_count = sa_count_keywords($sa_post_content, $sa_keywords['positive']);
    $negative_count = sa_count_keywords($sa_post_content, $sa_keywords['negative']);

    if ($positive_count > $negative_count) {
        $sa_sentiment = 'positive';
    } else if($positive_count < $negative_count) {
        $sa_sentiment = 'negative';
    }else {
        $sa_sentiment = 'neutral';
    }
    // store sentiment as post meta
    update_post_meta($post_id, '_post_sentiment', $sa_sentiment);
}

// keywords Counter function
function sa_count_keywords($content, $keywords)
{
    $count = 0;

    foreach ($keywords as $keyword) {
        $count += substr_count($content, strtolower($keyword));
    }

    return $count;
}

// Display Sentiment Badge Function
function sa_display_sentiment_badge($title)
{

    global $post;

    $sentiment = get_post_meta($post->ID, '_post_sentiment', true);

    if (is_single()) {
        if ($sentiment == 'positive') {
            $sentiment_badge = '<span class="sentiment-badge positive">Positive</span>';
        } elseif ($sentiment == 'negative') {
            $sentiment_badge = '<span class="sentiment-badge negative">Negative</span>';
        } else {
            $sentiment_badge = '<span class="sentiment-badge neutral">Neutral</span>';
        }

        return $title . ' ' . $sentiment_badge;
    }

    return $title;
}

function sa_filter_shortcode($atts, $content)
{
    $atts = shortcode_atts(array(
        'sentiment' => 'positive',
        'display' => 'list',
    ), $atts);

    // trim double quotes from shortcode atts value sentiment
    $sentiment_value = str_replace('"', '', $atts['sentiment']);

    // trim double quotes from shortcode atts value display
    $sentiment_display = str_replace('"', '', $atts['display']);

    // Santiment Query Arguments
    $sa_query_args = array(
        'post_type' => 'post',
        'posts_per_page' => 10,
        'meta_key' => '_post_sentiment',
        'meta_value' => $sentiment_value
    );

    $sa_query = new WP_Query($sa_query_args);


    $post_container = '<div class="sa-post-container">';

    if ($sa_query->have_posts()) {

        // Create variable for output results

        if ($sentiment_display === 'list') {
            $list_container = '<ul class="sa-list-container">';
            while ($sa_query->have_posts()) {
                $sa_query->the_post();

                $list_container .= '<li class="sa-list-item">';
                $list_container .= '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
                $list_container .= '</li>';
            }
            $list_container .= '</ul>';
            $post_container .= $list_container;
        } elseif ($sentiment_display === 'grid') {
            $grid_container = '<div class="sa-grid-container">';
            while ($sa_query->have_posts()) {

                $sa_query->the_post();

                $grid_container .= '<div class="sa-grid-item">';

                $grid_container .= '<div class="sa-grid-image-container">';
                $grid_container .= '<img class="sa-grid-image" src="' . get_the_post_thumbnail_url() . '" alt="' . get_the_title() . '">';
                $grid_container .= '</div>';
                $grid_container .= '<h3 class="sa-grid-title">' . get_the_title() . '</h3>';
                $grid_container .= '<p class="sa-grid-content">' . get_the_content() . '</p>';
                $grid_container .= '<a class="sa-grid-button" href="' . get_permalink() . '">Read More</a>';

                $grid_container .= '</div>';
            }
            $grid_container .= '</div>';

            $post_container .= $grid_container;
        }
    }
    $post_container .= '</div';

    return $post_container;
}
