<?php
/**
 * @filesource modules/personnel/models/lists.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Lists;

/**
 * module=personnel-lists
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
    private $datas;

    /**
     * query บุคลากร แยกตามตำแหน่ง.
     *
     * @param int $exclude_id ID ของสมาชิกที่ไม่ต้องการ
     *
     * @return static
     */
    public static function init($exclude_id)
    {
        // Model
        $model = new static;
        $query = $model->db()->createQuery()
            ->select('P.id', 'P.position', 'U.name')
            ->from('personnel P')
            ->join('user U', 'INNER', array('U.id', 'P.id'))
            ->where(array(
                array('P.id', '!=', $exclude_id),
                array('U.active', 1)
            ))
            ->toArray()
            ->order('U.name');
        foreach ($query->execute() as $item) {
            $model->datas[$item['position']][$item['id']] = $item['name'];
        }

        return $model;
    }

    /**
     * รายชื่อสมาชิก สถานะที่เลือก
     *
     * @param int $status
     *
     * @return array
     */
    public function get($status)
    {
        return isset($this->datas[$status]) ? $this->datas[$status] : [];
    }
}
