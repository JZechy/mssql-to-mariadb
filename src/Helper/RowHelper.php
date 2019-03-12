<?php declare(strict_types=1);

namespace Zet\DbMigration\Helper;

use Nextras\Dbal\Result\Row;

/**
 * Class RowHelper
 * @author  Zechy <email@zechy.cz>
 * @package Zet\DbMigration\Helper
 */
class RowHelper {

	/**
	 * @param Row $row
	 * @return string
	 */
	public static function stringifyRow(Row $row): string {
		$string = "{ ";
		$data = [];
		foreach($row->toArray() as $key => $value) {
			$data[] = "$key: $value";
		}
		$string .= implode("|", $data) . " }";

		return $string;
	}
}