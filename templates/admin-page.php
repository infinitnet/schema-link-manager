<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.ShortPrefixPassed -- Short prefix used in legacy code for backward compatibility.
/**
 * Admin page template for Schema Link Manager
 *
 * @package Schema_Link_Manager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap schema-link-manager-admin">
	<h1><?php esc_html_e( 'Schema Link Manager', 'schema-link-manager' ); ?></h1>
	
	<!-- Filters Section -->
	<div class="schema-link-manager-filters">
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="schema-link-manager">
			<?php wp_nonce_field( 'schema_link_manager_filter_nonce', '_wpnonce' ); ?>
			<!-- Reset to page 1 when applying new filters -->
			<input type="hidden" name="paged" value="1">
			
			<div class="filters-container">
				<!-- Main Search Bar -->
				<div class="filters-row filters-main">
					<div class="filter-group search-group">
						<div class="search-input-wrapper">
							<input type="search" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search...', 'schema-link-manager' ); ?>">
							<button type="submit" class="button search-button">
								<span class="dashicons dashicons-search"></span>
								<span class="screen-reader-text"><?php esc_html_e( 'Search', 'schema-link-manager' ); ?></span>
							</button>
						</div>
					</div>
				</div>
				
				<!-- Advanced Filters -->
				<div class="filters-row filters-advanced">
					<div class="filter-group">
						<label for="search_column"><?php esc_html_e( 'Search In:', 'schema-link-manager' ); ?></label>
						<select name="search_column" id="search_column">
							<option value="all" <?php selected( $search_column, 'all' ); ?>><?php esc_html_e( 'All Columns', 'schema-link-manager' ); ?></option>
							<option value="title" <?php selected( $search_column, 'title' ); ?>><?php esc_html_e( 'Title', 'schema-link-manager' ); ?></option>
							<option value="url" <?php selected( $search_column, 'url' ); ?>><?php esc_html_e( 'URL', 'schema-link-manager' ); ?></option>
							<option value="schema_links" <?php selected( $search_column, 'schema_links' ); ?>><?php esc_html_e( 'Schema Links', 'schema-link-manager' ); ?></option>
						</select>
					</div>
					
					<div class="filter-group">
						<label for="post_type"><?php esc_html_e( 'Post Type:', 'schema-link-manager' ); ?></label>
						<select name="post_type" id="post_type">
							<option value="all" <?php selected( $post_type, 'all' ); ?>><?php esc_html_e( 'All Post Types', 'schema-link-manager' ); ?></option>
							<?php foreach ( $post_types as $schema_link_manager_type_name => $schema_link_manager_type_label ) : ?>
								<option value="<?php echo esc_attr( $schema_link_manager_type_name ); ?>" <?php selected( $post_type, $schema_link_manager_type_name ); ?>>
									<?php echo esc_html( $schema_link_manager_type_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					
					<div class="filter-group">
						<label for="category"><?php esc_html_e( 'Category:', 'schema-link-manager' ); ?></label>
						<select name="category" id="category">
							<option value="all" <?php selected( $category, 'all' ); ?>><?php esc_html_e( 'All Categories', 'schema-link-manager' ); ?></option>
							<?php foreach ( $categories as $schema_link_manager_cat ) : ?>
								<option value="<?php echo esc_attr( $schema_link_manager_cat->slug ); ?>" <?php selected( $category, $schema_link_manager_cat->slug ); ?>>
									<?php echo esc_html( $schema_link_manager_cat->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					
					<div class="filter-group">
						<label for="per_page"><?php esc_html_e( 'Per Page:', 'schema-link-manager' ); ?></label>
						<select name="per_page" id="per_page">
							<option value="10" <?php selected( $posts_per_page, 10 ); ?>>10</option>
							<option value="20" <?php selected( $posts_per_page, 20 ); ?>>20</option>
							<option value="50" <?php selected( $posts_per_page, 50 ); ?>>50</option>
							<option value="100" <?php selected( $posts_per_page, 100 ); ?>>100</option>
						</select>
					</div>
					
					<div class="filter-group filter-actions">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Apply Filters', 'schema-link-manager' ); ?></button>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=schema-link-manager' ) ); ?>" class="button"><?php esc_html_e( 'Reset', 'schema-link-manager' ); ?></a>
					</div>
				</div>
			</div>
		</form>
	</div>
	
	<!-- Posts Table -->
	<div class="schema-link-manager-table-container">
		<?php if ( empty( $posts_data['posts'] ) ) : ?>
			<div class="schema-link-manager-no-results">
				<p><?php esc_html_e( 'No posts found matching your criteria.', 'schema-link-manager' ); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped schema-link-manager-table">
				<thead>
					<tr>
						<!-- Sortable column headers with current sort indicator -->
						<th class="column-title sortable <?php echo isset( $_GET['orderby'] ) && 'title' === sanitize_key( wp_unslash( $_GET['orderby'] ) ) ? 'sorted ' . esc_attr( isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : '' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display template, not form handler. ?>" data-sort="title">
							<a href="
							<?php
								echo esc_url(
									add_query_arg(
										array(
											'orderby' => 'title',
											// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display template, not form handler.
											'order'   => ( isset( $_GET['orderby'] ) && 'title' === sanitize_key( wp_unslash( $_GET['orderby'] ) ) && isset( $_GET['order'] ) && 'asc' === sanitize_key( wp_unslash( $_GET['order'] ) ) ) ? 'desc' : 'asc',
										)
									)
								);
							?>
							">
								<span><?php esc_html_e( 'Title', 'schema-link-manager' ); ?></span>
								<span class="sorting-indicator"></span>
							</a>
						</th>
						<th class="column-type sortable <?php echo isset( $_GET['orderby'] ) && 'type' === sanitize_key( wp_unslash( $_GET['orderby'] ) ) ? 'sorted ' . esc_attr( isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : '' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display template, not form handler. ?>" data-sort="type">
							<a href="
							<?php
								echo esc_url(
									add_query_arg(
										array(
											'orderby' => 'type',
											// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display template, not form handler.
											'order'   => ( isset( $_GET['orderby'] ) && 'type' === sanitize_key( wp_unslash( $_GET['orderby'] ) ) && isset( $_GET['order'] ) && 'asc' === sanitize_key( wp_unslash( $_GET['order'] ) ) ) ? 'desc' : 'asc',
										)
									)
								);
							?>
							">
								<span><?php esc_html_e( 'Type', 'schema-link-manager' ); ?></span>
								<span class="sorting-indicator"></span>
							</a>
						</th>
						<th class="column-url sortable <?php echo isset( $_GET['orderby'] ) && 'url' === sanitize_key( wp_unslash( $_GET['orderby'] ) ) ? 'sorted ' . esc_attr( isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : '' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display template, not form handler. ?>" data-sort="url">
							<a href="
							<?php
								echo esc_url(
									add_query_arg(
										array(
											'orderby' => 'url',
											// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display template, not form handler.
											'order'   => ( isset( $_GET['orderby'] ) && 'url' === sanitize_key( wp_unslash( $_GET['orderby'] ) ) && isset( $_GET['order'] ) && 'asc' === sanitize_key( wp_unslash( $_GET['order'] ) ) ) ? 'desc' : 'asc',
										)
									)
								);
							?>
							">
								<span><?php esc_html_e( 'URL', 'schema-link-manager' ); ?></span>
								<span class="sorting-indicator"></span>
							</a>
						</th>
						<th class="column-significant-links">
							<?php esc_html_e( 'Significant Links', 'schema-link-manager' ); ?>
						</th>
						<th class="column-related-links">
							<?php esc_html_e( 'Related Links', 'schema-link-manager' ); ?>
						</th>
						<th class="column-actions">
							<?php esc_html_e( 'Actions', 'schema-link-manager' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $posts_data['posts'] as $schema_link_manager_post ) : ?>
						<!-- Each post row with schema links -->
						<tr data-post-id="<?php echo esc_attr( $schema_link_manager_post['id'] ); ?>">
							<td class="column-title">
								<strong>
									<a href="<?php echo esc_url( get_edit_post_link( $schema_link_manager_post['id'] ) ); ?>" target="_blank">
										<?php echo esc_html( $schema_link_manager_post['title'] ); ?>
									</a>
								</strong>
							</td>
							<td class="column-type">
								<?php echo esc_html( $schema_link_manager_post['post_type'] ); ?>
							</td>
							<td class="column-url">
								<a href="<?php echo esc_url( $schema_link_manager_post['url'] ); ?>" target="_blank">
									<?php echo esc_url( $schema_link_manager_post['url'] ); ?>
								</a>
							</td>
							<td class="column-significant-links">
								<div class="schema-links-container">
									<?php if ( empty( $schema_link_manager_post['significant_links'] ) ) : ?>
										<p class="no-links"><?php esc_html_e( 'No significant links', 'schema-link-manager' ); ?></p>
									<?php else : ?>
										<ul class="schema-links-list significant-links">
											<?php foreach ( $schema_link_manager_post['significant_links'] as $schema_link_manager_link ) : ?>
												<li class="schema-link-item">
													<span class="link-url"><?php echo esc_url( $schema_link_manager_link ); ?></span>
													<!-- Remove button for individual link -->
													<button type="button" class="remove-link" data-link-type="significant" data-link="<?php echo esc_attr( $schema_link_manager_link ); ?>">
														<span class="dashicons dashicons-trash"></span>
													</button>
												</li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</div>
							</td>
							<td class="column-related-links">
								<div class="schema-links-container">
									<?php if ( empty( $schema_link_manager_post['related_links'] ) ) : ?>
										<p class="no-links"><?php esc_html_e( 'No related links', 'schema-link-manager' ); ?></p>
									<?php else : ?>
										<ul class="schema-links-list related-links">
											<?php foreach ( $schema_link_manager_post['related_links'] as $schema_link_manager_link ) : ?>
												<li class="schema-link-item">
													<span class="link-url"><?php echo esc_url( $schema_link_manager_link ); ?></span>
													<button type="button" class="remove-link" data-link-type="related" data-link="<?php echo esc_attr( $schema_link_manager_link ); ?>">
														<span class="dashicons dashicons-trash"></span>
													</button>
												</li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</div>
							</td>
							<td class="column-actions">
								<div class="schema-link-actions">
									<!-- Link Type and Input Controls -->
									<div class="add-link-form">
										<select class="link-type-select">
											<option value="significant"><?php esc_html_e( 'Significant', 'schema-link-manager' ); ?></option>
											<option value="related"><?php esc_html_e( 'Related', 'schema-link-manager' ); ?></option>
										</select>
										<!-- Single/Bulk Tab Switcher -->
										<div class="add-link-tabs">
											<button type="button" class="link-tab-button active" data-tab="single" onclick="switchTab(this, 'single')"><?php esc_html_e( 'Single', 'schema-link-manager' ); ?></button>
											<button type="button" class="link-tab-button" data-tab="bulk" onclick="switchTab(this, 'bulk')"><?php esc_html_e( 'Bulk', 'schema-link-manager' ); ?></button>
										</div>
										<div class="link-input-containers">
											<!-- Single Link Input -->
											<div class="link-input-container single-link-container active">
												<input type="url" class="new-link-input" placeholder="<?php esc_attr_e( 'https://example.com', 'schema-link-manager' ); ?>">
												<button type="button" class="button add-link-button"><?php esc_html_e( 'Add', 'schema-link-manager' ); ?></button>
											</div>
											<!-- Bulk Links Input (one URL per line) -->
											<div class="link-input-container bulk-link-container">
												<textarea class="bulk-links-input" placeholder="https://example.com
https://another-example.com" rows="4"></textarea>
												<div class="bulk-input-help"><?php esc_html_e( 'Enter one URL per line', 'schema-link-manager' ); ?></div>
												<button type="button" class="button add-bulk-links-button"><?php esc_html_e( 'Add All', 'schema-link-manager' ); ?></button>
											</div>
										</div>
									</div>
									<!-- Bulk Removal Buttons -->
									<div class="remove-all-links">
										<button type="button" class="button remove-significant-links" data-link-type="significant">
											<?php esc_html_e( 'Remove All Significant', 'schema-link-manager' ); ?>
										</button>
										<button type="button" class="button remove-related-links" data-link-type="related">
											<?php esc_html_e( 'Remove All Related', 'schema-link-manager' ); ?>
										</button>
										<button type="button" class="button remove-all-links-button" data-link-type="all">
											<?php esc_html_e( 'Remove All Links', 'schema-link-manager' ); ?>
										</button>
									</div>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			
			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="schema-link-manager-pagination">
					<div class="tablenav-pages">
						<span class="displaying-num">
							<?php
							/* translators: %s: Number of items */
							printf(
								/* translators: %s: Number of items */
								esc_html( _n( '%s item', '%s items', $total_posts, 'schema-link-manager' ) ),
								esc_html( number_format_i18n( $total_posts ) )
							);
							?>
						</span>
						
						<span class="pagination-links">
							<?php
							// First page link.
							if ( $current_page > 1 ) {
								printf(
									'<a class="first-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">«</span></a>',
									esc_url( add_query_arg( array( 'paged' => 1 ) ) ),
									esc_html__( 'First page', 'schema-link-manager' )
								);
							} else {
								echo '<span class="first-page button disabled"><span class="screen-reader-text">' . esc_html__( 'First page', 'schema-link-manager' ) . '</span><span aria-hidden="true">«</span></span>';
							}

							// Previous page link.
							if ( $current_page > 1 ) {
								printf(
									'<a class="prev-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">‹</span></a>',
									esc_url( add_query_arg( array( 'paged' => max( 1, $current_page - 1 ) ) ) ),
									esc_html__( 'Previous page', 'schema-link-manager' )
								);
							} else {
								echo '<span class="prev-page button disabled"><span class="screen-reader-text">' . esc_html__( 'Previous page', 'schema-link-manager' ) . '</span><span aria-hidden="true">‹</span></span>';
							}

							// Current page indicator.
							printf(
								'<span class="paging-input"><span class="tablenav-paging-text">%d / <span class="total-pages">%d</span></span></span>',
								(int) $current_page,
								(int) $total_pages
							);

							// Next page link.
							if ( $current_page < $total_pages ) {
								printf(
									'<a class="next-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">›</span></a>',
									esc_url( add_query_arg( array( 'paged' => min( $total_pages, $current_page + 1 ) ) ) ),
									esc_html__( 'Next page', 'schema-link-manager' )
								);
							} else {
								echo '<span class="next-page button disabled"><span class="screen-reader-text">' . esc_html__( 'Next page', 'schema-link-manager' ) . '</span><span aria-hidden="true">›</span></span>';
							}

							// Last page link.
							if ( $current_page < $total_pages ) {
								printf(
									'<a class="last-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">»</span></a>',
									esc_url( add_query_arg( array( 'paged' => $total_pages ) ) ),
									esc_html__( 'Last page', 'schema-link-manager' )
								);
							} else {
								echo '<span class="last-page button disabled"><span class="screen-reader-text">' . esc_html__( 'Last page', 'schema-link-manager' ) . '</span><span aria-hidden="true">»</span></span>';
							}
							?>
						</span>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
