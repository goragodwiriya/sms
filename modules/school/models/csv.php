<?php
/**
 * @filesource modules/school/models/csv.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Csv;

use Kotchasan\Language;

/**
 * CSV Header
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * คืนค่า CSV Header ของ student
     *
     * @return array
     */
    public static function student()
    {
        $header = array(
            Language::get('Number'),
            Language::trans('{LNG_Student ID} **'),
            Language::trans('{LNG_Name} *'),
            Language::trans('{LNG_Identification No.} **'),
            Language::get('Birthday'),
            Language::get('Phone'),
            Language::get('Sex'),
            Language::get('Address'),
            Language::trans('{LNG_Name} ({LNG_Parent})'),
            Language::trans('{LNG_Phone} ({LNG_Parent})')
        );
        foreach (Language::get('SCHOOL_CATEGORY', []) as $key => $label) {
            $header[] = $label;
        }
        return $header;
    }

    /**
     * คืนค่า CSV Header ของ grade
     *
     * @return array
     */
    public static function grade()
    {
        if (empty(self::$cfg->school_grade_caculations)) {
            return array(
                Language::get('Course'),
                Language::get('Number'),
                Language::get('Student ID'),
                Language::get('Grade'),
                Language::get('Room'),
                Language::get('Academic year'),
                Language::get('Term')
            );
        } else {
            return array(
                Language::get('Course'),
                Language::get('Number'),
                Language::get('Student ID'),
                Language::get('Midterm'),
                Language::get('Final'),
                Language::get('Grade'),
                Language::get('Room'),
                Language::get('Academic year'),
                Language::get('Term')
            );
        }
    }
    /**
     * คืนค่า CSV Header ของ course
     *
     * @return array
     */
    public static function course()
    {
        return array(
            Language::trans('{LNG_Course Code} **'),
            Language::trans('{LNG_Course Name} *'),
            Language::trans('{LNG_Credit} *'),
            Language::get('Period'),
            Language::get('Type'),
            Language::get('Class'),
            Language::get('Academic year'),
            Language::get('Term'),
            Language::get('Teacher')
        );
    }
}
