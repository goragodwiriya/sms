<?php
/**
 * @filesource modules/school/controllers/grade.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Grade;

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
        // ข้อความ title bar
        $this->title = Language::get('Grade Report');
        // เลือกเมนู
        $this->menu = 'school';
        // อ่านข้อมูลนักเรียน
        $student = \School\User\Model::get($request->request('id')->toInt());
        // สมาชิก
        $login = Login::isMember();
        if ($student && $login) {
            if (Login::checkPermission($login, array('can_manage_student', 'can_manage_course', 'can_teacher', 'can_rate_student'))) {
                // ครู-อาจารย์, สามารถจัดการนักเรียนได้ ดูได้ทุกคน
            } elseif ($login['status'] == self::$cfg->student_status) {
                // นักเรียน ดูได้เฉพาะของตัวเอง
                if ($login['id'] != $student->id) {
                    $student = null;
                }
            }
            // สมาชิก
            if ($student) {
                $this->title .= ' '.$student->name;
                // แสดงผล
                $section = Html::create('section');
                // breadcrumbs
                $breadcrumbs = $section->add('nav', array(
                    'class' => 'breadcrumbs'
                ));
                $ul = $breadcrumbs->add('ul');
                $ul->appendChild('<li><span class="icon-elearning">{LNG_School}</span></li>');
                $ul->appendChild('<li><span>'.$student->name.'</span></li>');
                $ul->appendChild('<li><span>{LNG_Grade}</span></li>');
                $section->add('header', array(
                    'innerHTML' => '<h2 class="icon-elearning">'.$this->title.'</h2>'
                ));
                $div = $section->add('div', array(
                    'class' => 'content_bg'
                ));
                // แสดงตาราง
                $div->appendChild(\School\Grade\View::create()->render($request, $student));
                // คืนค่า HTML
                return $section->render();
            }
        }
        // 404
        return \Index\Error\Controller::execute($this, 200, 'You are not enrolled Please contact your teacher');
    }
}
