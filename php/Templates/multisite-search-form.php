<?php
/**
 * Copy this file to override the default search box styling.
 *
 * @package MultisiteSearch
 */

mss_search_box(
	array(
		'id'          => 'search',      // Id for the form.
		'class'       => 'mss-search',  // A classname for styling.
		'label'       => '',            // A label to output before the search box.
		'placeholder' => 'Search',      // Placeholder text for the search box.
		'button'      => '',            // Optional button to render.
	),
	true // (Optional) true will output the form, false will return the form. Default: false.
);
