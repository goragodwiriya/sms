<?php
/**
 * @filesource modules/school/views/course.php
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
class View extends \Gcms\View
{
    /**
     * ฟอร์ม เพิ่ม-แก้ไข ข้อมูลรายวิชา
     *
     * @param Request $request
     * @param object $course
     *
     * @return string
     */
    public function render(Request $request, $course, $login)
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'action' => 'index.php/school/model/course/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-elearning',
            'title' => '{LNG_Details of} {LNG_Course}'
        ));
        $groups = $fieldset->add('groups');
        // course_code
        $groups->add('text', array(
            'id' => 'course_code',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width50',
            'label' => '{LNG_Course Code}',
            'maxlength' => 20,
            'autofocus' => true,
            'value' => $course->course_code
        ));
        // course_name
        $groups->add('text', array(
            'id' => 'course_name',
            'labelClass' => 'g-input icon-elearning',
            'itemClass' => 'width50',
            'label' => '{LNG_Course Name}',
            'maxlength' => 50,
            'value' => $course->course_name
        ));
        $groups = $fieldset->add('groups');
        // credit
        $groups->add('number', array(
            'id' => 'credit',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width50',
            'label' => '{LNG_Credit}',
            'data-keyboard' => '1234567890.',
            'value' => $course->credit
        ));
        // period
        $groups->add('number', array(
            'id' => 'period',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width50',
            'label' => '{LNG_Period}',
            'value' => $course->period
        ));
        $groups = $fieldset->add('groups');
        // type
        $groups->add('select', array(
            'id' => 'type',
            'labelClass' => 'g-input icon-category',
            'itemClass' => 'width50',
            'label' => '{LNG_Type}',
            'options' => Language::get('COURSE_TYPIES'),
            'value' => $course->type
        ));
        // หมวดหมู่ของนักเรียน
        $category = \School\Category\Model::init();
        // class
        $groups->add('select', array(
            'id' => 'class',
            'labelClass' => 'g-input icon-office',
            'itemClass' => 'width50',
            'label' => '{LNG_Class}',
            'options' => $category->toSelect('class'),
            'value' => $course->class
        ));
        // สามารถจัดการรายวิชาทั้งหมดได้
        if (Login::checkPermission($login, 'can_manage_course')) {
            // teacher_id
            $fieldset->add('select', array(
                'id' => 'teacher_id',
                'labelClass' => 'g-input icon-customer',
                'itemClass' => 'item',
                'label' => '{LNG_Teacher}',
                'comment' => '{LNG_If no instructor is specified. No need to complete the academic year and semester.}',
                'options' => array(0 => '{LNG_Please select}')+\School\Teacher\Model::init()->toSelect(0),
                'value' => $course->teacher_id
            ));
        }
        $groups = $fieldset->add('groups');
        // year
        $groups->add('number', array(
            'id' => 'year',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width50',
            'label' => '{LNG_Academic year}',
            'maxlength' => 4,
            'value' => $course->year
        ));
        // term
        $groups->add('select', array(
            'id' => 'term',
            'labelClass' => 'g-input icon-category',
            'itemClass' => 'width50',
            'label' => '{LNG_Term}',
            'options' => $category->toSelect('term'),
            'value' => $course->term
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // id
        $fieldset->add('hidden', array(
            'id' => 'id',
            'value' => $course->id
        ));
        // Javascript
        $form->script('initSchoolCourse();');
        // คืนค่า HTML
        return $form->render();
    }
}
