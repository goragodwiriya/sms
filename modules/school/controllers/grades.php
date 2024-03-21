<?php
/**
 * @filesource modules/school/controllers/grades.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Grades;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-grades
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * รายการนักเรียนที่ลงทะเบียนเรียน
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
        $this->title = Language::get('Grade');
        // เลือกเมนู
        $this->menu = 'school';
        // ครู-อาจาร์ย, สามารถจัดการรายวิชาได้
        if (!empty($course->id) && $login = Login::checkPermission(Login::isMember(), array('can_manage_student', 'can_manage_course', 'can_teacher', 'can_rate_student'))) {
            $this->title .= ' {LNG_Course} '.$course->course_name.($course->course_code != '' ? ' ('.$course->course_code.')' : '');
            $this->title .= ' {LNG_Academic year} '.$course->year.'/'.$course->term;
            $this->title = Language::trans($this->title);
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-elearning">{LNG_School}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=school-courses&subject=0}">{LNG_Course}</a></li>');
            $ul->appendChild('<li><span>'.$course->course_name.'</span></li>');
            $ul->appendChild('<li><span>{LNG_Grade}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-elearning">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงตาราง
            $div->appendChild(\School\Grades\View::create()->render($request, $course, $login));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
