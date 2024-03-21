<?php
/**
 * @filesource modules/personnel/views/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Setup;

use Kotchasan\DataTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

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
     * แสดงรายการบุคลากร สำหรับผู้ดูแล
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // สำหรับปุ่ม export
        $export = [];
        // สถานะบุคลากร
        $this->personnel_status = Language::get('PERSONNEL_STATUS');
        $this->school_category = \School\Category\Model::init();
        // เตรียมข้อมูลสำหรับใส่ลงในตาราง
        $fields = array('name', 'active');
        $headers = array(
            'name' => array(
                'text' => '{LNG_Name}',
                'sort' => 'name'
            ),
            'active' => array(
                'text' => '{LNG_Status}',
                'class' => 'center',
                'sort' => 'active'
            )
        );
        $cols = array(
            'order' => array(
                'class' => 'center'
            ),
            'id' => array(
                'class' => 'center'
            ),
            'active' => array(
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
            $export[$type] = $request->request($type)->toInt();
            $fields[] = $type;
            $filters[$type] = array(
                'name' => $type,
                'text' => $this->category->label($type),
                'options' => array(0 => '{LNG_all items}') + $this->category->toSelect($type),
                'default' => 0,
                'value' => $export[$type]
            );
            $headers[$type] = array(
                'text' => $this->category->label($type),
                'class' => 'center',
                'sort' => $type
            );
            $cols[$type] = array(
                'class' => 'center'
            );
        }
        $fields[] = 'class';
        $fields[] = 'room';
        $headers['class'] = array(
            'text' => '{LNG_Class teacher}',
            'class' => 'center'
        );
        $fields[] = 'order';
        $headers['order'] = array(
            'text' => '{LNG_Order}',
            'class' => 'center',
            'sort' => 'order'
        );
        $fields[] = 'id';
        $headers['id'] = array(
            'text' => '{LNG_Image}',
            'class' => 'center'
        );
        $export['active'] = $request->request('active', -1)->toInt();
        $filters['active'] = array(
            'name' => 'active',
            'text' => '{LNG_Status}',
            'options' => array(-1 => '{LNG_all items}') + $this->personnel_status,
            'default' => -1,
            'value' => $export['active']
        );
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Personnel\User\Model::toDataTable(),
            /* ฟิลด์ที่กำหนด (หากแตกต่างจาก Model) */
            'fields' => $fields,
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('person_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('person_sort', 'id DESC')->toString(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/personnel/model/setup/action',
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
                    'class' => 'button orange icon-excel border',
                    'id' => 'export&'.http_build_query($export),
                    'text' => '{LNG_Download} {LNG_Personnel list}'
                )
            ),
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
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'view' => array(
                    'class' => 'icon-info button brown notext',
                    'id' => ':id',
                    'title' => '{LNG_Details of} {LNG_Personnel}'
                ),
                'edit' => array(
                    'class' => 'icon-edit button green notext',
                    'href' => $uri->createBackUri(array('module' => 'personnel-write', 'id' => ':id')),
                    'title' => '{LNG_Edit}'
                )
            ),
            /* ปุ่มเพิม */
            'addNew' => array(
                'class' => 'float_button icon-new',
                'href' => $uri->createBackUri(array('module' => 'personnel-write', 'id' => 0)),
                'title' => '{LNG_Add}'
            )
        ));
        // save cookie
        setcookie('person_perPage', $table->perPage, time() + 3600 * 24 * 365, '/');
        // Javascript
        $table->script('initPerson("datatable");');
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
        foreach ($this->category->typies() as $type) {
            $item[$type] = $this->category->get($type, $item[$type]);
        }
        $item['order'] = '<label><input type=number size=5 id=order_'.$item['id'].' value="'.$item['order'].'"></label>';
        if ($item['active'] == 0) {
            $item['active'] = '<a id=active_0_'.$item['id'].' class="icon-valid disabled" title="'.$this->personnel_status[0].'"></a>';
        } else {
            $item['active'] = '<a id=active_1_'.$item['id'].' class="icon-valid access" title="'.$this->personnel_status[1].'"></a>';
        }
        if (is_file(ROOT_PATH.DATA_FOLDER.'personnel/'.$item['id'].'.jpg')) {
            $item['id'] = '<img src="'.WEB_URL.DATA_FOLDER.'personnel/'.$item['id'].'.jpg'.'" style="max-height:50px" alt=thumbnail>';
        } else {
            $item['id'] = '<img src="'.WEB_URL.'modules/personnel/img/noimage.jpg" style="max-height:50px" alt=thumbnail>';
        }
        $item['class'] = $this->school_category->get('class', $item['class']).$this->school_category->get('room', $item['room'], '/');
        return $item;
    }
}
