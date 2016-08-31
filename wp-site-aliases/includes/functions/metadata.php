<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add metadata to an alias.
 *
 * @since 1.0.0
 *
 * @param int    $id         Alias ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @param bool   $unique     Optional. Whether the same key should not be added.
 *                           Default false.
 * @return int|false Meta ID on success, false on failure.
 */
function add_alias_meta( $id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'blog_aliasmeta', $id, $meta_key, $meta_value, $unique );
}

/**
 * Remove from an alias, metadata matching key and/or value.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @since 1.0.0
 *
 * @param int    $id         Alias ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Optional. Metadata value. Must be serializable if
 *                           non-scalar. Default empty.
 * @return bool True on success, false on failure.
 */
function delete_alias_meta( $id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'blog_aliasmeta', $id, $meta_key, $meta_value );
}

/**
 * Retrieve from an alias, metadata value by key.
 *
 * @since 1.0.0
 *
 * @param int    $id        Alias ID.
 * @param string $meta_key  Optional. The meta key to retrieve. By default, returns
 *                          data for all keys. Default empty.
 * @param bool   $single    Optional. Whether to return a single value. Default false.
 * @return mixed Will be an array if $single is false. Will be value of meta data
 *               field if $single is true.
 */
function get_alias_meta( $id, $meta_key = '', $single = false ) {
	return get_metadata( 'blog_aliasmeta', $id, $meta_key, $single );
}

/**
 * Update metadata for an alias ID, and/or key, and/or value.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and alias ID.
 *
 * If the meta field for the alias does not exist, it will be added.
 *
 * @since 1.0.0
 *
 * @param int    $id         Alias ID.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @param mixed  $prev_value Optional. Previous value to check before removing.
 *                           Default empty.
 * @return int|bool Meta ID if the key didn't exist, true on successful update,
 *                  false on failure.
 */
function update_alias_meta( $id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'blog_aliasmeta', $id, $meta_key, $meta_value, $prev_value );
}
