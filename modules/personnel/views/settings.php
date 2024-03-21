<?php
/**
 * @filesource modules/personnel/views/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Settings;

use Kotchasan\Html;

/**
 * module=personnel-settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มตั้งค่า
     *
     * @return string
     */
    public function render()
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/personnel/model/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-portfolio',
            'title' => '{LNG_Image}'
        ));
        $groups = $fieldset->add('groups', array(
            'comment' => '{LNG_Image size is in pixels} {LNG_Uploaded images are resized automatically}'
        ));
        // personnel_w
        $groups->add('text', array(
            'id' => 'personnel_w',
            'labelClass' => 'g-input icon-width',
            'itemClass' => 'width',
            'label' => '{LNG_Width}',
            'value' => isset(self::$cfg->personnel_w) ? self::$cfg->personnel_w : 500
        ));
        // personnel_h
        $groups->add('text', array(
            'id' => 'personnel_h',
            'labelClass' => 'g-input icon-height',
            'itemClass' => 'width',
            'label' => '{LNG_Height}',
            'value' => isset(self::$cfg->personnel_h) ? self::$cfg->personnel_h : 500
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
