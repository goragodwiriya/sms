<?php
/**
 * @filesource modules/school/models/student.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Student;

use Gcms\Login;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-student
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (student.php)
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
                $index = \School\User\Model::getForWrite($request->post('student_id')->toInt());
                if (!$index) {
                    // ไม่พบรายการที่แก้ไข
                    $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
                } else {
                    // ครู-อาจารย์, สามารถจัดการนักเรียนได้
                    if (!Login::checkPermission($login, array('can_teacher', 'can_manage_student'))) {
                        // ตัวเอง
                        $login = $login['id'] == $index->id ? $login : false;
                    }
                    if ($login && $login['active'] == 1) {
                        // ค่าที่ส่งมา
                        $user = array(
                            'name' => $request->post('student_name')->topic(),
                            'phone' => $request->post('student_phone')->topic(),
                            'birthday' => $request->post('student_birthday')->date(),
                            'sex' => $request->post('student_sex')->topic()
                        );
                        $student = array(
                            'id_card' => $request->post('student_id_card')->number(),
                            'student_id' => $request->post('student_student_id')->topic(),
                            'address' => $request->post('student_address')->topic(),
                            'parent' => $request->post('student_parent')->topic(),
                            'parent_phone' => $request->post('student_parent_phone')->topic()
                        );
                        $urls = [];
                        if ($login['id'] != $index->id) {
                            foreach (Language::get('SCHOOL_CATEGORY') as $key => $label) {
                                $student[$key] = $request->post('student_'.$key)->toInt();
                                $urls[$key] = $student[$key];
                            }
                        } else {
                            // ตัวเอง ไม่สามารถอัปเดต student_id ได้
                            unset($student['student_id']);
                        }
                        // อัปเดต Username และ Password ด้วย เลขประชาชนและวันเกิด
                        $updatepassword = ($request->post('updatepassword')->toInt() == 1);
                        if ($user['name'] == '') {
                            // ไม่ได้กรอก name
                            $ret['ret_student_name'] = 'Please fill in';
                        } elseif ($updatepassword && $student['id_card'] == '') {
                            // อัปเดต Username แต่ไม่ได้กรอก id_card
                            $ret['ret_student_id_card'] = 'Please fill in';
                        } elseif ($updatepassword && $user['birthday'] == '') {
                            // อัปเดต Password แต่ไม่ได้กรอก วันเกิด
                            $ret['ret_student_birthday'] = 'Please fill in';
                        } elseif ($err = \School\User\Model::exists($index->id, $student)) {
                            // เลขประชาชนหรือรหัสนักเรียนซ้ำ
                            $ret['ret_student_'.$err] = Language::replace('This :name already exist', array(':name' => Language::get($err == 'student_id' ? 'Student ID' : 'Identification No.')));
                        } else {
                            // ใหม่หรือต้องการปรับปรุง Username บันทึก user
                            if ($student['id_card'] != '' && $user['birthday'] != '' && ($index->id == 0 || $updatepassword) && preg_match('/([0-9]{4,4})\-([0-9]{1,2})\-([0-9]{1,2})/', $user['birthday'], $match)) {
                                $user['username'] = $student['id_card'];
                                $user['password'] = (543 + (int) $match[1]).sprintf('%02d', $match[2]).sprintf('%02d', $match[3]);
                                $updatepassword = true;
                            }
                            if ($index->id == 0) {
                                // สถานะนักเรียน
                                $user['status'] = isset(self::$cfg->student_status) ? self::$cfg->student_status : 0;
                                // register
                                $user = \Index\Register\Model::execute($this, $user, []);
                                // id ของนักเรียน
                                $student['id'] = $user['id'];
                            } else {
                                // id ของนักเรียนจาก DB
                                $student['id'] = $index->id;
                            }
                            // อัปโหลดรูปภาพพร้อมปรับขนาด
                            $dir = ROOT_PATH.DATA_FOLDER.'school/';
                            foreach ($request->getUploadedFiles() as $item => $file) {
                                /* @var $file UploadedFile */
                                if ($file->hasUploadFile()) {
                                    if (!File::makeDirectory($dir)) {
                                        // ไดเรคทอรี่ไม่สามารถสร้างได้
                                        $ret['ret_'.$item] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'school/');
                                    } elseif ($item == 'student_picture') {
                                        try {
                                            $file->cropImage(array('jpg', 'jpeg', 'png'), $dir.$student['id'].'.jpg', self::$cfg->student_w, self::$cfg->student_h);
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
                                if ($index->id > 0) {
                                    // แก้ไข
                                    if ($updatepassword && isset($user['password']) && isset($user['username'])) {
                                        $user['salt'] = uniqid();
                                        $user['password'] = sha1($user['password'].$user['salt']);
                                    }
                                    // update user
                                    $this->db()->update($this->getTableName('user'), $index->id, $user);
                                    // update student
                                    $this->db()->update($this->getTableName('student'), $index->id, $student);
                                } else {
                                    // ใหม่
                                    $this->db()->insertOrUpdate($this->getTableName('student'), $student);
                                }
                                // log
                                \Index\Log\Model::add($index->id, 'school', 'Save', '{LNG_Student} ID : '.$student['id'], $login['id']);
                                // ส่งค่ากลับ
                                if ($login['id'] == $index->id) {
                                    // นักเรียน
                                    $ret['location'] = 'reload';
                                } else {
                                    // ครู-อาจารย์
                                    if ($index->id == 0) {
                                        // แสดงรายการใหม่
                                        $urls['sort'] = 'number,id desc';
                                        $urls['page'] = 1;
                                    } else {
                                        $urls = [];
                                    }
                                    $urls['module'] = 'school-students';
                                    $urls['id'] = 0;
                                    $ret['location'] = $request->getUri()->postBack('index.php', $urls);
                                }
                                $ret['alert'] = Language::get('Saved successfully');
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
