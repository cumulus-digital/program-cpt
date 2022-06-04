<?php

namespace CUMULUS\Wordpress\ProgramCPT;

/*
 * Plugin Name: Program/Format Custom Post Types
 * Plugin URI: https://github.com/cumulus-digital/program-cpt/
 * GitHub Plugin URI: https://github.com/cumulus-digital/program-cpt/
 * Primary Branch: main
 * Description: Custom post types, taxonomies, block templates and tooling for "programs" and "formats"
 * Version: 1.2.11
 * Author: vena
 * License: UNLICENSED
 */

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

const TXTDOMAIN     = 'program-cpt';
const PLUGIN_NAME   = 'program-cpt';
const PREFIX        = 'program';
const BASE_FILENAME = PLUGIN_NAME . \DIRECTORY_SEPARATOR . PLUGIN_NAME . '.php';

\define( 'CUMULUS\Wordpress\ProgramCPT\BASEPATH', \untrailingslashit( \plugin_dir_path( __FILE__ ) ) );
\define( 'CUMULUS\Wordpress\ProgramCPT\BASEURL', \untrailingslashit( \plugin_dir_url( __FILE__ ) ) );

// Scoped Autoloader
require_once __DIR__ . '/build/composer/vendor/scoper-autoload.php';

// Extend extended-cpts to support hierarchical links
//require_once __DIR__ . '/libs/extended-cpts-hierarchical-post-link.php';

const CPTs = [];

// Initialize
require __DIR__ . '/init/index.php';

// Flush rewrite rules on plugin activation
\register_activation_hook(
	__FILE__,
	function () {
		\add_action( 'admin_init', 'flush_rewrite_rules', 20 );
	}
);
