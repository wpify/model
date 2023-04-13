<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

declare( strict_types=1 );

namespace Wpify\Model\Attributes;

use Attribute;
use Wpify\Model\Interfaces\ModelInterface;
use Wpify\Model\Interfaces\SourceAttributeInterface;

#[Attribute( Attribute::TARGET_PROPERTY )]
class Column implements SourceAttributeInterface {
	const TINYINT = 'tinyint';
	const INT = 'int';
	const BIGINT = 'bigint';
	const BOOLEAN = 'boolean';
	const DECIMAL = 'decimal';
	const DATE = 'date';
	const DATETIME = 'datetime';
	const TIMESTAMP = 'timestamp';
	const TIME = 'time';
	const CHAR = 'char';
	const VARCHAR = 'varchar';
	const BLOB = 'blob';
	const TEXT = 'text';
	const ENUM = 'enum';
	const SET = 'set';
	const JSON = 'json';

	public function __construct(
		public string $name = '',
		public string $type = '',
		public $params = null,
		public bool $unsigned = false,
		public bool $nullable = false,
		public bool $auto_increment = false,
		public bool $primary_key = false,
		public bool $unique = false,
	) {

	}

	public function create_column_sql( string $key, string $type ): string {
		$key = $this->name ?: $key;
		$sql = "{$key} {$this->type}";

		if ( empty( $this->type ) ) {
			if ( $type === 'bool' ) {
				$this->type = self::BOOLEAN;
			} elseif ( $type === 'int' ) {
				$this->type = self::INT;
			} elseif ( $type === 'float' ) {
				$this->type = self::DECIMAL;
			} elseif ( $type === 'string' ) {
				$this->type = self::VARCHAR;
			} elseif ( $type === 'array' || $type === 'object' ) {
				$this->type = self::TEXT;
			}
		}

		if ( empty( $this->params ) ) {
			if ( $this->type === self::VARCHAR ) {
				$this->params = 255;
			}

			if ( $this->type === self::CHAR ) {
				$this->params = 1;
			}

			if ( $this->type === self::TINYINT ) {
				$this->params = 4;
			}

			if ( $this->type === self::INT ) {
				$this->params = 11;
			}

			if ( $this->type === self::BIGINT ) {
				$this->params = 21;
			}

			if ( $this->type === self::DECIMAL ) {
				$this->params = array( 65, 10 );
			}
		}

		if ( ! empty( $this->params ) ) {
			if ( is_array( $this->params ) ) {
				$sql .= '(' . implode( ',', $this->params ) . ')';
			} else {
				$sql .= "({$this->params})";
			}
		}

		if ( $this->unsigned ) {
			$sql .= ' UNSIGNED';
		}

		if ( $this->nullable ) {
			$sql .= ' NULL';
		} else {
			$sql .= ' NOT NULL';
		}

		if ( $this->auto_increment ) {
			$sql .= ' AUTO_INCREMENT';
		}

		return $sql;
	}

	public function get( ModelInterface $model, string $key ): mixed {
		$current = $model->source();
		$value   = null;

		if ( $current && isset( $current->$key ) ) {
			$value = maybe_unserialize( $current->$key );
		}

		if ( $this->type === self::JSON && is_string( $value ) ) {
			$value = json_decode( $value, true );
		}

		return $value;
	}
}
