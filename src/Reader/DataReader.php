<?php declare(strict_types=1);

namespace Zet\DbMigration\Reader;

use Nextras\Dbal\Connection;
use Nextras\Dbal\Result\Row;

/**
 * Class DataReader
 * @author  Zechy <email@zechy.cz>
 * @package Zet\DbMigration\Reader
 */
class DataReader {

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * DataReader constructor.
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param string $tableName
	 * @return int
	 * @throws \Nextras\Dbal\QueryException
	 */
	public function count(string $tableName): int {
		$result = $this->connection->query("select count(*) as 'count' from [$tableName]")->fetch();

		return (int) $result->count;
	}

	/**
	 * @param string      $tableName
	 * @param int         $limit
	 * @param int         $offset
	 * @param string|null $primaryKey
	 * @return Row[]
	 * @throws \Nextras\Dbal\QueryException
	 */
	public function select(string $tableName, int $limit, int $offset, ?string $primaryKey = null): array {
		if($primaryKey !== null) {
			return $this->connection->query("
				select * from [$tableName] 
				order by $primaryKey 
				offset $offset rows 
				fetch next $limit rows only
			")->fetchAll();
		} else {
			return $this->connection->query("select * from [$tableName]")->fetchAll();
		}
	}
}