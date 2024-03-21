<?php
/**
 * @filesource modules/school/views/courses.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Courses;

use Gcms\Login;
use Kotchasan\DataTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-courses
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var object
     */
    private $teacher;
    /**
     * @var object
     */
    private $category;
    /**
     * @var bool
     */
    private $canEdit;
    /**
     * @var array
     */
    private $course_typies;
    /**
     * ตารางรางรายวิชา
     *
     * @param Request $request
     * @param array   $login
     *
     * @return string
     */
    public function render(Request $request, $login)
    {
        // ค่าที่ส่งมา
        $params = array(
            'teacher' => $request->request('teacher')->toInt(),
            'year' => $request->request('year', self::$cfg->academic_year)->toInt(),
            'term' => $request->request('term', self::$cfg->term)->toInt(),
            'class' => $request->request('class')->toInt()
        );
        // สามารถแกไขรายวิชาได้
        $this->canEdit = Login::checkPermission($login, array('can_manage_course', 'can_teacher'));
        // โหลดตัวแปรต่างๆ
        $this->teacher = \School\Teacher\Model::init();
        // ประเภท
        $this->course_typies = Language::get('COURSE_TYPIES');
        // หมวดหมู่ของนักเรียน
        $this->category = \School\Category\Model::init();
        if (Login::checkPermission($login, 'can_manage_course')) {
            // สามารถจัดการรายวิชาทั้งหมดได้
            $can_manage_course = 0;
            $teachers = array(0 => '{LNG_all items}') + $this->teacher->toSelect(0);
        } else {
            // ไม่สามารถจัดการรายวิชาทั้งหมดได้ แสดงเฉพาะรายการของตัวเอง
            $can_manage_course = $login['id'];
            $params['teacher'] = $login['id'];
            $teachers = $this->teacher->toSelect($can_manage_course);
        }
        $filters = array(
            array(
                'name' => 'teacher',
                'text' => '{LNG_Teacher}',
                'options' => $teachers,
                'value' => $params['teacher']
            ),
            array(
                'name' => 'year',
                'text' => '{LNG_Academic year}',
                'options' => array(0 => '{LNG_all items}')+\School\Academicyear\Model::toSelect(),
                'value' => $params['year']
            ),
            array(
                'name' => 'term',
                'text' => '{LNG_Term}',
                'options' => array(0 => '{LNG_all items}') + $this->category->toSelect('term'),
                'value' => $params['term']
            )
        );
        if ($can_manage_course == 0) {
            $filters[] = array(
                'name' => 'class',
                'text' => '{LNG_Class}',
                'options' => array(0 => '{LNG_all items}') + $this->category->toSelect('class'),
                'value' => $params['class']
            );
            $hideColumns = array('id', 'term');
            $sort = 'year DESC,term DESC,teacher_id DESC';
        } else {
            $hideColumns = array('id', 'term', 'teacher_id');
            $sort = 'year DESC,term DESC';
        }
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \School\Courses\Model::toDataTable($params),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('courses_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('courses_Sort', $sort)->topic(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => $hideColumns,
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/school/model/courses/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => array(
                array(
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => array(
                        'delete' => '{LNG_Delete}'
                    )
                ),
                array(
                    'class' => 'button orange icon-excel',
                    'href' => 'export.php?module=school-courses&amp;'.http_build_query($params),
                    'target' => 'export',
                    'text' => '{LNG_Download} {LNG_Course}'
                )
            ),
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => $filters,
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'course_code' => array(
                    'text' => '{LNG_Course Code}',
                    'sort' => 'course_code'
                ),
                'course_name' => array(
                    'text' => '{LNG_Course Name}',
                    'sort' => 'course_name'
                ),
                'type' => array(
                    'text' => '{LNG_Type}',
                    'class' => 'center',
                    'sort' => 'type'
                ),
                'teacher_id' => array(
                    'text' => '{LNG_Teacher}',
                    'class' => 'center',
                    'sort' => 'teacher_id'
                ),
                'year' => array(
                    'text' => '{LNG_Academic year}',
                    'class' => 'center',
                    'sort' => 'year'
                ),
                'class' => array(
                    'text' => '{LNG_Class}',
                    'class' => 'center',
                    'sort' => 'class'
                ),
                'credit' => array(
                    'text' => '{LNG_Credit}',
                    'class' => 'center'
                ),
                'period' => array(
                    'text' => '{LNG_Period}',
                    'class' => 'center'
                ),
                'student' => array(
                    'text' => '{LNG_Student}',
                    'class' => 'center'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'teacher_id' => array(
                    'class' => 'center'
                ),
                'type' => array(
                    'class' => 'center'
                ),
                'year' => array(
                    'class' => 'center'
                ),
                'class' => array(
                    'class' => 'center'
                ),
                'credit' => array(
                    'class' => 'center'
                ),
                'period' => array(
                    'class' => 'center'
                ),
                'student' => array(
                    'class' => 'center'
                )
            ),
            /* ฟังก์ชั่นตรวจสอบการแสดงปุ่มในแถว */
            'onCreateButton' => array($this, 'onCreateButton'),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'grades' => array(
                    'class' => 'icon-users button pink notext',
                    'href' => $uri->createBackUri(array('module' => 'school-grades', 'subject' => ':id')),
                    'title' => '{LNG_Student}'
                ),
                'register' => array(
                    'class' => 'icon-register button orange notext',
                    'href' => $uri->createBackUri(array('module' => 'school-register', 'subject' => ':id', 'class' => ':class', 'year' => $params['year'], 'term' => $params['term'])),
                    'title' => '{LNG_Register course}'
                ),
                'edit' => array(
                    'class' => 'icon-edit button green notext',
                    'href' => $uri->createBackUri(array('module' => 'school-course', 'id' => ':id')),
                    'title' => '{LNG_Edit}'
                )
            ),
            /* ปุ่มเพิม */
            'addNew' => array(
                'class' => 'float_button icon-new',
                'href' => $uri->createBackUri(array('module' => 'school-course', 'class' => $params['class'], 'teacher' => $params['teacher'])),
                'title' => '{LNG_Add} {LNG_Course}'
            )
        ));
        // save cookie
        setcookie('courses_perPage', $table->perPage, time() + 3600 * 24 * 365, '/');
        setcookie('courses_Sort', $table->sort, time() + 3600 * 24 * 365, '/');
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array $item
     *
     * @return array
     */
    public function onRow($item, $o, $prop)
    {
        if ($item['teacher_id'] == 0) {
            $item['year'] = '';
        } else {
            $item['year'] = $item['year'].'/'.$item['term'];
        }
        $item['period'] = empty($item['period']) ? '' : $item['period'];
        $item['class'] = $this->category->get('class', $item['class']);
        $item['teacher_id'] = $this->teacher->get($item['teacher_id']);
        $item['type'] = isset($this->course_typies[$item['type']]) ? $this->course_typies[$item['type']] : '';
        return $item;
    }

    /**
     * ฟังก์ชั่นจัดการปุ่มในแต่ละแถว
     *
     * @param string $btn
     * @param array  $attributes
     * @param array  $items
     *
     * @return array|bool คืนค่า property ของปุ่ม ($attributes) ถ้าแสดงปุ่มได้, คืนค่า false ถ้าไม่สามารถแสดงปุ่มได้
     */
    public function onCreateButton($btn, $attributes, $items)
    {
        if ($btn == 'grades') {
            // ผู้เรียน
            return !empty($items['teacher_id']) || !empty($items['student']) ? $attributes : false;
        } elseif ($btn == 'register') {
            // ลงทะเบียนรายวิชา
            return (!empty($items['teacher_id']) || !empty($items['student'])) && $this->canEdit ? $attributes : false;
        } else {
            // edit
            return $this->canEdit ? $attributes : false;
        }
    }
}
