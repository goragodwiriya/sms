<?php
/**
 * @filesource modules/school/models/user.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\User;

/**
 * ตารางสมาชิก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query ข้อมูลนักเรียนสำหรับส่งให้กับ DataTable
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable()
    {
        $model = new static;

        return $model->query();
    }

    /**
     * query ข้อมูลนักเรียน
     *
     * @return QueryBuilder
     */
    private function query()
    {
        return $this->db()->createQuery()
            ->select('S.*', 'U.name', 'U.active')
            ->from('student S')
            ->join('user U', 'INNER', array('U.id', 'S.id'));
    }

    /**
     * อ่านข้อมูลนักเรียนที่ $id.
     *
     * @param int $id
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($id)
    {
        $model = new static;

        return $model->query()
            ->where(array('S.id', $id))
            ->first('S.*', 'U.name', 'U.birthday', 'U.phone', 'U.sex', 'U.permission');
    }

    /**
     * อ่านข้อมูลรายการที่เลือกสำหรับหน้า student.php.
     *
     * @param int   $id     หมายถึงรายการใหม่, > รายการที่ต้องการ
     * @param array $params
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function getForWrite($id, $params = [])
    {
        // Model
        $model = new static;
        if (empty($id)) {
            // ใหม่ query รหัสนักเรียนถัดไป
            $sql = \Kotchasan\Database\Sql::NEXT('student_id', $model->getTableName('student'), null, 'student_id');
            // สำหรับฟอร์มเพิ่มนักเรียน
            $result = array(
                'id' => 0,
                'student_id' => $model->db()->createQuery()->first($sql)->student_id,
                'name' => '',
                'id_card' => '',
                'birthday' => '',
                'sex' => 'f',
                'phone' => '',
                'address' => '',
                'parent' => '',
                'parent_phone' => ''
            );
            foreach ($params as $key => $value) {
                $result[$key] = $value;
            }

            return (object) $result;
        } else {
            // query ข้อมูลที่เลือก
            return $model->query()
                ->where(array('S.id', $id))
                ->first('S.*', 'U.name', 'U.birthday', 'U.phone', 'U.sex', 'U.permission');
        }
    }

    /**
     * ตรวจสอบเลขประจำตัวประชาชนหรือรหัสนักเรียนซ้ำ
     *
     * @param int   $id
     * @param array $student
     *
     * @return bool true ถ้ามีแล้วแต่ไม่ใช่ ID ตัวเอง
     */
    public static function exists($id, $student)
    {
        $where = [];
        if (!empty($student['id_card'])) {
            $where[] = array('id_card', $student['id_card']);
        }
        if (!empty($student['student_id'])) {
            $where[] = array('student_id', $student['student_id']);
        }
        if (empty($where)) {
            // ไม่มีข้อมูลต้องตรวจสอบ
            return false;
        } else {
            $search = \Kotchasan\Model::createQuery()
                ->from('student')
                ->where($where)
                ->first('id', 'id_card', 'student_id');
            if ($search !== false && ($id == 0 || $search->id != $id)) {
                if (!empty($student['student_id']) && $student['student_id'] == $search->student_id) {
                    return 'student_id';
                } else {
                    return 'id_card';
                }
            } else {
                return false;
            }
        }
    }
}
