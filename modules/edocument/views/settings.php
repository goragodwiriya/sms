<?php
/**
 * @filesource modules/edocument/views/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Settings;

use Kotchasan\Html;
use Kotchasan\Http\UploadedFile;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * module=edocument-settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ตั้งค่าโมดูล
     *
     * @return string
     */
    public function render()
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/edocument/model/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-config',
            'title' => '{LNG_Module settings}'
        ));
        $comment = '{LNG_Prefix, if changed The number will be counted again. You can enter %Y%M (year, month).}';
        $comment .= ', {LNG_Number such as %04d (%04d means 4 digits, maximum 11 digits)}';
        $groups = $fieldset->add('groups', array(
            'comment' => $comment
        ));
        // edocument_prefix
        $groups->add('text', array(
            'id' => 'edocument_prefix',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width50',
            'label' => '{LNG_Prefix}',
            'placeholder' => 'ที่ ศธ%Y%M-',
            'value' => isset(self::$cfg->edocument_prefix) ? self::$cfg->edocument_prefix : ''
        ));
        // edocument_format_no
        $groups->add('text', array(
            'id' => 'edocument_format_no',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width50',
            'label' => '{LNG_Document No.}',
            'placeholder' => '%04d, ที่ ศธ%Y%M-%04d',
            'value' => isset(self::$cfg->edocument_format_no) ? self::$cfg->edocument_format_no : 'ที่ ศธ%Y%M-%04d'
        ));
        // edocument_send_mail
        $fieldset->add('select', array(
            'id' => 'edocument_send_mail',
            'labelClass' => 'g-input icon-email',
            'itemClass' => 'item',
            'label' => '{LNG_Emailing}',
            'comment' => '{LNG_When adding a new document Email alert to the recipient. When enabled this option.}',
            'options' => Language::get('BOOLEANS'),
            'value' => isset(self::$cfg->edocument_send_mail) ? self::$cfg->edocument_send_mail : 1
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-upload',
            'title' => '{LNG_Upload}'
        ));
        // edocument_file_typies
        $fieldset->add('text', array(
            'id' => 'edocument_file_typies',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Type of file uploads}',
            'comment' => '{LNG_Specify the file extension that allows uploading. English lowercase letters and numbers 2-4 characters to separate each type with a comma (,) and without spaces. eg zip,rar,doc,docx}',
            'value' => isset(self::$cfg->edocument_file_typies) ? implode(',', self::$cfg->edocument_file_typies) : 'doc,ppt,pptx,docx,rar,zip,jpg,pdf'
        ));
        // อ่านการตั้งค่าขนาดของไฟลอัปโหลด
        $upload_max = UploadedFile::getUploadSize(true);
        // dms_upload_size
        $sizes = [];
        foreach (array(1, 2, 4, 6, 8, 16, 32, 64, 128, 256, 512, 1024, 2048) as $i) {
            $a = $i * 1048576;
            if ($a <= $upload_max) {
                $sizes[$a] = Text::formatFileSize($a);
            }
        }
        if (!isset($sizes[$upload_max])) {
            $sizes[$upload_max] = Text::formatFileSize($upload_max);
        }
        // edocument_upload_size
        $fieldset->add('select', array(
            'id' => 'edocument_upload_size',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Size of the file upload}',
            'comment' => '{LNG_The size of the files can be uploaded. (Should not exceed the value of the Server :upload_max_filesize.)}',
            'options' => $sizes,
            'value' => isset(self::$cfg->edocument_upload_size) ? self::$cfg->edocument_upload_size : ':upload_max_filesize'
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-download',
            'title' => '{LNG_Download}'
        ));
        // edocument_download_action
        $fieldset->add('select', array(
            'id' => 'edocument_download_action',
            'labelClass' => 'g-input icon-download',
            'itemClass' => 'item',
            'label' => '{LNG_When download}',
            'options' => Language::get('DOWNLOAD_ACTIONS'),
            'value' => isset(self::$cfg->edocument_download_action) ? self::$cfg->edocument_download_action : 0
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        \Gcms\Controller::$view->setContentsAfter(array(
            '/:upload_max_filesize/' => Text::formatFileSize($upload_max)
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
