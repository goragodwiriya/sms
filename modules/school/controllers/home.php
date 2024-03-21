<?php
/**
 * @filesource modules/school/controllers/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Home;

use Gcms\Login;
use Kotchasan\Http\Request;

/**
 * Controller สำหรับการแสดงผลหน้า Home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * ฟังก์ชั่นสร้าง card.
     *
     * @param Request               $request
     * @param \Kotchasan\Collection $card
     * @param array                 $login
     */
    public static function addCard(Request $request, $card, $login)
    {
        if ($login) {
            $datas = \School\Home\Model::getCount();
            if (Login::checkPermission($login, array('can_manage_student', 'can_manage_course', 'can_teacher'))) {
                \Index\Home\Controller::renderCard($card, 'icon-user', '{LNG_Student}', number_format($datas->student), '{LNG_Student list}', 'index.php?module=school-students');
            }
            \Index\Home\Controller::renderCard($card, 'icon-customer', '{LNG_Teacher}', number_format($datas->teacher), '{LNG_Personnel list}', 'index.php?module=personnel-setup&amp;active=1');
        }
    }

    /**
     * ฟังก์ชั่นสร้าง เมนูด่วน
     *
     * @param Request               $request
     * @param \Kotchasan\Collection $card
     * @param array                 $login
     */
    public static function addMenu(Request $request, $menu, $login)
    {
        if ($login) {
            // นักเรียน
            if ($login['status'] == self::$cfg->student_status) {
                \Index\Home\Controller::renderQuickMenu($menu, 'icon-elearning', '{LNG_Grade Report}', 'index.php?module=school-grade&amp;id='.$login['id']);
            }
            // ครู-อาจาร์ย, สามารถจัดการรายชื่อนักเรียนได้, สามารถจัดการรายวิชาได้
            if (Login::checkPermission($login, array('can_manage_student', 'can_manage_course', 'can_teacher', 'can_rate_student'))) {
                \Index\Home\Controller::renderQuickMenu($menu, 'icon-elearning', '{LNG_Course}', 'index.php?module=school-courses');
            }
        }
    }
}
