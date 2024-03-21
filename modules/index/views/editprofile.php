<?php
/**
 * @filesource modules/index/views/editprofile.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Editprofile;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=editprofile
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มแก้ไขสมาชิก
     *
     * @param Request $request
     * @param array   $user
     * @param array   $login
     *
     * @return string
     */
    public function render(Request $request, $user, $login)
    {
        // แอดมิน
        $isAdmin = Login::isAdmin();
        // หมวดหมู่
        $category = \Index\Category\Model::init(false);
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/editprofile/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        if ($user['active'] == 1) {
            $fieldset = $form->add('fieldset', array(
                'title' => '{LNG_Login information}'
            ));
            $groups = $fieldset->add('groups');
            // username
            $groups->add('text', array(
                'id' => 'register_username',
                'itemClass' => 'width50',
                'labelClass' => 'g-input icon-email',
                'label' => '{LNG_Email}',
                'comment' => '{LNG_Email address used for login or request a new password}',
                'disabled' => $isAdmin ? false : true,
                'maxlength' => 255,
                'value' => $user['username'],
                'validator' => array('keyup,change', 'checkUsername', 'index.php/index/model/checker/username')
            ));
            // password, repassword
            $groups = $fieldset->add('groups', array(
                'comment' => '{LNG_To change your password, enter your password to match the two inputs}'
            ));
            // password
            $groups->add('password', array(
                'id' => 'register_password',
                'itemClass' => 'width50',
                'labelClass' => 'g-input icon-password',
                'label' => '{LNG_Password}',
                'placeholder' => '{LNG_Passwords must be at least four characters}',
                'maxlength' => 50,
                'showpassword' => true,
                'validator' => array('keyup,change', 'checkPassword')
            ));
            // repassword
            $groups->add('password', array(
                'id' => 'register_repassword',
                'itemClass' => 'width50',
                'labelClass' => 'g-input icon-password',
                'label' => '{LNG_Confirm password}',
                'placeholder' => '{LNG_Enter your password again}',
                'maxlength' => 50,
                'showpassword' => true,
                'validator' => array('keyup,change', 'checkPassword')
            ));
        }
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_User}'
        ));
        // name
        $fieldset->add('text', array(
            'id' => 'register_name',
            'labelClass' => 'g-input icon-customer',
            'itemClass' => 'item',
            'label' => '{LNG_Name}',
            'value' => $user['name']
        ));
        $groups = $fieldset->add('groups');
        // phone
        $groups->add('text', array(
            'id' => 'register_phone',
            'labelClass' => 'g-input icon-phone',
            'itemClass' => 'width50',
            'label' => '{LNG_Phone}',
            'maxlength' => 32,
            'value' => $user['phone']
        ));
        // หมวดหมู่
        $a = 0;
        foreach ($category->items() as $k => $label) {
            if ($isAdmin || !$category->isEmpty($k)) {
                if (in_array($k, self::$cfg->categories_multiple)) {
                    if (!$category->isEmpty($k)) {
                        $fieldset->add('checkboxgroups', array(
                            'id' => 'register_'.$k,
                            'itemClass' => 'item',
                            'label' => $category->name($k),
                            'labelClass' => 'g-input icon-group',
                            'options' => $category->toSelect($k),
                            'value' => empty($user[$k]) ? [] : $user[$k],
                            'disabled' => !$isAdmin && in_array($k, self::$cfg->categories_disabled)
                        ));
                    }
                } else {
                    if ($a % 2 == 0) {
                        $groups = $fieldset->add('groups');
                    }
                    $a++;
                    if ($isAdmin) {
                        $groups->add('text', array(
                            'id' => 'register_'.$k,
                            'labelClass' => 'g-input icon-menus',
                            'itemClass' => 'width50',
                            'label' => $label,
                            'datalist' => $category->toSelect($k),
                            'value' => empty($user[$k]) ? '' : $user[$k][0],
                            'text' => true
                        ));
                    } else {
                        $groups->add('select', array(
                            'id' => 'register_'.$k,
                            'labelClass' => 'g-input icon-menus',
                            'itemClass' => 'width50',
                            'label' => $label,
                            'options' => $category->toSelect($k),
                            'value' => empty($user[$k]) ? '' : $user[$k][0],
                            'disabled' => !$isAdmin && in_array($k, self::$cfg->categories_disabled)
                        ));
                    }
                }
            }
        }
        $groups = $fieldset->add('groups');
        // id_card
        $groups->add('number', array(
            'id' => 'register_id_card',
            'labelClass' => 'g-input icon-profile',
            'itemClass' => 'width50',
            'label' => '{LNG_Identification No.}',
            'maxlength' => 13,
            'value' => $user['id_card'],
            'validator' => array('keyup,change', 'checkIdcard')
        ));
        // avatar
        if (is_file(ROOT_PATH.DATA_FOLDER.'avatar/'.$user['id'].'.jpg')) {
            $img = WEB_URL.DATA_FOLDER.'avatar/'.$user['id'].'.jpg?'.time();
        } else {
            $img = WEB_URL.'skin/img/noicon.png';
        }
        $fieldset->add('file', array(
            'id' => 'avatar',
            'labelClass' => 'g-input icon-image',
            'itemClass' => 'item',
            'label' => '{LNG_Avatar}',
            'comment' => '{LNG_Browse image uploaded, type :type} ({LNG_resized automatically})',
            'dataPreview' => 'avatarImage',
            'previewSrc' => $img,
            'accept' => self::$cfg->member_img_typies
        ));
        // delete_avatar
        $fieldset->add('checkbox', array(
            'id' => 'delete_avatar',
            'itemClass' => 'subitem',
            'label' => '{LNG_Remove} {LNG_Avatar}',
            'value' => 1
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Other}'
        ));
        // status
        $fieldset->add('select', array(
            'id' => 'register_status',
            'itemClass' => 'item',
            'label' => '{LNG_Member status}',
            'labelClass' => 'g-input icon-star0',
            'disabled' => $isAdmin && $user['id'] != $login['id'] && $user['id'] != 1 ? false : true,
            'options' => self::$cfg->member_status,
            'value' => $user['status']
        ));
        if ($isAdmin) {
            // permission
            $fieldset->add('checkboxgroups', array(
                'id' => 'register_permission',
                'itemClass' => 'item',
                'label' => '{LNG_Permission}',
                'labelClass' => 'g-input icon-list',
                'options' => \Gcms\Controller::getPermissions(),
                'value' => $user['permission']
            ));
        }
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        $fieldset->add('hidden', array(
            'id' => 'register_id',
            'value' => $user['id']
        ));
        \Gcms\Controller::$view->setContentsAfter(array(
            '/:type/' => implode(', ', self::$cfg->member_img_typies)
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
