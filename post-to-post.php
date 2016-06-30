<?php
/**
 * Plugin Name: Post to Post - No New Tables
 * Plugin URI: https://github.com/lgedeon/post-to-post.git
 * Description: Create Post to Post relationship without adding a table. Super light. Super simple.
 * Version: 0.2
 * Author:  lgedeon
 */

/**
 * Class Post_To_Post
 */
class Post_To_Post {

	const POST_TO_POST_TAXONOMY = 'hidden_post_to_post_link';

	/**
	 * Post_To_Post constructor.
	 */
	public function __construct() {

		// Register late (priority 99) in init to get most post_types
		add_action( 'init', function () {
			$post_types = get_post_types();

			register_taxonomy( self::POST_TO_POST_TAXONOMY, $post_types, array(
				'label'  => self::POST_TO_POST_TAXONOMY,
				'public' => false,
			) );
		}, 99 );
	}

	/**
	 * Add a link between two or more posts.
	 *
	 * Creates a unique term for each link and assigns it to all posts in the relationship.
	 *
	 * @param array $posts Array of WP_Post objects or ids
	 */
	public function add_post_to_post_link ( array $posts ) {
		if ( $term_value = self::encode_post_to_post_term( $posts ) ) {
			foreach ( $posts as $post ) {
				$post = get_post( $post );
				wp_set_post_terms( $post->ID, $term_value, self::POST_TO_POST_TAXONOMY, true );
			}
		}
	}

	/**
	 * Get all related posts of a given post_type.
	 *
	 * Uses term name analysis.
	 *
	 * TODO: Test getting all posts that have the same terms, it may be faster, may not. We may never need to decode.
	 *
	 * @param int|WP_Post $post WP_Post object or ID that might have related posts
	 * @param array $post_types Post types (post, page, CPT) could be the same as post_type of $post
	 *
	 * @return array Array of post ids
	 */
	public function get_post_to_post_links ( $post, array $post_types = array() ) {
		$post = get_post( $post );
		$terms = wp_get_post_terms( $post->ID, self::POST_TO_POST_TAXONOMY );

		$post_types[] = $post->post_type;
		$post_types = array_unique( $post_types );

		$posts = array();

		foreach ( $terms as $term ) {
			$posts = array_merge( $posts, self::decode_post_to_post_term( $term->name, $post_types ) );
		}

		return array_diff( $posts, array( $post->ID, false ) );
	}

	/**
	 * Create a unique string to use as a term slug.
	 *
	 * Concatenates post_type and post->ID of each. Dependant on post_types being unique because allowed characters
	 * for post_type and term slug are the same so convention is tightly controlled.
	 *
	 * Term slugs returned are in the format: {$post_type}{$post->ID}{$post_type}{$post->ID}...
	 * Example: page12post15 or post12post15
	 *
	 * Most importantly, the post_types and ID's are sorted so that they are always the same.
	 *
	 * @param array $posts Array of WP_Post objects or ids
	 *
	 * @return bool|string Unique string to use as term slug
	 */
	public static function encode_post_to_post_term ( array $posts ) {
		$_posts = array();

		foreach ( $posts as $post ) {
			if ( $post = get_post( $post ) ) {
				$_posts[] = array(
					'id' => $post->ID,
					'post_type' => $post->post_type,
				);
			}
		}

		if ( count( $_posts ) < 2 ) {
			return false;
		}

		uasort( $_posts, function ($a, $b) {
			if ( $a['post_type'] === $b['post_type'] ) {
				return ( $a['id'] < $b['id'] ) ? -1 :  1;
			}
			return ( $a['post_type'] < $b['post_type'] ) ? -1 :  1;
		});

		$encoded = '';

		foreach ( $_posts as $post ){
			$encoded .= $post['post_type'].$post['id'];
		}

		return $encoded;
	}

	/**
	 * Split a key into the id(s) that it is composed of.
	 *
	 * @param sting $encoded Must of of the form: {$post_type}{$post->ID}{$post_type}{$post->ID}...
	 * @param array $post_types Array of post_types listed in key. Runs faster with less post_types. Defaults to all.
	 *
	 * @return array Array of post ids
	 */
	public static function decode_post_to_post_term ( $encoded, array $post_types = array() ) {
		$return = array();

		if ( ! is_array( $post_types ) || empty( $post_types ) ) {
			$post_types = get_post_types();
		}

		// Sort post_types by length just in case we are given post_types like "event" and "event_date"
		usort( $post_types, function( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		});

		$bits = array( $encoded );

		foreach ( $post_types as $post_type ) {
			foreach ( $bits as $bit ) {

				// Reset (we are iterating through a copy) and refill with any pieces that still need processed.
				$bits = array();

				$_bits = explode( $post_type, $bit );

				foreach ( $_bits as $_bit ) {
					if ( ctype_digit( $_bit ) ) {
						$return[] = $_bit;
					} elseif ( ! empty( $_bit ) ) {
						$bits[] = $_bit;
					}
				}
			}
		}

		return $return;
	}

}
