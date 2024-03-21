<?php
/**
 * @filesource modules/personnel/views/import.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Import;

use Kotchasan\Html;
use Kotchasan\Http\UploadedFile;
use Kotchasan\Language;

/**
 * module=personnel-import
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มนำเข้าข้อมูล บุคลากร
     *
     * @return string
     */
    public function render()
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/personnel/model/import/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-import',
            'title' => '{LNG_Import} {LNG_Personnel list}'
        ));
        // import
        $fieldset->add('file', array(
            'id' => 'import',
            'labelClass' => 'g-input icon-excel',
            'itemClass' => 'item',
            'label' => '{LNG_Browse file}',
            'placeholder' => 'personnel.csv {ENCODE}',
            'comment' => Language::replace('File size is less than :size', array(':size' => UploadedFile::getUploadSize())),
            'accept' => array('csv')
        ));
        // หมวดหมู่ของบุคลากร
        $categories = [];
        foreach (Language::get('CATEGORIES') as $key => $label) {
            $categories[] = '<a href="'.WEB_URL.'index.php?module=categories&amp;type='.$key.'" target=_blank>'.$label.'</a>';
        }
        $file = 'modules/personnel/views/import_'.Language::name().'.html';
        if (!is_file(ROOT_PATH.$file)) {
            $file = 'modules/personnel/views/import_th.html';
        }
        $fieldset->add('div', array(
            'class' => 'message',
            'innerHTML' => file_get_contents(ROOT_PATH.$file)
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Import}'
        ));
        \Gcms\Controller::$view->setContentsAfter(array(
            '/{CATEGORIES}/' => implode(', ', $categories),
            '/{ENCODE}/' => Language::get('CSV_ENCODING', '', self::$cfg->csv_language)
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
