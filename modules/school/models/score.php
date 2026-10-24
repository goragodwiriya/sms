<?php
/**
 * @filesource modules/school/models/score.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Score;

use Kotchasan\Language;

/**
 * การคำนวณเกรด (เหมือนระบบเดิม)
 *
 * เกณฑ์อยู่ใน config school_grade_caculations [คะแนนน้อยกว่าหรือเท่ากับ => เกรด]
 * ถ้าไม่ได้ตั้งเกณฑ์ไว้ = กรอกเกรดเอง (ไม่มีช่องคะแนนกลางภาค/ปลายภาค)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * เกณฑ์การคำนวณเกรด [คะแนน => เกรด] เรียงจากน้อยไปมาก
     *
     * @return array
     */
    public static function scores()
    {
        $scores = empty(self::$cfg->school_grade_caculations) ? [] : (array) self::$cfg->school_grade_caculations;
        ksort($scores, SORT_NUMERIC);

        return $scores;
    }

    /**
     * กรอกเกรดเอง (ไม่ได้ตั้งเกณฑ์คำนวณ)
     *
     * @return bool
     */
    public static function gradeOnly()
    {
        return empty(self::$cfg->school_grade_caculations);
    }

    /**
     * ผลการเรียน
     *
     * @param int $type 0 = เกรดจากคะแนน, อื่น ๆ = ร. มส. มผ. ผ. (SCHOOL_TYPIES)
     * @param mixed $midterm
     * @param mixed $final
     * @param string|null $grade เกรดที่กรอกเอง (ใช้เมื่อไม่ได้ตั้งเกณฑ์)
     *
     * @return string|null
     */
    public static function toGrade($type, $midterm, $final, $grade = null)
    {
        if (!empty($type)) {
            return Language::get('SCHOOL_TYPIES', '', (int) $type);
        }
        $scores = self::scores();
        if (empty($scores)) {
            return $grade;
        }
        $value = (int) $midterm + (int) $final;
        foreach ($scores as $score => $result) {
            if ($value <= $score) {
                return (string) $result;
            }
        }

        return 'Err';
    }
}
