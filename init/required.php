<?php

namespace CUMULUS\Wordpress\ProgramCPT;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

function registerRequiredPlugins() {
	$plugins = [
		[
			'name'             => 'Advanced Custom Fields',
			'slug'             => 'advanced-custom-fields',
			'required'         => true,
			'force_activation' => true,
			'is_callable'      => 'acf_register_block_type',
		],
	];

	$config = [
		// Unique ID for hashing notices for multiple instances of TGMPA.
		'id' => TXTDOMAIN,
		// Default absolute path to bundled plugins.
		'default_path' => '',
		// Menu slug.
		'menu' => TXTDOMAIN . '-install-plugins',
		// Parent menu slug.
		'parent_slug' => 'plugins.php',
		// Capability needed to view plugin install page,
		// should be a capability associated with the parent menu used.
		'capability' => 'activate_plugins',
		// Show admin notices or not.
		'has_notices' => true,
		// If false, a user cannot dismiss the nag message.
		'dismissable' => false,
		// If 'dismissable' is false, this message will be output at top of nag.
		'dismiss_msg' => '',
		// Automatically activate plugins after installation or not.
		'is_automatic' => true,
		// Message to output right before the plugins table.
		'message' => '',
		'strings' => [],
	];

	\tgmpa( $plugins, $config );
}
\add_action( 'tgmpa_register', __NAMESPACE__ . '\\registerRequiredPlugins' );
