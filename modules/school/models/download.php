<?php
/**
 * @filesource modules/school/models/download.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Download;

/**
 * เพิ่ม/แก้ไข ข้อมูลนักเรียน
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query ข้อมูลนักเรียน
     *
     * @param array $params
     * @param int   $active
     *
     * @return array
     */
    public static function student($params, $active)
    {
        $select = array('S.number', 'S.student_id', 'U.name', 'S.id_card', 'U.sex', 'U.phone', 'S.address', 'S.parent', 'S.parent_phone');
        $where = array(
            array('U.active', $active)
        );
        $order = [];
        foreach ($params as $k => $v) {
            if ($v > 0) {
                $where[] = array("S.{$k}", $v);
            }
            $select[] = $k;
            $order[] = $k;
        }
        $order[] = 'S.number';
        $model = new static;
        $query = $model->db()->createQuery()
            ->select($select)
            ->from('student S')
            ->join('user U', 'INNER', array('U.id', 'S.id'))
            ->order($order)
            ->toArray();
        if (!empty($where)) {
            $query->where($where);
        }
        return $query->execute();
    }

    /**
     * Query ข้อมูลผลการเรียน
     *
     * @param array $params
     *
     * @return array
     */
    public static function grade($params)
    {
        $where = array(
            array('G.course_id', $params['subject'])
        );
        if (!empty($params['room'])) {
            $where[] = array('G.room', $params['room']);
        }
        $model = new static;
        $q1 = $model->db()->createQuery()
            ->select('name')
            ->from('user U')
            ->where(array('U.id', 'S.id'))
            ->limit(1);
        return $model->db()->createQuery()
            ->select('G.number', 'S.student_id', array($q1, 'name'), 'C.course_code', 'C.year', 'C.term', 'C.class', 'G.room', 'G.grade')
            ->from('grade G')
            ->join('course C', 'INNER', array('C.id', 'G.course_id'))
            ->join('student S', 'LEFT', array('S.id', 'G.student_id'))
            ->where($where)
            ->order(array('G.room', 'G.number', 'G.student_id'))
            ->toArray()
            ->execute();
    }
}
