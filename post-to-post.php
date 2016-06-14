<?php 
function add_link ( $p1, $p2 ) {
	$p1 = get_post( $p1 );
	$p2 = get_post( $p2 );
	$term_value = encode_term( array( $p1->post_type => $p1->ID, $p2->post_type => $p2->ID ));
	wp_set_post_terms( $p1, $term_value, TAXONOMY, true );
	wp_set_post_terms( $p2, $term_value, TAXONOMY, true );
}

function get_links ( $post, $relative_post_type ) {
	$return = array();
	$post = get_post( $post );
	$terms = wp_get_post_terms( $post, TAXONOMY );
	foreach ( $terms as $term ) {
		if ( false !== strpos( $term, $relative_post_type ) ) {
			$decoded = decode_term( $term, array( $post->post_type, $relative_post_type ) );
			$return[] = $decoded[$relative_post_type]; 
		}
	}
}

// expects array( post_type_A => 12, post_type_B => 15 )
// returns post_type_A12post_type_B15
function encode_term ( $posts ) {
	ksort( $posts );
	$encode = '';
	foreach ( $posts as $key => $value ){
		$encode .= $key.$value;
	}
	return $encode;
}

// expects $encoded = post_type_A12post_type_B15
// expects $post_types = array( post_type_A, post_type_B )
// returns array( post_type_A => 12, post_type_B => 15 )
function decode_term ( $encoded, $post_types ) {
	sort( $post_types );
	$decoded = explode( $post_types[1], $encoded );
	$_decoded = explode( $post_types[0], $decoded[0] );
	return array( $post_types[0] => $_decoded[0], $post_types[1] => $decoded[1] );
}

/*
 * Note that encode_term and decode_term rely on sorting post_type alphabetically. Ideal for
 * linking posts of different post types. It would need additional logic for relating back to same
 * post type (traditional post-to-post). In that case if post_types are the same, sort by ID and
 * only on explode needed.
 */
