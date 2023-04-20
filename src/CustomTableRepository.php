<?php
declare( strict_types=1 );

namespace Wpify\Model;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use Wpify\Model\Attributes\Column;
use Wpify\Model\Exceptions\KeyNotFoundException;
use Wpify\Model\Exceptions\PrimaryKeyException;
use Wpify\Model\Exceptions\RepositoryNotInitialized;
use Wpify\Model\Exceptions\SqlException;
use Wpify\Model\Interfaces\ModelInterface;
use wpdb;

/**
 * Base class for custom table repositories.
 *
 * Extend this class to create a custom repository for your model from database table.
 */
abstract class CustomTableRepository extends Repository {
	/**
	 * Repository constructor.
	 *
	 * @param bool $auto_migrate Whether to automatically migrate the table when the repository is used. Default is true.
	 * @param bool $use_prefix Whether to use the WordPress table prefix for the table name. Default is true.
	 */
	public function __construct(
		private bool $auto_migrate = true,
		private bool $use_prefix = true
	) {
	}

	/**
	 * Gets the model class reflection.
	 *
	 * @return ReflectionClass
	 * @throws ReflectionException
	 */
	private function reflection(): ReflectionClass {
		static $reflection;

		if ( empty( $reflection ) ) {
			$reflection = new ReflectionClass( $this->model() );
		}

		return $reflection;
	}

	/**
	 * Generates the SQL for the table creation.
	 *
	 * @return string
	 * @throws PrimaryKeyException
	 * @throws ReflectionException
	 */
	private function create_table_sql(): string {
		static $sql;

		if ( empty( $sql ) ) {
			$columns     = array();
			$unique_keys = array();

			foreach ( $this->columns() as $column ) {
				$columns[] = $column['attribute']->create_column_sql( $column['name'], $column['type'] );

				if ( $column['attribute']->unique ) {
					$unique_keys[] = $column['name'];
				}
			}

			if ( count( $unique_keys ) > 0 ) {
				foreach ( $unique_keys as $unique_key ) {
					$columns[] = sprintf( "UNIQUE KEY (%s)", $unique_key );
				}
			}

			$columns[] = sprintf( "PRIMARY KEY  (%s)", $this->primary_key() );

			$sql = sprintf(
				"CREATE TABLE %s (\n\t%s\n) %s;",
				$this->prefixed_table_name(),
				implode( ",\n\t", $columns ),
				$this->db()->get_charset_collate(),
			);
		}

		return $sql;
	}

	/**
	 * Returns the primary key for the table. If the model is passed in, it will return the value of the primary key.
	 *
	 * @param ModelInterface|null $model The model to get the primary key value from.
	 *
	 * @return string
	 * @throws PrimaryKeyException
	 * @throws ReflectionException
	 */
	private function primary_key( ?ModelInterface $model = null ): mixed {
		static $primary_keys;

		if ( empty( $primary_keys ) ) {
			$primary_keys = array();

			foreach ( $this->columns() as $column ) {
				if ( $column['attribute']->primary_key ) {
					$primary_keys[ $column['property'] ] = $column['name'];
				}
			}
		}

		if ( count( $primary_keys ) !== 1 ) {
			throw new PrimaryKeyException( 'The model ' . $this->model() . ' must contain at exactly one primary key column.' );
		}

		foreach ( $primary_keys as $property => $column ) {
			if ( $model ) {
				return $model->$property;
			} else {
				return $column;
			}
		}

		return '';
	}

	/**
	 * Returns the version of the table.
	 *
	 * @return string
	 * @throws PrimaryKeyException
	 * @throws ReflectionException
	 */
	private function version(): string {
		static $version;

		if ( empty( $version ) ) {
			$version = md5( $this->create_table_sql() );
		}

		return $version;
	}

	/**
	 * Returns or sets the installed version of the table.
	 *
	 * @param string $new_version If a new version is passed in, it will be saved.
	 *
	 * @return string
	 */
	private function current_version( string $new_version = '' ): string {
		static $current_version;

		$option_name = 'custom_table_' . $this->table_name() . '_version';

		if ( ! empty( $new_version ) ) {
			update_option( $option_name, $new_version );

			$current_version = $new_version;
		}

		if ( empty( $current_version ) ) {
			$current_version = get_option( $option_name );
		}

		if ( ! is_string( $current_version ) ) {
			$current_version = '';
		}

		return $current_version;
	}

	/**
	 * Returns the table name without WordPress table prefix.
	 *
	 * @return string
	 */
	abstract public function table_name(): string;

	/**
	 * Returns prefixed table name. If the prefix is disabled, it will return the table name without prefix.
	 *
	 * @return string
	 */
	private function prefixed_table_name(): string {
		if ( $this->use_prefix ) {
			return $this->db()->prefix . $this->table_name();
		}

		return $this->table_name();
	}

	/**
	 * Migrates the table to the latest version if it is not already.
	 *
	 * @return void
	 * @throws PrimaryKeyException
	 * @throws ReflectionException
	 */
	public function migrate(): void {
		static $migrated;

		if ( $migrated ) {
			return;
		}

		if ( $this->version() === $this->current_version() ) {
			return;
		}

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta( $this->create_table_sql() );

		dump( $this->create_table_sql() );

		$this->current_version( $this->version() );

		$migrated = true;
	}

	/**
	 * Migrates the table if auto migration is enabled.
	 *
	 * @return void
	 * @throws PrimaryKeyException
	 * @throws ReflectionException
	 */
	protected function auto_migrate(): void {
		if ( $this->auto_migrate ) {
			$this->migrate();
		}
	}

	/**
	 * Returns the database object.
	 *
	 * @return wpdb
	 */
	protected function db(): wpdb {
		global $wpdb;

		return $wpdb;
	}

	/**
	 * Returns the columns for the table.
	 *
	 * @return array{ property:string, name:string, type:string, attribute:Column }[]
	 * @throws ReflectionException
	 */
	public function columns(): array {
		static $columns;

		if ( empty( $columns ) ) {
			$columns = array();

			foreach ( $this->reflection()->getProperties() as $property ) {
				foreach ( $property->getAttributes( Column::class, ReflectionAttribute::IS_INSTANCEOF ) as $attribute ) {
					/** @var Column $column */
					$column = $attribute->newInstance();

					$columns[ $property->getName() ] = array(
						'property'  => $property->getName(),
						'name'      => empty( $column->name ) ? $property->getName() : $column->name,
						'type'      => $property->getType()->getName(),
						'attribute' => $column,
					);
				}
			}
		}

		return $columns;
	}

	/**
	 * Returns a single result from the database by its primary key(s).
	 *
	 * @param mixed $source
	 *
	 * @return object|null
	 * @throws KeyNotFoundException
	 * @throws PrimaryKeyException
	 * @throws ReflectionException
	 * @throws RepositoryNotInitialized
	 * @throws SqlException
	 */
	public function query_single( mixed $source ): ?object {
		if ( empty( $source ) ) {
			return null;
		}

		$items = $this->find( array(
			'where' => sprintf( '`%s` = \'%s\'', $this->primary_key(), esc_sql( $source ) ),
			'limit' => 1,
		) );

		foreach ( $items as $item ) {
			return $item;
		}

		return null;
	}

	/**
	 * Returns a model instance from the source by its primary key.
	 *
	 * @param mixed $source
	 *
	 * @return ModelInterface|null
	 * @throws KeyNotFoundException
	 * @throws PrimaryKeyException
	 * @throws ReflectionException
	 * @throws RepositoryNotInitialized
	 * @throws SqlException
	 */
	public function get( mixed $source ): ?ModelInterface {
		if ( empty( $source ) ) {
			return null;
		}

		if ( is_object( $source ) ) {
			$db_item     = $source;
			$primary_key = $this->primary_key();

			if ( ! isset( $source->$primary_key ) ) {
				throw new PrimaryKeyException( 'The source must contain a primary key ' . $primary_key );
			}

			$storage_key = $source->$primary_key;
		} else {
			$db_item     = $this->query_single( $source );
			$storage_key = $source;
		}

		if ( empty( $db_item ) ) {
			return null;
		}

		$model_class = $this->model();
		$item        = new $model_class( $this->manager() );

		$item->source( $db_item );

		if ( ! empty( $storage_key ) ) {
			$this->storage()->save( $storage_key, $item );
		}

		return $item;
	}

	/**
	 * Drops the table from the database.
	 *
	 * @return bool
	 */
	public function drop_table(): bool {
		return $this->db()->query( sprintf( 'DROP TABLE IF EXISTS `%s`', $this->prefixed_table_name() ) );
	}

	/**
	 * Updates or inserts a model into the database.
	 *
	 * If the model has a source, it will be updated, otherwise it will be inserted. If the model has a primary key, it will be used to find the row to update.
	 *
	 * @param ModelInterface $model
	 *
	 * @return ModelInterface
	 * @throws KeyNotFoundException
	 * @throws PrimaryKeyException
	 * @throws ReflectionException
	 * @throws RepositoryNotInitialized
	 * @throws SqlException
	 */
	public function save( ModelInterface $model ): ModelInterface {
		$this->auto_migrate();

		$data  = array();
		$where = array();

		foreach ( $this->columns() as $column ) {
			/** @var Column $attribute */
			$attribute = $column['attribute'];

			if ( $attribute->primary_key ) {
				$where[ $column['name'] ] = maybe_serialize( $model->{$column['property']} ?? null );
			} elseif ( $column['attribute']->type === Column::JSON ) {
				$data[ $column['name'] ] = wp_json_encode( $model->{$column['property']} ?? null );
			} else {
				$data[ $column['name'] ] = maybe_serialize( $model->{$column['property']} ?? null );
			}
		}

		if ( $model->source() ) {
			$result = $this->db()->update( $this->prefixed_table_name(), $data, $where );

			$this->storage()->delete( $this->primary_key( $model ) );
		} else {
			$result = $this->db()->insert( $this->prefixed_table_name(), array_merge( $where, $data ) );

			if ( $this->db()->insert_id && count( $where ) === 1 ) {
				foreach ( $where as $key => $value ) {
					$where[ $key ] = $this->db()->insert_id;
				}
			}

			$model->{$this->primary_key()} = $this->db()->insert_id;
		}

		if ( false === $result ) {
			throw new SqlException( $this->db()->last_error );
		}

		$model->refresh( $this->query_single( $this->primary_key( $model ) ) );

		return $model;
	}

	/**
	 * Deletes a model from the database. The model must have a primary key.
	 *
	 * @param ModelInterface $model
	 *
	 * @return bool
	 * @throws PrimaryKeyException
	 * @throws ReflectionException
	 * @throws SqlException
	 */
	public function delete( ModelInterface $model ): bool {
		$this->auto_migrate();

		if ( empty( $this->primary_key( $model ) ) ) {
			return false;
		}

		$result = $this->db()->delete( $this->prefixed_table_name(), array(
			'where' => array( $this->primary_key() => $this->primary_key( $model ) ),
			'limit' => 1,
		) );

		if ( false === $result ) {
			throw new SqlException( $this->db()->last_error );
		}

		return $result > 0;
	}

	/**
	 * Returns a list of models from the database based on the given arguments.
	 *
	 * Arguments:
	 * - where: string|array
	 * - order_by: string|array
	 * - limit: int
	 * - offset: int
	 * - group_by: string|array
	 * - having: string|array
	 * - distinct: bool
	 * - count: bool
	 *
	 * @param array $args
	 *
	 * @return ModelInterface[]
	 * @throws KeyNotFoundException
	 * @throws PrimaryKeyException
	 * @throws ReflectionException
	 * @throws RepositoryNotInitialized
	 * @throws SqlException
	 */
	public function find( array $args = array() ): array {
		$this->auto_migrate();

		$columns = implode( ',', array_map( fn( $column ) => $column['name'], $this->columns() ) );
		$query   = "SELECT {$columns} FROM {$this->prefixed_table_name()}";

		if ( ! empty( $args['count'] ) ) {
			$query = str_replace( 'SELECT', 'SELECT COUNT(*)', $query );
		}

		if ( ! empty( $args['distinct'] ) ) {
			$query = str_replace( 'SELECT', 'SELECT DISTINCT', $query );
		}

		if ( ! empty( $args['where'] ) ) {
			if ( is_array( $args['where'] ) ) {
				$args['where'] = join( ' AND ', $args['where'] );
			}

			$query .= ' WHERE ' . $args['where'];
		}

		if ( ! empty( $args['order_by'] ) ) {
			if ( is_array( $args['order_by'] ) ) {
				$args['order_by'] = join( ', ', $args['order_by'] );
			}

			$query .= ' ORDER BY ' . $args['order_by'];
		}

		if ( ! empty( $args['limit'] ) ) {
			$query .= ' LIMIT ' . $args['limit'];
		}

		if ( ! empty( $args['offset'] ) ) {
			$query .= ' OFFSET ' . $args['offset'];
		}

		if ( ! empty( $args['group_by'] ) ) {
			if ( is_array( $args['group_by'] ) ) {
				$args['group_by'] = join( ', ', $args['group_by'] );
			}

			$query .= ' GROUP BY ' . $args['group_by'];
		}

		if ( ! empty( $args['having'] ) ) {
			if ( is_array( $args['having'] ) ) {
				$args['having'] = join( ' AND ', $args['having'] );
			}

			$query .= ' HAVING ' . $args['having'];
		}

		$data = $this->db()->get_results( $query );

		if ( $this->db()->last_error ) {
			throw new SqlException( $this->db()->last_error );
		}

		$items = array();

		foreach ( $data as $item ) {
			$items[] = $this->get( $item );
		}

		return $items;
	}

	/**
	 * Returns a list of models by their primary keys.
	 *
	 * If the model has a composite primary key, the ids must be an array of arrays.
	 *
	 * @param array $ids
	 *
	 * @return ModelInterface[]
	 * @throws KeyNotFoundException
	 * @throws PrimaryKeyException
	 * @throws ReflectionException
	 * @throws RepositoryNotInitialized
	 * @throws SqlException
	 */
	public function find_by_ids( array $ids ): array {
		$primary_key = $this->primary_key();

		return $this->find( array(
			'where' => array(
				"{$primary_key} IN (" . join( ',', $ids ) . ')'
			)
		) );
	}

	/**
	 * Returns all items from the database.
	 *
	 * @param array $args
	 *
	 * @return ModelInterface[]
	 * @throws KeyNotFoundException
	 * @throws PrimaryKeyException
	 * @throws ReflectionException
	 * @throws RepositoryNotInitialized
	 * @throws SqlException
	 */
	public function find_all( array $args = array() ): array {
		return $this->find( $args );
	}
}