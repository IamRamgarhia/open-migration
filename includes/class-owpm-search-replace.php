<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OWPM_Search_Replace {

	public static function run( $old_string, $new_string ) {
		global $wpdb;

		if ( empty( $old_string ) || empty( $new_string ) || $old_string === $new_string ) {
			return false;
		}

		// Clean strings
		$old_string = trim( $old_string );
		$new_string = trim( $new_string );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tables = $wpdb->get_col( 'SHOW TABLES' );

		foreach ( $tables as $table ) {
			// Get primary key
			$primary_key = '';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$columns_info = $wpdb->get_results( $wpdb->prepare( "DESCRIBE %i", $table ) );
			$columns = array();
			foreach ( $columns_info as $col ) {
				$columns[] = $col->Field;
				if ( $col->Key === 'PRI' ) {
					$primary_key = $col->Field;
				}
			}

			// If no primary key, skip (we can't safely update row-by-row without it)
			if ( empty( $primary_key ) ) continue;

			// Build LIKE query to only fetch rows that actually contain the string
			$where = array();
			$where_values = array();
			$where_values[] = $table; // first parameter for %i

			foreach ( $columns as $col ) {
				// Column name can't be prepared with %i in a loop easily without flattening, but we can escape it safely
				// Actually, we can just escape the column name
				$escaped_col = esc_sql( $col );
				$where[] = "`$escaped_col` LIKE %s";
				$where_values[] = '%' . $wpdb->esc_like( $old_string ) . '%';
			}
			
			$query_base = "SELECT * FROM %i WHERE " . implode( ' OR ', $where );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$query = $wpdb->prepare( $query_base, $where_values );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $query, ARRAY_A );

			if ( ! empty( $rows ) ) {
				foreach ( $rows as $row ) {
					$update_data = array();
					
					foreach ( $row as $col_name => $col_value ) {
						// Skip primary key updates
						if ( $col_name === $primary_key ) continue;

						$new_value = self::recursive_replace( $old_string, $new_string, $col_value );
						
						// If value changed, add to update array
						if ( $new_value !== $col_value ) {
							$update_data[ $col_name ] = $new_value;
						}
					}

					if ( ! empty( $update_data ) ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->update(
							$table,
							$update_data,
							array( $primary_key => $row[ $primary_key ] )
						);
					}
				}
			}
		}

		return true;
	}

	private static function recursive_replace( $search, $replace, $data ) {
		if ( is_string( $data ) ) {
			// Check if serialized
			if ( is_serialized( $data ) ) {
				$unserialized = @unserialize( $data );
				if ( $unserialized !== false ) {
					$unserialized = self::recursive_replace( $search, $replace, $unserialized );
					return serialize( $unserialized );
				}
			}
			// Normal string replacement
			return str_replace( $search, $replace, $data );
		} elseif ( is_array( $data ) ) {
			$new_array = array();
			foreach ( $data as $key => $value ) {
				$new_key = is_string( $key ) ? str_replace( $search, $replace, $key ) : $key;
				$new_array[ $new_key ] = self::recursive_replace( $search, $replace, $value );
			}
			return $new_array;
		} elseif ( is_object( $data ) ) {
			$new_object = new stdClass();
			foreach ( $data as $key => $value ) {
				$new_key = is_string( $key ) ? str_replace( $search, $replace, $key ) : $key;
				$new_object->$new_key = self::recursive_replace( $search, $replace, $value );
			}
			return $new_object;
		}
		return $data;
	}
}
