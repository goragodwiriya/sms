<?php
/**
 * @filesource modules/school/views/grades.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Grades;

use Gcms\Login;
use Kotchasan\DataTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-grades
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var string
     */
    private $room;
    /**
     * @var string
     */
    private $type;
    /**
     * @var mixed
     */
    private $canManage;
    /**
     * @var mixed
     */
    private $category;

    /**
     * ตารางผลการเรียนรายวิชา (รายชื่อนักเรียน)
     *
     * @param Request $request
     * @param object  $course
     *
     * @return string
     */
    public function render(Request $request, $course, $login)
    {
        $this->canManage = Login::checkPermission($login, array('can_manage_student', 'can_manage_course', 'can_teacher'));
        // ค่าที่ส่งมา
        $room = $request->request('room')->toInt();
        // โหลดตัวแปรต่างๆ
        $this->type = '';
        foreach (Language::get('SCHOOL_TYPIES') as $k => $v) {
            $this->type .= '<option value="'.$k.'">'.$v.'</option>';
        }
        $this->category = \School\Category\Model::init();
        $rooms = $this->category->toSelect('room');
        $this->room = '<option value=""></option>';
        foreach ($rooms as $k => $v) {
            $this->room .= '<option value="'.$k.'">'.$v.'</option>';
        }
        // คอลัมน์ที่ไม่ต้องแสดงผล
        if (empty(self::$cfg->school_grade_caculations)) {
            $hideColumns = array('id', 'student', 'midterm', 'final');
        } else {
            $hideColumns = array('id', 'student');
        }
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \School\Grades\Model::toDataTable($course->id),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('grades_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => 'room,number,student_id',
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => $hideColumns,
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('student_id', 'name'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/school/model/grades/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => array(
                array(
                    'class' => 'button orange icon-excel',
                    'id' => 'export&subject='.$course->id.'&room='.$room,
                    'text' => '{LNG_Download} {LNG_Grade}'
                )
            ),
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => array(
                'room' => array(
                    'name' => 'room',
                    'text' => '{LNG_Room}',
                    'default' => 0,
                    'options' => array(0 => '{LNG_all items}') + $rooms,
                    'value' => $room
                )
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'number' => array(
                    'text' => '{LNG_Number}'
                ),
                'student_id' => array(
                    'text' => '{LNG_Student ID}'
                ),
                'name' => array(
                    'text' => '{LNG_Name}'
                ),
                'room' => array(
                    'text' => '{LNG_Room}',
                    'class' => 'center'
                ),
                'type' => array(
                    'text' => ''
                ),
                'midterm' => array(
                    'text' => '{LNG_Midterm}',
                    'class' => 'center'
                ),
                'final' => array(
                    'text' => '{LNG_Final}',
                    'class' => 'center'
                ),
                'grade' => array(
                    'text' => '{LNG_Grade}',
                    'class' => 'center'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'room' => array(
                    'class' => 'center'
                ),
                'type' => array(
                    'class' => 'center'
                ),
                'midterm' => array(
                    'class' => 'center'
                ),
                'final' => array(
                    'class' => 'center'
                ),
                'grade' => array(
                    'class' => 'center'
                )
            )
        ));
        if ($this->canManage) {
            $table->actions[] = array(
                'id' => 'action',
                'class' => 'ok',
                'text' => '{LNG_With selected}',
                'options' => array(
                    'delete' => '{LNG_Delete}'
                )
            );
        }
        // save cookie
        setcookie('grades_perPage', $table->perPage, time() + 3600 * 24 * 365, '/');
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
        $item['name'] = '<a id=view_'.$item['student'].'>'.$item['name'].'</a>';
        if ($this->canManage) {
            $item['room'] = '<label><select id=room_'.$item['id'].'>'.str_replace('value="'.$item['room'].'"', 'value="'.$item['room'].'" selected', $this->room).'</select></label>';
            $item['number'] = '<label><input type=number size=5 id=number_'.$item['id'].' value="'.$item['number'].'"></label>';
            $item['type'] = '<label><select id=type_'.$item['id'].'>'.str_replace('value="'.$item['type'].'"', 'value="'.$item['type'].'" selected', $this->type).'</select></label>';
            $item['midterm'] = '<label><input type=number size=5 max=100 id=midterm_'.$item['id'].' value="'.$item['midterm'].'"></label>';
            $item['final'] = '<label><input type=number size=5 max=100 id=final_'.$item['id'].' value="'.$item['final'].'"></label>';
        } else {
            $item['room'] = $this->category->get('room', $item['room']);
        }
        $item['grade'] = '<span id=grade_'.$item['id'].'>'.$item['grade'].'</span>';
        return $item;
    }
}
