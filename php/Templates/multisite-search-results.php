<?php
/**
 * Template for search results.
 *
 * @package MultisiteSearch
 */

wp_head();

?>

<h2 class="mss-search-results-title"><?php esc_html_e( 'Search Results', 'multisite-search' ); ?></h2>

<?php

mss_search_box(
	array(
		'id'          => 'search',      // Id for the form.
		'class'       => 'mss-search-on-results',  // A classname for styling.
		'label'       => '',            // A label to output before the search box.
		'placeholder' => 'Search',      // Placeholder text for the search box.
		'button'      => '',            // Optional button to render.
	),
	true // (Optional) true will output the form, false will return the form. Default: false.
);

$results = mss_get_results(
	mss_get_search_variables()
);

mss_render_results( $results );

wp_footer();
