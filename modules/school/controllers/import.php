<?php
/**
 * @filesource modules/school/controllers/import.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Import;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-import
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * นำเข้าข้อมูล
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // สมาชิก
        $login = Login::isMember();
        // ข้อความ title bar
        $this->title = Language::get('Import').' ';
        switch ($request->request('type')->toString()) {
            case 'student':
                $className = 'School\Importstudent\View';
                $this->title .= Language::get('Student list');
                $breadcrumb = '<li><a href="{BACKURL?module=school-students&id=0}">{LNG_Student}</a></li>';
                // สามารถจัดการรายชื่อนักเรียนได้
                $login = Login::checkPermission($login, 'can_manage_student');
                break;
            case 'grade':
                $className = 'School\Importgrade\View';
                $this->title .= Language::get('Grade');
                $breadcrumb = '<li><a href="{BACKURL?module=school-students&id=0}">{LNG_Student}</a></li>';
                // ครู-อาจาร์ย, สามารถจัดการรายวิชาได้, ให้คะแนนได้
                $login = Login::checkPermission($login, array('can_teacher', 'can_manage_course', 'can_rate_student'));
                break;
            case 'course':
                $className = 'School\Importcourse\View';
                $this->title .= Language::get('Course');
                $breadcrumb = '<li><a href="{BACKURL?module=school-courses&id=0}">{LNG_Course}</a></li>';
                // ครู-อาจาร์ย, สามารถจัดการรายชื่อนักเรียนได้
                $login = Login::checkPermission($login, array('can_teacher', 'can_manage_student'));
                break;
        }
        // เลือกเมนู
        $this->menu = 'school';
        if (!empty($login)) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-modules">{LNG_Module}</span></li>');
            $ul->appendChild('<li><span>{LNG_School}</span></li>');
            $ul->appendChild($breadcrumb);
            $ul->appendChild('<li><span>{LNG_Import}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-import">'.$this->title.'</h2>'
            ));
            // แสดงฟอร์ม
            if (isset($className)) {
                $div = $section->add('div', array(
                    'class' => 'content_bg'
                ));
                $div->appendChild(createClass($className)->render($request, $login));
            }
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
