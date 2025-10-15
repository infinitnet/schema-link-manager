<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.ShortPrefixPassed -- Short prefix used in legacy code for backward compatibility.
/**
 * Schema Link Manager Admin Page
 *
 * Handles the admin interface for managing schema links including the admin page,
 * AJAX operations, and search/filter functionality.
 *
 * @package Schema_Link_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema Link Manager Admin Class.
 *
 * Provides admin functionality including a management page for viewing and editing
 * schema links across all posts, with advanced search and filtering capabilities.
 *
 * @package Schema_Link_Manager
 * @since 1.0.0
 */
class Schema_Link_Manager_Admin {

	/**
	 * Search term for permalink filter.
	 *
	 * Used internally to store search term when filtering by URL/permalink.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $search_permalink_term = '';

	/**
	 * General search term for filters.
	 *
	 * Used internally to store search term when filtering by title or all columns.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $search_term = '';

	/**
	 * Constructor - Initialize admin functionality.
	 *
	 * Sets up admin menu, assets, and AJAX handlers.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Add admin menu page.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Register admin scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Register AJAX handlers for link management.
		add_action( 'wp_ajax_schema_link_manager_update', array( $this, 'ajax_update_links' ) );
		add_action( 'wp_ajax_schema_link_manager_remove_all', array( $this, 'ajax_remove_all_links' ) );
	}

	/**
	 * Add admin menu page.
	 *
	 * Creates a top-level admin menu page for managing schema links.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		/**
		 * Filters the capability required to access the admin page.
		 *
		 * @since 1.1.8
		 * @hook schema_link_manager_admin_capability
		 *
		 * @param {string} $capability The capability required. Default 'manage_options'.
		 *
		 * @return {string} Filtered capability.
		 */
		$capability = apply_filters( 'schema_link_manager_admin_capability', 'manage_options' );

		add_menu_page(
			__( 'Schema Link Manager', 'schema-link-manager' ),
			__( 'Schema Links', 'schema-link-manager' ),
			$capability,
			'schema-link-manager',
			array( $this, 'render_admin_page' ),
			'dashicons-admin-links',
			30
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * Loads CSS and JavaScript files needed for the admin page,
	 * including Select2 for enhanced dropdowns.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our admin page.
		if ( 'toplevel_page_schema-link-manager' !== $hook ) {
			return;
		}

		// Enqueue WordPress dashicons.
		wp_enqueue_style( 'dashicons' );

		// Enqueue Select2 CSS and JS from local plugin files.
		wp_enqueue_style(
			'select2',
			SCHEMA_LINK_MANAGER_PLUGIN_URL . 'css/select2.min.css',
			array(),
			'4.1.0-rc.0'
		);

		wp_enqueue_script(
			'select2',
			SCHEMA_LINK_MANAGER_PLUGIN_URL . 'js/select2.min.js',
			array( 'jquery' ),
			'4.1.0-rc.0',
			true
		);

		// Enqueue admin CSS.
		wp_enqueue_style(
			'schema-link-manager-admin',
			SCHEMA_LINK_MANAGER_PLUGIN_URL . 'css/schema-link-manager-admin.css',
			array( 'select2' ),
			SCHEMA_LINK_MANAGER_VERSION
		);

		// Enqueue admin JavaScript.
		wp_enqueue_script(
			'schema-link-manager-admin',
			SCHEMA_LINK_MANAGER_PLUGIN_URL . 'js/schema-link-manager-admin.js',
			array( 'jquery', 'select2' ),
			SCHEMA_LINK_MANAGER_VERSION,
			true
		);

		// Localize script with AJAX URL, nonce, and translated strings.
		wp_localize_script(
			'schema-link-manager-admin',
			'schemaLinkManager',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'schema_link_manager_nonce' ),
				'strings' => array(
					'confirmRemoveAll'   => __( 'Are you sure you want to remove all schema links from this post?', 'schema-link-manager' ),
					'linkAdded'          => __( 'Link added successfully!', 'schema-link-manager' ),
					'linkRemoved'        => __( 'Link removed successfully!', 'schema-link-manager' ),
					'allLinksRemoved'    => __( 'All links removed successfully!', 'schema-link-manager' ),
					'error'              => __( 'An error occurred. Please try again.', 'schema-link-manager' ),
					'pleaseEnterUrl'     => __( 'Please enter a valid URL', 'schema-link-manager' ),
					'urlMustStartHttp'   => __( 'URL must start with http:// or https://', 'schema-link-manager' ),
					'pleaseEnterOneUrl'  => __( 'Please enter at least one URL', 'schema-link-manager' ),
					'noValidUrls'        => __( 'No valid URLs found. URLs must start with http:// or https://', 'schema-link-manager' ),
					'someUrlsInvalid'    => __( 'Some URLs were invalid and will be skipped.', 'schema-link-manager' ),
					'adding'             => __( 'Adding...', 'schema-link-manager' ),
					'add'                => __( 'Add', 'schema-link-manager' ),
					'addAll'             => __( 'Add All', 'schema-link-manager' ),
					/* translators: %s: number of links added */
					'linksAddedSuccess'  => __( 'Added %s links successfully!', 'schema-link-manager' ),
					'noNewLinksAdded'    => __( 'No new links were added. They may already exist.', 'schema-link-manager' ),
					'noSignificantLinks' => __( 'No significant links', 'schema-link-manager' ),
					'noRelatedLinks'     => __( 'No related links', 'schema-link-manager' ),
					'yes'                => __( 'Yes', 'schema-link-manager' ),
					'no'                 => __( 'No', 'schema-link-manager' ),
				),
			)
		);
	}

	/**
	 * Render admin page.
	 *
	 * Displays the main admin interface with filters, post list, and link management.
	 *
	 * @since 1.0.0
	 */
	public function render_admin_page() {
		// Verify nonce if form was submitted.
		if ( isset( $_REQUEST['_wpnonce'] ) && ! empty( $_REQUEST['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );
			if ( ! wp_verify_nonce( $nonce, 'schema_link_manager_filter_nonce' ) ) {
				wp_die( esc_html__( 'Security check failed', 'schema-link-manager' ) );
			}
		}

		// Get and sanitize URL parameters.
		$current_page   = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$posts_per_page = isset( $_GET['per_page'] ) ? max( 10, min( 100, intval( $_GET['per_page'] ) ) ) : 20;
		$search_term    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$search_column  = isset( $_GET['search_column'] ) ? sanitize_key( $_GET['search_column'] ) : 'all';
		$post_type      = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : 'all';
		$category       = isset( $_GET['category'] ) ? sanitize_key( $_GET['category'] ) : 'all';
		$orderby        = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'title';
		$order          = isset( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'asc';

		// Get available post types for filter.
		$post_types = $this->get_available_post_types();

		// Get available categories for filter.
		$categories = get_categories( array( 'hide_empty' => true ) );

		// Get posts with schema data and apply filters.
		$posts_data = $this->get_posts_with_schema_data( $current_page, $posts_per_page, $search_term, $search_column, $post_type, $category, $orderby, $order );

		// Calculate pagination values.
		$total_posts = $posts_data['total'];
		$total_pages = ceil( $total_posts / $posts_per_page );

		// Include the admin page template.
		include SCHEMA_LINK_MANAGER_PLUGIN_DIR . 'templates/admin-page.php';
	}

	/**
	 * Get available post types.
	 *
	 * Retrieves all public post types for use in the filter dropdown.
	 *
	 * @since 1.0.0
	 *
	 * @return array Associative array of post type slug => label.
	 */
	private function get_available_post_types() {
		$post_types      = get_post_types( array( 'public' => true ), 'objects' );
		$available_types = array();

		foreach ( $post_types as $type ) {
			$available_types[ $type->name ] = $type->labels->singular_name;
		}

		return $available_types;
	}

	/**
	 * Get posts with schema data.
	 *
	 * @param int    $page Current page.
	 * @param int    $per_page Posts per page.
	 * @param string $search Search term.
	 * @param string $search_column Column to search in.
	 * @param string $post_type Post type to filter.
	 * @param string $category Category to filter.
	 * @param string $orderby Order by column.
	 * @param string $order Order direction.
	 * @return array Posts data with pagination info.
	 */
	private function get_posts_with_schema_data( $page = 1, $per_page = 20, $search = '', $search_column = 'all', $post_type = 'all', $category = '', $orderby = 'title', $order = 'ASC' ) {
		$args = array(
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'type' === $orderby ? 'post_type' : ( 'url' === $orderby ? 'name' : 'title' ),
			'order'          => in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $order ) : 'ASC',
		);

		// Handle post type filtering - fix for post type filter not working.
		if ( 'all' === $post_type ) {
			$args['post_type'] = get_post_types( array( 'public' => true ) );
		} else {
			$args['post_type'] = $post_type;
		}

		// Add category filter if selected.
		if ( ! empty( $category ) && 'all' !== $category ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Intentional for filtering.
				array(
					'taxonomy' => 'category',
					'field'    => 'slug',
					'terms'    => $category,
				),
			);
		}

		// Add search parameters.
		if ( ! empty( $search ) ) {
			if ( 'schema_links' === $search_column ) {
				// For schema links, search in meta fields only.
				$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Intentional for filtering.
					'relation' => 'OR',
					array(
						'key'     => 'schema_significant_links',
						'value'   => $search,
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'schema_related_links',
						'value'   => $search,
						'compare' => 'LIKE',
					),
				);

				// Disable standard search.
				$args['s'] = '';
			} elseif ( 'title' === $search_column ) {
				// For title searches, use a specialized title filter.
				add_filter( 'posts_where', array( $this, 'filter_search_by_title' ) );
				$this->search_term = $search;

				// Disable standard search to prevent it from interfering.
				$args['s'] = '';
			} elseif ( 'url' === $search_column ) {
				// For URL searches, use the permalink filter.
				add_filter( 'posts_where', array( $this, 'filter_posts_by_permalink' ) );
				$this->search_permalink_term = $search;

				// Disable standard search.
				$args['s'] = '';
			} elseif ( 'all' === $search_column ) {
				// For "all columns" search, we need to combine multiple approaches.

				// 1. Add hook to search in title and content.
				add_filter( 'posts_where', array( $this, 'filter_search_all_content' ) );
				$this->search_term = $search;

				// 2. Add meta query for schema links.
				$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Intentional for filtering.
					'relation' => 'OR',
					array(
						'key'     => 'schema_significant_links',
						'value'   => $search,
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'schema_related_links',
						'value'   => $search,
						'compare' => 'LIKE',
					),
				);

				// 3. We'll add permalink search in the filter_search_all_content method.
				$this->search_permalink_term = $search;

				// Disable standard search since we're handling it manually.
				$args['s'] = '';
			} else {
				// Default case - use standard WP search.
				$args['s'] = $search;
			}
		}

		$query = new WP_Query( $args );

		// Clean up filters based on the search column.
		if ( ! empty( $search ) ) {
			// Remove any filters we added based on search type.
			if ( 'title' === $search_column ) {
				remove_filter( 'posts_where', array( $this, 'filter_search_by_title' ) );
			} elseif ( 'url' === $search_column ) {
				remove_filter( 'posts_where', array( $this, 'filter_posts_by_permalink' ) );
			} elseif ( 'all' === $search_column ) {
				remove_filter( 'posts_where', array( $this, 'filter_search_all_content' ) );
			}
		}

		$posts = array();

		foreach ( $query->posts as $post ) {
			$significant_links = get_post_meta( $post->ID, 'schema_significant_links', true );
			$related_links     = get_post_meta( $post->ID, 'schema_related_links', true );

			$posts[] = array(
				'id'                => $post->ID,
				'title'             => $post->post_title,
				'url'               => get_permalink( $post->ID ),
				'post_type'         => get_post_type_object( $post->post_type )->labels->singular_name,
				'significant_links' => ! empty( $significant_links ) ? explode( "\n", $significant_links ) : array(),
				'related_links'     => ! empty( $related_links ) ? explode( "\n", $related_links ) : array(),
			);
		}

		return array(
			'posts' => $posts,
			'total' => $query->found_posts,
			'pages' => ceil( $query->found_posts / $per_page ),
		);
	}

	/**
	 * AJAX handler for updating links.
	 */
	public function ajax_update_links() {
		// Check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'schema_link_manager_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'schema-link-manager' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied', 'schema-link-manager' ) );
		}

		// Get parameters.
		$post_id   = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$link_type = isset( $_POST['link_type'] ) ? sanitize_key( $_POST['link_type'] ) : '';
		$action    = isset( $_POST['action_type'] ) ? sanitize_key( $_POST['action_type'] ) : '';
		$link      = isset( $_POST['link'] ) ? esc_url_raw( wp_unslash( $_POST['link'] ) ) : '';

		if ( ! $post_id || ! in_array( $link_type, array( 'significant', 'related' ), true ) || ! in_array( $action, array( 'add', 'remove' ), true ) ) {
			wp_send_json_error( __( 'Invalid parameters', 'schema-link-manager' ) );
		}

		$meta_key      = 'significant' === $link_type ? 'schema_significant_links' : 'schema_related_links';
		$current_links = get_post_meta( $post_id, $meta_key, true );
		$links_array   = ! empty( $current_links ) ? explode( "\n", $current_links ) : array();

		if ( 'add' === $action && ! empty( $link ) ) {
			/**
			 * Fires before adding a link via AJAX.
			 *
			 * @since 1.1.8
			 * @hook schema_link_manager_before_add_link
			 *
			 * @param {int}    $post_id   The post ID.
			 * @param {string} $link      The link URL.
			 * @param {string} $link_type The link type (significant or related).
			 */
			do_action( 'schema_link_manager_before_add_link', $post_id, $link, $link_type );

			// Add link if it doesn't exist.
			if ( ! in_array( $link, $links_array, true ) ) {
				$links_array[] = $link;
				update_post_meta( $post_id, $meta_key, implode( "\n", $links_array ) );

				/**
				 * Fires after successfully adding a link via AJAX.
				 *
				 * @since 1.1.8
				 * @hook schema_link_manager_link_added
				 *
				 * @param {int}    $post_id   The post ID.
				 * @param {string} $link      The link URL.
				 * @param {string} $link_type The link type (significant or related).
				 */
				do_action( 'schema_link_manager_link_added', $post_id, $link, $link_type );

				wp_send_json_success(
					array(
						'message' => __( 'Link added successfully!', 'schema-link-manager' ),
						'links'   => $links_array,
					)
				);
			} else {
				wp_send_json_error( __( 'Link already exists!', 'schema-link-manager' ) );
			}
		} elseif ( 'remove' === $action && ! empty( $link ) ) {
			/**
			 * Fires before removing a link via AJAX.
			 *
			 * @since 1.1.8
			 * @hook schema_link_manager_before_remove_link
			 *
			 * @param {int}    $post_id   The post ID.
			 * @param {string} $link      The link URL.
			 * @param {string} $link_type The link type (significant or related).
			 */
			do_action( 'schema_link_manager_before_remove_link', $post_id, $link, $link_type );

			// Remove specific link.
			$links_array = array_filter(
				$links_array,
				function ( $item ) use ( $link ) {
					return $item !== $link;
				}
			);
			update_post_meta( $post_id, $meta_key, implode( "\n", $links_array ) );

			/**
			 * Fires after successfully removing a link via AJAX.
			 *
			 * @since 1.1.8
			 * @hook schema_link_manager_link_removed
			 *
			 * @param {int}    $post_id   The post ID.
			 * @param {string} $link      The link URL.
			 * @param {string} $link_type The link type (significant or related).
			 */
			do_action( 'schema_link_manager_link_removed', $post_id, $link, $link_type );

			wp_send_json_success(
				array(
					'message' => __( 'Link removed successfully!', 'schema-link-manager' ),
					'links'   => $links_array,
				)
			);
		}

		wp_send_json_error( __( 'Invalid action', 'schema-link-manager' ) );
	}

	/**
	 * AJAX handler for removing all links.
	 */
	public function ajax_remove_all_links() {
		// Check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'schema_link_manager_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'schema-link-manager' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied', 'schema-link-manager' ) );
		}

		// Get parameters.
		$post_id   = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$link_type = isset( $_POST['link_type'] ) ? sanitize_key( $_POST['link_type'] ) : '';

		if ( ! $post_id || ! in_array( $link_type, array( 'significant', 'related', 'all' ), true ) ) {
			wp_send_json_error( __( 'Invalid parameters', 'schema-link-manager' ) );
		}

		/**
		 * Fires before removing all links via AJAX.
		 *
		 * @since 1.1.8
		 * @hook schema_link_manager_before_remove_all_links
		 *
		 * @param {int}    $post_id   The post ID.
		 * @param {string} $link_type The link type (significant, related, or all).
		 */
		do_action( 'schema_link_manager_before_remove_all_links', $post_id, $link_type );

		if ( 'all' === $link_type ) {
			// Remove both types of links.
			update_post_meta( $post_id, 'schema_significant_links', '' );
			update_post_meta( $post_id, 'schema_related_links', '' );

			/**
			 * Fires after removing all links of all types via AJAX.
			 *
			 * @since 1.1.8
			 * @hook schema_link_manager_all_links_removed
			 *
			 * @param {int} $post_id The post ID.
			 */
			do_action( 'schema_link_manager_all_links_removed', $post_id );

			wp_send_json_success(
				array(
					'message'           => __( 'All links removed successfully!', 'schema-link-manager' ),
					'significant_links' => array(),
					'related_links'     => array(),
				)
			);
		} else {
			// Remove specific type of links.
			$meta_key = 'significant' === $link_type ? 'schema_significant_links' : 'schema_related_links';
			update_post_meta( $post_id, $meta_key, '' );

			/**
			 * Fires after removing all links of a specific type via AJAX.
			 *
			 * @since 1.1.8
			 * @hook schema_link_manager_links_removed
			 *
			 * @param {int}    $post_id   The post ID.
			 * @param {string} $link_type The link type (significant or related).
			 */
			do_action( 'schema_link_manager_links_removed', $post_id, $link_type );

			wp_send_json_success(
				array(
					'message' => __( 'Links removed successfully!', 'schema-link-manager' ),
					'links'   => array(),
				)
			);
		}
	}

	/**
	 * Filter posts by title.
	 * This function is used as a callback for the 'posts_where' filter.
	 *
	 * @param string $where The WHERE clause of the query.
	 * @return string Modified WHERE clause.
	 */
	public function filter_search_by_title( $where ) {
		global $wpdb;

		if ( ! empty( $this->search_term ) ) {
			// Escape the search term for SQL.
			$search_term = '%' . $wpdb->esc_like( $this->search_term ) . '%';

			// Add title search to WHERE clause.
			$where .= $wpdb->prepare(
				" AND ($wpdb->posts.post_title LIKE %s)",
				$search_term
			);
		}

		return $where;
	}

	/**
	 * Filter to search in all content (title, content, excerpt, and permalink).
	 * This function is used as a callback for the 'posts_where' filter.
	 *
	 * @param string $where The WHERE clause of the query.
	 * @return string Modified WHERE clause.
	 */
	public function filter_search_all_content( $where ) {
		global $wpdb;

		if ( ! empty( $this->search_term ) ) {
			// Escape the search term for SQL.
			$search_term = '%' . $wpdb->esc_like( $this->search_term ) . '%';

			// Create a comprehensive search across multiple fields.
			// We need to use OR to connect all search conditions.
			$content_search = $wpdb->prepare(
				" AND ($wpdb->posts.post_title LIKE %s OR $wpdb->posts.post_content LIKE %s OR $wpdb->posts.post_excerpt LIKE %s OR $wpdb->posts.post_name LIKE %s OR $wpdb->posts.guid LIKE %s)",
				$search_term,
				$search_term,
				$search_term,
				$search_term,
				$search_term
			);

			// Extract the post_type constraints from the existing WHERE clause.
			preg_match( "/$wpdb->posts.post_type IN \([^)]+\)/", $where, $matches );
			$post_type_constraint = ! empty( $matches[0] ) ? $matches[0] : '';

			// Add the new search conditions to the WHERE clause.
			// Preserve the post type filtering.
			if ( ! empty( $post_type_constraint ) ) {
				// Add the condition to the WHERE clause, maintaining post type constraint.
				$where .= $content_search;
			} else {
				// No post type constraint, just add the search.
				$where .= $content_search;
			}
		}

		return $where;
	}

	/**
	 * Filter posts by permalink.
	 * This function is used as a callback for the 'posts_where' filter.
	 *
	 * @param string $where The WHERE clause of the query.
	 * @return string Modified WHERE clause.
	 */
	public function filter_posts_by_permalink( $where ) {
		global $wpdb;

		if ( ! empty( $this->search_permalink_term ) ) {
			// Escape the search term for SQL.
			$search_term = '%' . $wpdb->esc_like( $this->search_permalink_term ) . '%';

			// Add permalink search to WHERE clause.
			$where .= $wpdb->prepare(
				" AND ($wpdb->posts.post_name LIKE %s OR $wpdb->posts.guid LIKE %s)",
				$search_term,
				$search_term
			);
		}

		return $where;
	}
}

// Initialize the admin page.
new Schema_Link_Manager_Admin();
