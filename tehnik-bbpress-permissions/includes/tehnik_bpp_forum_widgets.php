<?php

/**
 * Unregisters the default bbPress Widgets
 */
function unregister_bbpress_widgets() {
    unregister_widget("BBP_Forums_Widget");
    unregister_widget("BBP_Topics_Widget");
    unregister_widget("BBP_Replies_Widget");
}
/**
 * Registeres the filtered Tehnik bbPress Widgets
 */
function register_tehnik__widgets() {
    register_widget("Tehnik_BBP_Forums_Widget");
    register_widget("Tehnik_BBP_Topics_Widget");
    register_widget("Tehnik_BBP_Replies_Widget");
}

add_action('widgets_init', 'unregister_bbpress_widgets');
add_action('widgets_init', 'register_tehnik__widgets');


/**
 * bbPress Widgets
 *
 * Contains the forum list, topic list, reply list and login form widgets.
 *
 * @package bbPress
 * @subpackage Widgets
 */
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

/**
 * Tehnik bbPress Forum Widget
 *
 * Adds a widget which displays the forum list
 *
 * @since bbPress (r2653)
 *
 * @uses WP_Widget
 */
class Tehnik_BBP_Forums_Widget extends WP_Widget {

    /**
     * bbPress Forum Widget
     *
     * Registers the forum widget
     *
     * @since bbPress (r2653)
     *
     * @uses apply_filters() Calls 'bbp_forums_widget_options' with the
     *                        widget options
     */
    public function __construct() {
        $widget_ops = apply_filters('bbp_forums_widget_options', array(
            'classname' => 'widget_display_forums',
            'description' => __('A list of forums with an option to set the parent.', 'bbpress')
        ));

        parent::__construct(false, __('(Tehnik - bbPress) Forums List', 'bbpress'), $widget_ops);
    }

    /**
     * Register the widget
     *
     * @since bbPress (r3389)
     *
     * @uses register_widget()
     */
    public static function register_widget() {
        register_widget('Tehnik_BBP_Forums_Widget');
    }

    /**
     * Displays the output, the forum list
     *
     * @since bbPress (r2653)
     *
     * @param mixed $args Arguments
     * @param array $instance Instance
     * @uses apply_filters() Calls 'bbp_forum_widget_title' with the title
     * @uses get_option() To get the forums per page option
     * @uses current_user_can() To check if the current user can read
     *                           private() To resety name
     * @uses bbp_has_forums() The main forum loop
     * @uses bbp_forums() To check whether there are more forums available
     *                     in the loop
     * @uses bbp_the_forum() Loads up the current forum in the loop
     * @uses bbp_forum_permalink() To display the forum permalink
     * @uses bbp_forum_title() To display the forum title
     */
    public function widget($args, $instance) {

        // Get widget settings
        $settings = $this->parse_settings($instance);

        // Typical WordPress filter
        $settings['title'] = apply_filters('widget_title', $settings['title'], $instance, $this->id_base);

        // bbPress filter
        $settings['title'] = apply_filters('bbp_forum_widget_title', $settings['title'], $instance, $this->id_base);

        // Note: private and hidden forums will be excluded via the
        // bbp_pre_get_posts_exclude_forums filter and function.
        $query_data = array(
            'post_type' => bbp_get_forum_post_type(),
            'post_parent' => $settings['parent_forum'],
            'post_status' => bbp_get_public_status_id(),
            'posts_per_page' => get_option('_bbp_forums_per_page', 50),
            'orderby' => 'menu_order',
            'order' => 'ASC'
        );

        //Get an array of IDs which the current user has permissions to view
        $allowed_posts = tehnik_bpp_get_permitted_post_ids(new WP_Query($query_data));
        // The default forum query with allowed forum ids array added
        $query_data['post__in'] = $allowed_posts;

        $widget_query = new WP_Query($query_data);

        // Bail if no posts
        if (!$widget_query->have_posts()) {
            return;
        }

		echo $args['before_widget'];
        if (!empty($settings['title'])) {
            echo $args['before_title'] . $settings['title'] . $args['after_title'];
        }
        ?>

        <ul>

            <?php while ($widget_query->have_posts()) : $widget_query->the_post(); ?>

                <li><a class="bbp-forum-title" href="<?php bbp_forum_permalink($widget_query->post->ID); ?>" title="<?php bbp_forum_title($widget_query->post->ID); ?>"><?php bbp_forum_title($widget_query->post->ID); ?></a></li>

            <?php endwhile; ?>

        </ul>

        <?php
        echo $args['after_widget'];

        // Reset the $post global
        wp_reset_postdata();
    }

    /**
     * Update the forum widget options
     *
     * @since bbPress (r2653)
     *
     * @param array $new_instance The new instance options
     * @param array $old_instance The old instance options
     */
    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['parent_forum'] = $new_instance['parent_forum'];

        // Force to any
        if (!empty($instance['parent_forum']) && !is_numeric($instance['parent_forum'])) {
            $instance['parent_forum'] = 'any';
        }

        return $instance;
    }

    /**
     * Output the forum widget options form
     *
     * @since bbPress (r2653)
     *
     * @param $instance Instance
     * @uses Tehnik_BBP_Forums_Widget::get_field_id() To output the field id
     * @uses Tehnik_BBP_Forums_Widget::get_field_name() To output the field name
     */
    public function form($instance) {

        // Get widget settings
        $settings = $this->parse_settings($instance);
        ?>

        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'bbpress'); ?>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($settings['title']); ?>" />
            </label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('parent_forum'); ?>"><?php _e('Parent Forum ID:', 'bbpress'); ?>
                <input class="widefat" id="<?php echo $this->get_field_id('parent_forum'); ?>" name="<?php echo $this->get_field_name('parent_forum'); ?>" type="text" value="<?php echo esc_attr($settings['parent_forum']); ?>" />
            </label>

            <br />

            <small><?php _e('"0" to show only root - "any" to show all', 'bbpress'); ?></small>
        </p>

        <?php
    }

    /**
     * Merge the widget settings into defaults array.
     *
     * @since bbPress (r4802)
     *
     * @param $instance Instance
     * @uses bbp_parse_args() To merge widget settings into defaults
     */
    public function parse_settings($instance = array()) {
        return bbp_parse_args($instance, array(
            'title' => __('Forums', 'bbpress'),
            'parent_forum' => 0
                ), 'forum_widget_settings');
    }

}

/**
 * Tehnik bbPress Topic Widget
 *
 * Adds a widget which displays the topic list
 *
 * @since bbPress (r2653)
 *
 * @uses WP_Widget
 */
class Tehnik_BBP_Topics_Widget extends WP_Widget {

    /**
     * bbPress Topic Widget
     *
     * Registers the topic widget
     *
     * @since bbPress (r2653)
     *
     * @uses apply_filters() Calls 'bbp_topics_widget_options' with the
     *                        widget options
     */
    public function __construct() {
        $widget_ops = apply_filters('bbp_topics_widget_options', array(
            'classname' => 'widget_display_topics',
            'description' => __('A list of recent topics, sorted by popularity or freshness.', 'bbpress')
        ));

        parent::__construct(false, __('(Tehnik - bbPress) Recent Topics', 'bbpress'), $widget_ops);
    }

    /**
     * Register the widget
     *
     * @since bbPress (r3389)
     *
     * @uses register_widget()
     */
    public static function register_widget() {
        register_widget('Tehnik_BBP_Topics_Widget');
    }

    /**
     * Displays the output, the topic list
     *
     * @since bbPress (r2653)
     *
     * @param mixed $args
     * @param array $instance
     * @uses apply_filters() Calls 'bbp_topic_widget_title' with the title
     * @uses bbp_topic_permalink() To display the topic permalink
     * @uses bbp_topic_title() To display the topic title
     * @uses bbp_get_topic_last_active_time() To get the topic last active
     *                                         time
     * @uses bbp_get_topic_id() To get the topic id
     */
    public function widget($args = array(), $instance = array()) {

        // Get widget settings
        $settings = $this->parse_settings($instance);

        // Typical WordPress filter
        $settings['title'] = apply_filters('widget_title', $settings['title'], $instance, $this->id_base);

        // bbPress filter
        $settings['title'] = apply_filters('bbp_topic_widget_title', $settings['title'], $instance, $this->id_base);

        // How do we want to order our results?
        switch ($settings['order_by']) {

            // Order by most recent replies
            case 'freshness' :
                $topics_query = array(
                    'post_type' => bbp_get_topic_post_type(),
                    'post_parent' => $settings['parent_forum'],
                    'posts_per_page' => (int) $settings['max_shown'],
                    'post_status' => array(bbp_get_public_status_id(), bbp_get_closed_status_id()),
                    'show_stickies' => false,
                    'meta_key' => '_bbp_last_active_time',
                    'orderby' => 'meta_value',
                    'order' => 'DESC',
                );
                break;

            // Order by total number of replies
            case 'popular' :
                $topics_query = array(
                    'post_type' => bbp_get_topic_post_type(),
                    'post_parent' => $settings['parent_forum'],
                    'posts_per_page' => (int) $settings['max_shown'],
                    'post_status' => array(bbp_get_public_status_id(), bbp_get_closed_status_id()),
                    'show_stickies' => false,
                    'meta_key' => '_bbp_reply_count',
                    'orderby' => 'meta_value',
                    'order' => 'DESC'
                );
                break;

            // Order by which topic was created most recently
            case 'newness' :
            default :
                $topics_query = array(
                    'post_type' => bbp_get_topic_post_type(),
                    'post_parent' => $settings['parent_forum'],
                    'posts_per_page' => (int) $settings['max_shown'],
                    'post_status' => array(bbp_get_public_status_id(), bbp_get_closed_status_id()),
                    'show_stickies' => false,
                    'order' => 'DESC'
                );
                break;
        }

        //Get an array of IDs which the current user has permissions to view
        $allowed_posts = tehnik_bpp_get_permitted_post_ids(new WP_Query($topics_query));
        // The default forum query with allowed forum ids array added
        $topics_query['post__in'] = $allowed_posts;

        // Note: private and hidden forums will be excluded via the
        // bbp_pre_get_posts_exclude_forums filter and function.
        $widget_query = new WP_Query($topics_query);

        // Bail if no topics are found
        if (!$widget_query->have_posts()) {
            return;
        }

        echo $args['before_widget'];

        if (!empty($settings['title'])) {
            echo $args['before_title'] . $settings['title'] . $args['after_title'];
        }
        ?>

        <ul>

            <?php
            while ($widget_query->have_posts()) :

                $widget_query->the_post();
                $topic_id = bbp_get_topic_id($widget_query->post->ID);
                $author_link = '';

                // Maybe get the topic author
                if ('on' == $settings['show_user']) :
                    $author_link = bbp_get_topic_author_link(array('post_id' => $topic_id, 'type' => 'both', 'size' => 14));
                endif;
                ?>

                <li>
                    <a class="bbp-forum-title" href="<?php echo esc_url(bbp_get_topic_permalink($topic_id)); ?>" title="<?php echo esc_attr(bbp_get_topic_title($topic_id)); ?>"><?php bbp_topic_title($topic_id); ?></a>

                    <?php if (!empty($author_link)) : ?>

                        <?php printf(_x('by %1$s', 'widgets', 'bbpress'), '<span class="topic-author">' . $author_link . '</span>'); ?>

                    <?php endif; ?>

                    <?php if ('on' == $settings['show_date']) : ?>

                        <div><?php bbp_topic_last_active_time($topic_id); ?></div>

                    <?php endif; ?>

                </li>

            <?php endwhile; ?>

        </ul>

        <?php
        echo $args['after_widget'];

        // Reset the $post global
        wp_reset_postdata();
    }

    /**
     * Update the topic widget options
     *
     * @since bbPress (r2653)
     *
     * @param array $new_instance The new instance options
     * @param array $old_instance The old instance options
     */
    public function update($new_instance = array(), $old_instance = array()) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['order_by'] = strip_tags($new_instance['order_by']);
        $instance['show_date'] = (bool) $new_instance['show_date'];
        $instance['show_user'] = (bool) $new_instance['show_user'];
        $instance['max_shown'] = (int) $new_instance['max_shown'];

        // Force to any
        if (!empty($instance['parent_forum']) || !is_numeric($instance['parent_forum'])) {
            $instance['parent_forum'] = 'any';
        } else {
            $instance['parent_forum'] = (int) $new_instance['parent_forum'];
        }

        return $instance;
    }

    /**
     * Output the topic widget options form
     *
     * @since bbPress (r2653)
     *
     * @param $instance Instance
     * @uses Tehnik_BBP_Topics_Widget::get_field_id() To output the field id
     * @uses Tehnik_BBP_Topics_Widget::get_field_name() To output the field name
     */
    public function form($instance = array()) {

        // Get widget settings
        $settings = $this->parse_settings($instance);
        ?>

        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'bbpress'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($settings['title']); ?>" /></label></p>
        <p><label for="<?php echo $this->get_field_id('max_shown'); ?>"><?php _e('Maximum topics to show:', 'bbpress'); ?> <input class="widefat" id="<?php echo $this->get_field_id('max_shown'); ?>" name="<?php echo $this->get_field_name('max_shown'); ?>" type="text" value="<?php echo esc_attr($settings['max_shown']); ?>" /></label></p>

        <p>
            <label for="<?php echo $this->get_field_id('parent_forum'); ?>"><?php _e('Parent Forum ID:', 'bbpress'); ?>
                <input class="widefat" id="<?php echo $this->get_field_id('parent_forum'); ?>" name="<?php echo $this->get_field_name('parent_forum'); ?>" type="text" value="<?php echo esc_attr($settings['parent_forum']); ?>" />
            </label>

            <br />

            <small><?php _e('"0" to show only root - "any" to show all', 'bbpress'); ?></small>
        </p>

        <p><label for="<?php echo $this->get_field_id('show_date'); ?>"><?php _e('Show post date:', 'bbpress'); ?> <input type="checkbox" id="<?php echo $this->get_field_id('show_date'); ?>" name="<?php echo $this->get_field_name('show_date'); ?>" <?php checked('on', $settings['show_date']); ?> /></label></p>
        <p><label for="<?php echo $this->get_field_id('show_user'); ?>"><?php _e('Show topic author:', 'bbpress'); ?> <input type="checkbox" id="<?php echo $this->get_field_id('show_user'); ?>" name="<?php echo $this->get_field_name('show_user'); ?>" <?php checked('on', $settings['show_user']); ?> /></label></p>

        <p>
            <label for="<?php echo $this->get_field_id('order_by'); ?>"><?php _e('Order By:', 'bbpress'); ?></label>
            <select name="<?php echo $this->get_field_name('order_by'); ?>" id="<?php echo $this->get_field_name('order_by'); ?>">
                <option <?php selected($settings['order_by'], 'newness'); ?> value="newness"><?php _e('Newest Topics', 'bbpress'); ?></option>
                <option <?php selected($settings['order_by'], 'popular'); ?> value="popular"><?php _e('Popular Topics', 'bbpress'); ?></option>
                <option <?php selected($settings['order_by'], 'freshness'); ?> value="freshness"><?php _e('Topics With Recent Replies', 'bbpress'); ?></option>
            </select>
        </p>

        <?php
    }

    /**
     * Merge the widget settings into defaults array.
     *
     * @since bbPress (r4802)
     *
     * @param $instance Instance
     * @uses bbp_parse_args() To merge widget options into defaults
     */
    public function parse_settings($instance = array()) {
        return bbp_parse_args($instance, array(
            'title' => __('Recent Topics', 'bbpress'),
            'max_shown' => 5,
            'show_date' => false,
            'show_user' => false,
            'parent_forum' => 'any',
            'order_by' => false
                ), 'topic_widget_settings');
    }

}

/**
 * Tehnik bbPress Replies Widget
 *
 * Adds a widget which displays the replies list
 *
 *
 * @uses WP_Widget
 */
class Tehnik_BBP_Replies_Widget extends WP_Widget {

    /**
     * Tehnik bbPress Replies Widget
     *
     * Registers the replies widget
     *
     *
     * @uses apply_filters() Calls 'bbp_replies_widget_options' with the
     *                        widget options
     */
    public function __construct() {
        $widget_ops = apply_filters('bbp_replies_widget_options', array(
            'classname' => 'widget_display_replies',
            'description' => __('A list of the most recent replies.', 'bbpress')
        ));

        parent::__construct(false, __('(Tehnik - bbPress) Recent Replies', 'bbpress'), $widget_ops);
    }

    /**
     * Register the widget
     *
     * @since bbPress (r3389)
     *
     * @uses register_widget()
     */
    public static function register_widget() {
        register_widget('Tehnik_BBP_Replies_Widget');
    }

    /**
     * Displays the output, the replies list
     *
     * @since bbPress (r2653)
     *
     * @param mixed $args
     * @param array $instance
     * @uses apply_filters() Calls 'bbp_reply_widget_title' with the title
     * @uses bbp_get_reply_author_link() To get the reply author link
     * @uses bbp_get_reply_author() To get the reply author name
     * @uses bbp_get_reply_id() To get the reply id
     * @uses bbp_get_reply_url() To get the reply url
     * @uses bbp_get_reply_excerpt() To get the reply excerpt
     * @uses bbp_get_reply_topic_title() To get the reply topic title
     * @uses get_the_date() To get the date of the reply
     * @uses get_the_time() To get the time of the reply
     */
    public function widget($args, $instance) {

        // Get widget settings
        $settings = $this->parse_settings($instance);

        // Typical WordPress filter
        $settings['title'] = apply_filters('widget_title', $settings['title'], $instance, $this->id_base);

        // bbPress filter
        $settings['title'] = apply_filters('bbp_replies_widget_title', $settings['title'], $instance, $this->id_base);

        // Note: private and hidden forums will be excluded via the
        // bbp_pre_get_posts_exclude_forums filter and function.
        $query_data = array(
            'post_type' => bbp_get_reply_post_type(),
            'post_status' => array(bbp_get_public_status_id(), bbp_get_closed_status_id()),
            'posts_per_page' => (int) $settings['max_shown']
        );

        //Get an array of IDs which the current user has permissions to view
        $allowed_posts = tehnik_bpp_get_permitted_post_ids(new WP_Query($query_data));
        // The default forum query with allowed forum ids array added
        $query_data['post__in'] = $allowed_posts;

        $widget_query = new WP_Query($query_data);

        // Bail if no replies
        if (!$widget_query->have_posts()) {
            return;
        }

        echo $args['before_widget'];

        if (!empty($settings['title'])) {
            echo $args['before_title'] . $settings['title'] . $args['after_title'];
        }
        ?>

        <ul>

            <?php while ($widget_query->have_posts()) : $widget_query->the_post(); ?>

                <li>

                    <?php
                    // Verify the reply ID
                    $reply_id = bbp_get_reply_id($widget_query->post->ID);
                    $reply_link = '<a class="bbp-reply-topic-title" href="' . esc_url(bbp_get_reply_url($reply_id)) . '" title="' . esc_attr(bbp_get_reply_excerpt($reply_id, 50)) . '">' . bbp_get_reply_topic_title($reply_id) . '</a>';

                    // Only query user if showing them
                    if ('on' == $settings['show_user']) :
                        $author_link = bbp_get_reply_author_link(array('post_id' => $reply_id, 'type' => 'both', 'size' => 14));
                    else :
                        $author_link = false;
                    endif;

                    // Reply author, link, and timestamp
                    if (( 'on' == $settings['show_date'] ) && !empty($author_link)) :

                        // translators: 1: reply author, 2: reply link, 3: reply timestamp
                        printf(_x('%1$s on %2$s %3$s', 'widgets', 'bbpress'), $author_link, $reply_link, '<div>' . bbp_get_time_since(get_the_time('U')) . '</div>');

                    // Reply link and timestamp
                    elseif ('on' == $settings['show_date']) :

                        // translators: 1: reply link, 2: reply timestamp
                        printf(_x('%1$s %2$s', 'widgets', 'bbpress'), $reply_link, '<div>' . bbp_get_time_since(get_the_time('U')) . '</div>');

                    // Reply author and title
                    elseif (!empty($author_link)) :

                        // translators: 1: reply author, 2: reply link
                        printf(_x('%1$s on %2$s', 'widgets', 'bbpress'), $author_link, $reply_link);

                    // Only the reply title
                    else :

                        // translators: 1: reply link
                        printf(_x('%1$s', 'widgets', 'bbpress'), $reply_link);

                    endif;
                    ?>

                </li>

            <?php endwhile; ?>

        </ul>

        <?php
        echo $args['after_widget'];

        // Reset the $post global
        wp_reset_postdata();
    }

    /**
     * Update the reply widget options
     *
     * @since bbPress (r2653)
     *
     * @param array $new_instance The new instance options
     * @param array $old_instance The old instance options
     */
    public function update($new_instance = array(), $old_instance = array()) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['show_date'] = (bool) $new_instance['show_date'];
        $instance['show_user'] = (bool) $new_instance['show_user'];
        $instance['max_shown'] = (int) $new_instance['max_shown'];

        return $instance;
    }

    /**
     * Output the reply widget options form
     *
     * @since bbPress (r2653)
     *
     * @param $instance Instance
     * @uses Tehnik_BBP_Replies_Widget::get_field_id() To output the field id
     * @uses Tehnik_BBP_Replies_Widget::get_field_name() To output the field name
     */
    public function form($instance = array()) {

        // Get widget settings
        $settings = $this->parse_settings($instance);
        ?>

        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'bbpress'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($settings['title']); ?>" /></label></p>
        <p><label for="<?php echo $this->get_field_id('max_shown'); ?>"><?php _e('Maximum replies to show:', 'bbpress'); ?> <input class="widefat" id="<?php echo $this->get_field_id('max_shown'); ?>" name="<?php echo $this->get_field_name('max_shown'); ?>" type="text" value="<?php echo esc_attr($settings['max_shown']); ?>" /></label></p>
        <p><label for="<?php echo $this->get_field_id('show_date'); ?>"><?php _e('Show post date:', 'bbpress'); ?> <input type="checkbox" id="<?php echo $this->get_field_id('show_date'); ?>" name="<?php echo $this->get_field_name('show_date'); ?>" <?php checked('on', $settings['show_date']); ?> /></label></p>
        <p><label for="<?php echo $this->get_field_id('show_user'); ?>"><?php _e('Show reply author:', 'bbpress'); ?> <input type="checkbox" id="<?php echo $this->get_field_id('show_user'); ?>" name="<?php echo $this->get_field_name('show_user'); ?>" <?php checked('on', $settings['show_user']); ?> /></label></p>

        <?php
    }

    /**
     * Merge the widget settings into defaults array.
     *
     * @since bbPress (r4802)
     *
     * @param $instance Instance
     * @uses bbp_parse_args() To merge widget settings into defaults
     */
    public function parse_settings($instance = array()) {
        return bbp_parse_args($instance, array(
            'title' => __('Recent Replies', 'bbpress'),
            'max_shown' => 5,
            'show_date' => false,
            'show_user' => false
                ), 'replies_widget_settings');
    }

}


/*
 * LIST WIDGETS QUERY FILTER
 */

/*
 * This function filters the Topic Lists Widget based on user permissions
 * 
 * returns: Filtered Query for bbPress Topics
 */
function tehnik_bbp_has_topics($args = '') {
    global $wp_rewrite;

    /** Defaults ************************************************************* */
    // Other defaults
    $default_topic_search = !empty($_REQUEST['ts']) ? $_REQUEST['ts'] : false;
    $default_show_stickies = (bool) ( bbp_is_single_forum() || bbp_is_topic_archive() ) && ( false === $default_topic_search );
    $default_post_parent = bbp_is_single_forum() ? bbp_get_forum_id() : 'any';

    // Default argument array
    $default = array(
        'post_type' => bbp_get_topic_post_type(), // Narrow query down to bbPress topics
        'post_parent' => $default_post_parent, // Forum ID
        'meta_key' => '_bbp_last_active_time', // Make sure topic has some last activity time
        'orderby' => 'meta_value', // 'meta_value', 'author', 'date', 'title', 'modified', 'parent', rand',
        'order' => 'DESC', // 'ASC', 'DESC'
        'posts_per_page' => bbp_get_topics_per_page(), // Topics per page
        'paged' => bbp_get_paged(), // Page Number
        's' => $default_topic_search, // Topic Search
        'show_stickies' => $default_show_stickies, // Ignore sticky topics?
        'max_num_pages' => false, // Maximum number of pages to show
    );


    //Get an array of IDs which the current user has permissions to view
    $allowed_forums = tehnik_bpp_get_permitted_post_ids(new WP_Query($default));

    // The default forum query with allowed forum ids array added
    $default['post__in'] = $allowed_forums;

    // What are the default allowed statuses (based on user caps)
    if (bbp_get_view_all()) {

        // Default view=all statuses
        $post_statuses = array(
            bbp_get_public_status_id(),
            bbp_get_closed_status_id(),
            bbp_get_spam_status_id(),
            bbp_get_trash_status_id()
        );

        // Add support for private status
        if (current_user_can('read_private_topics')) {
            $post_statuses[] = bbp_get_private_status_id();
        }

        // Join post statuses together
        $default['post_status'] = join(',', $post_statuses);

        // Lean on the 'perm' query var value of 'readable' to provide statuses
    } else {
        $default['perm'] = 'readable';
    }

    // Maybe query for topic tags
    if (bbp_is_topic_tag()) {
        $default['term'] = bbp_get_topic_tag_slug();
        $default['taxonomy'] = bbp_get_topic_tag_tax_id();
    }

    /** Setup **************************************************************** */
    // Parse arguments against default values
    $r = bbp_parse_args($args, $default, 'has_topics');

    // Get bbPress
    $bbp = bbpress();

    // Call the query
    $bbp->topic_query = new WP_Query($r);

    // Set post_parent back to 0 if originally set to 'any'
    if ('any' == $r['post_parent'])
        $r['post_parent'] = 0;

    // Limited the number of pages shown
    if (!empty($r['max_num_pages']))
        $bbp->topic_query->max_num_pages = $r['max_num_pages'];

    /** Stickies ************************************************************* */
    // Put sticky posts at the top of the posts array
    if (!empty($r['show_stickies']) && $r['paged'] <= 1) {

        // Get super stickies and stickies in this forum
        $stickies = bbp_get_super_stickies();

        // Get stickies for current forum
        if (!empty($r['post_parent'])) {
            $stickies = array_merge($stickies, bbp_get_stickies($r['post_parent']));
        }

        // Remove any duplicate stickies
        $stickies = array_unique($stickies);

        // We have stickies
        if (is_array($stickies) && !empty($stickies)) {

            // Start the offset at -1 so first sticky is at correct 0 offset
            $sticky_offset = -1;

            // Loop over topics and relocate stickies to the front.
            foreach ($stickies as $sticky_index => $sticky_ID) {

                // Get the post offset from the posts array
                $post_offsets = wp_filter_object_list($bbp->topic_query->posts, array('ID' => $sticky_ID), 'OR', 'ID');

                // Continue if no post offsets
                if (empty($post_offsets)) {
                    continue;
                }

                // Loop over posts in current query and splice them into position
                foreach (array_keys($post_offsets) as $post_offset) {
                    $sticky_offset++;

                    $sticky = $bbp->topic_query->posts[$post_offset];

                    // Remove sticky from current position
                    array_splice($bbp->topic_query->posts, $post_offset, 1);

                    // Move to front, after other stickies
                    array_splice($bbp->topic_query->posts, $sticky_offset, 0, array($sticky));

                    // Cleanup
                    unset($stickies[$sticky_index]);
                    unset($sticky);
                }

                // Cleanup
                unset($post_offsets);
            }

            // Cleanup
            unset($sticky_offset);

            // If any posts have been excluded specifically, Ignore those that are sticky.
            if (!empty($stickies) && !empty($r['post__not_in'])) {
                $stickies = array_diff($stickies, $r['post__not_in']);
            }

            // Fetch sticky posts that weren't in the query results
            if (!empty($stickies)) {

                // Query to use in get_posts to get sticky posts
                $sticky_query = array(
                    'post_type' => bbp_get_topic_post_type(),
                    'post_parent' => 'any',
                    'meta_key' => '_bbp_last_active_time',
                    'orderby' => 'meta_value',
                    'order' => 'DESC',
                    'include' => $stickies
                );


                //Get an array of IDs which the current user has permissions to view
                $allowed_forums = tehnik_bpp_get_permitted_post_ids(new WP_Query($sticky_query));

                // The default forum query with allowed forum ids array added
                $sticky_query['post__in'] = $allowed_forums;

                // Cleanup
                unset($stickies);

                // What are the default allowed statuses (based on user caps)
                if (bbp_get_view_all()) {
                    $sticky_query['post_status'] = $r['post_status'];

                    // Lean on the 'perm' query var value of 'readable' to provide statuses
                } else {
                    $sticky_query['post_status'] = $r['perm'];
                }

                // Get all stickies
                $sticky_posts = get_posts($sticky_query);
                if (!empty($sticky_posts)) {

                    // Get a count of the visible stickies
                    $sticky_count = count($sticky_posts);

                    // Merge the stickies topics with the query topics .
                    $bbp->topic_query->posts = array_merge($sticky_posts, $bbp->topic_query->posts);

                    // Adjust loop and counts for new sticky positions
                    $bbp->topic_query->found_posts = (int) $bbp->topic_query->found_posts + (int) $sticky_count;
                    $bbp->topic_query->post_count = (int) $bbp->topic_query->post_count + (int) $sticky_count;

                    // Cleanup
                    unset($sticky_posts);
                }
            }
        }
    }

    // If no limit to posts per page, set it to the current post_count
    if (-1 == $r['posts_per_page'])
        $r['posts_per_page'] = $bbp->topic_query->post_count;

    // Add pagination values to query object
    $bbp->topic_query->posts_per_page = $r['posts_per_page'];
    $bbp->topic_query->paged = $r['paged'];

    // Only add pagination if query returned results
    if (( (int) $bbp->topic_query->post_count || (int) $bbp->topic_query->found_posts ) && (int) $bbp->topic_query->posts_per_page) {

        // Limit the number of topics shown based on maximum allowed pages
        if ((!empty($r['max_num_pages']) ) && $bbp->topic_query->found_posts > $bbp->topic_query->max_num_pages * $bbp->topic_query->post_count)
            $bbp->topic_query->found_posts = $bbp->topic_query->max_num_pages * $bbp->topic_query->post_count;

        // If pretty permalinks are enabled, make our pagination pretty
        if ($wp_rewrite->using_permalinks()) {

            // User's topics
            if (bbp_is_single_user_topics()) {
                $base = bbp_get_user_topics_created_url(bbp_get_displayed_user_id());

                // User's favorites
            } elseif (bbp_is_favorites()) {
                $base = bbp_get_favorites_permalink(bbp_get_displayed_user_id());

                // User's subscriptions
            } elseif (bbp_is_subscriptions()) {
                $base = bbp_get_subscriptions_permalink(bbp_get_displayed_user_id());

                // Root profile page
            } elseif (bbp_is_single_user()) {
                $base = bbp_get_user_profile_url(bbp_get_displayed_user_id());

                // View
            } elseif (bbp_is_single_view()) {
                $base = bbp_get_view_url();

                // Topic tag
            } elseif (bbp_is_topic_tag()) {
                $base = bbp_get_topic_tag_link();

                // Page or single post
            } elseif (is_page() || is_single()) {
                $base = get_permalink();

                // Topic archive
            } elseif (bbp_is_topic_archive()) {
                $base = bbp_get_topics_url();

                // Default
            } else {
                $base = get_permalink((int) $r['post_parent']);
            }

            // Use pagination base
            $base = trailingslashit($base) . user_trailingslashit($wp_rewrite->pagination_base . '/%#%/');

            // Unpretty pagination
        } else {
            $base = add_query_arg('paged', '%#%');
        }

        // Pagination settings with filter
        $bbp_topic_pagination = apply_filters('bbp_topic_pagination', array(
            'base' => $base,
            'format' => '',
            'total' => $r['posts_per_page'] == $bbp->topic_query->found_posts ? 1 : ceil((int) $bbp->topic_query->found_posts / (int) $r['posts_per_page']),
            'current' => (int) $bbp->topic_query->paged,
            'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
            'next_text' => is_rtl() ? '&larr;' : '&rarr;',
            'mid_size' => 1
                ));

        // Add pagination to query object
        $bbp->topic_query->pagination_links = paginate_links($bbp_topic_pagination);

        // Remove first page from pagination
        $bbp->topic_query->pagination_links = str_replace($wp_rewrite->pagination_base . "/1/'", "'", $bbp->topic_query->pagination_links);
    }

    // Return object
    return  array($bbp->topic_query->have_posts(), $bbp->topic_query);
}

//add_filter('bbp_has_topics', 'tehnik_bbp_has_topics', 1, 2);