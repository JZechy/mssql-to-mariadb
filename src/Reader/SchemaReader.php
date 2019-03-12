<?php declare(strict_types=1);

namespace Zet\DbMigration\Reader;

use Nextras\Dbal\Connection;
use Nextras\Dbal\Result\Row;

/**
 * Class SchemaReader
 * @author  Zechy <email@zechy.cz>
 * @package Zet\DbMigration\Reader
 */
class SchemaReader {

	/**
	 * @var string
	 */
	const SCHEMA_PATH = "INFORMATION_SCHEMA";

	/**
	 * @var string
	 */
	const TABLES = "TABLES";

	/**
	 * @var string
	 */
	const COLUMNS = "COLUMNS";

	/**
	 * @var string
	 */
	const TABLE_CONSTRAINTS = "TABLE_CONSTRAINTS";

	/**
	 * @var string
	 */
	const CONSTRAINT_COLUMN_USAGE = "CONSTRAINT_COLUMN_USAGE";

	/**
	 * @var string
	 */
	const REFERENTIAL_CONSTRAINTS = "REFERENTIAL_CONSTRAINTS";

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * SchemaReader constructor.
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	private function getSchemaTable(string $name): string {
		return sprintf("[%s].[%s]", self::SCHEMA_PATH, $name);
	}

	/**
	 * @return array
	 * @throws \Nextras\Dbal\QueryException
	 */
	public function getDatabaseTables(): array {
		$tables = $this->connection->query(
			"SELECT [TABLE_NAME] FROM %raw WHERE [TABLE_TYPE] = 'BASE TABLE' ORDER BY [TABLE_NAME] ASC",
			$this->getSchemaTable(self::TABLES)
		);

		return $tables->fetchAll();
	}

	/**
	 * @param string $tableName
	 * @return array
	 * @throws \Nextras\Dbal\QueryException
	 */
	public function getTableColumns(string $tableName): array {
		$columns = $this->connection->query(
			"SELECT [COLUMN_NAME], [COLUMN_DEFAULT], [IS_NULLABLE], [DATA_TYPE], [CHARACTER_MAXIMUM_LENGTH], [NUMERIC_PRECISION], [NUMERIC_SCALE]
			FROM %raw
			WHERE [TABLE_NAME] = %s
			ORDER BY [ORDINAL_POSITION] ASC",
			$this->getSchemaTable(self::COLUMNS), $tableName
		);

		return $columns->fetchAll();
	}

	/**
	 * @param string $tableName
	 * @param string $columnName
	 * @return bool
	 * @throws \Nextras\Dbal\QueryException
	 */
	public function isIdentity(string $tableName, string $columnName): bool {
		$row = $this->connection->query(
			"SELECT [COLUMN_NAME]
			FROM %raw
			WHERE COLUMNPROPERTY(object_id(TABLE_SCHEMA+'.'+TABLE_NAME), COLUMN_NAME, 'IsIdentity') = 1
			AND [TABLE_NAME] = %s AND [COLUMN_NAME] = %s",
			$this->getSchemaTable(self::COLUMNS), $tableName, $columnName
		)->fetch();

		return $row !== null;
	}

	/**
	 * @param string $tableName
	 * @return array
	 * @throws \Nextras\Dbal\QueryException
	 */
	public function getPrimaryKey(string $tableName): array {
		$result = $this->connection->query(
			"SELECT a.[COLUMN_NAME] as 'COLUMN_NAME'
			FROM %raw a
			JOIN %raw b ON a.[CONSTRAINT_NAME] = b.[CONSTRAINT_NAME]
			WHERE b.[CONSTRAINT_TYPE] = 'PRIMARY KEY' AND a.[TABLE_NAME] = %s",
			$this->getSchemaTable(self::CONSTRAINT_COLUMN_USAGE), $this->getSchemaTable(self::TABLE_CONSTRAINTS), $tableName
		);

		return $result->fetchAll();
	}

	/**
	 * @param string $tableName
	 * @return array
	 * @throws \Nextras\Dbal\QueryException
	 */
	public function getUniqueKeys(string $tableName): array {
		$result = $this->connection->query(
			"SELECT [CONSTRAINT_NAME]
			FROM %raw 
			WHERE [CONSTRAINT_TYPE] = 'UNIQUE' AND [TABLE_NAME] = %s",
			$this->getSchemaTable(self::TABLE_CONSTRAINTS), $tableName
		);

		return $result->fetchAll();
	}

	/**
	 * @param string $constraintName
	 * @return array
	 * @throws \Nextras\Dbal\QueryException
	 */
	public function getUniqueColumns(string $constraintName): array {
		$result = $this->connection->query(
			"SELECT [COLUMN_NAME]
			FROM %raw
			WHERE [CONSTRAINT_NAME] = %s",
			$this->getSchemaTable(self::CONSTRAINT_COLUMN_USAGE), $constraintName
		);

		return $result->fetchAll();
	}

	/**
	 * @param string $tableName
	 * @return array
	 * @throws \Nextras\Dbal\QueryException
	 */
	public function getTableConstraints(string $tableName): array {
		$result = $this->connection->query(
			"SELECT [CONSTRAINT_NAME]
			FROM %raw
			WHERE [TABLE_NAME] = %s AND [CONSTRAINT_TYPE] = 'FOREIGN KEY'",
			$this->getSchemaTable(self::TABLE_CONSTRAINTS), $tableName
		);

		return $result->fetchAll();
	}

	/**
	 * @param string $constraintName
	 * @return Row
	 * @throws \Nextras\Dbal\QueryException
	 */
	public function getConstraintDefinition(string $constraintName): Row {
		$result = $this->connection->query(
			"SELECT [TABLE_NAME], [COLUMN_NAME]
			FROM %raw
			WHERE [CONSTRAINT_NAME] = %s",
			$this->getSchemaTable(self::CONSTRAINT_COLUMN_USAGE), $constraintName
		);

		return $result->fetch();
	}

	/**
	 * @param string $constraintName
	 * @return Row
	 * @throws \Nextras\Dbal\QueryException
	 */
	public function getConstraintTarget(string $constraintName): Row {
		$result = $this->connection->query(
			"SELECT a.[TABLE_NAME] as 'TABLE_NAME', a.[COLUMN_NAME] as 'COLUMN_NAME'
			FROM %raw a
			JOIN %raw b ON a.[CONSTRAINT_NAME] = b.[UNIQUE_CONSTRAINT_NAME]
			WHERE b.[CONSTRAINT_NAME] = %s",
			$this->getSchemaTable(self::CONSTRAINT_COLUMN_USAGE), $this->getSchemaTable(self::REFERENTIAL_CONSTRAINTS), $constraintName
		);

		return $result->fetch();
	}
}