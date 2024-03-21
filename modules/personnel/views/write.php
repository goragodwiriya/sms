<?php
/**
 * @filesource modules/personnel/views/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Write;

use Kotchasan\Html;
use Kotchasan\Language;

/**
 * module=personnel-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มสร้าง/แก้ไข บุคลากร
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
            'action' => 'index.php/personnel/model/write/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-write',
            'title' => '{LNG_Details of} {LNG_Personnel}'
        ));
        $groups = $fieldset->add('groups');
        // name
        $groups->add('text', array(
            'id' => 'personnel_name',
            'labelClass' => 'g-input icon-customer',
            'itemClass' => 'width50',
            'label' => '{LNG_Name}',
            'maxlength' => 100,
            'value' => $index->name
        ));
        $groups = $fieldset->add('groups', array(
            'comment' => $index->id == 0 ? '{LNG_Identification No. and birthday used to login}' : '<label><input type=checkbox name=updatepassword value=1>&nbsp;{LNG_Update username and password with personal identification number and birthday}</label>'
        ));
        // id_card
        $groups->add('text', array(
            'id' => 'personnel_id_card',
            'labelClass' => 'g-input icon-profile',
            'itemClass' => 'width50',
            'label' => '{LNG_Identification No.}',
            'pattern' => '[0-9]+',
            'maxlength' => 13,
            'value' => $index->id_card,
            'validator' => array('keyup,change', 'checkIdcard')
        ));
        // birthday
        $groups->add('date', array(
            'id' => 'personnel_birthday',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width50',
            'label' => '{LNG_Birthday}',
            'value' => $index->birthday
        ));
        $groups = $fieldset->add('groups');
        // phone
        $groups->add('text', array(
            'id' => 'personnel_phone',
            'labelClass' => 'g-input icon-phone',
            'itemClass' => 'width50',
            'label' => '{LNG_Phone}',
            'maxlength' => 32,
            'value' => $index->phone
        ));
        // order
        $groups->add('number', array(
            'id' => 'personnel_order',
            'labelClass' => 'g-input icon-sort',
            'itemClass' => 'width50',
            'label' => '{LNG_Order}',
            'placeholder' => '{LNG_Order of persons in positions}',
            'value' => $index->order
        ));
        // category
        $category = \Index\Category\Model::init();
        foreach ($category->typies() as $type) {
            $fieldset->add('select', array(
                'id' => 'personnel_'.$type,
                'labelClass' => 'g-input icon-category',
                'label' => $category->label($type),
                'itemClass' => 'item',
                'options' => array(0 => '{LNG_Please select}') + $category->toSelect($type),
                'value' => isset($index->$type) ? $index->$type : 0
            ));
        }
        $groups = $fieldset->add('groups');
        $school_category = \School\Category\Model::init();
        // class
        $groups->add('select', array(
            'id' => 'personnel_class',
            'labelClass' => 'g-input icon-menus',
            'itemClass' => 'width50',
            'label' => '{LNG_Class teacher}',
            'options' => array(0 => '-') + $school_category->toSelect('class'),
            'value' => $index->class
        ));
        // room
        $groups->add('select', array(
            'id' => 'personnel_room',
            'labelClass' => 'g-input icon-menus',
            'itemClass' => 'width50',
            'label' => '{LNG_Room}',
            'options' => array(0 => '-') + $school_category->toSelect('room'),
            'value' => $index->room
        ));
        // custom item
        foreach (Language::get('PERSONNEL_DETAILS', []) as $type => $label) {
            $fieldset->add('text', array(
                'id' => 'personnel_'.$type,
                'labelClass' => 'g-input icon-edit',
                'itemClass' => 'item',
                'label' => $label,
                'value' => isset($index->custom[$type]) ? $index->custom[$type] : ''
            ));
        }
        // personnel picture
        if (is_file(ROOT_PATH.DATA_FOLDER.'personnel/'.$index->id.'.jpg')) {
            $img = WEB_URL.DATA_FOLDER.'personnel/'.$index->id.'.jpg';
        } else {
            $img = WEB_URL.'modules/personnel/img/noimage.jpg';
        }
        $fieldset->add('file', array(
            'id' => 'personnel_picture',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Image}',
            'comment' => '{LNG_Browse image uploaded, type :type} {LNG_size :width*:height pixel} ({LNG_automatic resize})',
            'dataPreview' => 'imgPicture',
            'previewSrc' => $img
        ));
        // permission
        $fieldset->add('checkboxgroups', array(
            'id' => 'personnel_permission',
            'itemClass' => 'item',
            'label' => '{LNG_Permission}',
            'labelClass' => 'g-input icon-list',
            'options' => \Gcms\Controller::getPermissions(),
            'value' => []
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button ok large icon-save',
            'value' => '{LNG_Save}'
        ));
        // id
        $fieldset->add('hidden', array(
            'id' => 'personnel_id',
            'value' => $index->id
        ));
        \Gcms\Controller::$view->setContentsAfter(array(
            '/:type/' => 'jpg, jpeg, png',
            '/:width/' => self::$cfg->personnel_w,
            '/:height/' => self::$cfg->personnel_h
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
