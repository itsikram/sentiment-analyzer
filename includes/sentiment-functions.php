<?php

// load text domain on init
function sa_load_textdomain()
{

    $language_dir = dirname(plugin_basename(__FILE__)) . '/languages';
    load_plugin_textdomain('sa', false, $language_dir);
}

// Add or Get options on plugin activation
function sa_add_default_keywords()
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
    if (empty(get_option('disable_cache'))) {
        update_option('disable_cache', 'no');
    }
    if (empty(get_option('clear_cache'))) {
        update_option('clear_cache', '1');
    }
}


function sa_re_save_all_posts()
{

    // Get all posts
    $args = array(
        'posts_per_page' => -1,
        'post_type' => 'post',
        'post_status' => 'publish',
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            wp_update_post(array(
                'ID' => get_the_ID(),
            ));
        }
        wp_reset_postdata();
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

    // Fetch sentiment keywords
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

    // Store cache for 12 hours
    set_transient('sa_cache_sentiment_' . $post_id, $sa_sentiment, 12 * HOUR_IN_SECONDS);
}


// Clear cache on save post
function sa_clear_sentiment_cache($post_id)
{
    do_action('sa_clear_all_cache');
}

// Clear entire cache
function sa_clear_all_cache_callback()
{
    global $wpdb;
    $sa_transients = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");

    $sa_transient_keys = array_map(function ($name) {
        return str_replace(array('_transient_', '_transient_timeout_'), '', $name);
    }, $sa_transients);

    $sa_transient_keys = array_unique($sa_transient_keys);

    foreach ($sa_transient_keys as $key) {
        delete_transient($key);
    }
    return;
}

// Disable transient chech
function disable_transient_cache($transient, $value, $expiration)
{
    $is_disable_cache = get_option('disable_cache') == 'yes' ? '1' : '0';

    if ($is_disable_cache === '1') {
        return false;
    }
    if (defined('WP_CACHE') && WP_CACHE) {
        return false;
    }
}

// keywords Counter function
function sa_count_keywords($content, $keywords_string)
{
    $count = 0;
    if (!empty($keywords_string)) {
        $keywords = explode(',', trim($keywords_string));

        foreach ($keywords as $keyword) {
            $count += substr_count(strtolower($content), strtolower(trim($keyword)));
        }
        return $count;
    }
    return;
}

// Display Sentiment Badge Function
function sa_display_sentiment_badge($title)
{
    global $post;

    if (is_page() || is_single()) {
        $the_sentiment = get_transient('sa_cache_sentiment_' . $post->ID);

        if ($the_sentiment === false) {

            $the_sentiment = ucwords(get_post_meta($post->ID, '_post_sentiment', true));
            set_transient('sa_cache_sentiment_' . $post->ID, $the_sentiment, 12 * HOUR_IN_SECONDS);
        }

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
        'posts_per_page' => 3
    ), $atts, 'sentiment_filter');

    // Get Current Page Number
    $paged = isset($_REQUEST['page_number']) ? sanitize_text_field($_REQUEST['page_number']) : 1;
    // $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

    // trim double quotes from shortcode atts value sentiment
    $sentiment_value =  $atts['sentiment'];
    $sentiment_values_array = explode('|', $sentiment_value);

    $sentiment_display = $atts['display'];

    $sentiment_title = $atts['title'];
    $sentiment_post_per_page = $atts['posts_per_page'];

    // Create a unique cache transient kye
    $sa_cache_posts_key = 'sa_cache_posts_html_' . md5(serialize($atts) . $paged);

    $sa_query_cache = get_transient($sa_cache_posts_key);

    if ($sa_query_cache !== false) {
        // return with current cache
        return $sa_query_cache;
    }

    // Santiment Query Arguments
    $sa_query_args = array(
        'post_type' => 'post',
        'posts_per_page' => $sentiment_post_per_page,
        'paged'          => $paged,
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

        $sa_pagination_links = paginate_links(array(
            'total'   => $sa_query->max_num_pages,
            'current' => $paged,
            'format' => '?page_number=%#%',
            'prev_text' => __('« Previous'),
            'next_text' => __('Next »'),
        ));

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
                $list_container .= '<a class="sa-list-button" href="' . $post_permalink . '">' . __('Read More', 'sa') . '</a>';

                $list_container .= '</a></div>';
                $list_container .= '</li>';
            }

            $list_container .= '</ul>';
            $list_paginations = '<div class="sa-list-pagination-container">' . $sa_pagination_links . '.</div>';
            $post_container .= $list_container;
            $post_container .= $list_paginations;
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
                $grid_container .= '<a class="sa-grid-button" href="' . $post_permalink . '">' . __('Read More', 'sa') . '</a>';
                $grid_container .= '</div>';

                $grid_container .= '</a></div>';
            }

            $grid_container .= '</div>';

            $grid_paginations = '<div class="sa-grid-pagination-container">' . $sa_pagination_links . '.</div>';

            $post_container .= $grid_container;
            $post_container .= $grid_paginations;
        }
    } else {
        $post_container .= '<p class="no-post_found"> ' . __('No Post Found', 'sa') . ' </p>';
    }
    $post_container .= '</div>';

    // Store Posts Cache
    set_transient($sa_cache_posts_key, $post_container, 24 * HOUR_IN_SECONDS);


    return $post_container;
}

// Enqueue Scripts callback function
function sa_enqueue_stylesheet()
{
    wp_enqueue_style('sentiment-analyzer', SA_PLUGIN_DIR_URL . '/assets/css/style.css', array(), false);

    wp_enqueue_script('sentiment-analyzer', SA_PLUGIN_DIR_URL . '/assets/js/main.js', array('jquery'), false);
    wp_localize_script('sentiment-analyzer', 'cache_ajax', array('url' => admin_url('admin-ajax.php')));
}

function sa_admin_scripts()
{
    wp_enqueue_script('sentiment-analyzer-admin', SA_PLUGIN_DIR_URL . '/assets/js/admin-script.js', array('jquery'), false);
    wp_localize_script('sentiment-analyzer-admin', 'admin_ajax', array('url'  => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ajax_clear_sa_caches')));
}

// Admin Setting Page Callback Function
function sa_admin_settings_page()
{
    // Add new page in wordpress dashboard menu
    add_menu_page(__('Sentiment Analyzer Settings', 'sa'), __('Sentiment Analyzer', 'sa'), 'manage_options', 'sentiment-analyzer-settings', 'sa_render_admin_settings_page', 'dashicons-chart-line', 30);
}

// Renter Admin Menu Page
function sa_render_admin_settings_page()
{
?>
    <div class="sa-admin-container">

        <h1><?php _e('Sentiment Keywords Options', 'sa'); ?></h1>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php

            settings_fields('sa_settings_group');
            do_settings_sections('sa_settings_group');
            $is_clear_cache = get_option('is_clear_cache');
            $is_disable_cache = get_option('is_disable_cache');

            ?>

            <input type="hidden" name="action" value="save_sa_settings">

            <?php wp_nonce_field('sa_save_keywords', 'sa_keywords_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="positiveKeywords"><?php _e('Positive Keywords', 'sa'); ?></label></th>
                    <td>
                        <textarea name="sa_positive_keywords" cols="50" rows="10" id="positiveKeywords"><?php echo esc_attr(get_option('sa_positive_keywords')); ?></textarea>
                        <p class="description"><?php _e('Configure Positive Keywords (Separate by comma)', 'sa'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="negativeKeywords"><?php _e('Negative Keywords', 'sa'); ?></label></th>
                    <td>
                        <textarea name="sa_negative_keywords" cols="50" rows="10" id="negativeKeywords"><?php echo esc_attr(get_option('sa_negative_keywords')); ?></textarea>
                        <p class="description"><?php _e('Configure Negative Keywords (Separate by comma)', 'sa'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="neutralKeywords"><?php _e('Neutral Keywords', 'sa'); ?></label></th>
                    <td>
                        <textarea name="sa_neutral_keywords" cols="50" rows="10" id="neutralKeywords"><?php echo esc_attr(get_option('sa_neutral_keywords')); ?></textarea>
                        <p class="description"><?php _e('Configure Neutral Keywords (Separate by comma)', 'sa'); ?></p>
                    </td>

                </tr>
                <tr>
                    <th scope="row"><label for="disableCache"><?php _e('Disable Cache', 'sa'); ?></label></th>
                    <td>
                        <input type="checkbox" name="is_disable_cache" value="<?php echo $is_disable_cache; ?>" id="clearCache" <?php checked($is_disable_cache); ?>>
                        <label for="clearCache"><?php _e('Select it if you want to disable all caches', 'sa'); ?></label> <br> <br>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="clearCache"><?php _e('Clear Cache', 'sa'); ?></label></th>
                    <td>
                        <input type="checkbox" name="is_clear_cache" value="<?php echo $is_clear_cache; ?>" id="clearCache" <?php checked($is_clear_cache); ?>>
                        <label for="clearCache"><?php _e('Select it if you want to delete all caches on save changes', 'sa'); ?></label> <br> <br>
                        <button id="clearChacheNow" class="button button-primary"><?php _e('Clear Cache Now', 'sa'); ?></button>
                    </td>
                </tr>

            </table>
            <?php submit_button(__('Save Settings', 'sa')); ?>
        </form>
    </div>

<?php

}

// Register admin Settings
function sa_register_admin_settings()
{

    // Register all admin Settings
    register_setting('sa_settings_group', 'sa_positive_keywords');
    register_setting('sa_settings_group', 'sa_negative_keywords');
    register_setting('sa_settings_group', 'sa_neutral_keywords');
    register_setting('sa_settings_group', 'is_clear_cache');
    register_setting('sa_settings_group', 'is_disable_cache');
}

function sa_save_admin_page_settings()
{

    // Verify nonce on admin settings changes
    if (!isset($_POST['sa_keywords_nonce'])  || !wp_verify_nonce($_POST['sa_keywords_nonce'], 'sa_save_keywords')) {
        wp_die('Security check failed');
    }

    // Check if the user has the required capability to save settings
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }


    // update sentiment keywordes by input names
    if (isset($_POST['sa_positive_keywords'])) {
        update_option('sa_positive_keywords', sanitize_text_field($_POST['sa_positive_keywords']));
    }

    if (isset($_POST['sa_negative_keywords'])) {
        update_option('sa_negative_keywords', sanitize_text_field($_POST['sa_negative_keywords']));
    }

    if (isset($_POST['sa_neutral_keywords'])) {
        update_option('sa_neutral_keywords', sanitize_text_field($_POST['sa_neutral_keywords']));
    }

    // Clear cache if checkbox is checked in admin settings page.
    if (isset($_POST['is_disable_cache'])) {
        update_option('is_disable_cache', '1');
    } else {
        update_option('is_disable_cache', '0');
    }

    // Clear cache if checkbox is checked in admin settings page.
    if (isset($_POST['is_clear_cache'])) {
        update_option('is_clear_cache', '1');

        if ($_POST['is_clear_cache']) {
            do_action('sa_clear_all_cache');
        }
    } else {
        update_option('is_clear_cache', '0');
    }

    wp_redirect(admin_url('admin.php?page=sentiment-analyzer-settings&status=success'));
    exit();
}

function ajax_clear_all_caches()
{

    // Verify nonce for admin clear cache request
    check_ajax_referer('ajax_clear_sa_caches', 'nonce');

    // Check if user allowed to clear cache
    if (!current_user_can('manage_options')) {
        return wp_send_json_error(__('Unauthorized access'));
    }

    // Send Success Response after cache Cleared successfully
    do_action('sa_clear_all_cache');
    return wp_send_json_success(array('message' => __('Cache cleared successfully', 'sa')), 200);
}

function sa_register_sentiment_bulk_actions($bulk_actions)
{

    $bulk_actions['move_to_positive'] = __('Move to Positive', 'sa');
    $bulk_actions['move_to_negative'] = __('Move to Negative', 'sa');
    $bulk_actions['move_to_neutral'] = __('Move to Neutral', 'sa');

    return $bulk_actions;
}

function sa_handle_sentiment_bulk_action($redirect, $action, $post_ids)
{
    $bulk_actions = array(
        'move_to_positive',
        'move_to_negative',
        'move_to_neutral'
    );

    if (!in_array($action, $bulk_actions)) {
        return $redirect;
    }

    function handle_sentiment_update($post_ids, $sentiment, $redirect )
    {
        foreach ($post_ids as $post_id) {
            update_post_meta($post_id, '_post_sentiment', $sentiment);
        }

    };

    switch ($action) {
        case 'move_to_positive':

            handle_sentiment_update($post_ids, 'positive',$redirect);
            break;
        case 'move_to_negative':
            handle_sentiment_update($post_ids, 'negative',$redirect);
            break;
        case 'move_to_neutral':
            handle_sentiment_update($post_ids, 'neutral',$redirect);
            break;
    }

    do_action('sa_clear_all_cache');


    $redirect_to = add_query_arg($action. '_bulk_action_done', count($post_ids), $redirect);
    return $redirect_to;
}


function sa_bulk_action_sentiment_admin_notice()
{

    if (!empty($_REQUEST['move_to_positive_bulk_action_done'])) {
        $count = intval($_REQUEST['move_to_positive_bulk_action_done']);
        echo '<div class="updated notice is-dismissible"><p>' . $count. __(' Posts Moved to Positive.','sa') . '</p></div>';
    }

    if (!empty($_REQUEST['move_to_negative_bulk_action_done'])) {
        $count = intval($_REQUEST['move_to_negative_bulk_action_done']);
        echo '<div class="updated notice is-dismissible"><p>' . $count .__(' Posts Moved to Negative.','sa'). '</p></div>';
    }

    if (!empty($_REQUEST['move_to_neutral_bulk_action_done'])) {
        $count = intval($_REQUEST['move_to_neutral_bulk_action_done']);
        echo '<div class="updated notice is-dismissible"><p>' . $count .__(' Posts Moved to Neutral.','sa'). '</p></div>';
    }
}
