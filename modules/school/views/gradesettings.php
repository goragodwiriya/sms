<?php
/**
 * @filesource modules/school/views/gradesettings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Gradesettings;

use Kotchasan\DataTable;
use Kotchasan\Form;
use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=school-gradesettings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * รายการหมวดหมู่ที่ต้องการแก้ไข
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/school/model/gradesettings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-number',
            'title' => '{LNG_Grade calculation}'
        ));
        // ตารางหมวดหมู่
        $table = new DataTable(array(
            /* ข้อมูลใส่ลงในตาราง */
            'datas' => \School\Gradesettings\Model::toDataTable($request),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id'),
            /* กำหนดให้ input ตัวแรก (id) รับค่าเป็นตัวเลขเท่านั้น */
            'onInitRow' => 'initFirstRowNumberOnly',
            'border' => true,
            'responsive' => true,
            'pmButton' => true,
            'showCaption' => false,
            'headers' => array(
                'score' => array(
                    'text' => '{LNG_Score less than or equal to}'
                ),
                'grade' => array(
                    'text' => '{LNG_Grade}'
                )
            )
        ));
        $fieldset->add('div', array(
            'class' => 'item',
            'innerHTML' => $table->render()
        ));
        $fieldset->add('aside', array(
            'class' => 'message',
            'innerHTML' => '{LNG_Set grade calculation conditions for each level, starting from 0 points or subtracting all fields if you want to manually enter grades.}'
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // default
        $fieldset->add('a', array(
            'href' => 'index.php?module=school-gradesettings&default=1',
            'class' => 'button pink large icon-wizard border',
            'innerHTML' => '{LNG_Use default}'
        ));
        // คืนค่าฟอร์ม
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
        $item['score'] = Form::text(array(
            'name' => 'score[]',
            'labelClass' => 'g-input icon-number',
            'value' => $item['score']
        ))->render();
        $item['grade'] = Form::text(array(
            'name' => 'grade[]',
            'labelClass' => 'g-input icon-win',
            'value' => $item['grade']
        ))->render();
        return $item;
    }
}
