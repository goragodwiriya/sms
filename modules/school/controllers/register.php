<?php
/**
 * @filesource modules/school/controllers/register.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Register;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-register
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * เลือกนักเรียนเพื่อลงทะเบียนเรียน
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // อ่านข้อมูลรายวิชา
        $course = \School\Course\Model::find($request->request('subject')->toInt());
        // ข้อความ title bar
        $this->title = Language::get('Register course');
        // เลือกเมนู
        $this->menu = 'school';
        // ครู-อาจาร์ย, สามารถจัดการรายวิชาได้
        if (!empty($course->id) && Login::checkPermission(Login::isMember(), array('can_manage_course', 'can_teacher'))) {
            $this->title .= ' '.Language::get('Course').' '.$course->course_name.($course->course_code != '' ? ' ('.$course->course_code.')' : '');
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-elearning">{LNG_School}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=school-courses&subject=0}">{LNG_Course}</a></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=school-grades&subject='.$course->id.'}">'.$course->course_name.'</a></li>');
            $ul->appendChild('<li><span>{LNG_Register course}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-register">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงตาราง
            $div->appendChild(\School\Register\View::create()->render($request, $course));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
