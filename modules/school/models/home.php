<?php
/**
 * @filesource modules/school/models/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Home;

use Kotchasan\Database\Sql;

/**
 * โมเดลสำหรับอ่านข้อมูลแสดงในหน้า  Home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านจำนวนครูและนักเรียน
     *
     * @return object
     */
    public static function getCount()
    {
        $model = new static;
        $q1 = $model->db()->createQuery()
            ->from('personnel A')
            ->exists('user', array(
                array('id', 'A.id'),
                array('active', 1),
                array('status', self::$cfg->teacher_status)
            ))
            ->select(Sql::COUNT('A.id', 'count'));
        $q2 = $model->db()->createQuery()
            ->from('student A')
            ->exists('user', array(
                array('id', 'A.id'),
                array('active', 1),
                array('status', self::$cfg->student_status)
            ))
            ->select(Sql::COUNT('A.id', 'count'));

        return $model->db()->createQuery()->first(array($q1, 'teacher'), array($q2, 'student'));
    }
}
