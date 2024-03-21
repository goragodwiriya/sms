<?php
/**
 * @filesource modules/school/controllers/course.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Course;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-course
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * แก้ไขข้อมูลนักเรียน
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // อ่านข้อมูลตาม Request ที่ส่งมา
        $course = \School\Course\Model::getForWrite($request);
        // ข้อความ title bar
        $title = $course->id == 0 ? '{LNG_Add}' : '{LNG_Edit}';
        $this->title = Language::trans($title.' {LNG_Course}');
        // เลือกเมนู
        $this->menu = 'school';
        // สมาชิก
        $login = Login::isMember();
        if ($course && $login) {
            if (
                /* ครูเจ้าของรายวิชา */
                ($course->teacher_id == $login['id'] && Login::checkPermission($login, 'can_teacher')) ||
                /* สามารถจัดการรายวิชาได้ */
                Login::checkPermission($login, 'can_manage_course')
            ) {
                // แสดงผล
                $section = Html::create('section');
                // breadcrumbs
                $breadcrumbs = $section->add('nav', array(
                    'class' => 'breadcrumbs'
                ));
                $ul = $breadcrumbs->add('ul');
                $ul->appendChild('<li><span class="icon-modules">{LNG_Module}</span></li>');
                $ul->appendChild('<li><span>{LNG_School}</span></li>');
                $ul->appendChild('<li><a href="{BACKURL?module=school-courses&id=0}">{LNG_Course}</a></li>');
                $ul->appendChild('<li><span>'.$title.'</span></li>');
                $section->add('header', array(
                    'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
                ));
                $div = $section->add('div', array(
                    'class' => 'content_bg'
                ));
                // แสดงฟอร์ม
                $div->appendChild(\School\Course\View::create()->render($request, $course, $login));
                // คืนค่า HTML
                return $section->render();
            }
        }
        // 404

        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
