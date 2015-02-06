<?php
namespace Jelito\DevStack\PHPUnit\Database;

class DefaultErrorPrinter implements ErrorPrinter
{
	/**
	 * sirka
	 * @var int
	 */
	private $primaryKeyWidth = 10;
	/**
	 * @var int
	 */
	private $lineWidth = 200;
	/**
	 * @var int
	 */
	private $messageWidth;

	/**
	 * @param string $tableName
	 * @param ComparatorError[] $errors
	 * @param array $primaryKeys
	 * @return string
	 */
	public function getString($tableName, array $errors, array $primaryKeys)
	{
		$this->countLastColumnWidth($primaryKeys);

		$lineSeparator = str_repeat('+' . str_repeat('-', $this->primaryKeyWidth + 2), count($primaryKeys));
		$lineSeparator .= str_repeat('-', $this->messageWidth + 3);
		$lineSeparator .= "+\n";

		// hlavicka
		$message = $lineSeparator;
		$message .= '| ' . str_pad($tableName, $this->lineWidth - 4, ' ', STR_PAD_RIGHT) . " |\n";
		$message .= $lineSeparator;

		// radky
		$message .= $this->getHeader($primaryKeys);
		$message .= $lineSeparator;
		foreach ($errors as $error) {
			$message .= $this->rowToString($error);
		}
		$message .= $lineSeparator;

		return $message;
	}

	private function countLastColumnWidth(array $primaryKeys)
	{
		// - 4 protoze posledni sloupec ma odsazeni 1 zleva i zprava a 2 ramecky
		// + 3 protoze kazdy sloupecek ma odsazeni 1 zleva i zprava a vpravo ramecek |
		$this->messageWidth = $this->lineWidth - 4 -(count($primaryKeys) * ($this->primaryKeyWidth + 3));
	}

	private function getHeader(array $primaryKeys)
	{
		$rowString = array();
		foreach ($primaryKeys as $name) {
			$rowString[] = $this->strPadUnicode(
				mb_substr($name, 0, $this->primaryKeyWidth, 'UTF-8'),
				$this->primaryKeyWidth,
				' ',
				STR_PAD_BOTH
			);
		}
		$rowString[] = $this->strPadUnicode(
			mb_substr('message', 0, $this->messageWidth, 'UTF-8'),
			$this->messageWidth,
			' ',
			STR_PAD_BOTH
		);

		return "| " . implode(' | ', $rowString) . " |\n";
	}

	/**
	 * @param ComparatorError $error
	 * @return string
	 */
	private function rowToString(ComparatorError $error)
	{
		$rowString = array();

		foreach ($error->primaryKeysData as $value) {
			if (is_null($value)) {
				$value = 'NULL';
			}
			$string = $this->strPadUnicode(mb_substr($value, 0, $this->primaryKeyWidth, 'UTF-8'), $this->primaryKeyWidth, ' ', STR_PAD_BOTH);
			$rowString[] = $string;
		}
		$rowString[] = $this->strPadUnicode(
			mb_substr($this->getErrorMessage($error), 0, $this->messageWidth, 'UTF-8'),
			$this->messageWidth,
			' ',
			STR_PAD_RIGHT
		);

		return "| " . implode(' | ', $rowString) . " |\n";
	}

	/**
	 * @param ComparatorError $error
	 * @return string
	 */
	private function getErrorMessage(ComparatorError $error)
	{
		switch ($error->type) {
			case ComparatorError::EXPECTED_ROW_NOT_FOUND:
				return 'EXPECTED ROW NOT FOUND';
				break;
			case ComparatorError::ACTUAL_ROW_NOT_FOUND:
				return 'ACTUAL ROW NOT FOUND';
				break;
			case ComparatorError::COLUMN_VALUE_NOT_EQUAL:
				$columnName = $this->strPadUnicode(mb_substr($error->columnName, 0, 25, 'UTF-8'), 25, ' ', STR_PAD_RIGHT);
				return "$columnName e: {$error->expectedValue}, a: {$error->actualValue}";
				break;
			default:
				return "!!! unknown error type: {$error->type} !!!";
				break;
		}
	}

	/**
	 * strpad funkce ktera podporuje utf
	 *
	 * @param string $str
	 * @param int $padLength
	 * @param string $padString
	 * @param int $dir
	 * @return string
	 */
	private function strPadUnicode($str, $padLength, $padString = ' ', $dir = STR_PAD_RIGHT)
	{
		$encoding = 'UTF-8';

		$strLength = mb_strlen($str, $encoding);
		$padStrLength = mb_strlen($padString, $encoding);
		if (!$strLength && ($dir == STR_PAD_RIGHT || $dir == STR_PAD_LEFT)) {
			$strLength = 1; // @debug
		}
		if (!$padLength || !$padStrLength || $padLength <= $strLength) {
			return $str;
		}

		$result = "";
		$repeat = ceil($strLength - $padStrLength + $padLength);
		if ($dir == STR_PAD_RIGHT) {
			$result = $str . str_repeat($padString, $repeat);
			$result = mb_substr($result, 0, $padLength, $encoding);
		} else if ($dir == STR_PAD_LEFT) {
			$result = str_repeat($padString, $repeat) . $str;
			$result = mb_substr($result, -$padLength, null, $encoding);
		} else if ($dir == STR_PAD_BOTH) {
			$length = ($padLength - $strLength) / 2;
			$repeat = ceil($length / $padStrLength);
			$result = mb_substr(str_repeat($padString, $repeat), 0, floor($length), $encoding)
				. $str
				. mb_substr(str_repeat($padString, $repeat), 0, ceil($length), $encoding);
		}

		return $result;
	}
}