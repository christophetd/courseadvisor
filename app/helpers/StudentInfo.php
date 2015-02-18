<?php namespace Helpers;

use \Tequila;

class StudentInfo {

	public static function getFullSection() {
		return explode(',', Tequila::get('unit'))[0];
	}
	public static function getSection() {
		return explode('-', self::getFullSection())[0];
	}

	public static function getSemester() {
		return explode('-', self::getFullSection())[1];
	}

	public static function getLowerSemesters() {
		$current = self::getSemester();
		$semesters = ['BA1', 'BA2', 'BA3', 'BA4', 'BA5', 'BA6', 'MA1', 'MA2', 'MA3', 'MA4'];
		for($i = 0; $i < sizeof($semesters); ++$i) {
			if($current == $semesters[$i]) {
				return array_slice($semesters, 0, $i);
			}
		}
	}
}