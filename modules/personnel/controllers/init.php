<?php
/**
 * @filesource modules/personnel/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Init;

use Gcms\Api as ApiController;

/**
 * เมนู สิทธิ์ และการ์ดหน้าแรกของโมดูลบุคลากร
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
        $permissions[] = [
            'value' => 'can_manage_personnel',
            'text' => '{LNG_Can manage the} {LNG_Personnel list}'
        ];

        return $permissions;
    }

    /**
     * เมนูของโมดูล
     * เมนูบุคลากรเห็นได้ทุกคนที่ล็อกอิน เหมือนระบบเดิม
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
        $canManage = ApiController::hasPermission($login, 'can_manage_personnel');
        $children = [
            [
                'title' => '{LNG_Personnel list}',
                'url' => $canManage ? '/personnel-setup' : '/personnel',
                'icon' => 'icon-customer'
            ]
        ];
        if ($canManage) {
            $children[] = [
                'title' => '{LNG_Import} {LNG_Personnel list}',
                'url' => '/personnel-import',
                'icon' => 'icon-import'
            ];
        }

        if (count($children) === 1) {
            $item = ['title' => '{LNG_Personnel}', 'url' => $children[0]['url'], 'icon' => 'icon-customer'];
        } else {
            $item = ['title' => '{LNG_Personnel}', 'icon' => 'icon-customer', 'children' => $children];
        }

        // ต่อจากเมนูของโมดูล school (ถ้ามี) ไม่งั้นต่อจากหน้าแรก
        $menus = parent::insertMenuByKey($menus, 'personnel', $item, isset($menus['school']) ? 'school' : 'dashboard');

        if (ApiController::hasPermission($login, 'can_config')) {
            // ตั้งค่าทั้งหมดของโมดูลอยู่ใต้เมนูของโมดูลเอง ไม่อยู่ในเมนูตั้งค่าของแกน
            $settings = [
                [
                    'title' => '{LNG_Module Settings}',
                    'url' => '/personnel-settings',
                    'icon' => 'icon-cog'
                ]
            ];
            foreach (\Personnel\Category\Model::items() as $type => $label) {
                $settings[] = [
                    'title' => $label,
                    'url' => '/personnel-categories?type='.$type,
                    'icon' => 'icon-tags'
                ];
            }
            // หมวดหมู่ของบุคลากรเป็นแบบหลายภาษา ใช้หน้าของโมดูลแทนหน้าแผนกของแกน
            // (หน้าของแกนบันทึกแบบภาษาเดียว กดบันทึกแล้วคำแปลภาษาอื่นหายหมด)
            if (isset($menus['settings']['children'])) {
                $menus['settings']['children'] = array_values(array_filter($menus['settings']['children'], function ($item) {
                    return !isset($item['url']) || $item['url'] !== '/categories?type=department';
                }));
            }

            $menus = parent::insertMenuChildren($menus, [
                [
                    'title' => '{LNG_Personnel}',
                    'icon' => 'icon-customer',
                    'children' => $settings
                ]
            ], 'settings', null, 1);
        }

        return $menus;
    }

    /**
     * การ์ดหน้าแรก จำนวนครู-อาจารย์ (เดิมอยู่ใน School\Home\Controller::addCard)
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
        $count = \Kotchasan\Model::createQuery()
            ->selectCount('P.id')
            ->from('personnel P')
            ->join('user U', [['U.id', 'P.id']], 'INNER')
            ->where([
                ['U.active', 1],
                ['U.status', (int) self::$cfg->teacher_status]
            ])
            ->first();
        $cards[] = [
            'title' => \Kotchasan\Language::get('Teacher'),
            'value' => number_format($count ? (int) $count->count : 0),
            'unit' => '',
            'icon' => 'icon-customer',
            'url' => ApiController::hasPermission($login, 'can_manage_personnel') ? '/personnel-setup?active=1' : '/personnel',
            'hint' => \Kotchasan\Language::get('Personnel list'),
            'class' => 'positive'
        ];

        return $cards;
    }
}
