<?php declare(strict_types=1);

namespace Zet\DbMigration\Helper;

/**
 * Class EncodingHelper
 * @author  Zechy <email@zechy.cz>
 * @package Zet\DbMigration\Helper
 */
class EncodingHelper {

	/**
	 * @param string $text
	 * @return string
	 */
	public static function toUTF8(string $text): string {
		$encoding = mb_detect_encoding($text, mb_detect_order(), false);

		if ($encoding == "UTF-8") {
			$text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
		}

		$out = iconv(mb_detect_encoding($text, mb_detect_order(), false), "UTF-8//IGNORE", $text);

		return $out;
	}
}