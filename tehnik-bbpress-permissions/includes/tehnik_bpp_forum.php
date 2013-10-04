<?php

/**
 * This function filters the list of forums based on the users rank as set by the Mebmers plugin
 */
function tehnik_bpp_filter_forums_by_permissions($args = '') {
    $bbp = bbpress();

    // Setup possible post__not_in array
    $post_stati[] = bbp_get_public_status_id();

    // Check if user can read private forums
    if (current_user_can('read_private_forums'))
        $post_stati[] = bbp_get_private_status_id();

    // Check if user can read hidden forums
    if (current_user_can('read_hidden_forums'))
        $post_stati[] = bbp_get_hidden_status_id();

    // The default forum query for most circumstances
    $meta_query = array(
        'post_type' => bbp_get_forum_post_type(),
        'post_parent' => bbp_is_forum_archive() ? 0 : bbp_get_forum_id(),
        'post_status' => implode(',', $post_stati),
        'posts_per_page' => get_option('_bbp_forums_per_page', 50),
        'orderby' => 'menu_order',
        'order' => 'ASC'
    );

    //Get an array of IDs which the current user has permissions to view
    $allowed_forums = tehnik_bpp_get_permitted_post_ids(new WP_Query($meta_query));

    // The default forum query with allowed forum ids array added
    $meta_query['post__in'] = $allowed_forums;

    $bbp_f = bbp_parse_args($args, $meta_query, 'has_forums');

    // Run the query
    $bbp->forum_query = new WP_Query($bbp_f);

    return apply_filters('bpp_filter_forums_by_permissions', $bbp->forum_query->have_posts(), $bbp->forum_query);
}
	
/**
 * Use the given query to determine which forums the user has access to. 
 * Return an array of forums which user has permission to access
 */
function tehnik_bpp_get_permitted_forums($forum_list)
{
	if(function_exists('members_can_user_view_post'))
	{
		$filtered_forums = array();
		
		//Get Current User ID
		$user_id = wp_get_current_user()->ID;
	
		foreach ($forum_list as $forum) 
		{
			$forum_id = $forum->ID;
			
			if(members_can_user_view_post($user_id, $forum_id))
			{
				array_push($filtered_forums, $forum);
			}
		}
		
		return (array) $filtered_forums;
	}
	
	return true;
}

/**
 * This function filters the list of subforums based on the users rank as set by the Mebmers plugin
 */
function tehnik_bpp_get_permitted_subforums($sub_forums = '') {
    $filtered_sub_forums = tehnik_bpp_get_permitted_forums($sub_forums);
	
    if (empty($filtered_sub_forums)) {	
        return (array) $sub_forums;
    }

    return (array) $filtered_sub_forums;
}

/**
 * Check if the user is allowed to view the content (forum/topic/post)
 * Show a 404 error if the user does not have a permission to access the content
 */
function tehnik_bpp_enforce_permissions() {
    // Bail if not viewing a bbPress item
    if (!is_bbpress())
        return;

    // Bail if not viewing a single item or if user has caps
    if (!is_singular() || bbp_is_user_keymaster() || current_user_can('read_hidden_forums'))
        return;

    if (!tehnik_bpp_can_user_view_post()) {
        bbp_set_404();
    }
}

add_filter('bbp_has_forums', 'tehnik_bpp_filter_forums_by_permissions', 10, 2);
add_filter('bbp_forum_get_subforums', 'tehnik_bpp_get_permitted_subforums', 10, 1);
add_action('bbp_template_redirect', 'tehnik_bpp_enforce_permissions', 1);
?>