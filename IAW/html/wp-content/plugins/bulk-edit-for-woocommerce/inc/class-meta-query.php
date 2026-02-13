<?php
class PBE_WP_Meta_Query  extends WP_Meta_Query
{


	public $table_alias_prefix = 'mt';

	public function get_sql_for_clause(&$clause, $parent_query, $clause_key = '')
	{
		global $wpdb;

		$sql_chunks = array(
			'where' => array(),
			'join'  => array(),
		);

		if (isset($clause['compare'])) {
			$clause['compare'] = strtoupper($clause['compare']);
		} else {
			$clause['compare'] = isset($clause['value']) && is_array($clause['value']) ? 'IN' : '=';
		}

		$non_numeric_operators = array(
			'=',
			'!=',
			'LIKE',
			'NOT LIKE',
			'IN',
			'NOT IN',
			'EXISTS',
			'NOT EXISTS',
			'RLIKE',
			'REGEXP',
			'NOT REGEXP',
		);

		$numeric_operators = array(
			'>',
			'>=',
			'<',
			'<=',
			'BETWEEN',
			'NOT BETWEEN',
		);

		if (!in_array($clause['compare'], $non_numeric_operators, true) && !in_array($clause['compare'], $numeric_operators, true)) {
			$clause['compare'] = '=';
		}

		if (isset($clause['compare_key'])) {
			$clause['compare_key'] = strtoupper($clause['compare_key']);
		} else {
			$clause['compare_key'] = isset($clause['key']) && is_array($clause['key']) ? 'IN' : '=';
		}

		if (!in_array($clause['compare_key'], $non_numeric_operators, true)) {
			$clause['compare_key'] = '=';
		}

		$meta_compare     = $clause['compare'];
		$meta_compare_key = $clause['compare_key'];

		// First build the JOIN clause, if one is required.
		$join = '';

		// We prefer to avoid joins if possible. Look for an existing join compatible with this clause.
		$alias = $this->find_compatible_table_alias($clause, $parent_query);
		if (false === $alias) {
			$i     = count($this->table_aliases);
			$alias = $this->table_alias_prefix . $i;

			// JOIN clauses for NOT EXISTS have their own syntax.
			if ('NOT EXISTS' === $meta_compare) {
				$join .= " LEFT JOIN $this->meta_table";
				$join .= " AS $alias";

				if ('LIKE' === $meta_compare_key) {
					$join .= $wpdb->prepare(" ON ( $this->primary_table.$this->primary_id_column = $alias.$this->meta_id_column AND $alias.meta_key LIKE %s )", '%' . $wpdb->esc_like($clause['key']) . '%');
				} else {
					$join .= $wpdb->prepare(" ON ( $this->primary_table.$this->primary_id_column = $alias.$this->meta_id_column AND $alias.meta_key = %s )", $clause['key']);
				}

				// All other JOIN clauses.
			} else {
				$join .= " INNER JOIN $this->meta_table";
				$join .= " AS $alias";
				$join .= " ON ( $this->primary_table.$this->primary_id_column = $alias.$this->meta_id_column )";
			}

			$this->table_aliases[] = $alias;
			$sql_chunks['join'][]  = $join;
		}

		// Save the alias to this clause, for future siblings to find.
		$clause['alias'] = $alias;

		// Determine the data type.
		$_meta_type     = isset($clause['type']) ? $clause['type'] : '';
		$meta_type      = $this->get_cast_for_type($_meta_type);
		$clause['cast'] = $meta_type;

		// Fallback for clause keys is the table alias. Key must be a string.
		if (is_int($clause_key) || !$clause_key) {
			$clause_key = $clause['alias'];
		}

		// Ensure unique clause keys, so none are overwritten.
		$iterator        = 1;
		$clause_key_base = $clause_key;
		while (isset($this->clauses[$clause_key])) {
			$clause_key = $clause_key_base . '-' . $iterator;
			$iterator++;
		}

		// Store the clause in our flat array.
		$this->clauses[$clause_key] = &$clause;

		// Next, build the WHERE clause.

		// meta_key.
		if (array_key_exists('key', $clause)) {
			if ('NOT EXISTS' === $meta_compare) {
				$sql_chunks['where'][] = $alias . '.' . $this->meta_id_column . ' IS NULL';
			} else {
				/**
				 * In joined clauses negative operators have to be nested into a
				 * NOT EXISTS clause and flipped, to avoid returning records with
				 * matching post IDs but different meta keys. Here we prepare the
				 * nested clause.
				 */
				if (in_array($meta_compare_key, array('!=', 'NOT IN', 'NOT LIKE', 'NOT EXISTS', 'NOT REGEXP'), true)) {
					// Negative clauses may be reused.
					$i                     = count($this->table_aliases);
					$subquery_alias        = $this->table_alias_prefix . $i;
					$this->table_aliases[] = $subquery_alias;

					$meta_compare_string_start  = 'NOT EXISTS (';
					$meta_compare_string_start .= "SELECT 1 FROM $wpdb->postmeta $subquery_alias ";
					$meta_compare_string_start .= "WHERE $subquery_alias.post_ID = $alias.post_ID ";
					$meta_compare_string_end    = 'LIMIT 1';
					$meta_compare_string_end   .= ')';
				}

				switch ($meta_compare_key) {
					case '=':
					case 'EXISTS':
						$where = $wpdb->prepare("$alias.meta_key = %s", trim($clause['key'])); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						break;
					case 'LIKE':
						$meta_compare_value = '%' . $wpdb->esc_like(trim($clause['key'])) . '%';
						$where              = $wpdb->prepare("$alias.meta_key LIKE %s", $meta_compare_value); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						break;
					case 'IN':
						$meta_compare_string = "$alias.meta_key IN (" . substr(str_repeat(',%s', count($clause['key'])), 1) . ')';
						$where               = $wpdb->prepare($meta_compare_string, $clause['key']); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						break;
					case 'RLIKE':
					case 'REGEXP':
						$operator = $meta_compare_key;
						if (isset($clause['type_key']) && 'BINARY' === strtoupper($clause['type_key'])) {
							$cast = 'BINARY';
						} else {
							$cast = '';
						}
						$where = $wpdb->prepare("$alias.meta_key $operator $cast %s", trim($clause['key'])); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						break;

					case '!=':
					case 'NOT EXISTS':
						$meta_compare_string = $meta_compare_string_start . "AND $subquery_alias.meta_key = %s " . $meta_compare_string_end;
						$where               = $wpdb->prepare($meta_compare_string, $clause['key']); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						break;
					case 'NOT LIKE':
						$meta_compare_string = $meta_compare_string_start . "AND $subquery_alias.meta_key LIKE %s " . $meta_compare_string_end;

						$meta_compare_value = '%' . $wpdb->esc_like(trim($clause['key'])) . '%';
						$where              = $wpdb->prepare($meta_compare_string, $meta_compare_value); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						break;
					case 'NOT IN':
						$array_subclause     = '(' . substr(str_repeat(',%s', count($clause['key'])), 1) . ') ';
						$meta_compare_string = $meta_compare_string_start . "AND $subquery_alias.meta_key IN " . $array_subclause . $meta_compare_string_end;
						$where               = $wpdb->prepare($meta_compare_string, $clause['key']); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						break;
					case 'NOT REGEXP':
						$operator = $meta_compare_key;
						if (isset($clause['type_key']) && 'BINARY' === strtoupper($clause['type_key'])) {
							$cast = 'BINARY';
						} else {
							$cast = '';
						}

						$meta_compare_string = $meta_compare_string_start . "AND $subquery_alias.meta_key REGEXP $cast %s " . $meta_compare_string_end;
						$where               = $wpdb->prepare($meta_compare_string, $clause['key']); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						break;
				}

				$sql_chunks['where'][] = $where;
			}
		}

		// meta_value.
		if (array_key_exists('value', $clause)) {
			$meta_value = $clause['value'];

			if (in_array($meta_compare, array('IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'), true)) {
				if (!is_array($meta_value)) {
					$meta_value = preg_split('/[,\s]+/', $meta_value);
				}
			} elseif (is_string($meta_value)) {
				$meta_value = trim($meta_value);
			}

			switch ($meta_compare) {
				case 'IN':
				case 'NOT IN':
					$meta_compare_string = '(' . substr(str_repeat(',%s', count($meta_value)), 1) . ')';
					$where               = $wpdb->prepare($meta_compare_string, $meta_value);
					break;

				case 'BETWEEN':
				case 'NOT BETWEEN':
					$where = $wpdb->prepare('%s AND %s', $meta_value[0], $meta_value[1]);
					break;

				case 'LIKE':
				case 'NOT LIKE':
					$meta_value = '%' . $wpdb->esc_like($meta_value) . '%';
					$where      = $wpdb->prepare('%s', $meta_value);
					break;

					// EXISTS with a value is interpreted as '='.
				case 'EXISTS':
					$meta_compare = '=';
					$where        = $wpdb->prepare('%s', $meta_value);
					break;

					// 'value' is ignored for NOT EXISTS.
				case 'NOT EXISTS':
					$where = '';
					break;

				default:
					$where = $wpdb->prepare('%s', $meta_value);
					break;
			}

			if ($where) {
				if ('CHAR' === $meta_type) {
					$sql_chunks['where'][] = "$alias.meta_value {$meta_compare} {$where}";
				} else {
					$sql_chunks['where'][] = "CAST($alias.meta_value AS {$meta_type}) {$meta_compare} {$where}";
				}
			}
		}

		/*
		 * Multiple WHERE clauses (for meta_key and meta_value) should
		 * be joined in parentheses.
		 */
		if (1 < count($sql_chunks['where'])) {
			$sql_chunks['where'] = array('( ' . implode(' AND ', $sql_chunks['where']) . ' )');
		}

		return $sql_chunks;
	}
}
