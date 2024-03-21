<?php
/**
 * @filesource modules/school/controllers/students.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Students;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-students
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * รายชื่อนักเรียน.
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('Student list');
        // เลือกเมนู
        $this->menu = 'school';
        // ครู-อาจาร์ย, สามารถจัดการรายชื่อนักเรียนได้
        if ($login = Login::checkPermission(Login::isMember(), array('can_manage_student', 'can_manage_course', 'can_teacher', 'can_rate_student'))) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-modules">{LNG_Module}</span></li>');
            $ul->appendChild('<li><span>{LNG_School}</span></li>');
            $ul->appendChild('<li><span>{LNG_Student list}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-users">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงตาราง
            $div->appendChild(\School\Students\View::create()->render($request, $login));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
