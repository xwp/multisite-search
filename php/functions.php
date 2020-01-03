<?php
/**
 * Non-Namespaced functions for easy use across a WordPress codebase.
 *
 * @package MultisiteSearch
 */

/**
 * Queries the Multisite Search Index.
 *
 * @param array $args Search args.
 *
 * @return array
 */
function mss_get_results( $args = array() ) {

	if ( empty( $args['per_page'] ) ) {
		unset( $args['per_page'] );
	}

	if ( empty( $args['page'] ) ) {
		unset( $args['page'] );
	}

	$base_url = '/multisite-search/v1/search';

	$request = new WP_REST_Request( 'GET', $base_url );
	$request->set_query_params( $args );
	$response = rest_do_request( $request );

	// If we have an error.
	if ( $response->is_error() ) {
		return array(
			'query'   => array(
				'keywords' => $args['q'],
				'per_page' => $args['per_page'],
				'page'     => $args['page'],
				'count'    => 0,
			),
			'entries' => array(),
		);
	}

	return $response->get_data();
}

/**
 * Renders results from API.
 *
 * @param arrat   $results The results.
 * @param string  $css_prefix A base CSS prefix.
 * @param integer $pagination_limit Number of pages to visibly show.
 * @param bool    $echo True for immediate output. False for return only.
 *
 * @return void
 */
function mss_render_results( $results, $css_prefix = 'mss-results', $pagination_limit = 0, $echo = true ) {

	if ( 0 === (int) $results['query']['count'] ) {
		$keywords = esc_html( $results['query']['keywords'] );
		mss_render_no_results( $keywords, $css_prefix, $echo );
	}

	foreach ( $results['entries'] as $entry ) {
		mss_render_search_result( $entry, $css_prefix, $echo );
	}

	mss_render_results_pagination( $results['query'], $css_prefix, $pagination_limit, true, $echo );
}

/**
 * Render an individual result.
 *
 * @param Object $result The result object.
 * @param string $css_prefix The base CSS prefix.
 * @param bool   $show_sitename Show sitename if true.
 * @param bool   $show_url Show result URL if true.
 * @param bool   $echo True for immediate output.
 *
 * @return string
 */
function mss_render_search_result( $result, $css_prefix = 'mss-results', $show_sitename = true, $show_url = false, $echo = true ) {

	$title = apply_filters(
		'mss_render_search_result_title',
		sprintf(
			'<h2 class="%s__result-title mss__result-title">%s</h2>',
			$css_prefix,
			mss_get_safe_link( $result->url, $result->post_title )
		),
		$result
	);

	$blog_details = get_blog_details( $result->blog_id );
	$sitename     = apply_filters(
		'mss_render_search_result_sitename',
		sprintf(
			'<div class="%s__result-sitename mss__result-sitename">%s</div>',
			$css_prefix,
			mss_get_safe_link( $result->url, $blog_details->blogname )
		),
		$result
	);

	$content = apply_filters(
		'mss_render_search_result_body',
		sprintf(
			'<div class="%s__result-body mss__result-body">%s</div>',
			$css_prefix,
			mss_limit_words( $result->post_content )
		),
		$result
	);

	$url = apply_filters(
		'mss_render_search_result_url',
		sprintf(
			'<div class="%s__result-url mss__result-url">%s</div>',
			$css_prefix,
			mss_get_safe_link( $result->url, $result->url )
		),
		$result
	);

	$output = apply_filters(
		'mss_render_search_result_entry',
		sprintf(
			'<div class="%s__result-entry-wrapper mss__result-entry-wrapper">%s %s %s %s</div>',
			$css_prefix,
			$title,
			$show_sitename ? $sitename : '',
			$content,
			$show_url ? $url : ''
		),
		$result
	);

	if ( $echo ) {
		mss_echo( $output );
	}

	return $output;
}

/**
 * Render the "No Results" display.
 *
 * @param string $keywords The given keywords.
 * @param string $css_prefix The base CSS prefix.
 * @param bool   $echo True for immediate output.
 *
 * @return string
 */
function mss_render_no_results( $keywords, $css_prefix = 'mss-results', $echo = true ) {
	$keyword_wrapper = sprintf( '<div class="%s__no-results-keyword mss__no-results-keyword">%s</div>', $css_prefix, $keywords );

	$message = sprintf(
		// Translators: %s contains the original keywords submitted.
		esc_html__( 'No results for: %s', 'multisite-search' ),
		$keyword_wrapper
	);

	$html = sprintf(
		'<div class="%s__no-results-wrapper><div class="%s__no-results-message mss__no-results-message>%s</div></div>',
		$css_prefix,
		$css_prefix,
		$message
	);

	$html = apply_filters( 'mss_render_no_results_output', $html, $keywords );

	$output = \wp_kses_post( $html );

	if ( $output ) {
		mss_echo( $html );
	}

	return $output;
}

/**
 * Render results pagination.
 *
 * @param array   $query Pagination information.
 * @param string  $css_prefix The base CSS prefic.
 * @param integer $limit The number of visible page links.
 * @param bool    $hide_if_none Don't show pagination if there are no results.
 * @param bool    $echo True for immediate output.
 * @return string
 */
function mss_render_results_pagination( $query, $css_prefix = 'mss-results', $limit = 0, $hide_if_none = true, $echo = true ) {

	if ( 0 === (int) $query['count'] && $hide_if_none ) {
		return '';
	}

	$limit        = empty( $limit ) ? apply_filters( 'mss_search_pagination_list_size', 5 ) : $limit;
	$current_page = (int) $query['page'];
	$last_page    = (int) ceil( (int) $query['count'] / (int) $query['per_page'] ) - 1;
	$offset       = (int) ceil( $limit / 2 ) - 1;
	$nonce        = wp_create_nonce();

	$left  = $current_page - $offset;
	$right = $current_page + $offset;

	if ( $last_page <= $limit ) {
		$left  = 0;
		$right = $last_page;
	} elseif ( $current_page <= $offset ) {
		$left  = 0;
		$right = $limit - 1;
	} elseif ( ( $current_page + $offset ) >= $last_page ) {
		$left  = $last_page - $limit + 1;
		$right = $last_page;
	}

	$left_arrow = sprintf(
		'<a class="%s__result-pagination-link mss__result-pagination-link" href="?search=%s&per_page=%s&page=%s&_wpnonce=%s">%s</a>',
		$css_prefix,
		$query['keywords'],
		$query['per_page'],
		$current_page - 1,
		$nonce,
		'&lt;'
	);

	$left_arrow_dud = sprintf(
		'<a class="%s__result-pagination-link mss__result-pagination-link %s" href="#">%s</a>',
		$css_prefix,
		$current_page - 1 < 0 ? 'disabled' : '',
		'&lt;'
	);

	$right_arrow = sprintf(
		'<a class="%s__result-pagination-link mss__result-pagination-link" href="?search=%s&per_page=%s&page=%s&_wpnonce=%s">%s</a>',
		$css_prefix,
		$query['keywords'],
		$query['per_page'],
		$current_page + 1,
		$nonce,
		'&gt;'
	);

	$right_arrow_dud = sprintf(
		'<a class="%s__result-pagination-link mss__result-pagination-link %s" href="#">%s</a>',
		$css_prefix,
		$current_page + 1 > $last_page ? 'disabled' : '',
		'&gt;'
	);

	$links = '';
	for ( $i = $left; $i <= $right; $i++ ) {
		$links = sprintf(
			'%s<a class="%s__result-pagination-link mss__result-pagination-link %s" href="?search=%s&per_page=%s&page=%s&_wpnonce=%s"> %s </a>',
			$links,
			$css_prefix,
			$current_page === $i ? 'active' : '',
			$query['keywords'],
			$query['per_page'],
			$i,
			$nonce,
			( $i + 1 )
		);
	}

	$output = apply_filters(
		'mss_render_search_result_pagination',
		sprintf(
			'<div class="%s__result-pagination-wrapper mss__result-pagination-wrapper">%s%s%s</div>',
			$css_prefix,
			$current_page - 1 >= 0 ? $left_arrow : $left_arrow_dud,
			$links,
			$current_page + 1 <= $last_page ? $right_arrow : $right_arrow_dud
		),
		$query
	);

	if ( $echo ) {
		mss_echo( $output );
	}

	return $output;
}

/**
 * Creates a new Multisite Search input field.
 *
 * @param array $attributes The input field options.
 * @param bool  $echo If true it outputs the form. If false it returns the markup.
 * @return mixed
 */
function mss_search_box( $attributes, $echo = true ) {

	$base_class = ! empty( $attributes['class'] ) ? esc_attr( $attributes['class'] ) : 'mss-search';
	$input_id   = $base_class . ( ! empty( $attributes['id'] ) ? '__' . esc_attr( $attributes['id'] ) : '' );
	$button     = '';
	$label      = '';

	$search_vars = mss_get_search_variables();

	if ( ! empty( $attributes['label'] ) ) {
		$label = sprintf(
			'<label for="%s" class="%s__label mss-search__label">%s</label>',
			$input_id,
			$base_class,
			esc_html( $attributes['label'] )
		);
	}

	$input = sprintf(
		'<input type="search" id="%s" class="%s__input mss-search__input" name="search" value="%s" placeholder="%s" />',
		$input_id,
		$base_class,
		esc_attr( $search_vars['q'] ),
		esc_attr( $attributes['placeholder'] )
	);

	if ( ! empty( $attributes['button'] ) ) {
		$button = sprintf(
			'<button type="submit" class="%s__button mss-search__button">%s</button>',
			$base_class,
			esc_html( $attributes['button'] )
		);
	}

	$nonce = wp_create_nonce();
	$nonce = sprintf(
		'<input type="hidden" name="_wpnonce" value="%s">',
		$nonce
	);

	$form = sprintf(
		'<div class="%s__wrapper"><form class="%s__form mss-search__form" role="search" method="get" action="%s">%s</form></div>',
		$base_class,
		$base_class,
		esc_url( home_url( '/' ) ),
		$nonce . $label . $input . $button
	);

	if ( $echo ) {
		mss_echo( $form );
	}

	return $form;
}

/**
 * Safely get $_GET variables for searching.
 *
 * @return array
 */
function mss_get_search_variables() {

	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

	$default = array(
		'q'        => '',
		'page'     => 0,
		'per_page' => 0,
	);

	if ( ! wp_verify_nonce( $nonce ) ) {
		return $default;
	}

	return array(
		'q'        => isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '',
		'page'     => isset( $_GET['page'] ) ? (int) $_GET['page'] : 0,
		'per_page' => isset( $_GET['per_page'] ) ? (int) $_GET['per_page'] : apply_filters( 'mss_search_per_page', 10 ),
	);
}

/**
 * Limit content to number of words.
 *
 * @param string $words The string to trim.
 * @param int    $limit The number of words.
 * @param string $append Something to leave behind.
 *
 * @return string
 */
function mss_limit_words( $words, $limit = 0, $append = ' &hellip;' ) {
	$limit = empty( $limit ) ? apply_filters( 'mss_search_result_body_word_limit', 30 ) : $limit;
	++$limit;
	$words = explode( ' ', $words, $limit );
	array_pop( $words );

	$words = implode( ' ', $words );

	// Only append ellipses to words not punctuation.
	if ( preg_match( '/\w$/', $words ) ) {
		$words .= $append;
	}

	return $words;
}

/**
 * Get a safe HTML link.
 *
 * @param string $url The URL.
 * @param string $title The title.
 * @return string
 */
function mss_get_safe_link( $url, $title ) {
	return sprintf(
		'<a href="%s">%s</a>',
		esc_url_raw( $url ),
		esc_html( $title )
	);
}

/**
 * Safe echo.
 *
 * @param string $input The input to output.
 * @return void
 */
function mss_echo( $input ) {

	$allowed_atts = array(
		'align'      => array(),
		'class'      => array(),
		'type'       => array(),
		'id'         => array(),
		'dir'        => array(),
		'lang'       => array(),
		'style'      => array(),
		'xml:lang'   => array(),
		'src'        => array(),
		'alt'        => array(),
		'href'       => array(),
		'rel'        => array(),
		'rev'        => array(),
		'target'     => array(),
		'novalidate' => array(),
		'type'       => array(),
		'value'      => array(),
		'name'       => array(),
		'tabindex'   => array(),
		'action'     => array(),
		'method'     => array(),
		'for'        => array(),
		'width'      => array(),
		'height'     => array(),
		'data'       => array(),
		'title'      => array(),
	);

	$allowed = array();

	$allowed['form']   = $allowed_atts;
	$allowed['label']  = $allowed_atts;
	$allowed['input']  = $allowed_atts;
	$allowed['hidden'] = $allowed_atts;
	$allowed['button'] = $allowed_atts;

	$allowed = array_merge( $allowed, wp_kses_allowed_html( 'post' ) );
	echo wp_kses( $input, $allowed );
}
