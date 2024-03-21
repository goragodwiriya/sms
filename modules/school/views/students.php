<?php
/**
 * @filesource modules/school/views/students.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Students;

use Gcms\Login;
use Kotchasan\DataTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-students
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var array
     */
    private $params = [];
    /**
     * @var object
     */
    private $category;
    /**
     * @var bool
     */
    private $canEdit;

    /**
     * ตารางรายชื่อนักเรียน
     *
     * @param Request $request
     * @param array   $login
     *
     * @return string
     */
    public function render(Request $request, $login)
    {
        // สามารถแกไขข้อมูลนักเรียนได้
        $this->canEdit = Login::checkPermission($login, array('can_manage_student', 'can_manage_course', 'can_teacher'));
        // เตรียมข้อมูลสำหรับใส่ลงในตาราง
        $filters = [];
        $fields = array('id', 'number', 'student_id', 'name', 'phone', 'active');
        $headers = array(
            'number' => array(
                'text' => '{LNG_Number}',
                'sort' => 'number'
            ),
            'student_id' => array(
                'text' => '{LNG_Student ID}',
                'sort' => 'student_id'
            ),
            'name' => array(
                'text' => '{LNG_Name}',
                'sort' => 'name'
            ),
            'phone' => array(
                'text' => '{LNG_Phone}',
                'class' => 'center'
            )
        );
        $cols = array(
            'phone' => array(
                'class' => 'center'
            )
        );
        $actions = [];
        // หมวดหมู่ของนักเรียน
        $this->category = \School\Category\Model::init();
        foreach (Language::get('SCHOOL_CATEGORY') as $key => $label) {
            $this->params[$key] = $request->request($key)->toInt();
            foreach ($this->category->toSelect($key) as $k => $v) {
                $actions[$key.'_'.$k] = '{LNG_move to} '.$label.' '.$v;
            }
            $filters[$key] = array(
                'name' => $key,
                'text' => $label,
                'default' => 0,
                'options' => array(0 => '{LNG_all items}') + $this->category->toSelect($key),
                'value' => $this->params[$key]
            );
            $fields[] = $key;
            $headers[$key] = array(
                'text' => $label,
                'class' => 'center',
                'sort' => $key
            );
            $cols[$key] = array(
                'class' => 'center'
            );
        }
        $actions['graduate'] = '{LNG_Graduate}';
        $actions['studying'] = '{LNG_Studying}';
        $actions['delete'] = '{LNG_Delete}';
        $active = $request->request('active', 1)->toInt();
        $filters['active'] = array(
            'name' => 'active',
            'text' => '{LNG_Status}',
            'options' => array(1 => '{LNG_Studying}', 0 => '{LNG_Graduate}'),
            'value' => $active
        );
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \School\Students\Model::toDataTable(),
            /* รายชื่อฟิลด์ที่ query (ถ้าแตกต่างจาก Model) */
            'fields' => $fields,
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('student_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('student_sort', 'id desc')->toString(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'active'),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('name', 'student_id'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/school/model/students/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => array(
                array(
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => $actions
                ),
                array(
                    'class' => 'button orange icon-excel border',
                    'id' => 'export&active='.$active.'&'.http_build_query($this->params),
                    'text' => '{LNG_Download} {LNG_Student list}'
                )
            ),
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => $filters,
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => $headers,
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => $cols,
            /* ฟังก์ชั่นตรวจสอบการแสดงปุ่มในแถว */
            'onCreateButton' => array($this, 'onCreateButton'),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'view' => array(
                    'class' => 'icon-info button brown notext',
                    'id' => ':id',
                    'title' => '{LNG_Details of} {LNG_Student}'
                ),
                'report' => array(
                    'class' => 'icon-elearning button blue notext',
                    'href' => WEB_URL.'index.php?module=school-grade&amp;id=:id',
                    'title' => '{LNG_Grade Report}'
                ),
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'school-student', 'id' => ':id') + $this->params),
                    'title' => '{LNG_Edit}'
                )
            ),
            /* ปุ่มเพิม */
            'addNew' => array(
                'class' => 'float_button icon-new',
                'href' => $uri->createBackUri(array('module' => 'school-student') + $this->params),
                'title' => '{LNG_Add} {LNG_Student}'
            )
        ));
        // save cookie
        setcookie('student_perPage', $table->perPage, time() + 3600 * 24 * 365, '/');
        setcookie('student_sort', $table->sort, time() + 3600 * 24 * 365, '/');
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว.
     *
     * @param array $item
     *
     * @return array
     */
    public function onRow($item, $o, $prop)
    {
        if ($item['active'] == 1) {
            $item['number'] = '<label><input type=number size=5 id=number_'.$item['id'].' value="'.$item['number'].'"></label>';
        }
        $item['phone'] = self::showPhone($item['phone']);
        foreach ($this->params as $k => $v) {
            $item[$k] = $this->category->get($k, $item[$k]);
        }
        return $item;
    }

    /**
     * ฟังก์ชั่นจัดการปุ่มในแต่ละแถว.
     *
     * @param string $btn
     * @param array  $attributes
     * @param array  $items
     *
     * @return array|bool คืนค่า property ของปุ่ม ($attributes) ถ้าแสดงปุ่มได้, คืนค่า false ถ้าไม่สามารถแสดงปุ่มได้
     */
    public function onCreateButton($btn, $attributes, $items)
    {
        if ($btn != 'edit' || ($items['active'] == 1 && $this->canEdit)) {
            return $attributes;
        } else {
            return false;
        }
    }
}
