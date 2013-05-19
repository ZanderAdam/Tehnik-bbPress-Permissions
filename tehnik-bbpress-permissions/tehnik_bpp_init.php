<?php
/*
Plugin Name: Tehnik BBPress Permissions
Plugin URI: www.tehnik-design.com
Description: Controls BBPress Forum Permissions using Wordpress Members Plugin
Version: 0.1
Author: Aleksandar Adamovic - Tehnik Design
Author URI: www.tehnik-design.com
License: GPL2
*/

/*  Copyright 2013  ALEKSANDAR ADAMOVIC (email : aadamovic@tehnik-design.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Tehnik_BBPress_Permissions_Load {	

	//Contructor for earlier versions of PHP
	function Tehnik_BBPress_Permissions_Load() {
		$this->__construct();
	}
	
	function __construct() {		
		add_action( 'plugins_loaded', array( &$this, 'constants' ), 1 );
		add_action( 'plugins_loaded', array( &$this, 'includes' ), 3 );
	}
	
	function constants() {
		/* Set constant path to the members plugin directory. */
		define( 'TEHNIKBPP_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );

		/* Set the constant path to the members includes directory. */
		define( 'TEHNIKBPP_INCLUDES', TEHNIKBPP_DIR . trailingslashit( 'includes' ) );
	}
	
	function includes() {

		/* Load the plugin functions file. */
		require_once( TEHNIKBPP_INCLUDES . 'tehnik_bpp_core.php' );
		require_once( TEHNIKBPP_INCLUDES . 'tehnik_bpp_forum.php' );
		require_once( TEHNIKBPP_INCLUDES . 'tehnik_bpp_forum_widgets.php' );
		require_once( TEHNIKBPP_INCLUDES . 'tehnik_bpp_buddypress.php' );
	}		
}

$tehnik_bbpress_permissions_load = new Tehnik_BBPress_Permissions_Load()

?>