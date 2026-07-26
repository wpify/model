<?php

declare( strict_types=1 );

namespace Wpify\Model\Tests\Integration;

use Wpify\Model\Attributes\Column;
use Wpify\Model\Exceptions\PrimaryKeyException;
use Wpify\Model\Exceptions\SqlException;
use Wpify\Model\Tests\Support\NoPkRecordRepository;
use Wpify\Model\Tests\Support\Record;
use Wpify\Model\Tests\Support\RecordRepository;
use Wpify\Model\Tests\TestCase;

class CustomTableTest extends TestCase {
	private RecordRepository $records;

	public function set_up(): void {
		parent::set_up();

		$this->records = new RecordRepository();
		$this->manager->register_repository( $this->records );
	}

	public function tear_down(): void {
		// The suite's query filter turns CREATE TABLE into CREATE TEMPORARY
		// TABLE; temp tables survive the transaction rollback, so drop
		// explicitly to keep tests isolated.
		global $wpdb;
		$wpdb->query( "DROP TEMPORARY TABLE IF EXISTS {$wpdb->prefix}wpify_test_records" );
		$wpdb->query( "DROP TEMPORARY TABLE IF EXISTS {$wpdb->prefix}wpify_test_no_pk" );

		parent::tear_down();
	}

	private function make_record( string $title = 'Row', int $quantity = 1, ?string $code = null ): Record {
		$record           = $this->records->create();
		$record->title    = $title;
		$record->quantity = $quantity;
		$record->code     = $code;
		$record->payload  = array( 'k' => $title );

		return $this->records->save( $record );
	}

	public function test_constructor_flags_control_prefix_and_migration(): void {
		global $wpdb;

		$unprefixed = new RecordRepository( true, false );

		$this->assertSame( 'wpify_test_records', $unprefixed->prefixed_table_name() );
		$this->assertSame( $wpdb->prefix . 'wpify_test_records', $this->records->prefixed_table_name() );
	}

	public function test_primary_key_returns_column_name_or_model_value(): void {
		$this->assertSame( 'id', $this->records->primary_key() );

		$record = $this->make_record();
		$this->assertSame( $record->id, $this->records->primary_key( $record ) );
	}

	public function test_primary_key_throws_without_exactly_one_primary_key(): void {
		$repository = new NoPkRecordRepository();
		$this->manager->register_repository( $repository );

		$this->expectException( PrimaryKeyException::class );

		$repository->primary_key();
	}

	public function test_migrate_creates_the_table(): void {
		global $wpdb;

		$this->records->migrate();

		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}wpify_test_records" );

		$this->assertContains( 'id', $columns );
		$this->assertContains( 'title', $columns );
		$this->assertContains( 'payload', $columns );
	}

	public function test_columns_describes_all_column_props(): void {
		$columns = $this->records->columns();

		$this->assertSame( array( 'id', 'title', 'quantity', 'code', 'payload', 'updated_at' ), array_keys( $columns ) );
		$this->assertSame( 'id', $columns['id']['name'] );
		$this->assertSame( 'int', $columns['id']['type'] );
		$this->assertInstanceOf( Column::class, $columns['id']['attribute'] );
		$this->assertTrue( $columns['id']['attribute']->primary_key );
	}

	public function test_query_single_returns_raw_row(): void {
		$record = $this->make_record( 'Raw row' );

		$row = $this->records->query_single( $record->id );

		$this->assertIsObject( $row );
		$this->assertSame( 'Raw row', $row->title );
	}

	public function test_get_by_id_row_object_and_model(): void {
		$record = $this->make_record( 'Gettable' );

		$by_id = $this->records->get( $record->id );
		$this->assertInstanceOf( Record::class, $by_id );
		$this->assertSame( 'Gettable', $by_id->title );

		$row       = $this->records->query_single( $record->id );
		$by_object = $this->records->get( $row );
		$this->assertSame( 'Gettable', $by_object->title );

		$this->assertSame( $record, $this->records->get( $record ) );

		$this->assertNull( $this->records->get( 0 ) );
		$this->assertNull( $this->records->get( 999999 ) );
	}

	public function test_get_throws_when_object_source_lacks_primary_key(): void {
		$this->expectException( PrimaryKeyException::class );

		$this->records->get( (object) array( 'title' => 'No id' ) );
	}

	public function test_save_inserts_updates_and_stores_json(): void {
		$record = $this->make_record( 'First', 3 );

		$this->assertGreaterThan( 0, $record->id );
		$this->assertSame( array( 'k' => 'First' ), $record->payload, 'JSON column round-trips' );

		$record->quantity = 9;
		$this->records->save( $record );

		$fresh = $this->records->get( $record->id );
		$this->assertSame( 9, $fresh->quantity );
	}

	public function test_save_throws_sql_exception_on_database_error(): void {
		$this->make_record( 'A', 1, 'dup' );

		$this->expectException( SqlException::class );

		// UNIQUE(code) violation.
		$this->make_record( 'B', 2, 'dup' );
	}

	public function test_delete_removes_row_and_throws_on_database_error(): void {
		$record = $this->make_record();

		$this->assertTrue( $this->records->delete( $record ) );
		$this->assertNull( $this->records->get( $record->id ) );

		// Deleting a model with an empty primary key short-circuits.
		$empty = $this->records->create();
		$this->assertFalse( $this->records->delete( $empty ) );

		// Against a dropped table (no auto-migrate), wpdb errors out.
		$no_migrate = new RecordRepository( false );
		$this->manager->register_repository( $no_migrate );
		$this->records->drop_table();

		$this->expectException( SqlException::class );

		$no_migrate->delete( $record );
	}

	public function test_drop_table(): void {
		$this->records->migrate();

		$this->assertTrue( (bool) $this->records->drop_table() );
	}

	public function test_find_supports_where_order_limit_offset_and_nesting(): void {
		$this->make_record( 'Alpha', 1, 'a' );
		$this->make_record( 'Beta', 5, 'b' );
		$this->make_record( 'Gamma', 9, 'c' );

		$all = $this->records->find_all();
		$this->assertCount( 3, $all );

		$big = $this->records->find( array( 'where' => array( 'quantity >' => 4 ) ) );
		$this->assertCount( 2, $big );

		$in = $this->records->find( array( 'where' => array( 'code' => array( 'a', 'c' ) ) ) );
		$this->assertCount( 2, $in );

		$like = $this->records->find( array( 'where' => array( 'title LIKE' => 'Al%' ) ) );
		$this->assertCount( 1, $like );
		$this->assertSame( 'Alpha', $like[0]->title );

		$or = $this->records->find(
			array( 'where' => array( 'OR', array( 'title' => 'Alpha' ), array( 'title' => 'Gamma' ) ) )
		);
		$this->assertCount( 2, $or );

		$between = $this->records->find( array( 'where' => array( 'quantity BETWEEN' => array( 2, 6 ) ) ) );
		$this->assertCount( 1, $between );
		$this->assertSame( 'Beta', $between[0]->title );

		$ordered = $this->records->find( array( 'order_by' => 'quantity DESC', 'limit' => 2 ) );
		$this->assertSame( array( 'Gamma', 'Beta' ), array_map( fn( $r ) => $r->title, $ordered ) );

		$offset = $this->records->find( array( 'order_by' => 'quantity DESC', 'limit' => 2, 'offset' => 2 ) );
		$this->assertCount( 1, $offset );
		$this->assertSame( 'Alpha', $offset[0]->title );

		$null_code = $this->make_record( 'Delta', 2 );
		$nulls     = $this->records->find( array( 'where' => array( 'code' => null ) ) );
		$this->assertCount( 1, $nulls );
		$this->assertSame( $null_code->id, $nulls[0]->id );
	}

	public function test_find_throws_for_malformed_between_condition(): void {
		$this->records->migrate();

		$this->expectException( SqlException::class );

		$this->records->find( array( 'where' => array( 'quantity BETWEEN' => array( 1 ) ) ) );
	}

	public function test_find_throws_sql_exception_for_invalid_sql(): void {
		$this->records->migrate();

		$this->expectException( SqlException::class );

		$this->records->find( array( 'where' => 'nonexistent_column = 1' ) );
	}

	public function test_find_by_ids(): void {
		$first = $this->make_record( 'One' );
		$this->make_record( 'Two' );
		$third = $this->make_record( 'Three' );

		$found = $this->records->find_by_ids( array( $first->id, $third->id ) );

		$this->assertSame( array( 'One', 'Three' ), array_map( fn( $r ) => $r->title, $found ) );
	}

	public function test_create_column_sql_with_explicit_types(): void {
		$id_sql = ( new Column( type: Column::BIGINT, unsigned: true, auto_increment: true, primary_key: true ) )
			->create_column_sql( 'id', 'int' );
		$this->assertSame( 'id bigint(21) UNSIGNED NOT NULL AUTO_INCREMENT', $id_sql );

		$title_sql = ( new Column( type: Column::VARCHAR, params: 100 ) )->create_column_sql( 'title', 'string' );
		$this->assertSame( 'title varchar(100) NOT NULL', $title_sql );

		$nullable_sql = ( new Column( type: Column::VARCHAR ) )->create_column_sql( 'code', 'string' );
		$this->assertSame( 'code varchar(255) NULL', ( new Column( type: Column::VARCHAR, nullable: true ) )->create_column_sql( 'code', 'string' ) );
		$this->assertSame( 'code varchar(255) NOT NULL', $nullable_sql );

		$stamp_sql = ( new Column( type: Column::TIMESTAMP, default: 'CURRENT_TIMESTAMP', on_update: 'CURRENT_TIMESTAMP' ) )
			->create_column_sql( 'updated_at', 'string' );
		$this->assertSame( 'updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', $stamp_sql );
	}

	/**
	 * Deriving the SQL type from the PHP property type is broken: the SQL is
	 * built before the derived type is assigned.
	 *
	 * @see https://github.com/wpify/model/issues/37
	 */
	public function test_create_column_sql_derives_type_from_php_type(): void {
		$this->markTestSkipped( 'Column::create_column_sql() derives the type after embedding it — https://github.com/wpify/model/issues/37' );
	}

	public function test_column_get_reads_from_source_row(): void {
		$record = $this->make_record( 'Attribute read', 7 );

		$fresh = $this->records->get( $record->id );

		$this->assertSame( 'Attribute read', $fresh->title );
		$this->assertSame( 7, $fresh->quantity );
		$this->assertSame( array( 'k' => 'Attribute read' ), $fresh->payload );
	}
}
