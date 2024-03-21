<?php
/**
 * @filesource modules/edocument/views/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Write;

use Kotchasan\Html;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * module=edocument-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มสร้าง/แก้ไข เอกสาร
     *
     * @param object $index
     * @param array $login
     *
     * @return string
     */
    public function render($index, $login)
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/edocument/model/write/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-write',
            'title' => '{LNG_Details of} {LNG_Document}'
        ));
        // document_no
        $fieldset->add('text', array(
            'id' => 'document_no',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'item',
            'label' => '{LNG_Document No.}',
            'placeholder' => '{LNG_Leave empty for generate auto}',
            'comment' => '{LNG_Registration number of the document}',
            'maxlength' => 20,
            'value' => $index->document_no
        ));
        // ไม่ต้องส่งถึงสถานะนักเรียน
        unset(self::$cfg->member_status[self::$cfg->student_status]);
        // receiver
        $fieldset->add('checkboxgroups', array(
            'id' => 'receiver',
            'name' => 'receiver[]',
            'labelClass' => 'g-input icon-group',
            'itemClass' => 'item',
            'label' => '{LNG_Recipient}',
            'comment' => '{LNG_The recipient is listed in the selected group can be downloaded (You can select multiple groups)}',
            'options' => self::$cfg->member_status,
            'value' => $index->receiver
        ));
        // urgency
        $urgencies = Language::get('URGENCIES');
        $fieldset->add('radiogroups', array(
            'id' => 'urgency',
            'labelClass' => 'g-input icon-rocket',
            'itemClass' => 'item',
            'label' => '{LNG_Urgency}',
            'options' => array_map(array('Edocument\View\View', 'urgencyStyle'), array_keys($urgencies), array_values($urgencies)),
            'value' => $index->urgency
        ));
        // topic
        $fieldset->add('text', array(
            'id' => 'topic',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Document title}',
            'comment' => '{LNG_The name of the document when downloaded. If empty, use the name of the uploaded file. (Thai language available)}',
            'maxlength' => 255,
            'value' => $index->topic
        ));
        // file
        $fieldset->add('file', array(
            'id' => 'file',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Browse file}',
            'comment' => '{LNG_Upload :type files no larger than :size}',
            'accept' => self::$cfg->edocument_file_typies,
            'onchange' => 'edocumentFileChanged',
            'dataPreview' => 'filePreview'
        ));
        // detail
        $fieldset->add('textarea', array(
            'id' => 'detail',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Description}',
            'comment' => '{LNG_Note or additional notes}',
            'rows' => 5,
            'value' => $index->detail
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button ok large icon-save',
            'value' => '{LNG_Save}'
        ));
        $fieldset->add('checkbox', array(
            'id' => 'send_mail',
            'label' => '&nbsp;{LNG_Send an email to members}',
            'checked' => self::$cfg->edocument_send_mail && $index->id == 0,
            'value' => 1
        ));
        // id
        $fieldset->add('hidden', array(
            'id' => 'id',
            'value' => $index->id
        ));
        \Gcms\Controller::$view->setContentsAfter(array(
            '/:type/' => implode(', ', self::$cfg->edocument_file_typies),
            '/:size/' => Text::formatFileSize(self::$cfg->edocument_upload_size)
        ));

        return $form->render();
    }
}
