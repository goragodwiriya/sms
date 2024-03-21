<?php
/**
 * @filesource modules/edocument/controllers/init.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Init;

/**
 * Init Module
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * รายการ permission ของโมดูล
     *
     * @param array $permissions
     *
     * @return array
     */
    public static function updatePermissions($permissions)
    {
        $permissions['can_handle_all_edocument'] = '{LNG_Can manage the} {LNG_E-Document}';
        $permissions['can_upload_edocument'] = '{LNG_Can upload your document file} ({LNG_E-Document})';
        return $permissions;
    }
}
