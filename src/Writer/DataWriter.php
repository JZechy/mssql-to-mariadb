<?php declare(strict_types=1);

namespace Zet\DbMigration\Writer;

use Nextras\Dbal\Connection;
use Nextras\Dbal\Result\Row;

/**
 * Class DataWriter
 * @author  Zechy <email@zechy.cz>
 * @package Zet\DbMigration\Writer
 */
class DataWriter {

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * DataWriter constructor.
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param string $tableName
	 * @param array  $columns
	 * @param Row    $data
	 * @throws \Nextras\Dbal\QueryException
	 */
	public function insert(string $tableName, array $columns, Row $data) {
		$insertData = [];
		foreach($columns as $column) {
			$columnName = $column->COLUMN_NAME;
			$insertData[$columnName] = $data->$columnName;
		}

		$this->connection->query("insert into `$tableName` %values", $insertData);
	}
}