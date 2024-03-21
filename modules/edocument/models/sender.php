<?php
/**
 * @filesource modules/edocument/models/sender.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Sender;

/**
 * โมเดลสำหรับขอข้อมูลผู้ส่ง
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @var array
     */
    private $datas = [];

    /**
     * query รายชื่อผู้ส่ง
     *
     * @param int $id (default) คืนค่าทุกคน, > คืนค่ารายการที่เลือก
     *
     * @return static
     */
    public static function init($id = 0)
    {
        $model = new static;
        if ($id == 0) {
            $sql1 = $model->db()->createQuery()
                ->select('sender_id')
                ->from('edocument');
        } else {
            $sql1 = array($id);
        }
        $query = $model->db()->createQuery()
            ->select('id', 'name')
            ->from('user U')
            ->where(array(
                array('U.id', 'IN', $sql1),
                array('U.active', 1)
            ))
            ->order('U.name')
            ->cacheOn();
        foreach ($query->execute() as $item) {
            $model->datas[$item->id] = $item->name;
        }
        return $model;
    }

    /**
     * ลิสต์รายชื่อผู้ส่ง
     * สำหรับใส่ลงใน select.
     *
     * @return array
     */
    public function toSelect()
    {
        return $this->datas;
    }

    /**
     * อ่านชื่อผู้ส่งที่ $id
     * ไม่พบ คืนค่าว่าง
     *
     * @param int $id
     *
     * @return string
     */
    public function get($id)
    {
        return isset($this->datas[$id]) ? $this->datas[$id] : '';
    }
}
