<?php

/*
 * Filters BuddyPress activity feed to exclude bbPress posts which the user does not have permission to view
 */
function tehnik_filter_buddypress_activities( $a, $activities ) { 
	//if admin we want to know
	if ( is_site_admin() )
		return $activities;
	 
	foreach ( $activities->activities as $key => $activity )
	{	
		//new_member is the type name (component is 'profile')
		if ( $activity->type =='bbp_topic_create' || $activity->type =='bbp_reply_create') 
		{
			$post_id = $activity->secondary_item_id;
			
			if($activity->type =='bbp_reply_create')
			{
				$post_id =  bbp_get_topic_forum_id($post_id);
			}
				
			if(!tehnik_bpp_can_user_view_post_id($post_id))
			{
				unset( $activities->activities[$key] );			 
				$activities->activity_count = $activities->activity_count-1;
				$activities->total_activity_count = $activities->total_activity_count-1;
				$activities->pag_num = $activities->pag_num -1;
			}			
		}
	}
	 
	/* Renumber the array keys to account for missing items */
	$activities_new = array_values( $activities->activities );
	$activities->activities = $activities_new;
	 
	return $activities;
}

add_action('bp_has_activities','tehnik_filter_buddypress_activities', 10, 2 );

?>