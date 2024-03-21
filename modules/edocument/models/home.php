<?php
/**
 * @filesource modules/edocument/models/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Home;

use Kotchasan\Database\Sql;

/**
 * โมเดลสำหรับอ่านข้อมูลแสดงในหน้า  Home.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านเอกสารใหม่
     *
     * @param array $login
     *
     * @return int
     */
    public static function getNew($login)
    {
        $search = static::createQuery()
            ->from('edocument A')
            ->where(array(
                array('A.receiver', 'LIKE', '%,'.$login['status'].',%')
            ))
            ->notExists('edocument_download', array(
                array('document_id', 'A.id'),
                array('member_id', $login['id'])
            ))
            ->first(Sql::COUNT('A.id', 'count'));
        if ($search) {
            return $search->count;
        }
        return 0;
    }
}
