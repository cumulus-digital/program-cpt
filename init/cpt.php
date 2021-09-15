<?php

namespace CUMULUS\Wordpress\ProgramCPT;

use Exception;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

// Static container for CPTs
class CPTs {

	public static $store = [];

	public static function add( $key, $cpt ) {
		self::$store[$key] = $cpt;
	}

	public static function get( $key ) {
		return self::$store[$key];
	}

	public static function getKeys() {
		return \array_keys( self::$store );
	}
}

// Generate CPTs, their category, and tags
class CPT {

	private $prefix = '';

	private $options = [
		'cpt' => [
			'public'             => true,
			'hierarchical'       => false,
			'show_in_feed'       => true,
			'show_in_rest'       => true,
			'dashboard_activity' => true,
			'dashboard_glance'   => true,
			'supports'           => [
				'title', 'editor', 'revisions', 'excerpt', 'thumbnail',
			],
			'capability_type' => 'page',
			'map_meta_cap'    => true,
			'menu_icon'       => 'dashicons-microphone',
			'template'        => [
				[
					'core/columns',
					[
						'templateLock' => 'all',
						'className'    => 'program-content',
						'alignWide'    => false,
						'columns'      => 2,
					],
					[
						[
							'core/column',
							[
								'templateLock'      => false,
								'className'         => 'sidebar',
								'verticalAlignment' => 'top',
								'align'             => 'right',
								'width'             => '350px',
								'spacing'           => false,
							],
							[],
						],
						[
							'core/column',
							[
								'templateLock'      => false,
								'className'         => 'main-content',
								'verticalAlignment' => 'top',
								'spacing'           => false,
							],
							[
								[ 'core/paragraph', [ 'placeholder' => 'Begin main content here.']  ],
							],
						],
					],
				],
			],
		],
		'category' => [
			'public'       => true,
			'hierarchical' => true,
			'show_in_rest' => true,
			'query_var'    => true,
			'label'        => null,
			'rewrite'      => [
				'slug'         => null,
				'hierarchical' => true,
				'with_front'   => false,
			],
		],
		'tag' => [
			'public'       => true,
			'hierarchical' => false,
			'show_in_rest' => true,
			'query_var'    => true,
			'label'        => null,
			'rewrite'      => [
				'slug'       => null,
				'with_front' => false,
			],
		],
	];

	private $labels = [
		'cpt'      => [],
		'category' => [],
		'tag'      => [],
	];

	private $cpt;

	private $category;

	private $tag;

	public function __construct( $prefix, $options, $labels ) {
		if ( ! $prefix ) {
			throw new Exception( 'Must provide a prefix for CPT' );
		} else {
			$this->prefix = $prefix;
		}

		if ( ! $labels ) {
			throw new Exception( 'Must provide labels for CPT' );
		} else {
			$this->labels = $labels;
		}

		if ( $options && \is_array( $options ) ) {
			$this->options = \array_replace_recursive( $this->options, $options );
		}

		// Set up rewrites
		$this->options['cpt']['rewrite'] = [
			'permastruct'  => '/' . $labels['cpt']['slug'] . '/%' . $this->prefix . '-cat%/%postname%',
			'hierarchical' => true,
		];

		// Set up admin post list cols
		$this->options['cpt']['admin_cols'] = [
			$labels['category']['plural'] => [
				'taxonomy' => $this->prefix . '-cat',
				'function' => [$this, 'outputAdminColsForHierarchicalTerms'],
			],
			'date',
		];

		// Dropdown filters for admin post list
		$this->options['cpt']['admin_filters'] = [
			$labels['category']['plural'] => [
				'taxonomy' => $this->prefix . '-cat',
			],
			$labels['tag']['plural'] => [
				'taxonomy' => $this->prefix . '-tag',
			],
		];

		$this->registerCategory();
		$this->registerTag();
		$this->registerPostType();
	}

	private function registerPostType() {
		\add_action( 'init', function () {
			$this->cpt = register_extended_post_type(
				$this->prefix,
				$this->options['cpt'],
				$this->labels['cpt']
			);

			/*
			 * Extended-CPTs does not handle hierarchical taxonomy links in
			 * permastruct, so we'll replace its post_type_link filter with
			 * one which does.
			 */
			\remove_filter( 'post_type_link', [$this->cpt, 'post_type_link'], 1 );
			\add_filter(
				'post_type_link',
				[$this, 'handleHierarchicalPostLinks'],
				1,
				4
			);
		} );

		// Allow Jetpack copy posts for this CPT
		\add_filter( 'jetpack_copy_post_post_types', function ( $post_types ) {
			$post_types[] = $this->prefix;

			return $post_types;
		} );

		// Include CPT in search results
		\add_action( 'pre_get_posts', function ( $query ) {
			if (
				\is_admin()
				|| ! $this->options['cpt']['public']
				|| ! $query->is_main_query()
				|| ! $query->is_search()
			) {
				return $query;
			}

			$current = (array) $query->get( 'post_type' );
			$current[] = $this->prefix;
			$query->set( 'post_type', $current );

			return $query;
		}, 10 );

		// Display filters
		\add_filter( 'display-archive-display_format', function ( $current ) {
			return $this->returnIfOurs( 'cards', $current, true );
		}, 10 );
		\add_filter( 'display-archive-show_title', function ( $current ) {
			return $this->returnIfOurs( false, $current, true );
		}, 10 );
		\add_filter( 'display-archive-show_date', function ( $current ) {
			return $this->returnIfOurs( false, $current );
		}, 10 );
		\add_filter( 'display-archive-show_author', function ( $current ) {
			return $this->returnIfOurs( false, $current );
		}, 10 );
		\add_filter( 'display-archive-show_category', function ( $current ) {
			return $this->returnIfOurs( false, $current, true );
		}, 10 );
		\add_filter( 'display-archive-show_source', function ( $current ) {
			return $this->returnIfOurs( false, $current, true );
		}, 10 );
		\add_filter( 'display-archive-show_excerpt', function ( $current ) {
			return $this->returnIfOurs( false, $current, true );
		}, 10 );
	}

	private function registerCategory() {
		$this->options['category']['label']           = $this->labels['category']['plural'];
		$this->options['category']['rewrite']['slug'] = $this->labels['cpt']['slug'] . '/' . $this->labels['category']['slug'];

		\add_action( 'init', function () {
			$this->category = \register_taxonomy(
				$this->prefix . '-cat',
				$this->prefix,
				$this->options['category']
			);
		} );

		// Filter frontend displays of this category
		\add_action( 'pre_get_posts', function ( $query ) {
			$taxonomy_slugs = [$this->prefix . '-cat'];

			if (
				\is_admin()
				|| ! $query->is_main_query()
				|| ! \is_tax( $taxonomy_slugs )
				|| \is_singular()
			) {
				return;
			}

			// Display all posts in this category
			$query->set( 'posts_per_page', -1 );

			// Order them by title
			$query->set( 'orderby', [ 'title' => 'ASC' ] );

			// Exclude children from archives for this taxonomy
			$tax_query = $query->tax_query->queries;
			$tax_query[0]['include_children'] = false;
			$query->set( 'tax_query', $tax_query );
		}, 1, 1 );

		// Handle redirects for deeply nested categories without /category/
		\add_action( 'template_redirect', function () {
			global $wp_query;
			$post_type = $wp_query->get( 'post_type' );

			if (
				! (
					$post_type === $this->prefix
					|| $this->isTermQuery()
				)
				|| ! \is_404()
				|| \is_admin()
			) {
				return;
			}

			// Trick to get the full URL
			$url = \array_filter( \explode( '/', \add_query_arg( '', '' ) ) );

			if ( \is_array( $url ) && \count( $url ) ) {
				$test_slug = \array_pop( $url );

				// Handle links to terms without term-slug base
				if ( $post_type === $this->prefix ) {
					$taxq = $wp_query->get( 'taxonomy' );
					$term = $this->prefix . '-cat';

					if ( $taxq === $this->prefix . '-tag' ) {
						$term = $this->prefix . '-tag';
					}

					$test_term = \get_term_by( 'slug', $test_slug, $term );

					if ( $test_term ) {
						return \wp_safe_redirect( \get_term_link( $test_term ), 302 );
					}
				}

				// Handle links to cat slug base
				if ( is_term_query() ) {
					if (
						$test_slug === $this->labels['category']['slug']
						|| $test_slug === $this->labels['tag']['slug']
					) {
						return \wp_safe_redirect( \get_post_type_archive_link( $this->prefix ), 301 );
					}
				}
			}
		} );
	}

	private function registerTag() {
		$this->options['tag']['label']           = $this->labels['tag']['plural'];
		$this->options['tag']['rewrite']['slug'] = $this->labels['cpt']['slug'] . '/' . $this->labels['tag']['slug'];

		\add_action( 'init', function () {
			$this->tag = \register_taxonomy(
				$this->prefix . '-tag',
				$this->prefix,
				$this->options['tag']
			);
		} );
	}

	/**
	 * Replacement for Extended CPTs post link handler to deal with hierarchical taxonomies
	 */
	public function handleHierarchicalPostLinks( $post_link, $post, $leavename, $sample ) {
		if ( $post->post_type !== $this->prefix ) {
			return $post_link;
		}

		$date         = \explode( ' ', \mysql2date( 'Y m d H i s', $post->post_date ) );
		$replacements = ['%year%' => $date[0], '%monthnum%' => $date[1], '%day%' => $date[2], '%hour%' => $date[3], '%minute%' => $date[4], '%second%' => $date[5], '%post_id%' => $post->ID];

		if ( false !== \mb_strpos( $post_link, '%author%' ) ) {
			$replacements['%author%'] = \get_userdata( (int) $post->post_author )->user_nicename;
		}

		foreach ( \get_object_taxonomies( $post ) as $tax ) {
			if ( false === \mb_strpos( $post_link, "%{$tax}%" ) ) {
				continue;
			}
			$terms = \get_the_terms( $post, $tax );

			if ( $terms ) {
				$current_term    = \reset( $terms );
				$taxonomy_object = \get_taxonomy( $tax );

				// Hierarchical rewrite
				if ( isset( $taxonomy_object->rewrite['hierarchical'] ) && $taxonomy_object->rewrite['hierarchical'] ) {
					// https://github.com/WordPress/WordPress/blob/4.9.5/wp-includes/taxonomy.php#L3957-L3965
					$hierarchical_slugs = [];
					$ancestors          = \get_ancestors( $current_term->term_id, $tax, 'taxonomy' );

					foreach ( (array) $ancestors as $ancestor ) {
						$ancestor_term        = \get_term( $ancestor, $tax );
						$hierarchical_slugs[] = $ancestor_term->slug;
					}
					$hierarchical_slugs   = \array_reverse( $hierarchical_slugs );
					$hierarchical_slugs[] = $current_term->slug;
					$term                 = \implode( '/', $hierarchical_slugs );
				} else {
					/**
					 * Filter the term that gets used in the `$tax` permalink token.
					 *
					 * @TODO make this more betterer ^
					 *
					 * @param WP_Term   $term  the `$tax` term to use in the permalink
					 * @param WP_Term[] $terms array of all `$tax` terms associated with the post
					 * @param WP_Post   $post  the post in question
					 */
					$term_object = \apply_filters( "post_link_{$tax}", \reset( $terms ), $terms, $post );
					$term        = \get_term( $term_object, $tax )->slug;
				}
			} else {
				$term = $post->post_type;
				/**
				 * Filter the default term name that gets used in the `$tax` permalink token.
				 *
				 * @TODO make this more betterer ^
				 *
				 * @param string  $term the `$tax` term name to use in the permalink
				 * @param WP_Post $post the post in question
				 */
				$default_term_name = \apply_filters( "default_{$tax}", \get_option( "default_{$tax}", '' ), $post );

				if ( $default_term_name ) {
					$default_term = \get_term( $default_term_name, $tax );

					if ( ! \is_wp_error( $default_term ) ) {
						$term = $default_term->slug;
					}
				}
			}
			$replacements["%{$tax}%"] = $term;
		}
		$post_link = \str_replace( \array_keys( $replacements ), $replacements, $post_link );

		return $post_link;
	}

	/**
	 * Determine if the current query is for our cpt/tax archive
	 */
	public function isOurQuery() {
		$q = \get_queried_object();

		if (
			(
				\is_object( $q )
				&& \property_exists( $q, 'taxonomy' )
				&& \in_array( $q->taxonomy, [$this->prefix . '-tag', $this->prefix . '-cat'] )
			)
			|| \is_post_type_archive( $this->prefix )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Determine if is a query for one of our terms
	 */
	public function isTermQuery() {
		global $wp_query;
		$tax = $wp_query->get( 'taxonomy' );

		return $tax === $this->prefix . '-cat' || $tax === $this->prefix . '-tag';
	}

	/**
	 * Accepts two variables and a short circuit for search queries.
	 */
	public function returnIfOurs( $ours = true, $else = false, $search = null ) {
		if ( ! \is_null( $search ) && \is_search() ) {
			return $else;
		}

		return $this->isOurQuery() ? $ours : $else;
	}

	/**
	 * Adds sortable admin columns for hierarchical terms
	 */
	public function outputAdminColsForHierarchicalTerms() {
		global $post;
		$tax   = $this->prefix . '-cat';
		$terms = \get_the_terms( $post, $tax );
		$taxes = [];

		if ( $terms ) {
			$current_term    = \reset( $terms );
			$taxonomy_object = \get_taxonomy( $tax );

			if ( isset( $taxonomy_object->rewrite['hierarchical'] ) && $taxonomy_object->rewrite['hierarchical'] ) {
				// https://github.com/WordPress/WordPress/blob/4.9.5/wp-includes/taxonomy.php#L3957-L3965
				$hierarchical = [];
				$ancestors    = \get_ancestors( $current_term->term_id, $tax, 'taxonomy' );

				foreach ( (array) $ancestors as $ancestor ) {
					$ancestor_term  = \get_term( $ancestor, $tax );
					$hierarchical[] = $ancestor_term;
				}
				$hierarchical   = \array_reverse( $hierarchical );
				$hierarchical[] = $current_term;
				$taxes          = $hierarchical;
			} else {
				$term_object = \apply_filters( "post_link_{$tax}", \reset( $terms ), $terms, $post );
				$taxes       = [\get_term( $term_object, $tax )];
			}
		} else {
			$default_term_name = \apply_filters( "default_{$tax}", \get_option( "default_{$tax}", '' ), $post );

			if ( $default_term_name ) {
				$default_term = \get_term( $default_term_name, $tax );

				if ( ! \is_wp_error( $default_term ) ) {
					$taxes = [$default_term];
				}
			}
		}
		$out = [];

		foreach ( $taxes as $tax ) {
			$out[] = \sprintf(
				'<a href="%s">%s</a>',
				\esc_url( \add_query_arg(
					[
						'post_type' => $this->prefix,
						'taxonomy'  => $this->prefix . '-cat',
						'term'      => $tax->slug,
					],
					'edit.php'
				) ),
				\esc_html(
					\sanitize_term_field(
						'name',
						$tax->name,
						$tax->term_id,
						$this->prefix . '-cat',
						'display'
					)
				)
			);
		}
		$top = \array_pop( $out );

		if ( \count( $out ) ) {
			echo '<small>' . \join( ' / ', $out ) . '</small><br>';
		}
		echo $top;
	}
}

CPTs::add( 'program', new CPT(
	'program',
	[],
	[
		'cpt' => [
			'singular' => 'Program',
			'plural'   => 'Programming',
			'slug'     => 'programs',
		],
		'category' => [
			'singular' => 'Program Category',
			'plural'   => 'Program Categories',
			'slug'     => 'category',
		],
		'tag' => [
			'singular' => 'Program Tag',
			'plural'   => 'Program Tags',
			'slug'     => 'tag',
		],
	]
) );

CPTs::add( 'format', new CPT(
	'format',
	[
		'cpt' => [ 'menu_icon' => 'dashicons-megaphone' ],
	],
	[
		'cpt' => [
			'singular' => '24/7 Format',
			'plural'   => '24/7 Formats',
			'slug'     => 'formats',
		],
		'category' => [
			'singular' => 'Format Category',
			'plural'   => 'Format Categories',
			'slug'     => 'category',
		],
		'tag' => [
			'singular' => 'Format Tag',
			'plural'   => 'Format Tags',
			'slug'     => 'tag',
		],
	]
) );
