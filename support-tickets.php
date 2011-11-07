<?php
/*
Plugin Name: Support Tickets
Plugin URI: http://ideasilo.wordpress.com/2009/10/28/support-tickets/
Description: With this plugin, you can manage a simple support ticket system on your WordPress.
Author: Takayuki Miyoshi
Author URI: http://ideasilo.wordpress.com/
Version: 1.0.1
*/

/*  Copyright 2009 Takayuki Miyoshi (email: takayukister at gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define( 'SUPTIC_VERSION', '1.0.1' );

if ( ! defined( 'SUPTIC_PLUGIN_BASENAME' ) )
	define( 'SUPTIC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

if ( ! defined( 'SUPTIC_PLUGIN_NAME' ) )
	define( 'SUPTIC_PLUGIN_NAME', trim( dirname( SUPTIC_PLUGIN_BASENAME ), '/' ) );

if ( ! defined( 'SUPTIC_PLUGIN_DIR' ) )
	define( 'SUPTIC_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . SUPTIC_PLUGIN_NAME );

if ( ! defined( 'SUPTIC_PLUGIN_MODULES_DIR' ) )
	define( 'SUPTIC_PLUGIN_MODULES_DIR', SUPTIC_PLUGIN_DIR . '/modules' );

if ( ! defined( 'SUPTIC_LOAD_JS' ) )
	define( 'SUPTIC_LOAD_JS', true );

if ( ! defined( 'SUPTIC_LOAD_CSS' ) )
	define( 'SUPTIC_LOAD_CSS', true );

if ( ! defined( 'SUPTIC_AUTOP' ) )
	define( 'SUPTIC_AUTOP', true );

if ( ! defined( 'SUPTIC_MANAGE_FORMS_CAPABILITY' ) )
	define( 'SUPTIC_MANAGE_FORMS_CAPABILITY', 'activate_plugins' ); // = Administrator

if ( ! defined( 'SUPTIC_ACCESS_ALL_TICKETS_CAPABILITY' ) )
	define( 'SUPTIC_ACCESS_ALL_TICKETS_CAPABILITY', 'activate_plugins' ); // = Administrator

if ( ! defined( 'WPML_LOAD_API_SUPPORT' ) )
	define( 'WPML_LOAD_API_SUPPORT', true );

require_once SUPTIC_PLUGIN_DIR . '/settings.php';

?>