<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Main plugin file is an exception.
/**
 * Schema Link Manager
 *
 * Plugin Name: Schema Link Manager
 * Plugin URI: https://infinitnet.io/schema-link-manager/
 * Description: Adds and manages significant and related links to JSON-LD WebPage schema for improved SEO and semantic structure. Integrates with Rank Math, Yoast SEO, and other schema providers.
 * Version: 1.2.1
 * Author: Infinitnet
 * Author URI: https://infinitnet.io/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: schema-link-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 *
 * @package Schema_Link_Manager
 * @author Infinitnet
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
if ( ! defined( 'SCHEMA_LINK_MANAGER_VERSION' ) ) {
	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 */
	define( 'SCHEMA_LINK_MANAGER_VERSION', '1.2.1' );
}

if ( ! defined( 'SCHEMA_LINK_MANAGER_PLUGIN_FILE' ) ) {
	/**
	 * Plugin main file path.
	 *
	 * @since 1.0.0
	 */
	define( 'SCHEMA_LINK_MANAGER_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'SCHEMA_LINK_MANAGER_PLUGIN_DIR' ) ) {
	/**
	 * Plugin directory path.
	 *
	 * @since 1.0.0
	 */
	define( 'SCHEMA_LINK_MANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SCHEMA_LINK_MANAGER_PLUGIN_URL' ) ) {
	/**
	 * Plugin directory URL.
	 *
	 * @since 1.0.0
	 */
	define( 'SCHEMA_LINK_MANAGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Include admin functionality.
require_once SCHEMA_LINK_MANAGER_PLUGIN_DIR . 'admin/class-schema-link-manager-admin.php';

/**
 * Main Schema Link Manager Class.
 *
 * This class handles the core functionality of the Schema Link Manager plugin,
 * including meta registration, SEO plugin integrations, and schema output.
 *
 * @package Schema_Link_Manager
 * @since 1.0.0
 */
class Schema_Link_Manager {
	/**
	 * Whether the output buffer has started.
	 *
	 * @since 1.2.1
	 * @var bool
	 */
	private $output_buffer_started = false;

	/**
	 * Constructor - Initialize the plugin.
	 *
	 * Sets up all WordPress hooks and initializes plugin functionality.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Load plugin textdomain for translations.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Register custom post meta fields.
		add_action( 'init', array( $this, 'register_meta' ) );

		// Enqueue block editor assets.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );

		// Setup integrations with SEO plugins.
		add_action( 'plugins_loaded', array( $this, 'setup_seo_plugin_integrations' ) );
	}

	/**
	 * Load plugin textdomain for translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'schema-link-manager',
			false,
			dirname( plugin_basename( SCHEMA_LINK_MANAGER_PLUGIN_FILE ) ) . '/languages/'
		);
	}

	/**
	 * Setup integrations with SEO plugins.
	 */
	public function setup_seo_plugin_integrations() {
		$has_seo_plugin = false;

		// Check for Rank Math.
		if ( class_exists( 'RankMath' ) ) {
			add_filter( 'rank_math/json_ld', array( $this, 'add_links_to_schema' ), 99, 2 );
			$has_seo_plugin = true;
		}

		// Check for Yoast SEO.
		if ( defined( 'WPSEO_VERSION' ) ) {
			add_filter( 'wpseo_schema_webpage', array( $this, 'add_links_to_yoast_schema' ), 10, 1 );
			$has_seo_plugin = true;
		}

		// If no supported SEO plugin is active, use the fallback method.
		if ( ! $has_seo_plugin ) {
			// Start an output buffer and inject links into any JSON-LD WebPage schema found.
			add_action( 'template_redirect', array( $this, 'start_output_buffer' ), 0 );

			// Also scan post content for inline JSON-LD (rare but possible).
			add_filter( 'the_content', array( $this, 'process_content_for_schema' ), 99 );
		}
	}

	/**
	 * Register post meta for storing links.
	 */
	public function register_meta() {
		/**
		 * Filters the post types that should have schema link meta registered.
		 *
		 * @since 1.1.8
		 * @hook schema_link_manager_post_types
		 *
		 * @param {array} $post_types Array of post type names.
		 *
		 * @return {array} Filtered array of post type names.
		 */
		$post_types = apply_filters( 'schema_link_manager_post_types', get_post_types( [ 'public' => true ] ) );

		foreach ( $post_types as $post_type ) {
			register_post_meta(
				$post_type,
				'schema_significant_links',
				array(
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
					'auth_callback'     => array( $this, 'meta_auth_callback' ),
				)
			);

			register_post_meta(
				$post_type,
				'schema_related_links',
				array(
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
					'auth_callback'     => array( $this, 'meta_auth_callback' ),
				)
			);
		}
	}

	/**
	 * Authorization callback for post meta
	 *
	 * @param bool   $allowed Whether the user can add the post meta. Default false.
	 * @param string $meta_key The meta key.
	 * @param int    $post_id Post ID.
	 * @param int    $user_id User ID.
	 * @param string $cap Capability name.
	 * @param array  $caps User capabilities.
	 *
	 * @return bool Whether the user can edit the post.
	 */
	public function meta_auth_callback( $allowed, $meta_key, $post_id, $user_id, $cap, $caps ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Signature must accept register_post_meta args.
		unset( $allowed, $meta_key, $user_id, $cap, $caps );
		if ( $post_id && current_user_can( 'edit_post', $post_id ) ) {
			return true;
		}

		return current_user_can( 'edit_posts' );
	}

	/**
	 * Enqueue assets for the block editor.
	 */
	public function enqueue_editor_assets() {
		// Enqueue JS.
		wp_enqueue_script(
			'schema-link-manager',
			SCHEMA_LINK_MANAGER_PLUGIN_URL . 'js/schema-link-manager.js',
			array( 'wp-element', 'wp-components', 'wp-data', 'wp-plugins', 'wp-i18n', 'wp-edit-post', 'wp-editor' ),
			SCHEMA_LINK_MANAGER_VERSION,
			true
		);

		// Enqueue CSS.
		wp_enqueue_style(
			'schema-link-manager',
			SCHEMA_LINK_MANAGER_PLUGIN_URL . 'css/schema-link-manager.css',
			array(),
			SCHEMA_LINK_MANAGER_VERSION
		);

		// Add inline translations.
		wp_set_script_translations( 'schema-link-manager', 'schema-link-manager' );
	}

	/**
	 * Get the current post ID for schema injection.
	 *
	 * Prefer the queried object ID (stable for the request) over get_the_ID() which can
	 * change within secondary loops.
	 *
	 * @since 1.2.1
	 *
	 * @return int Post ID or 0 when unavailable.
	 */
	private function get_current_post_id() {
		$post_id = (int) get_queried_object_id();
		if ( $post_id > 0 ) {
			return $post_id;
		}

		return (int) get_the_ID();
	}

	/**
	 * Process links from post meta.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key to retrieve.
	 * @return array Processed links.
	 */
	private function process_links( $post_id, $meta_key ) {
		$links_text = get_post_meta( $post_id, $meta_key, true );

		if ( empty( $links_text ) ) {
			return [];
		}

		$links = preg_split( '/\r\n|\r|\n/', $links_text );
		$links = array_map( 'trim', $links );

		$processed_links = array_values(
			array_filter(
				array_map(
					function ( $link ) {
						$validated = wp_http_validate_url( $link );
						return $validated ? esc_url_raw( $validated ) : '';
					},
					$links
				)
			)
		);

		/**
		 * Filters the processed links before they are added to schema.
		 *
		 * @since 1.1.8
		 * @hook schema_link_manager_processed_links
		 *
		 * @param {array}  $processed_links Array of processed and sanitized URLs.
		 * @param {int}    $post_id         The post ID.
		 * @param {string} $meta_key        The meta key (schema_significant_links or schema_related_links).
		 *
		 * @return {array} Filtered array of links.
		 */
		return apply_filters( 'schema_link_manager_processed_links', $processed_links, $post_id, $meta_key );
	}

	/**
	 * Add links to schema.
	 *
	 * @param array $data Schema data.
	 * @param mixed $jsonld Unused Rank Math context arg.
	 * @return array Modified schema data.
	 */
	public function add_links_to_schema( $data, $jsonld = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Signature must accept Rank Math args.
		unset( $jsonld );
		$post_id = $this->get_current_post_id();
		if ( ! $post_id ) {
			return $data;
		}

		$significant_links = $this->process_links( $post_id, 'schema_significant_links' );
		$related_links     = $this->process_links( $post_id, 'schema_related_links' );

		if ( empty( $significant_links ) && empty( $related_links ) ) {
			return $data;
		}

		/**
		 * Fires before adding links to Rank Math schema.
		 *
		 * @since 1.1.8
		 * @hook schema_link_manager_before_add_links
		 *
		 * @param {int}   $post_id           The post ID.
		 * @param {array} $significant_links Array of significant links.
		 * @param {array} $related_links     Array of related links.
		 * @param {array} $data              The schema data array.
		 */
		do_action( 'schema_link_manager_before_add_links', $post_id, $significant_links, $related_links, $data );

		// Process each entity in the schema.
		foreach ( $data as $entity_id => $entity ) {
			if ( ! isset( $entity['@type'] ) ) {
				continue;
			}

			$types = is_array( $entity['@type'] ) ? $entity['@type'] : array( $entity['@type'] );

			// If this is a WebPage entity, add links directly.
			if ( in_array( 'WebPage', $types, true ) ) {
				if ( ! empty( $significant_links ) ) {
					$data[ $entity_id ]['significantLink'] = $significant_links;
				}
				if ( ! empty( $related_links ) ) {
					$data[ $entity_id ]['relatedLink'] = $related_links;
				}
			}

			// If this is an Article with a nested WebPage, add links to the WebPage.
			if ( array_intersect( array( 'Article', 'BlogPosting', 'NewsArticle' ), $types ) &&
				isset( $entity['isPartOf'] ) && isset( $entity['isPartOf']['@type'] ) ) {

				$is_part_of_types = is_array( $entity['isPartOf']['@type'] ) ? $entity['isPartOf']['@type'] : array( $entity['isPartOf']['@type'] );

				if ( ! in_array( 'WebPage', $is_part_of_types, true ) ) {
					continue;
				}

				if ( ! empty( $significant_links ) ) {
					$data[ $entity_id ]['isPartOf']['significantLink'] = $significant_links;
				}
				if ( ! empty( $related_links ) ) {
					$data[ $entity_id ]['isPartOf']['relatedLink'] = $related_links;
				}
			}
		}

		/**
		 * Filters the schema data after adding links.
		 *
		 * @since 1.1.8
		 * @hook schema_link_manager_schema_data
		 *
		 * @param {array} $data              The modified schema data.
		 * @param {int}   $post_id           The post ID.
		 * @param {array} $significant_links Array of significant links.
		 * @param {array} $related_links     Array of related links.
		 *
		 * @return {array} Filtered schema data.
		 */
		return apply_filters( 'schema_link_manager_schema_data', $data, $post_id, $significant_links, $related_links );
	}

	/**
	 * Add links to Yoast SEO schema.
	 *
	 * @param array $data WebPage schema data.
	 * @return array Modified WebPage schema data.
	 */
	public function add_links_to_yoast_schema( $data ) {
		$post_id = $this->get_current_post_id();
		if ( ! $post_id ) {
			return $data;
		}

		$significant_links = $this->process_links( $post_id, 'schema_significant_links' );
		$related_links     = $this->process_links( $post_id, 'schema_related_links' );

		/**
		 * Fires before adding links to Yoast schema.
		 *
		 * @since 1.1.8
		 * @hook schema_link_manager_before_add_yoast_links
		 *
		 * @param {int}   $post_id           The post ID.
		 * @param {array} $significant_links Array of significant links.
		 * @param {array} $related_links     Array of related links.
		 * @param {array} $data              The schema data array.
		 */
		do_action( 'schema_link_manager_before_add_yoast_links', $post_id, $significant_links, $related_links, $data );

		if ( ! empty( $significant_links ) ) {
			$data['significantLink'] = $significant_links;
		}

		if ( ! empty( $related_links ) ) {
			$data['relatedLink'] = $related_links;
		}

		/**
		 * Filters the Yoast schema data after adding links.
		 *
		 * @since 1.1.8
		 * @hook schema_link_manager_yoast_schema_data
		 *
		 * @param {array} $data              The modified schema data.
		 * @param {int}   $post_id           The post ID.
		 * @param {array} $significant_links Array of significant links.
		 * @param {array} $related_links     Array of related links.
		 *
		 * @return {array} Filtered schema data.
		 */
		return apply_filters( 'schema_link_manager_yoast_schema_data', $data, $post_id, $significant_links, $related_links );
	}
	/**
	 * Process content for schema.
	 *
	 * @param string $content The post content.
	 * @return string Modified content.
	 */
	public function process_content_for_schema( $content ) {
		return $this->inject_links_into_json_ld( $content );
	}

	/**
	 * Start an output buffer for fallback injection.
	 *
	 * @since 1.2.1
	 */
	public function start_output_buffer() {
		if ( $this->output_buffer_started ) {
			return;
		}

		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || is_feed() || is_embed() ) {
			return;
		}

		$post_id = $this->get_current_post_id();
		if ( ! $post_id ) {
			return;
		}

		$significant_links = $this->process_links( $post_id, 'schema_significant_links' );
		$related_links     = $this->process_links( $post_id, 'schema_related_links' );

		if ( empty( $significant_links ) && empty( $related_links ) ) {
			return;
		}

		$this->output_buffer_started = true;
		ob_start( array( $this, 'inject_links_into_json_ld' ) );
	}

	/**
	 * Back-compat alias for older hook usage.
	 *
	 * @since 1.0.0
	 */
	public function inject_schema_links() {
		$this->start_output_buffer();
	}

	/**
	 * Fallback method to inject links into JSON-LD schema.
	 *
	 * @param string $html The HTML content.
	 * @return string Modified HTML content.
	 */
	public function inject_links_into_json_ld( $html ) {
		if ( empty( $html ) ) {
			return $html;
		}

		$post_id = $this->get_current_post_id();
		if ( ! $post_id ) {
			return $html;
		}

		$significant_links = $this->process_links( $post_id, 'schema_significant_links' );
		$related_links     = $this->process_links( $post_id, 'schema_related_links' );

		if ( empty( $significant_links ) && empty( $related_links ) ) {
			return $html;
		}

		// Use preg_replace_callback to find and modify JSON-LD scripts.
		return preg_replace_callback(
			'/(<script\b[^>]*type=["\']application\/ld\+json["\'][^>]*>)(.*?)<\/script>/is',
			function ( $matches ) use ( $significant_links, $related_links ) {
				$opening_tag = $matches[1];
				$json        = trim( $matches[2] );

				// Try to decode the JSON.
				$data = json_decode( $json, true );
				if ( empty( $data ) || JSON_ERROR_NONE !== json_last_error() ) {
					return $matches[0]; // Return original if not valid JSON.
				}

				$modified = false;

				// Check if this is a WebPage schema.
				if ( isset( $data['@type'] ) ) {
					// Handle both string and array @type values.
					$types = is_array( $data['@type'] ) ? $data['@type'] : [ $data['@type'] ];

					if ( in_array( 'WebPage', $types, true ) ) {
						if ( ! empty( $significant_links ) ) {
							$data['significantLink'] = $significant_links;
						}
						if ( ! empty( $related_links ) ) {
							$data['relatedLink'] = $related_links;
						}
						$modified = true;
					}
				}

				// Check for WebPage within a graph.
				if ( isset( $data['@graph'] ) && is_array( $data['@graph'] ) ) {
					foreach ( $data['@graph'] as $key => $entity ) {
						if ( isset( $entity['@type'] ) ) {
							// Handle both string and array @type values.
							$types = is_array( $entity['@type'] ) ? $entity['@type'] : [ $entity['@type'] ];

							if ( in_array( 'WebPage', $types, true ) ) {
								if ( ! empty( $significant_links ) ) {
									$data['@graph'][ $key ]['significantLink'] = $significant_links;
								}
								if ( ! empty( $related_links ) ) {
									$data['@graph'][ $key ]['relatedLink'] = $related_links;
								}
								$modified = true;
							}
						}
					}
				}

				// Only modify if we actually changed something.
				if ( $modified ) {
					return $opening_tag . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
				}

				return $matches[0]; // Return original if no WebPage found.
			},
			$html
		);
	}
}

/**
 * Activation hook callback.
 *
 * @since 1.1.8
 */
function schema_link_manager_activate() { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed -- Activation/deactivation hooks are acceptable.
	/**
	 * Fires when the plugin is activated.
	 *
	 * @since 1.1.8
	 * @hook schema_link_manager_activated
	 */
	do_action( 'schema_link_manager_activated' );
}
register_activation_hook( __FILE__, 'schema_link_manager_activate' );

/**
 * Deactivation hook callback
 *
 * @since 1.1.8
 */
function schema_link_manager_deactivate() {
	/**
	 * Fires when the plugin is deactivated.
	 *
	 * @since 1.1.8
	 * @hook schema_link_manager_deactivated
	 */
	do_action( 'schema_link_manager_deactivated' );
}
register_deactivation_hook( __FILE__, 'schema_link_manager_deactivate' );

// Initialize the plugin.
new Schema_Link_Manager();
