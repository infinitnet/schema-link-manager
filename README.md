# Schema Link Manager

A WordPress plugin that adds and manages **significant** and **related** links to JSON-LD WebPage schema for improved SEO and semantic structure.

## Description

Schema Link Manager enhances your website's structured data by allowing you to add Schema.org's `significantLink` and `relatedLink` properties to your WebPage schema. This helps search engines better understand the relationships between your content and other web resources.

### Features

- ✅ **SEO Plugin Integration**: Seamlessly works with Rank Math and Yoast SEO
- ✅ **Fallback Support**: Automatically injects schema when no SEO plugin is detected
- ✅ **Gutenberg Sidebar Panel**: Easy-to-use interface in the block editor
- ✅ **Admin Management Page**: Bulk manage links across all posts
- ✅ **Advanced Search & Filters**: Search by title, URL, schema links, post type, or category
- ✅ **Bulk Operations**: Add or remove multiple links at once
- ✅ **Developer Friendly**: Multiple hooks and filters for customization
- ✅ **Translation Ready**: Full i18n support

### What are Significant and Related Links?

According to Schema.org:

- **significantLink**: The most significant URLs on the page. Typically, these are the URLs of the main content.
- **relatedLink**: A link related to this web page, for example to other related web pages.

## Installation

1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the zip file and click "Install Now"
4. Activate the plugin
5. Start adding schema links via the Gutenberg editor or the admin page

## Usage

### Adding Links in Gutenberg Editor

1. Open any post or page in the block editor
2. Look for the "Schema Links" panel in the sidebar (Document tab)
3. Add URLs to either "Significant Links" or "Related Links" fields
4. Publish or update your post

### Managing Links via Admin Page

1. Go to WordPress Admin → Schema Links
2. Use filters to find specific posts
3. Add, edit, or remove links inline
4. Use bulk operations for efficiency

### Searching and Filtering

The admin page supports:
- **Search in**: All columns, Title, URL, or Schema Links
- **Filter by**: Post Type, Category
- **Sort by**: Title, Type, or URL
- **Pagination**: 10, 20, 50, or 100 posts per page

## For Developers

### Hooks and Filters

#### Filters

```php
// Modify post types that have schema link meta
add_filter( 'schema_link_manager_post_types', function( $post_types ) {
    // Add custom post type
    $post_types[] = 'custom_post_type';
    return $post_types;
} );

// Modify processed links before adding to schema
add_filter( 'schema_link_manager_processed_links', function( $links, $post_id, $meta_key ) {
    // Custom link processing
    return $links;
}, 10, 3 );

// Modify schema data after adding links
add_filter( 'schema_link_manager_schema_data', function( $data, $post_id, $significant_links, $related_links ) {
    // Custom schema modifications
    return $data;
}, 10, 4 );

// Modify admin page capability requirement
add_filter( 'schema_link_manager_admin_capability', function( $capability ) {
    return 'edit_posts'; // Allow editors access
} );
```

#### Actions

```php
// Hook into plugin activation
add_action( 'schema_link_manager_activated', function() {
    // Perform setup tasks
} );

// Hook into plugin deactivation
add_action( 'schema_link_manager_deactivated', function() {
    // Perform cleanup tasks
} );

// Before adding links to schema
add_action( 'schema_link_manager_before_add_links', function( $post_id, $significant_links, $related_links, $data ) {
    // Custom logic before adding links
}, 10, 4 );

// When a link is added via AJAX
add_action( 'schema_link_manager_link_added', function( $post_id, $link, $link_type ) {
    // Custom logic after link is added
}, 10, 3 );

// When a link is removed via AJAX
add_action( 'schema_link_manager_link_removed', function( $post_id, $link, $link_type ) {
    // Custom logic after link is removed
}, 10, 3 );
```

### Programmatic Usage

```php
// Add significant links to a post
update_post_meta( $post_id, 'schema_significant_links', "https://example.com\nhttps://example.org" );

// Add related links to a post
update_post_meta( $post_id, 'schema_related_links', "https://related-site.com" );

// Get links from a post
$significant_links = get_post_meta( $post_id, 'schema_significant_links', true );
$related_links = get_post_meta( $post_id, 'schema_related_links', true );
```

## Code Quality & Development

### Code Quality

This project uses [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) with [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards) to ensure code quality and consistency.

#### Prerequisites

Install dependencies using Composer:

```bash
composer install
```

#### Running Code Standards Checks

To check your code against WordPress coding standards:

```bash
./vendor/bin/phpcs
```

Or use the Composer script:

```bash
composer phpcs
```

#### Automatically Fix Issues

Many coding standard violations can be fixed automatically:

```bash
./vendor/bin/phpcbf
```

Or use the Composer script:

```bash
composer phpcbf
```

#### Configuration

The coding standards are configured in `phpcs.xml.dist`. The configuration:
- Checks all PHP files in the plugin
- Uses WordPress coding standards
- Excludes vendor and node_modules directories
- Ignores JavaScript and CSS files
- Requires proper prefixing with `schema_link_manager` or `slm`