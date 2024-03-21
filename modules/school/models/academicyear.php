<?php
/**
 * @filesource modules/school/models/academicyear.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Academicyear;

/**
 * อ่านปีการศึกษาทั้งหมดจากฐานข้อมูล grade
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านปีการศึกษาทั้งหมดจากฐานข้อมูล grade
     * รวมกับปีการศึกษาปัจจุบัน
     *
     * @return array
     */
    public static function toSelect()
    {
        $model = new static;
        $query = $model->db()->createQuery()
            ->selectDistinct('year')
            ->from('course')
            ->where(array('year', '>', 0))
            ->toArray()
            ->cacheOn()
            ->order('year');
        $datas = [];
        foreach ($query->execute() as $item) {
            $datas[$item['year']] = $item['year'];
        }
        $datas[self::$cfg->academic_year] = self::$cfg->academic_year;
        return $datas;
    }

    /**
     * อ่านปีการศึกษาทั้งหมดของนักเรียน
     *
     * @param int $student_id
     *
     * @return array
     */
    public static function fromStudent($student_id)
    {
        $model = new static;
        $q1 = $model->db()->createQuery()
            ->select('course_id')
            ->from('grade')
            ->where(array('student_id', $student_id));
        $query = $model->db()->createQuery()
            ->selectDistinct('year')
            ->from('course')
            ->where(array(
                array('id', 'IN', $q1),
                array('year', '>', 0)
            ))
            ->toArray()
            ->cacheOn()
            ->order('year');

        $datas = [];
        foreach ($query->execute() as $item) {
            $datas[$item['year']] = $item['year'];
        }
        $datas[self::$cfg->academic_year] = self::$cfg->academic_year;
        return $datas;
    }
}
