<?php

namespace Wpify\Model\Abstracts;

use Wpify\Model\Interfaces\RepositoryInterface;

abstract class AbstractDbTableRepository extends AbstractRepository implements RepositoryInterface {
	public $db;
	public $db_table;

	public function __construct() {
		global $wpdb;
		$this->db       = $wpdb;
		$this->db_table = $this::table();
	}

	abstract public static function table(): string;

	public function all() {
		return $this->db->query( "SELECT * FROM {$this->db_table}" );
	}

	public function find( array $args = array() ) {
		$defaults = [
			'include_deleted' => false,
		];

		$args  = wp_parse_args( $args, $defaults );
		$where = 'WHERE 1 = 1';
		$query = "SELECT * FROM {$this->db_table} ";
		if ( ! empty( $args['where'] ) ) {
			$where .= ' AND ' . $args['where'];
		}
		if ( ! $args['include_deleted'] ) {
			$where .= ' AND deleted_at IS NULL';
		}

		$query .= $where;

		if (!empty($args['order_by'])) {
			$query .= ' ORDER BY ' . $args['order_by'];
		}
		if (!empty($args['limit'])) {
			$query .= ' LIMIT ' . $args['limit'];
		}

		$collection = array();

		$data = $this->db->get_results( $query );
		foreach ( $data as $item ) {
			$collection[] = $this->factory( $item );
		}

		return $this->collection_factory( $collection );
	}


	protected function resolve_object( $data = null) {
		$object = null;
		if ( \is_object( $data ) && \get_class( $data ) === $this->model() ) {
			$object = $data->source_object();
		} else if ( is_object( $data ) ) {
			$object = $data;
		} else if ( is_numeric( $data ) ) {
			$row = $this->get_db_row( 'id', $data );
			if ( $row ) {
				$object = $row;
			}
		}

		return $object;
	}

	public function get_db_row( $column, $value ) {
		return $this->db->get_row(
			$this->db->prepare( "SELECT * FROM {$this->db_table} WHERE {$column} = %s", $value )
		);
	}


	public function delete( $model, $force = true ) {
		if ( $force ) {
			return $this->db->delete(
				$this->db_table,
				[
					'id' => $model->id,
				]
			);
		} else {
			return $this->db->update(
				$this->db_table,
				[
					'deleted_at' => date( 'Y-m-d H:i:s' ),
				],
				[
					'id' => $model->id,
				]
			);
		}
	}

	/**
	 * @param AbstractDbTableModel $model
	 *
	 * @return AbstractDbTableModel
	 * @throws \Exception
	 */
	public function save( $model ): AbstractDbTableModel {
		if ( $model->id ) {
			$result = $this->db->update(
				$this->db_table,
				$model->get_db_data(),
				[ 'id' => $model->id ]
			);
			if ( is_wp_error( $result ) ) {
				throw new \Exception( $result->get_error_message() );
			}
		} else {
			$result = $this->db->insert(
				$this->db_table,
				$model->get_db_data()
			);

			if ( is_wp_error( $result ) ) {
				throw new \Exception( $result->get_error_message() );
			}
			$model->id = $this->db->insert_id;
		}

		return $model;
	}

	public function get_by( $field, $value ) {
		return $this->find( [ 'where' => $this->db->prepare( "{$field} = %s", $value ) ] );
	}

	public function create(): AbstractDbTableModel {
		return $this->factory( null );
	}

	/**
	 * @param ?object $object
	 */
	public function get( $object = null ) {
		return ! empty( $object ) ? $this->factory( $object ) : null;
	}
}
