<?php
/**
 * @filesource modules/personnel/views/lists.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Lists;

use Kotchasan\DataTable;
use Kotchasan\Http\Request;

/**
 * module=personnel-setup
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
    private $category;
    /**
     * @var object
     */
    private $school_category;
    /**
     * @var array
     */
    private $personnel_status;
    /**
     * @var mixed
     */
    private $login;

    /**
     * แสดงรายการบุคลากร สำหรับสมาชิกทั่วไป
     *
     * @param Request $request
     * @param array   $login
     *
     * @return string
     */
    public function render(Request $request, $login)
    {
        $this->login = $login;
        $this->school_category = \School\Category\Model::init();
        // เตรียมข้อมูลสำหรับใส่ลงในตาราง
        $fields = array(
            'name' => 'name'
        );
        $headers = array(
            'name' => array(
                'text' => '{LNG_Name}'
            )
        );
        $cols = array(
            'id' => array(
                'class' => 'center'
            ),
            'class' => array(
                'class' => 'center'
            )
        );
        $filters = [];
        // หมวดหมู่
        $this->category = \Index\Category\Model::init();
        foreach ($this->category->typies() as $type) {
            $fields[$type] = $this->category->label($type);
            $filters[$type] = array(
                'name' => $type,
                'text' => $fields[$type],
                'options' => array(0 => '{LNG_all items}') + $this->category->toSelect($type),
                'default' => 0,
                'value' => $request->request($type)->toInt()
            );
            $headers[$type] = array(
                'text' => $fields[$type],
                'class' => 'center'
            );
            $cols[$type] = array(
                'class' => 'center'
            );
        }
        $fields['id'] = 'id';
        $headers['id'] = array(
            'text' => '{LNG_Image}',
            'class' => 'center'
        );
        $fields['class'] = 'class';
        $fields['room'] = 'room';
        $headers['class'] = array(
            'text' => '{LNG_Class teacher}',
            'class' => 'center'
        );
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Personnel\User\Model::toDataTable(),
            /* query where */
            'defaultFilters' => array(
                array('active', 1)
            ),
            /* ฟิลด์ที่กำหนด (หากแตกต่างจาก Model) */
            'fields' => array_keys($fields),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('person_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => 'position,order',
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/personnel/model/setup/action',
            'actionCallback' => 'dataTableActionCallback',
            /* ไม่แสดง checkbox */
            'hideCheckbox' => true,
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('name'),
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => $filters,
            /* คอลัมน์ที่ไม่ต้องการแสดงผล */
            'hideColumns' => array('room'),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => $headers,
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => $cols,
            /* ฟังก์ชั่นตรวจสอบการแสดงปุ่มในแถว */
            'onCreateButton' => array($this, 'onCreateButton'),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green notext',
                    'href' => $uri->createBackUri(array('module' => 'personnel-write', 'id' => ':id')),
                    'title' => '{LNG_Edit}'
                ),
                'view' => array(
                    'class' => 'icon-info button brown notext',
                    'id' => ':id',
                    'title' => '{LNG_Details of} {LNG_Personnel}'
                )
            )
        ));
        // save cookie
        setcookie('person_perPage', $table->perPage, time() + 3600 * 24 * 365, '/');
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
        foreach ($this->category->typies() as $type) {
            $item[$type] = $this->category->get($type, $item[$type]);
        }
        if (is_file(ROOT_PATH.DATA_FOLDER.'personnel/'.$item['id'].'.jpg')) {
            $item['id'] = '<img src="'.WEB_URL.DATA_FOLDER.'personnel/'.$item['id'].'.jpg'.'" style="max-height:50px" alt=thumbnail>';
        } else {
            $item['id'] = '<img src="'.WEB_URL.'modules/personnel/img/noimage.jpg" style="max-height:50px" alt=thumbnail>';
        }
        $item['class'] = $this->school_category->get('class', $item['class'], '{LNG_Class}').' '.$this->school_category->get('room', $item['room'], '{LNG_Room}');
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
        if ($btn == 'view' || $items['id'] == $this->login['id']) {
            return $attributes;
        } else {
            return false;
        }
    }
}
