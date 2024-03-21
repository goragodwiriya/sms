<?php
/**
 * @filesource modules/school/models/score.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Score;

/**
 * คำนวณเกรด
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * คืนค่ารายการตัดเกรด
     * ถ้ายังไม่เคยตั้งค่าคืนค่าเริ่มต้น
     *
     * @return array
     */
    public static function scores()
    {
        return empty(self::$cfg->school_grade_caculations) ? [] : self::$cfg->school_grade_caculations;
    }

    /**
     * แปลงคะแนนเป็นเกรด
     *
     * @param int $type
     * @param int $midterm
     * @param int $final
     * @param string $grade
     *
     * @return string
     */
    public static function toGrade($type, $midterm, $final, $grade = null)
    {
        $scores = self::scores();
        if (empty($type)) {
            if (empty($scores)) {
                // คืนค่าเกรดตามที่กรอก
                return $grade;
            } else {
                // คำนวณเกรด
                $value = $midterm + $final;
                foreach ($scores as $k => $v) {
                    if ($value <= $k) {
                        return $v;
                    }
                }
                return 'Err';
            }
        } else {
            // เกรดที่เลือกจากภาษา
            return \Kotchasan\Language::get('SCHOOL_TYPIES', 0, $type);
        }
    }
}
