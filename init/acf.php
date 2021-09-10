<?php

namespace CUMULUS\Wordpress\ProgramCPT;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

// ACF setup
$ACF_JSON = new vena\AcfJson\Loader( [
	'group_61240bc1afe31',
	'group_612524d9ef624',
], BASEPATH );
