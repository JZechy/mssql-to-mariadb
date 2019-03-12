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
	public function insert(string $tableName, array $columns, Row $data): void {
		$insertData = [];
		foreach($columns as $column) {
			$columnName = $column->COLUMN_NAME;
			$value = $data->$columnName;
			/*if(is_string($value)) {
				$value = EncodingHelper::toUTF8($value);
			}*/

			$insertData[$columnName] = $value;
		}

		$this->connection->query("insert into `$tableName` %values", $insertData);
	}
}