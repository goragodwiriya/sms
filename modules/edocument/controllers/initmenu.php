<?php
/**
 * @filesource modules/edocument/controllers/initmenu.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Initmenu;

use Gcms\Login;
use Kotchasan\Http\Request;

/**
 * Init Menus
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * ฟังก์ชั่นเริ่มต้นการทำงานของโมดูลที่ติดตั้ง
     * และจัดการเมนูของโมดูล
     *
     * @param Request                $request
     * @param \Index\Menu\Controller $menu
     * @param array                  $login
     */
    public static function execute(Request $request, $menu, $login)
    {
        if (Login::checkPermission($login, 'can_upload_edocument')) {
            // สามารถส่งและรับเอกสารได้
            $menu->addTopLvlMenu('edocument', '{LNG_E-Document}', null, array(
                array(
                    'text' => '{LNG_Received document}',
                    'url' => 'index.php?module=edocument'
                ),
                array(
                    'text' => '{LNG_Sent document}',
                    'url' => 'index.php?module=edocument-sent'
                )
            ), 'member');
        } elseif ($login) {
            // ทุกคนสามารถรับเอกสารได้
            $menu->addTopLvlMenu('edocument', '{LNG_Received document}', 'index.php?module=edocument', null, 'member');
        }
        // เมนูตั้งค่า
        if (Login::checkPermission($login, 'can_config')) {
            $menu->add('settings', '{LNG_E-Document}', 'index.php?module=edocument-settings', null, 'edocument');
        }
    }
}
