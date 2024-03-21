<?php
/**
 * @filesource modules/edocument/models/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\View;

/**
 * module=edocument-view
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านเอกสารที่ $id
     * ไม่พบ คืนค่า null
     *
     * @param int   $id
     * @param array $login
     *
     * @return object
     */
    public static function get($id, $login)
    {
        $model = new static;
        $sql2 = $model->db()->createQuery()
            ->select('E.downloads')
            ->from('edocument_download E')
            ->where(array(
                array('E.document_id', 'A.id'),
                array('E.member_id', (int) $login['id'])
            ))
            ->limit(1);
        return $model->db()->createQuery()
            ->from('edocument A')
            ->where(array('A.id', $id))
            ->first('A.id', 'A.document_no', 'A.urgency', array($sql2, 'new'), 'A.topic', 'A.ext', 'A.sender_id', 'A.size', 'A.last_update', 'A.detail');
    }
}
