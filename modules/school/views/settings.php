<?php
/**
 * @filesource modules/school/views/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Settings;

use Kotchasan\Date;
use Kotchasan\Html;
use Kotchasan\Language;

/**
 * module=school-settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์ม ตั้งค่าโรงเรียน
     *
     * @return string
     */
    public function render()
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/school/model/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-config',
            'title' => '{LNG_Module settings}'
        ));
        // school_name
        $fieldset->add('text', array(
            'id' => 'school_name',
            'labelClass' => 'g-input icon-user',
            'itemClass' => 'item',
            'label' => '{LNG_School Name}',
            'comment' => '%SCHOOLNAME%',
            'value' => isset(self::$cfg->school_name) ? self::$cfg->school_name : ''
        ));
        $groups = $fieldset->add('groups');
        // phone
        $groups->add('text', array(
            'id' => 'phone',
            'labelClass' => 'g-input icon-phone',
            'itemClass' => 'width50',
            'label' => '{LNG_Phone}',
            'maxlength' => 32,
            'comment' => '%SCHOOLPHONE%',
            'value' => isset(self::$cfg->phone) ? self::$cfg->phone : ''
        ));
        // fax
        $groups->add('text', array(
            'id' => 'fax',
            'labelClass' => 'g-input icon-print',
            'itemClass' => 'width50',
            'label' => '{LNG_Fax}',
            'maxlength' => 32,
            'comment' => '%SCHOOLFAX%',
            'value' => isset(self::$cfg->fax) ? self::$cfg->fax : ''
        ));
        // address
        $fieldset->add('text', array(
            'id' => 'address',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'item',
            'label' => '{LNG_Address}',
            'comment' => '%SCHOOLADDRESS%',
            'value' => isset(self::$cfg->address) ? self::$cfg->address : ''
        ));
        $groups = $fieldset->add('groups');
        // province
        $groups->add('text', array(
            'id' => 'province',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'width33',
            'label' => '{LNG_Province}',
            'comment' => '%SCHOOLPROVINCE%',
            'value' => isset(self::$cfg->province) ? self::$cfg->province : ''
        ));
        // provinceID
        $groups->add('select', array(
            'id' => 'provinceID',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'width33',
            'label' => '{LNG_Province}',
            'comment' => '%SCHOOLPROVINCE%',
            'options' => \Kotchasan\Province::all(),
            'value' => isset(self::$cfg->provinceID) ? self::$cfg->provinceID : 102
        ));
        // zipcode
        $groups->add('number', array(
            'id' => 'zipcode',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'width33',
            'label' => '{LNG_Zipcode}',
            'comment' => '%SCHOOLZIPCODE%',
            'maxlength' => 5,
            'value' => isset(self::$cfg->zipcode) ? self::$cfg->zipcode : 10000
        ));
        // country
        $groups->add('select', array(
            'id' => 'country',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'width33',
            'label' => '{LNG_Country}',
            'options' => \Kotchasan\Country::all(),
            'value' => isset(self::$cfg->country) ? self::$cfg->country : 'TH'
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-portfolio',
            'title' => '{LNG_Size of} {LNG_Image} ({LNG_Student})'
        ));
        $groups = $fieldset->add('groups', array(
            'comment' => '{LNG_Image size is in pixels} {LNG_Uploaded images are resized automatically}'
        ));
        // student_w
        $groups->add('number', array(
            'id' => 'student_w',
            'labelClass' => 'g-input icon-width',
            'itemClass' => 'width',
            'label' => '{LNG_Width}',
            'value' => isset(self::$cfg->student_w) ? self::$cfg->student_w : 500
        ));
        // student_h
        $groups->add('number', array(
            'id' => 'student_h',
            'labelClass' => 'g-input icon-height',
            'itemClass' => 'width',
            'label' => '{LNG_Height}',
            'value' => isset(self::$cfg->student_h) ? self::$cfg->student_h : 500
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-excel',
            'title' => '{LNG_Import}/{LNG_Export}'
        ));
        // csv_language
        $fieldset->add('select', array(
            'id' => 'csv_language',
            'labelClass' => 'g-input icon-excel',
            'itemClass' => 'item',
            'label' => '{LNG_Language}',
            'comment' => '{LNG_The language code of the CSV file used for data import-export}',
            'options' => Language::get('CSV_ENCODING'),
            'value' => isset(self::$cfg->csv_language) ? self::$cfg->csv_language : 'UTF-8'
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-star0',
            'title' => '{LNG_Member status}'
        ));
        // ลบแอดมินออก
        unset(self::$cfg->member_status[1]);
        // teacher_status
        $fieldset->add('select', array(
            'id' => 'teacher_status',
            'labelClass' => 'g-input icon-customer',
            'itemClass' => 'item',
            'label' => '{LNG_Teacher}',
            'options' => self::$cfg->member_status,
            'value' => isset(self::$cfg->teacher_status) ? self::$cfg->teacher_status : 2
        ));
        // student_status
        $fieldset->add('select', array(
            'id' => 'student_status',
            'labelClass' => 'g-input icon-user',
            'itemClass' => 'item',
            'label' => '{LNG_Student}',
            'comment' => '{LNG_Assign memberships based on member status}',
            'options' => self::$cfg->member_status,
            'value' => isset(self::$cfg->student_status) ? self::$cfg->student_status : 0
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-elearning',
            'title' => '{LNG_Current Academic Year}'
        ));
        // academic_year
        $fieldset->add('number', array(
            'id' => 'academic_year',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'item',
            'label' => '{LNG_Academic year}',
            'maxlength' => 4,
            'value' => isset(self::$cfg->academic_year) ? self::$cfg->academic_year : Date::format(0, 'Y')
        ));
        // term
        $fieldset->add('select', array(
            'id' => 'term',
            'labelClass' => 'g-input icon-category',
            'itemClass' => 'item',
            'label' => '{LNG_Term}',
            'options' => \School\Category\Model::init()->toSelect('term'),
            'value' => isset(self::$cfg->term) ? self::$cfg->term : 1
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // Javascript
        $form->script("countryChanged('');");
        // คืนค่า HTML
        return $form->render();
    }
}
