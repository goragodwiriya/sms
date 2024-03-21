<?php
/**
 * @filesource modules/school/controllers/initmenu.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Initmenu;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * Init Menu
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
        if ($login) {
            // นักเรียน
            if ($login['status'] == self::$cfg->student_status) {
                $menu->addTopLvlMenu('gradereport', '{LNG_Grade Report}', 'index.php?module=school-grade&amp;id='.$login['id'], null, 'member');
            } else {
                $submenus1 = [];
                $submenus2 = [];
                // ครู-อาจาร์ย, สามารถจัดการรายชื่อนักเรียนได้, สามารถจัดการรายวิชาได้
                if (Login::checkPermission($login, array('can_manage_student', 'can_manage_course', 'can_teacher', 'can_rate_student'))) {
                    $submenus2[] = array(
                        'text' => '{LNG_Course}',
                        'url' => 'index.php?module=school-courses'
                    );
                    $submenus1[] = array(
                        'text' => '{LNG_Student list}',
                        'url' => 'index.php?module=school-students'
                    );
                }
                // ครู-อาจาร์ย, สามารถจัดการรายชื่อนักเรียนได้
                if (Login::checkPermission($login, array('can_teacher', 'can_manage_student'))) {
                    $submenus2[] = array(
                        'text' => '{LNG_Import} {LNG_Course}',
                        'url' => 'index.php?module=school-import&amp;type=course'
                    );
                }
                // ครู-อาจาร์ย, สามารถจัดการรายวิชาได้, ให้คะแนนได้
                if (Login::checkPermission($login, array('can_teacher', 'can_manage_course', 'can_rate_student'))) {
                    $submenus2[] = array(
                        'text' => '{LNG_Import} {LNG_Grade}',
                        'url' => 'index.php?module=school-import&amp;type=grade'
                    );
                }
                //  สามารถจัดการนักเรียนได้
                if (Login::checkPermission($login, 'can_manage_student')) {
                    $submenus1[] = array(
                        'text' => '{LNG_Import} {LNG_Student list}',
                        'url' => 'index.php?module=school-import&amp;type=student'
                    );
                }
                if (!empty($submenus1) || !empty($submenus2)) {
                    $menu->addTopLvlMenu('school', '{LNG_School}', null, array_merge($submenus1, $submenus2), 'member');
                }
                // สามารถตั้งค่าระบบได้
                if (Login::checkPermission($login, 'can_config')) {
                    $submenus = array(
                        'school' => array(
                            'text' => '{LNG_Settings}',
                            'url' => 'index.php?module=school-settings'
                        ),
                        'gradesettings' => array(
                            'text' => '{LNG_Grade calculation}',
                            'url' => 'index.php?module=school-gradesettings'
                        ),
                        'gradetype' => array(
                            'text' => '{LNG_Grade settings}',
                            'url' => 'index.php?module=languageedit&amp;key=SCHOOL_TYPIES'
                        )
                    );
                    foreach (Language::get('SCHOOL_CATEGORY') as $type => $text) {
                        if ($type != 'department') {
                            $menu->add('settings', $text, 'index.php?module=school-categories&amp;type='.$type, null, 'school'.$type);
                        }
                    }
                    $menu->add('settings', '{LNG_Term}', 'index.php?module=school-categories&amp;type=term', null, 'schoolterm');
                    $menu->add('settings', '{LNG_School}', null, $submenus, 'school');
                }
            }
        }
    }
}
