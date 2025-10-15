=== Schema Link Manager ===
Contributors: infinitnet
Tags: schema, json-ld, seo, structured-data, schema-markup
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add and manage significant and related links to JSON-LD WebPage schema for improved SEO and semantic structure.

== Description ==

Schema Link Manager enhances your website's structured data by allowing you to add Schema.org's **significantLink** and **relatedLink** properties to your WebPage schema. This helps search engines better understand the relationships between your content and other web resources.

= Key Features =

* **SEO Plugin Integration**: Seamlessly works with Rank Math and Yoast SEO
* **Fallback Support**: Automatically injects schema when no SEO plugin is detected
* **Gutenberg Sidebar Panel**: Easy-to-use interface in the block editor
* **Admin Management Page**: Bulk manage links across all posts
* **Advanced Search & Filters**: Search by title, URL, schema links, post type, or category
* **Bulk Operations**: Add or remove multiple links at once
* **Developer Friendly**: Multiple hooks and filters for customization
* **Translation Ready**: Full i18n support

= What are Significant and Related Links? =

According to Schema.org:

* **significantLink**: The most significant URLs on the page. Typically, these are the URLs of the main content.
* **relatedLink**: A link related to this web page, for example to other related web pages.

= SEO Plugin Compatibility =

* ✅ **Rank Math SEO**: Full integration via `rank_math/json_ld` filter
* ✅ **Yoast SEO**: Full integration via `wpseo_schema_webpage` filter  
* ✅ **Other SEO Plugins**: Fallback JSON-LD injection
* ✅ **No SEO Plugin**: Automatic schema injection

= For Developers =

The plugin provides numerous hooks and filters for customization:

**Filters:**
* `schema_link_manager_post_types` - Modify supported post types
* `schema_link_manager_processed_links` - Modify links before adding to schema
* `schema_link_manager_schema_data` - Modify schema data after adding links
* `schema_link_manager_admin_capability` - Modify admin page capability requirement

**Actions:**
* `schema_link_manager_activated` - Plugin activation
* `schema_link_manager_deactivated` - Plugin deactivation
* `schema_link_manager_before_add_links` - Before adding links to schema
* `schema_link_manager_link_added` - After adding a link via AJAX
* `schema_link_manager_link_removed` - After removing a link via AJAX

See full documentation on [GitHub](https://github.com/yourusername/schema-link-manager).

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins → Add New
3. Search for "Schema Link Manager"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Go to Plugins → Add New → Upload Plugin
3. Upload the zip file and click "Install Now"
4. Activate the plugin

= After Activation =

1. Go to any post or page in the block editor
2. Look for the "Schema Links" panel in the sidebar (Document tab)
3. Add URLs to either "Significant Links" or "Related Links" fields
4. Or manage links across all posts via WordPress Admin → Schema Links

== Frequently Asked Questions ==

= Does this plugin work without an SEO plugin? =

Yes! The plugin has a built-in fallback that automatically injects schema links into any existing JSON-LD structured data on your site.

= What's the difference between significant and related links? =

* **Significant links** should point to the main, most important content referenced on your page
* **Related links** should point to additional, supplementary resources related to your page content

= Can I add the same link to both fields? =

Technically yes, but it's not recommended. Each link should serve a distinct purpose to provide the most value to search engines.

= Does this affect my SEO? =

Schema markup helps search engines better understand your content. While it doesn't directly affect rankings, it can improve how your content appears in search results and help with entity recognition.

= Can I use this with custom post types? =

Yes! The plugin works with all public post types by default. You can also use the `schema_link_manager_post_types` filter to customize which post types are supported.

= How do I add links via the Gutenberg editor? =

1. Open any post or page in the block editor
2. Look for the "Schema Links" panel in the sidebar (Document tab)
3. Add URLs (one per line) to either field
4. Publish or update your post

= How do I manage links in bulk? =

1. Go to WordPress Admin → Schema Links
2. Use filters to find specific posts
3. Add, edit, or remove links inline
4. Use bulk operations for efficiency

== Screenshots ==

1. Gutenberg editor sidebar panel for managing schema links
2. Admin page for bulk management with advanced filters
3. Search and filter posts by various criteria
4. Inline link editing with single and bulk operations
5. Schema output in page source showing significantLink and relatedLink

== Changelog ==

= 1.2.0 =
* Improved documentation and inline comments
* Enhanced WordPress coding standards compliance
* Better PHPDoc blocks throughout
* Added comprehensive README and readme.txt
* Improved code organization and commenting

= 1.1.8 =
* Added hooks and filters for extensibility
* Improved admin interface with better search functionality
* Added bulk operations for link management
* Bug fixes and performance improvements
* Enhanced security with nonce verification

= 1.1.0 =
* Added admin management page
* Improved SEO plugin integration
* Better fallback support for sites without SEO plugins

= 1.0.0 =
* Initial release
* Basic schema link management
* Rank Math and Yoast SEO integration
* Gutenberg editor panel

== Upgrade Notice ==

= 1.2.0 =
This version includes improved documentation, better code standards compliance, and enhanced developer experience. Safe to upgrade.

= 1.1.8 =
Adds important extensibility features with hooks and filters. Recommended upgrade for developers.

== Developer Documentation ==

= Adding Links Programmatically =

`
// Add significant links to a post
update_post_meta( $post_id, 'schema_significant_links', "https://example.com\nhttps://example.org" );

// Add related links to a post  
update_post_meta( $post_id, 'schema_related_links', "https://related-site.com" );

// Get links from a post
$significant_links = get_post_meta( $post_id, 'schema_significant_links', true );
$related_links = get_post_meta( $post_id, 'schema_related_links', true );
`

= Customizing Post Types =

`
add_filter( 'schema_link_manager_post_types', function( $post_types ) {
    $post_types[] = 'custom_post_type';
    return $post_types;
} );
`

= Modifying Schema Data =

`
add_filter( 'schema_link_manager_schema_data', function( $data, $post_id, $significant_links, $related_links ) {
    // Custom schema modifications
    return $data;
}, 10, 4 );
`

For complete documentation, visit our [GitHub repository](https://github.com/yourusername/schema-link-manager).