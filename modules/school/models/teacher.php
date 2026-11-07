<?php
/**
 * @filesource modules/school/models/teacher.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Teacher;

/**
 * รายชื่อครูผู้สอน (บุคลากรที่ยังใช้งานอยู่)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * @var array [id => name]
     */
    private $datas = [];

    /**
     * @return static
     */
    public static function init()
    {
        $obj = new static();
        $query = \Kotchasan\Model::createQuery()
            ->select('P.id', 'U.name')
            ->from('personnel P')
            ->join('user U', [['U.id', 'P.id']], 'INNER')
            ->where([['U.active', 1]])
            ->orderBy('U.name');
        foreach ($query->fetchAll() as $item) {
            $obj->datas[(int) $item->id] = $item->name;
        }

        return $obj;
    }

    /**
     * ตัวเลือกครู [{value, text}]
     *
     * @param int $only 0 = ทุกคน, อื่น ๆ = เฉพาะครูคนนี้
     *
     * @return array
     */
    public function toOptions($only = 0)
    {
        $result = [];
        foreach ($this->datas as $id => $name) {
            if ($only == 0 || $id == $only) {
                $result[] = ['value' => (string) $id, 'text' => $name];
            }
        }

        return $result;
    }

    /**
     * ชื่อครู
     *
     * @param int $id
     *
     * @return string
     */
    public function get($id)
    {
        return isset($this->datas[(int) $id]) ? $this->datas[(int) $id] : '';
    }
}
