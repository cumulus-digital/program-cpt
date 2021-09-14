<?php

namespace CUMULUS\Wordpress\ProgramCPT;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

$CPTs = [];

require __DIR__ . '/required.php';
//require __DIR__ . '/options.php';
require __DIR__ . '/acf.php';
//require __DIR__ . '/taxonomies.php';
require __DIR__ . '/cpt.php';
require __DIR__ . '/frontend.php';
require __DIR__ . '/backend.php';
