<?php
/**
 * @filesource modules/school/views/categories.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Categories;

use Kotchasan\DataTable;
use Kotchasan\Form;
use Kotchasan\Html;
use Kotchasan\Language;

/**
 * module=school-categories
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * รายการหมวดหมู่
     *
     * @param object $index
     *
     * @return string
     */
    public function render($index)
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/school/model/categories/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-menus',
            'title' => $index->type === 'term' ? '{LNG_Term}' : Language::get('SCHOOL_CATEGORY', '', $index->type)
        ));
        // ตารางหมวดหมู่
        $table = new DataTable(array(
            /* ข้อมูลใส่ลงในตาราง */
            'datas' => \School\Categories\Model::toDataTable($index->type),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* กำหนดให้ input ตัวแรก (id) รับค่าเป็นตัวเลขเท่านั้น */
            'onInitRow' => 'initFirstRowNumberOnly',
            'border' => true,
            'responsive' => true,
            'pmButton' => true,
            'showCaption' => false,
            'headers' => array(
                'category_id' => array(
                    'text' => '{LNG_ID}'
                )
            )
        ));
        $fieldset->add('div', array(
            'class' => 'item',
            'innerHTML' => $table->render()
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // type
        $fieldset->add('hidden', array(
            'id' => 'type',
            'value' => $index->type
        ));
        // คืนค่า HTML
        return $form->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array  $item ข้อมูลแถว
     * @param int    $o    ID ของข้อมูล
     * @param object $prop กำหนด properties ของ TR
     *
     * @return array
     */
    public function onRow($item, $o, $prop)
    {
        $item['category_id'] = Form::text(array(
            'name' => 'category_id[]',
            'labelClass' => 'g-input',
            'size' => 2,
            'value' => $item['category_id']
        ))->render();
        foreach (Language::installedLanguage() as $lng) {
            $item[$lng] = Form::text(array(
                'name' => $lng.'[]',
                'labelClass' => 'g-input',
                'value' => $item[$lng],
                'style' => 'background-image:url(../language/'.$lng.'.gif)'
            ))->render();
        }
        return $item;
    }
}
