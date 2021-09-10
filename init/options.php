<?php

namespace CUMULUS\Wordpress\ProgramCPT;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

class AdminOptions {

	public static $settingsName;

	public static $settingDefault = [
		'type'              => 'string',
		'title'             => null,
		'default'           => null,
		'sanitize_callback' => 'sanitize_text_field',
		'description'       => null,
		'show_in_rest'      => true,
		'required'          => false,
		'maxLength'         => null,
		'width'             => '100%',
		'params'            => null,
	];

	public static $settings;

	public static function init() {
		self::$settingsName = PREFIX;
		self::$settings     = [
			'cpt-icon' => [
				'type'        => 'string',
				'title'       => 'CPT Icon',
				'default'     => 'dashicons-microphone',
				'required'    => true,
				'description' => 'May be a dashicon string or URL',
			],
			'cpt-singular' => [
				'type'      => 'string',
				'title'     => 'CPT Singular Name',
				'default'   => 'Program',
				'required'  => true,
				'maxLength' => 15,
			],
			'cpt-plural' => [
				'type'      => 'string',
				'title'     => 'CPT Plural Name',
				'default'   => 'Programming',
				'required'  => true,
				'maxLength' => 15,
			],
			'cpt-slug' => [
				'type'              => 'string',
				'title'             => 'CPT Slug',
				'default'           => 'programs',
				'maxLength'         => 10,
				'sanitize_callback' => 'sanitize_title',
				'description'       => 'Public base path under which all CPT posts are accessed. Should be plural. Must be a valid Wordpress slug. Valid characters are letters, numbers, and dash (-)',
				'required'          => true,
				'params'            => [
					'pattern' => '[\w\-]+',
				],
			],
			'cpt-permastruct' => [
				'type'        => 'string',
				'title'       => 'CPT Permalink Structure',
				'default'     => '%' . PREFIX . '-cat%/%postname%',
				'required'    => true,
				'description' => 'Category: %' . PREFIX . '-cat%<br>Tag: %' . PREFIX . '-tag%',
			],
			'cat-singular' => [
				'type'      => 'string',
				'title'     => 'Category Singular Name',
				'default'   => 'Program Category',
				'required'  => true,
				'maxLength' => 30,
			],
			'cat-plural' => [
				'type'      => 'string',
				'title'     => 'Category Plural Name',
				'default'   => 'Program Categories',
				'required'  => true,
				'maxLength' => 30,
			],
			'cat-slug' => [
				'type'              => 'string',
				'title'             => 'Category Slug',
				'default'           => 'category',
				'maxLength'         => 10,
				'sanitize_callback' => 'sanitize_title',
				'description'       => 'SHOULD BE DIFFERENT FROM CPT OR TAG SLUG! The CPT slug will be prepended to the front of links.<br>Must be a valid Wordpress slug. Valid characters are letters, numbers, and hyphen (-)',
				'required'          => true,
				'params'            => [
					'pattern' => '[\w\-]+',
				],
			],
			'tag-singular' => [
				'type'      => 'string',
				'title'     => 'Tag Singular Name',
				'default'   => 'Program Tag',
				'required'  => true,
				'maxLength' => 30,
			],
			'tag-plural' => [
				'type'      => 'string',
				'title'     => 'Tag Plural Name',
				'default'   => 'Program Tags',
				'required'  => true,
				'maxLength' => 30,
			],
			'tag-slug' => [
				'type'              => 'string',
				'title'             => 'Tag Slug',
				'default'           => 'tag',
				'maxLength'         => 10,
				'sanitize_callback' => 'sanitize_title',
				'description'       => 'SHOULD BE DIFFERENT FROM CPT OR CATEGORY SLUG! The CPT slug will be prepended to the front of links.<br>Must be a valid Wordpress slug. Valid characters are letters, numbers, and hyphen (-)',
				'required'          => true,
				'params'            => [
					'pattern' => '[\w\-]+',
				],
			],
		];

		\add_action( 'admin_menu', [__CLASS__, 'addAdminMenu'] );
		\add_action( 'admin_init', [__CLASS__, 'register'] );

		\add_action(
			'plugin_action_links_' . BASE_FILENAME,
			[__CLASS__, 'addActionLink']
		);
	}

	// Add options page to admin menu
	public static function addAdminMenu() {
		\add_options_page(
			'Program CPT Options',
			'Program CPT',
			'manage_options',
			TXTDOMAIN,
			[__CLASS__, 'outputPage']
		);
	}

	// Add options link to plugin list
	public static function addActionLink( $links ) {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = [
			'<a href="' .
				\esc_url(
					\admin_url(
						'/options-general.php?page=' .
						TXTDOMAIN
					)
				) .
			'">' .
			\__( 'Settings' ) . '</a>',
		];

		return \array_merge( $links, $settings );
	}

	public static function register() {
		\register_setting( TXTDOMAIN, TXTDOMAIN );

		\add_settings_section(
			TXTDOMAIN,
			'General Options for Program Custom Post Type',
			null,
			TXTDOMAIN
		);

		$current      = \get_option( TXTDOMAIN );
		$needs_update = false;

		foreach ( self::$settings as $key => $setting ) {
			$setting = \array_replace_recursive( self::$settingDefault, $setting );

			if ( self::gav( $current, $key, '8Dha02DGcc' ) === '8Dha02DGcc' ) {
				$current[$key] = $setting['default'];
				$needs_update  = true;
			}

			if ( $setting['type'] !== 'hidden' ) {
				\add_settings_field(
					$key,
					$setting['title'],
					function () use ( $key, $setting ) {
						self::outputField( $key, $setting );
					},
					TXTDOMAIN,
					TXTDOMAIN,
					[
						'label_for' => $key,
					]
				);
			}
		}

		if ( $needs_update ) {
			\update_option( TXTDOMAIN, $current );
		}
	}

	public static function gav( $array, $key, $default = null ) {
		if (
				\is_array( $array )
				&& \array_key_exists( $key, $array )
			) {
			return $array[$key];
		}

		return $default;
	}

	public static function outputField( $key, $setting ) {
		$options = \get_option( TXTDOMAIN );
		$value   = self::gav( $options, $key, $setting['default'] );

		switch ( $setting['type'] ) {
			case 'text':
			case 'string':
				self::outputField_TEXT( $key, $setting, $value );

				break;
		}

		if ( $setting['description'] ):
			?>
			<p class="description">
				<?php echo $setting['description']; ?>
			</p>
			<?php
		endif;
	}

	public static function outputField_TEXT( $key, $setting, $value ) {
		$args = \array_map( function ( $k, $v ) {
			return "$k=\"$v\"";
		}, \array_keys( (array) $setting['params'] ), (array) $setting['params'] ); ?>
		<input type="text"
			name="<?php echo TXTDOMAIN; ?>[<?php echo $key; ?>]"
			value="<?php echo $value; ?>"
			<?php echo $setting['maxLength'] ? 'maxlength="' . $setting['maxLength'] . '"' : ''; ?>
			style="
				width: 100%;
				max-width:
				<?php if ( $setting['maxLength'] ): ?>
					<?php echo $setting['maxLength']; ?>em;
				<?php else: ?>
					100%;
				<?php endif; ?>
			"
			<?php if ( $setting['required'] ): ?>
				required
			<?php endif; ?>
			<?php echo \implode( ' ', $args ); ?>
		>
		<?php
	}

	public static function outputPage() {
		if ( isset( $_REQUEST['settings-updated'] ) && $_REQUEST['settings-updated'] ) {
			\flush_rewrite_rules();
			\flush_rewrite_rules( false );
		} ?>
		<form action="options.php" method="post">
			<h1>"Show" CPT Options</h1>
			<p>
				Changes here may affect permalinks. After saving, be sure to
				<a href="<?php echo \admin_url( 'options-permalink.php' ); ?>">
					flush them by re-saving permalinks.
				</a>
			</p>
			<?php \settings_fields( TXTDOMAIN ); ?>
			<?php \do_settings_sections( TXTDOMAIN ); ?>
			<?php \submit_button(); ?>
		</form>
		<?php
	}
}

AdminOptions::init();
