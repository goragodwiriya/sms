<?php
/**
 * @filesource modules/edocument/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Init;

use Gcms\Api as ApiController;
use Kotchasan\Language;

/**
 * สิทธิ์ เมนู และการ์ดหน้าแรกของโมดูลหนังสือเวียน
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * สิทธิ์ของโมดูล (ชื่อเดียวกับระบบเดิม ผู้ใช้เดิมไม่ต้องตั้งค่าใหม่)
     *
     * @param array $permissions
     * @param mixed $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initPermission($permissions, $params = null, $login = null)
    {
        $permissions[] = ['value' => 'can_handle_all_edocument', 'text' => '{LNG_Can manage the} {LNG_E-Document}'];
        $permissions[] = ['value' => 'can_upload_edocument', 'text' => '{LNG_Can upload your document file} ({LNG_E-Document})'];

        return $permissions;
    }

    /**
     * เมนูของโมดูล
     * ทุกคนที่ล็อกอินมีหนังสือรับ (เหมือนระบบเดิม) ผู้ส่งได้มีหนังสือส่ง
     * ตั้งค่าของโมดูลอยู่ใต้เมนูของโมดูลเอง ไม่อยู่ในเมนูตั้งค่าของแกน
     *
     * @param array $menus
     * @param mixed $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initMenus($menus, $params = null, $login = null)
    {
        if (!$login) {
            return $menus;
        }

        $children = [
            ['title' => '{LNG_Received document}', 'url' => '/edocument', 'icon' => 'icon-inbox']
        ];
        if (ApiController::hasPermission($login, 'can_upload_edocument')) {
            $children[] = ['title' => '{LNG_Sent document}', 'url' => '/edocument-sent', 'icon' => 'icon-outbox'];
        }
        if (count($children) === 1) {
            $item = ['title' => '{LNG_Received document}', 'url' => '/edocument', 'icon' => 'icon-edocument'];
        } else {
            $item = ['title' => '{LNG_E-Document}', 'url' => '/edocument', 'icon' => 'icon-edocument', 'children' => $children];
        }
        // ต่อท้ายเมนูบุคลากร/โรงเรียน ไม่ว่าโมดูลไหนถูกโหลดก่อน
        $after = isset($menus['personnel']) ? 'personnel' : (isset($menus['school']) ? 'school' : 'dashboard');
        $menus = parent::insertMenuByKey($menus, 'edocument', $item, $after);

        if (ApiController::hasPermission($login, 'can_config')) {
            $menus = parent::insertMenuChildren($menus, [
                [
                    'title' => '{LNG_E-Document}',
                    'icon' => 'icon-edocument',
                    'children' => [
                        [
                            'title' => '{LNG_Module Settings}',
                            'url' => '/edocument-settings',
                            'icon' => 'icon-cog'
                        ]
                    ]
                ]
            ], 'settings', null, 1);
        }

        return $menus;
    }

    /**
     * การ์ดหน้าแรก จำนวนหนังสือที่ยังไม่ได้รับ (แสดงเฉพาะเมื่อมี เหมือนระบบเดิม)
     *
     * @param array $cards
     * @param mixed $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initDashboardCards($cards, $params = null, $login = null)
    {
        if (!$login) {
            return $cards;
        }
        $count = \Edocument\Inbox\Model::countNew($login);
        if ($count > 0) {
            // หน้าแรกผูกค่าด้วย data-text ซึ่งไม่แปลง {LNG_} ให้ — แปลงที่นี่
            $cards[] = [
                'title' => Language::get('E-Document'),
                'value' => number_format($count),
                'unit' => '',
                'icon' => 'icon-edocument',
                'url' => '/edocument',
                'hint' => Language::get('New document'),
                'class' => 'positive'
            ];
        }

        return $cards;
    }
}
