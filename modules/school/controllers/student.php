<?php
/**
 * @filesource modules/school/controllers/student.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Student;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-student
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
        $params = [];
        foreach (Language::get('SCHOOL_CATEGORY') as $key => $label) {
            $params[$key] = $request->request($key)->toInt();
        }
        // อ่านข้อมูลที่เลือก
        $student = \School\User\Model::getForWrite($request->request('id')->toInt(), $params);
        // ข้อความ title bar
        $this->title = Language::get('Student');
        // เลือกเมนู
        $this->menu = 'school';
        // สมาชิก
        $login = Login::isMember();
        if ($student && $login) {
            if (Login::checkPermission($login, array('can_manage_student', 'can_manage_course', 'can_teacher'))) {
                // ครู-อาจารย์, สามารถจัดการนักเรียนได้ ดูได้ทุกคน
            } elseif ($login['status'] == self::$cfg->student_status) {
                // นักเรียน ดูได้เฉพาะของตัวเอง
                if ($login['id'] != $student->id) {
                    $student = null;
                }
            }
            // สามารถจัดการได้
            if ($student) {
                if ($login['id'] == $student->id) {
                    // นักเรียน
                    $title = Language::get('Profile');
                } else {
                    // ครู-อาจารย์
                    $title = Language::get($student->id == 0 ? 'Add' : 'Edit');
                }
                $this->title = $title.' '.$this->title;
                // แสดงผล
                $section = Html::create('section');
                // breadcrumbs
                $breadcrumbs = $section->add('nav', array(
                    'class' => 'breadcrumbs'
                ));
                $ul = $breadcrumbs->add('ul');
                $ul->appendChild('<li><span class="icon-modules">{LNG_Module}</span></li>');
                $ul->appendChild('<li><span>{LNG_School}</span></li>');
                $ul->appendChild('<li><a href="{BACKURL?module=school-students&id=0}">{LNG_Student list}</a></li>');
                $ul->appendChild('<li><span>'.$title.'</span></li>');
                $section->add('header', array(
                    'innerHTML' => '<h2 class="icon-profile">'.$this->title.'</h2>'
                ));
                $div = $section->add('div', array(
                    'class' => 'content_bg'
                ));
                // แสดงฟอร์ม
                $div->appendChild(\School\Student\View::create()->render($request, $student, $login));
                // คืนค่า HTML
                return $section->render();
            }
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
