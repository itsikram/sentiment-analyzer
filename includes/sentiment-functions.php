<?php

// add default options
function add_default_keywords()
{

    if (empty(get_option('sa_positive_keywords'))) {
        update_option('sa_positive_keywords', 'good, happy, excellent, amazing');
    }
    if (empty(get_option('sa_negative_keywords'))) {
        update_option('sa_negative_keywords', 'bad, sad, terrible, awful');
    }
    if (empty(get_option('sa_neutral_keywords'))) {
        update_option('sa_neutral_keywords', 'normal, average');
    }
}

// Sentiment analysis on save posts
function sa_analyze_sentiment_on_save_post($post_id)
{
    $post = get_post($post_id);

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Condition to process only post
    if ('post' !== $post->post_type) {
        return;
    }

    $sa_post_content = strip_tags($post->post_content);

    $sa_sentiment = 'neutral'; // Default Sentiment

    $sa_positive_keywords = get_option('sa_positive_keywords');
    $sa_negative_keywords = get_option('sa_negative_keywords');
    $sa_neutral_keywords = get_option('sa_neutral_keywords');

    // Count Positive, Negative and Neutral keywords
    $positive_count = sa_count_keywords($sa_post_content, $sa_positive_keywords);
    $negative_count = sa_count_keywords($sa_post_content, $sa_negative_keywords);
    $neutral_count = sa_count_keywords($sa_post_content, $sa_neutral_keywords);

    if ($positive_count > $negative_count) {
        $sa_sentiment = 'positive';
    } else if ($positive_count < $negative_count) {
        $sa_sentiment = 'negative';
    } else {
        $sa_sentiment = 'neutral';
    }
    // store sentiment as post meta
    update_post_meta($post_id, '_post_sentiment', $sa_sentiment);
}

// keywords Counter function
function sa_count_keywords($content, $keywords_string)
{
    $count = 0;

    if (!empty($keywords_string)) {
        $keywords = explode(',', trim($keywords_string));

        foreach ($keywords as $keyword) {
            $count += substr_count(strtolower($content), strtolower($keyword));
        }

        return $count;
    }

    return;
}

// Display Sentiment Badge Function
function sa_display_sentiment_badge($title)
{
    global $post;

    if (is_page()) {

        $the_sentiment = ucwords(get_post_meta($post->ID, '_post_sentiment', true));
        $sentiment_class = strtolower($the_sentiment);

        $sentiment_badge = '<span class="sentiment-badge ' . $sentiment_class . '">' . $the_sentiment . '</span>';

        return $title . ' ' . $sentiment_badge;
    }


    return $title;
}

// Sentiment Filter Shortcode Callback Fucktion
function sa_filter_shortcode($atts, $content)
{
    $atts = shortcode_atts(array(
        'sentiment' => 'positive',
        'display' => 'list',
        'title' => '',
    ), $atts);

    // trim double quotes from shortcode atts value sentiment
    $sentiment_value = $atts['sentiment'];
    $sentiment_values_array = explode('|', $sentiment_value);

    // trim double quotes from shortcode atts value display
    $sentiment_display = $atts['display'];

    // trim double quotes from shortcode atts value title
    $sentiment_title = $atts['title'];

    // Santiment Query Arguments
    $sa_query_args = array(
        'post_type' => 'post',
        'posts_per_page' => 10,
        'meta_query' => array(
            array(
                'key' => '_post_sentiment',
                'value' => $sentiment_values_array,
                'compare' => 'IN'
            )
        )
    );

    $sa_query = new WP_Query($sa_query_args);


    $post_container = '<div class="sa-post-container">';

    if (!empty($sentiment_title)) {
        $post_container .= '<h3 class="sa-title">' . $sentiment_title . '</h3>';
    }
    if ($sa_query->have_posts()) {

        // Create variable for output results

        if ($sentiment_display === 'list') {
            $list_container = '<ul class="sa-list-container">';
            while ($sa_query->have_posts()) {
                $sa_query->the_post();

                $post_title = html_entity_decode(get_the_title());
                $post_title_text = wp_strip_all_tags(get_the_title());

                $post_content = wp_trim_words(wp_strip_all_tags(get_the_content()), 30, '...');
                $thumbnail_ulr = get_the_post_thumbnail_url();
                $post_permalink = get_permalink();
                $thumbnail_ulr = get_the_post_thumbnail_url();

                $list_container .= '<li class="sa-list-item">';

                $list_container .= '<div class="sa-list-image-container"><a href="' . $post_permalink . '"><img decoding="async" class="sa-list-image" src="' . $thumbnail_ulr . '" alt="' . $post_title_text . '"></a></div>';


                $list_container .= '<div class="sa-list-content-container"><a href="' . $post_permalink . '">';

                $list_container .= '<h3 class="sa-list-title">' . $post_title . '</h3>';
                $list_container .= '<p class="sa-list-content">' . $post_content . '</p>';
                $list_container .= '<a class="sa-list-button" href="' . $post_permalink . '">Read More</a>';

                $list_container .= '</a></div>';

                $list_container .= '</li>';
            }


            $list_container .= '</ul>';

            $post_container .= $list_container;
        } elseif ($sentiment_display === 'grid') {
            $grid_container = '<div class="sa-grid-container">';
            while ($sa_query->have_posts()) {
                $sa_query->the_post();

                $post_title = html_entity_decode(get_the_title());
                $post_title_text = wp_strip_all_tags(get_the_title());

                $post_content = wp_trim_words(wp_strip_all_tags(get_the_content()), 15, '...');
                $thumbnail_ulr = get_the_post_thumbnail_url();
                $post_permalink = get_permalink();

                $grid_container .= '<div class="sa-grid-item"><a href="' . $post_permalink . '">';

                $grid_container .= '<div class="sa-grid-image-container">';
                $grid_container .= '<img class="sa-grid-image" src="' . $thumbnail_ulr . '" alt="' . $post_title_text . '">';
                $grid_container .= '</div>';
                $grid_container .= '<div class="sa-grid-content-container">';
                $grid_container .= '<h3 class="sa-grid-title">' . $post_title . '</h3>';
                $grid_container .= '<p class="sa-grid-content">' . $post_content . '</p>';
                $grid_container .= '<a class="sa-grid-button" href="' . $post_permalink . '">Read More</a>';
                $grid_container .= '</div>';
                $grid_container .= '</a></div>';
            }
            $grid_container .= '</div>';

            $post_container .= $grid_container;
        }
    } else {
        $post_container .= '<p class="no-post_found"> No Post Found </p>';
    }
    $post_container .= '</div';

    return $post_container;
}

// Enqueue Scripts callback function
function sa_enqueue_stylesheet()
{
    wp_enqueue_style('sentiment-analyzer', SA_PLUGIN_DIR_URL . '/assets/css/style.css', array(), false);
}

// Admin Setting Page Callback Function
function sa_admin_settings_page()
{

    add_menu_page('Sentiment Analyzer Settings', 'Sentiment Analyzer', 'manage_options', 'sentiment-analyzer-settings', 'sa_render_admin_settings_page', 'dashicons-chart-line', 30);
}

// Renter Admin Menu Page
function sa_render_admin_settings_page()
{

?>

    <div class="sa-admin-container">

        <h1>Sentiment Keywords Options</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('sa_settings_group');
            do_settings_sections('sa_settings_group');
            ?>
            <?php wp_nonce_field('sa_save_keywords', 'sa_keywords_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="positiveKeywords">Positive Keywords</label></th>
                    <td>
                        <textarea name="sa_positive_keywords" cols="50" rows="10" id="positiveKeywords"><?php echo esc_attr(get_option('sa_positive_keywords')); ?></textarea>
                        <p class="description">Configure Positive Keywords (Separate by comma)</p>

                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="negativeKeywords">Negative Keywords</label></th>
                    <td>
                        <textarea name="sa_negative_keywords" cols="50" rows="10" id="negativeKeywords"><?php echo esc_attr(get_option('sa_negative_keywords')); ?></textarea>
                        <p class="description">Configure Negative Keywords (Separate by comma)</p>

                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="neutralKeywords">Neutral Keywords</label></th>
                    <td>
                        <textarea name="sa_neutral_keywords" cols="50" rows="10" id="neutralKeywords"><?php echo esc_attr(get_option('sa_neutral_keywords')); ?></textarea>
                        <p class="description">Configure Neutral Keywords (Separate by comma)</p>

                    </td>

                </tr>

            </table>
            <?php submit_button(); ?>
        </form>
    </div>

<?php

}

// Register admin Settings
function sa_register_admin_settings()
{

    register_setting('sa_settings_group', 'sa_positive_keywords');
    register_setting('sa_settings_group', 'sa_negative_keywords');
    register_setting('sa_settings_group', 'sa_neutral_keywords');
}


function sa_save_admin_page_settings()
{
    if (isset($_POST['sa_keywords_nonce']) && $_POST['sa_keywords_nonce'] && wp_verify_nonce($_POST['sa_keywords_nonce'], 'sa_save_keywords')) {

        if (isset($_POST['sa_positive_keywords'])) {
            update_option('sa_positive_keywords', sanitize_text_field($_POST['sa_positive_keywords']));
        }

        if (isset($_POST['sa_negative_keywords'])) {
            update_option('sa_negative_keywords', sanitize_text_field($_POST['sa_negative_keywords']));
        }

        if (isset($_POST['sa_neutral_keywords'])) {
            update_option('sa_neutral_keywords', sanitize_text_field($_POST['sa_neutral_keywords']));
        }
    } else {
        wp_die('Nonce Verification Failed');
    }
}
