<?php

/**
 * Export terms implementation.
 *
 * @package capsule-server
 */

namespace CrowdFavorite\Capsule;

/**
 * Export Terms class.
 */
class CapsuleServerExportTerms
{

	/**
	 * Constructor.
	 *
	 * @param array $taxonomies Existing taxonomies.
	 */
	public function __construct($taxonomies = array())
	{
		$this->taxonomies = $taxonomies;
	}

	/**
	 * Get existing terms in a nicely formatted array.
	 *
	 * @return array of formatted taxonomies:
	 *  'taxonomy_1' => array(
	 *       'term-slug' => array(
	 *          'id' => 1,
	 *          'name' => 'Amazing Term',
	 *          'description' => 'This term is amazing AND useful',
	 *       ),
	 *       'term-slug-2' ...
	 *  ),
	 *  'taxonomy_2' ...
	 */
	public function getTerms()
	{
		$taxonomy_array = array();

		$terms = get_terms($this->taxonomies, array(
			'hide_empty' => false,
			'orderby'    => 'slug',
			'order'      => 'ASC',
		));

		if (is_array($terms)) {
			foreach ($terms as $term) {
				$taxonomy_array[ $term->taxonomy ][ $term->slug ] = array(
					'id'          => $term->term_id,
					'name'        => $term->name,
					'description' => $term->description,
					'taxonomy'    => $term->taxonomy,
				);
			}
		}
		return $taxonomy_array;
	}
}
