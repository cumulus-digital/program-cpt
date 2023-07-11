<?php

namespace CUMULUS\Wordpress\ProgramCPT;

// Exit if accessed directly.
\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

// Static container for CPTs
class CPTs {
	public static $store = array();

	public static function add( $key, $cpt ) {
		self::$store[$key] = $cpt;
	}

	public static function get( $key ) {
		return self::$store[$key];
	}

	public static function getKeys() {
		return \array_keys( self::$store );
	}

	public static function isOurQuery() {
		$CPTs = self::getKeys();
		$ours = false;

		foreach ( $CPTs as $slug ) {
			$cpt = CPTs::get( $slug );

			if ( $cpt->isOurQuery() ) {
				$ours = true;

				break;
			}
		}

		return $ours;
	}
}

// Generate CPTs, their category, and tags
class CPT {
	private $prefix = '';

	private $options = array(
		'cpt' => array(
			'public'             => true,
			'hierarchical'       => false,
			'show_in_feed'       => true,
			'show_in_rest'       => true,
			'dashboard_activity' => true,
			'dashboard_glance'   => true,
			'supports'           => array(
				'title', 'editor', 'revisions', 'excerpt', 'thumbnail', 'page-attributes',
			),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'menu_icon'       => 'dashicons-microphone',
			'template'        => array(
				array(
					'core/columns',
					array(
						'templateLock' => 'all',
						'className'    => 'program-content',
						'alignWide'    => false,
						'columns'      => 2,
					),
					array(
						array(
							'core/column',
							array(
								'templateLock'      => false,
								'className'         => 'sidebar',
								'verticalAlignment' => 'top',
								'align'             => 'right',
								'width'             => '350px',
								'spacing'           => false,
							),
							array(),
						),
						array(
							'core/column',
							array(
								'templateLock'      => false,
								'className'         => 'main-content',
								'verticalAlignment' => 'top',
								'spacing'           => false,
							),
							array(
								array(
									'core/paragraph',
									array(
										'placeholder' => 'Begin main content here.',
									),
								),
							),
						),
					),
				),
			),
		),
		'category' => array(
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_rest'       => true,
			'hierarchical'       => true,
			'query_var'          => true,
			'label'              => null,
			'rewrite'            => array(
				'slug'         => null,
				'hierarchical' => true,
				'with_front'   => false,
			),
		),
		'tag' => array(
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_rest'       => true,
			'hierarchical'       => false,
			'query_var'          => true,
			'label'              => null,
			'rewrite'            => array(
				'slug'       => null,
				'with_front' => false,
			),
		),
	);

	private $labels = array(
		'cpt'      => array(),
		'category' => array(),
		'tag'      => array(),
	);

	private $capabilities;

	private $cpt;

	private $category;

	private $tag;

	public function __construct( $prefix, $options, $labels, $capabilities = null ) {
		if ( ! $prefix ) {
			throw new \Exception( 'Must provide a prefix for CPT' );
		}
		$this->prefix = $prefix;

		if ( ! $labels ) {
			throw new \Exception( 'Must provide labels for CPT' );
		}
		$this->labels = $labels;

		$this->capabilities = $capabilities;

		if ( $options && \is_array( $options ) ) {
			$this->options = \array_replace_recursive( $this->options, $options );
		}

		// Set up rewrites
		$this->options['cpt']['rewrite'] = array(
			'permastruct'  => '/' . $labels['cpt']['slug'] . '/%' . $this->prefix . '-cat%/%postname%',
			'hierarchical' => true,
		);

		// Set up admin post list cols
		$this->options['cpt']['admin_cols'] = array(
			$labels['category']['plural'] => array(
				'taxonomy' => $this->prefix . '-cat',
				'function' => array( $this, 'outputAdminColsForHierarchicalTerms' ),
			),
			'date',
		);

		// Dropdown filters for admin post list
		$this->options['cpt']['admin_filters'] = array(
			$labels['category']['plural'] => array(
				'taxonomy' => $this->prefix . '-cat',
			),
			$labels['tag']['plural'] => array(
				'taxonomy' => $this->prefix . '-tag',
			),
		);

		if ( $this->options['category'] ) {
			$this->registerCategory();
		}

		if ( $this->options['tag'] ) {
			$this->registerTag();
		}

		$this->registerPostType();
		$this->mapCapabilities();
	}

	/**
	 * Replacement for Extended CPTs post link handler to deal with hierarchical taxonomies.
	 *
	 * @param mixed $post_link
	 * @param mixed $post
	 * @param mixed $leavename
	 * @param mixed $sample
	 */
	public function handleHierarchicalPostLinks( $post_link, $post, $leavename, $sample ) {
		if ( $post->post_type !== $this->prefix ) {
			return $post_link;
		}

		$date         = \explode( ' ', \mysql2date( 'Y m d H i s', $post->post_date ) );
		$replacements = array(
			'%year%'     => $date[0],
			'%monthnum%' => $date[1],
			'%day%'      => $date[2],
			'%hour%'     => $date[3],
			'%minute%'   => $date[4],
			'%second%'   => $date[5],
			'%post_id%'  => $post->ID,
		);

		if ( false !== \mb_strpos( $post_link, '%author%' ) ) {
			$replacements['%author%'] = \get_userdata( (int) $post->post_author )->user_nicename;
		}

		foreach ( \get_object_taxonomies( $post ) as $tax ) {
			if ( false === \mb_strpos( $post_link, "%{$tax}%" ) ) {
				continue;
			}
			$terms = \get_the_terms( $post, $tax );
			$key   = "%{$tax}%";

			if ( $terms ) {
				$current_term    = \reset( $terms );
				$taxonomy_object = \get_taxonomy( $tax );

				// Hierarchical rewrite
				if ( isset( $taxonomy_object->rewrite['hierarchical'] ) && $taxonomy_object->rewrite['hierarchical'] ) {
					// https://github.com/WordPress/WordPress/blob/4.9.5/wp-includes/taxonomy.php#L3957-L3965
					$hierarchical_slugs = array();
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
				// If there's no category, we first check if there is a default category for
				// this CPT and use that, otherwise we eliminate the category base from the URL.
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
				} else {
					$key  = "{$key}/";
					$term = '';
				}
			}
			$replacements[$key] = $term;
		}

		return \str_replace(
			\array_keys( $replacements ),
			$replacements,
			$post_link
		);
	}

	/**
	 * Determine if the current query is for our cpt/tax archive.
	 */
	public function isOurQuery() {
		$q = \get_queried_object();

		if (
			(
				\is_object( $q )
				&& \property_exists( $q, 'taxonomy' )
				&& \in_array( $q->taxonomy, array( $this->prefix . '-tag', $this->prefix . '-cat' ) )
			)
			|| \is_post_type_archive( $this->prefix )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Determine if is a query for one of our terms.
	 */
	public function isTermQuery() {
		global $wp_query;
		$tax = $wp_query->get( 'taxonomy' );

		return $tax === $this->prefix . '-cat' || $tax === $this->prefix . '-tag';
	}

	/**
	 * Accepts two variables and a short circuit for search queries.
	 *
	 * @param mixed      $ours
	 * @param mixed      $else
	 * @param mixed|null $search
	 */
	public function returnIfOurs( $ours = true, $else = false, $search = null ) {
		if ( ! \is_null( $search ) && \is_search() ) {
			return $else;
		}

		return $this->isOurQuery() ? $ours : $else;
	}

	/**
	 * Adds sortable admin columns for hierarchical terms.
	 */
	public function outputAdminColsForHierarchicalTerms() {
		global $post;
		$tax   = $this->prefix . '-cat';
		$terms = \get_the_terms( $post, $tax );
		$taxes = array();

		if ( $terms ) {
			$current_term    = \reset( $terms );
			$taxonomy_object = \get_taxonomy( $tax );

			if ( isset( $taxonomy_object->rewrite['hierarchical'] ) && $taxonomy_object->rewrite['hierarchical'] ) {
				// https://github.com/WordPress/WordPress/blob/4.9.5/wp-includes/taxonomy.php#L3957-L3965
				$hierarchical = array();
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
				$taxes       = array( \get_term( $term_object, $tax ) );
			}
		} else {
			$default_term_name = \apply_filters( "default_{$tax}", \get_option( "default_{$tax}", '' ), $post );

			if ( $default_term_name ) {
				$default_term = \get_term( $default_term_name, $tax );

				if ( ! \is_wp_error( $default_term ) ) {
					$taxes = array( $default_term );
				}
			}
		}
		$out = array();

		foreach ( $taxes as $tax ) {
			$out[] = \sprintf(
				'<a href="%s">%s</a>',
				\esc_url( \add_query_arg(
					array(
						'post_type' => $this->prefix,
						'taxonomy'  => $this->prefix . '-cat',
						'term'      => $tax->slug,
					),
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
			\remove_filter( 'post_type_link', array( $this->cpt, 'post_type_link' ), 1 );
			\add_filter(
				'post_type_link',
				array( $this, 'handleHierarchicalPostLinks' ),
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
				|| $query->is_post_type_archive()
				|| $query->is_archive()
			) {
				return $query;
			}

			$current = (array) $query->get( 'post_type' );

			if ( ! \in_array( $this->prefix, $current ) ) {
				$current[] = $this->prefix;
				$query->set( 'post_type', $current );
			}

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

	private function mapCapabilities() {
		if ( ! $this->capabilities ) {
			return;
		}

		foreach ( $this->capabilities as $role_name => $caps ) {
			$role = \get_role( $role_name );

			foreach ( $caps as $cap => $value ) {
				$role->add_cap( $cap, $value );
			}
		}
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
			$taxonomy_slugs = array( $this->prefix . '-cat' );

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
			$query->set( 'orderby', array( 'title' => 'ASC' ) );

			// Exclude children from archives for this taxonomy,
			// but preserve them in search.
			if ( \is_archive() && ! \is_search() ) {
				$tax_query = $query->tax_query->queries;

				if ( \count( $tax_query ) ) {
					$tax_query[0]['include_children'] = false;
					$query->set( 'tax_query', $tax_query );
				}
			}
		}, 1, 1 );

		// Handle template include for posts without a category slug
		\add_action( 'template_include', function ( $template ) {
			global $wp_query;
			$post_type = $wp_query->get( 'post_type' );

			if ( \is_admin() || ! \is_404() ) {
				return $template;
			}

			if ( $post_type === '' && $this->isTermQuery() ) {
				$slugq = $wp_query->get( 'term' );
				if ( \mb_strlen( $slugq ) ) {
					$status = array(
						'publish',
					);

					foreach ( (array) $this->options['cpt']['capability_type'] as $captype ) {
						if ( \current_user_can( "publish_{$captype}" ) ) {
							$status[] = 'draft';
							$status[] = 'pending';
							$status[] = 'future';
						}
						if ( \current_user_can( "read_private_{$captype}" ) ) {
							$status[] = 'private';
						}
					}

					$test_post = new \WP_Query( array(
						'name'        => $slugq,
						'post_type'   => $this->prefix,
						'post_status' => $status,
					) );

					if ( $test_post ) {
						$new_template = \CMLS_Base\cmls_locate_template( array(
							"single-{$this->prefix}.php",
							'single.php',
							'singular.php',
							'index.php',
						) );
						if ( $new_template ) {
							// Post and template are good, let's rewrite the query and header
							\status_header( 200 );
							\wp_reset_postdata();
							$wp_query = $test_post;
							$template = $new_template;
						}
					}
				}
			}

			return $template;
		} );

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
				if ( $this->isTermQuery() ) {
					if (
						$test_slug    === $this->labels['category']['slug']
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
}

CPTs::add( 'program', new CPT(
	'program',
	array(
		'cpt' => array(
			'menu_icon'       => 'dashicons-microphone',
			'capability_type' => array(
				'program',
				'programs',
			),
		),
	),
	array(
		'cpt' => array(
			'singular' => 'Program',
			'plural'   => 'Programming',
			'slug'     => 'programs',
		),
		'category' => array(
			'singular' => 'Program Category',
			'plural'   => 'Program Categories',
			'slug'     => 'category',
		),
		'tag' => array(
			'singular' => 'Program Tag',
			'plural'   => 'Program Tags',
			'slug'     => 'tag',
		),
	),
	array(
		'administrator' => array(
			'edit_programs'         => true,
			'edit_others_programs'  => true,
			'delete_programs'       => true,
			'publish_programs'      => true,
			'read_private_programs' => true,
		),
		'editor' => array(
			'edit_programs'         => true,
			'edit_others_programs'  => true,
			'delete_programs'       => true,
			'publish_programs'      => true,
			'read_private_programs' => true,
		),
	)
) );

CPTs::add( 'format', new CPT(
	'format',
	array(
		'cpt' => array(
			'menu_icon'       => 'dashicons-megaphone',
			'capability_type' => array(
				'format',
				'formats',
			),
		),
	),
	array(
		'cpt' => array(
			'singular' => '24/7 Format',
			'plural'   => '24/7 Formats',
			'slug'     => 'formats',
		),
		'category' => array(
			'singular' => 'Format Category',
			'plural'   => 'Format Categories',
			'slug'     => 'category',
		),
		'tag' => array(
			'singular' => 'Format Tag',
			'plural'   => 'Format Tags',
			'slug'     => 'tag',
		),
	),
	array(
		'administrator' => array(
			'edit_formats'         => true,
			'edit_others_formats'  => true,
			'delete_formats'       => true,
			'publish_formats'      => true,
			'read_private_formats' => true,
		),
		'editor' => array(
			'edit_formats'         => true,
			'edit_others_formats'  => true,
			'delete_formats'       => true,
			'publish_formats'      => true,
			'read_private_formats' => true,
		),
	)
) );
