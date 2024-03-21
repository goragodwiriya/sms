<?php
/**
 * @filesource modules/personnel/models/user.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\User;

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
     * Query ข้อมูลสำหรับส่งให้กับ DataTable
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable()
    {
        return static::createQuery()
            ->select('P.*', 'U.name', 'U.active')
            ->from('personnel P')
            ->join('user U', 'INNER', array('U.id', 'P.id'));
    }

    /**
     * อ่านข้อมูลบุคลากรที่ $id
     *
     * @param int $id
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($id)
    {
        return static::createQuery()
            ->from('personnel P')
            ->join('user U', 'INNER', array('U.id', 'P.id'))
            ->where(array('P.id', $id))
            ->first('P.*', 'U.name', 'U.birthday', 'U.phone', 'U.sex', 'U.permission');
    }

    /**
     * อ่านข้อมูลรายการที่เลือกสำหรับหน้า (write.php)
     *
     * @param int $id หมายถึงรายการใหม่, > รายการที่ต้องการ
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function getForWrite($id)
    {
        if (empty($id)) {
            return (object) array(
                'id' => 0,
                'name' => '',
                'id_card' => '',
                'phone' => '',
                'order' => '',
                'custom' => [],
                'birthday' => '',
                'class' => 0,
                'room' => 0
            );
        } else {
            $search = static::createQuery()
                ->from('personnel P')
                ->join('user U', 'INNER', array('U.id', 'P.id'))
                ->where(array('P.id', $id))
                ->first('P.*', 'U.name', 'U.birthday', 'U.phone', 'U.sex', 'U.permission');
            if ($search) {
                $search->custom = @unserialize($search->custom);
                if (!is_array($search->custom)) {
                    $search->custom = [];
                }
            }
            return $search;
        }
    }

    /**
     * ตรวจสอบเลขประจำตัวประชาชนซ้ำ
     *
     * @param int   $id
     * @param array $personnel
     *
     * @return bool true ถ้ามีแล้วแต่ไม่ใช่ ID ตัวเอง
     */
    public static function exists($id, $personnel)
    {
        if ($personnel['id_card'] == '') {
            // ไม่มีข้อมูลต้องตรวจสอบ
            return false;
        } else {
            $search = static::createQuery()
                ->from('personnel')
                ->where(array('id_card', $personnel['id_card']))
                ->toArray()
                ->first('id');
            return $search !== false && $search['id'] != $id;
        }
    }
}
