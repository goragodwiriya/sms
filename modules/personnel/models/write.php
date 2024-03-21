<?php
/**
 * @filesource modules/personnel/models/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Write;

use Gcms\Login;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=personnel-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม write.php.
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, สมาชิก
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            try {
                // ตรวจสอบค่าที่ส่งมา
                $index = \Personnel\User\Model::getForWrite($request->post('personnel_id')->toInt());
                if (!$index) {
                    // ไม่พบรายการที่แก้ไข
                    $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
                } else {
                    // สามารถจัดการบุคลากรได้
                    if (!Login::checkPermission($login, 'can_manage_personnel')) {
                        // ตัวเอง
                        $login = $login['id'] == $index->id ? $login : false;
                    }
                    if ($login && $login['active'] == 1) {
                        // ค่าที่ส่งมา
                        $user = array(
                            'name' => $request->post('personnel_name')->topic(),
                            'phone' => $request->post('personnel_phone')->topic(),
                            'birthday' => $request->post('personnel_birthday')->date()
                        );
                        $personnel = array(
                            'id_card' => $request->post('personnel_id_card')->number(),
                            'order' => $request->post('personnel_order')->toInt(),
                            'class' => $request->post('personnel_class')->toInt(),
                            'room' => $request->post('personnel_room')->toInt()
                        );
                        $urls = [];
                        foreach (Language::get('CATEGORIES', []) as $key => $label) {
                            $personnel[$key] = $request->post('personnel_'.$key)->toInt();
                            $urls[$key] = $personnel[$key];
                        }
                        // custom item
                        foreach (Language::get('PERSONNEL_DETAILS', []) as $key => $label) {
                            $personnel['custom'][$key] = $request->post('personnel_'.$key)->topic();
                        }
                        // อัปเดต Username และ Password ด้วย เลขประชาชนและวันเกิด
                        $updatepassword = ($request->post('updatepassword')->toInt() == 1);
                        if ($user['name'] == '') {
                            // ไม่ได้กรอก name
                            $ret['ret_personnel_name'] = 'Please fill in';
                        } elseif ($updatepassword && $personnel['id_card'] == '') {
                            // อัปเดต Username แต่ไม่ได้กรอก id_card
                            $ret['ret_personnel_id_card'] = 'Please fill in';
                        } elseif ($updatepassword && $user['birthday'] == '') {
                            // อัปเดต Password แต่ไม่ได้กรอก วันเกิด
                            $ret['ret_personnel_birthday'] = 'Please fill in';
                        } elseif (\Personnel\User\Model::exists($index->id, $personnel)) {
                            // เลขประชาชนซ้ำ
                            $ret['ret_personnel_id_card'] = Language::replace('This :name already exist', array(':name' => Language::get('Identification No.')));
                        } else {
                            // ใหม่หรือต้องการปรับปรุง Username บันทึก user
                            if ($personnel['id_card'] != '' && $user['birthday'] != '' && ($index->id == 0 || $updatepassword) && preg_match('/([0-9]{4,4})\-([0-9]{1,2})\-([0-9]{1,2})/', $user['birthday'], $match)) {
                                $user['username'] = $personnel['id_card'];
                                $user['password'] = ((int) Language::get('YEAR_OFFSET') + (int) $match[1]).sprintf('%02d', $match[2]).sprintf('%02d', $match[3]);
                            }
                            if ($index->id == 0) {
                                // สถานะครู
                                $user['status'] = isset(self::$cfg->teacher_status) ? self::$cfg->teacher_status : 0;
                                // register
                                $user = \Index\Register\Model::execute($this, $user);
                                // id ของบุคลากร
                                $personnel['id'] = $user['id'];
                            } else {
                                // id ของบุคลากรจาก DB
                                $personnel['id'] = $index->id;
                            }
                            $dir = ROOT_PATH.DATA_FOLDER.'personnel/';
                            // อัปโหลดรูปภาพพร้อมปรับขนาด
                            foreach ($request->getUploadedFiles() as $item => $file) {
                                /* @var $file UploadedFile */
                                if ($file->hasUploadFile()) {
                                    if (!File::makeDirectory($dir)) {
                                        // ไดเรคทอรี่ไม่สามารถสร้างได้
                                        $ret['ret_'.$item] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'personnel/');
                                    } elseif ($item == 'personnel_picture') {
                                        try {
                                            $file->cropImage(array('jpg', 'jpeg', 'png'), $dir.$personnel['id'].'.jpg', self::$cfg->personnel_w, self::$cfg->personnel_h);
                                        } catch (\Exception $exc) {
                                            // ไม่สามารถอัปโหลดได้
                                            $ret['ret_'.$item] = Language::get($exc->getMessage());
                                        }
                                    }
                                } elseif ($file->hasError()) {
                                    // upload Error
                                    $ret['ret_'.$item] = $file->getErrorMessage();
                                }
                            }
                            if (empty($ret)) {
                                $personnel['custom'] = empty($personnel['custom']) ? '' : serialize($personnel['custom']);
                                if ($index->id > 0) {
                                    // แก้ไข
                                    if ($updatepassword && isset($user['password']) && isset($user['username'])) {
                                        $user['password'] = sha1($user['password'].$user['username']);
                                    }
                                    // update user
                                    $this->db()->update($this->getTableName('user'), $index->id, $user);
                                    // update personnel
                                    $this->db()->update($this->getTableName('personnel'), $index->id, $personnel);
                                } else {
                                    // ใหม่
                                    $this->db()->insertOrUpdate($this->getTableName('personnel'), $personnel);
                                }
                                // ส่งค่ากลับ
                                if ($index->id == 0) {
                                    // แสดงรายการใหม่
                                    $urls['sort'] = 'id desc';
                                    $urls['page'] = 1;
                                } else {
                                    $urls = [];
                                }
                                $urls['module'] = 'personnel-setup';
                                $urls['id'] = 0;
                                $ret['location'] = $request->getUri()->postBack('index.php', $urls);
                                $ret['alert'] = Language::get('Saved successfully');
                                // log
                                \Index\Log\Model::add($index->id, 'personnel', 'Save', '{LNG_Edit} {LNG_Personnel} ID : '.$index->id, $login['id']);
                            }
                        }
                    }
                }
            } catch (\Kotchasan\InputItemException $e) {
                $ret['alert'] = $e->getMessage();
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
