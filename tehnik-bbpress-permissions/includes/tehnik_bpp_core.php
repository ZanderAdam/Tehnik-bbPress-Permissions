<?php

/*
 * Check if the current user has rights to view the given Post ID
 * 
 * returns true if user can view the post
 */
function tehnik_bpp_can_user_view_post_id($post_id) {
    $user_id = wp_get_current_user()->ID;
    return members_can_user_view_post($user_id, $post_id);
}

/*
 * Check if the current user can view the current post
 * 
 * returns true if the user can view the post
 */
function tehnik_bpp_can_user_view_post() {

    if (!function_exists('members_can_user_view_post')) {
        return;
    }

    global $wp_query;

    // Get Forum Id for the current post    
    $post_id = $wp_query->post->ID;
    $post_type = $wp_query->get('post_type');
    $forum_id = tehnik_bpp_get_forum_id_from_post_id($post_id, $post_type);

    if (tehnik_bpp_can_user_view_post_id($forum_id))
        return true;
}

/*
 * Returns the bbPress Forum ID from given Post ID and Post Type
 * 
 * returns: bbPRess Forum ID
 */
function tehnik_bpp_get_forum_id_from_post_id($post_id, $post_type) {
    $forum_id = 0;

    // Check post type
    switch ($post_type) {
        // Forum
        case bbp_get_forum_post_type() :
            $forum_id = bbp_get_forum_id($post_id);
            break;

        // Topic
        case bbp_get_topic_post_type() :
            $forum_id = bbp_get_topic_forum_id($post_id);
            break;

        // Reply
        case bbp_get_reply_post_type() :
            $forum_id = bbp_get_reply_forum_id($post_id);
            break;
    }

    return $forum_id;
}

/**
 * Use the given query to determine which forums the user has access to.
 * 
 * returns: an array of forum objects which user has permission to access
 */
function tehnik_bpp_get_permitted_forums($forum_query) {

    if (function_exists('members_can_user_view_forum')) {
        $filtered_forums = array();

        foreach ($forum_query as $forum) {
            $forum_id = $forum->ID;

            if (tehnik_bpp_can_user_view_forum_id($forum_id)) {
                array_push($filtered_forums, $forum);
            }
        }

        return (array) $filtered_forums;
    }

    return false;
}

/**
 * Use the given query to determine which forums the user has access to. 
 * 
 * returns: an array of forum IDs which user has access to.
 */
function tehnik_bpp_get_permitted_post_ids($forum_query) {
    //Check if Members plugin function exists. No need to reinvent the wheel..use what is available
    if (!function_exists('members_can_user_view_post'))
        return array();

    //Init the Array which will hold our list of allowed forums
    $allowed_posts = array();
    $post_type = $forum_query->get('post_type');

    //Loop through all the forums
    while ($forum_query->have_posts()) :
        $forum_query->the_post();

        //Get the Post ID
        $post_id = $forum_query->post->ID;

        //Get the Forum ID based on Post Type (Reply, Topic, Forum)
        $forum_id = tehnik_bpp_get_forum_id_from_post_id($post_id, $post_type);

        //Check if User has permissions to view this Post ID
        if (tehnik_bpp_can_user_view_post_id($forum_id)) {
            //User can view this post (forum) - add it to the allowed forums array
            array_push($allowed_posts, $post_id);
        }

    endwhile;

    //Return the list		
    return $allowed_posts;
}

?>