<?php
/**
 * @filesource modules/school/models/csv.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Csv;

use Kotchasan\Language;

/**
 * หัวคอลัมน์ของไฟล์นำเข้า/ส่งออก ต้องตรงกับระบบเดิมทุกตัวอักษร
 * ไฟล์ CSV ที่ผู้ใช้เคยเตรียมไว้กับระบบเดิมจึงนำเข้าระบบนี้ได้ทันที
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * นักเรียน
     *
     * @return array
     */
    public static function student()
    {
        $header = [
            Language::get('Number'),
            Language::trans('{LNG_Student ID} **'),
            Language::trans('{LNG_Full Name} *'),
            Language::trans('{LNG_Identification No.} **'),
            Language::get('Birthday'),
            Language::get('Phone'),
            Language::get('Sex'),
            Language::get('Address'),
            Language::trans('{LNG_Full Name} ({LNG_Parent})'),
            Language::trans('{LNG_Phone} ({LNG_Parent})')
        ];
        foreach (\School\Category\Model::studentTypies() as $label) {
            $header[] = $label;
        }

        return $header;
    }

    /**
     * ผลการเรียน (ไม่ได้ตั้งเกณฑ์คำนวณ = ไม่มีคอลัมน์คะแนน)
     *
     * @return array
     */
    public static function grade()
    {
        if (\School\Score\Model::gradeOnly()) {
            return [
                Language::get('Course'),
                Language::get('Number'),
                Language::get('Student ID'),
                Language::get('Grade'),
                Language::get('Room'),
                Language::get('Academic year'),
                Language::get('Term')
            ];
        }

        return [
            Language::get('Course'),
            Language::get('Number'),
            Language::get('Student ID'),
            Language::get('Midterm'),
            Language::get('Final'),
            Language::get('Grade'),
            Language::get('Room'),
            Language::get('Academic year'),
            Language::get('Term')
        ];
    }

    /**
     * รายวิชา
     *
     * @return array
     */
    public static function course()
    {
        return [
            Language::trans('{LNG_Course Code} **'),
            Language::trans('{LNG_Course Name} *'),
            Language::trans('{LNG_Credit} *'),
            Language::get('Period'),
            Language::get('Type'),
            Language::get('Class'),
            Language::get('Academic year'),
            Language::get('Term'),
            Language::get('Teacher')
        ];
    }
}
