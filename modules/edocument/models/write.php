<?php
/**
 * @filesource modules/edocument/models/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Write;

use Gcms\Login;
use Kotchasan\Email;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=edocument-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลรายการที่เลือก
     * ถ้า $id = 0 หมายถึงรายการใหม่
     *
     * @param int   $id    ID
     * @param array $login
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($id, $login)
    {
        if (empty($id)) {
            // ใหม่
            return (object) array(
                'id' => 0,
                'document_no' => '',
                'sender_id' => (int) $login['id'],
                'receiver' => array_keys(self::$cfg->member_status),
                'urgency' => 2,
                'topic' => '',
                'detail' => ''
            );
        } else {
            // แก้ไข อ่านรายการที่เลือก
            $result = static::createQuery()
                ->from('edocument')
                ->where(array('id', $id))
                ->first();
            if ($result) {
                $result->receiver = explode(',', trim($result->receiver, ','));
            }
            return $result;
        }
    }

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (write.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, member, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::notDemoMode($login)) {
                try {
                    // ค่าที่ส่งมา
                    $save = array(
                        'document_no' => $request->post('document_no')->topic(),
                        'receiver' => $request->post('receiver', [])->toInt(),
                        'urgency' => $request->post('urgency')->toInt(),
                        'topic' => $request->post('topic')->topic(),
                        'detail' => $request->post('detail')->textarea(),
                    );
                    // ตรวจสอบรายการที่เลือก
                    $index = self::get($request->post('id')->toInt(), $login);
                    if (!$index) {
                        // ไม่พบ
                        $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
                    } elseif ($index->id > 0 && !($login['id'] == $index->sender_id || Login::checkPermission($login, 'can_upload_edocument'))) {
                        // แก้ไข ไม่ใช่เจ้าของ หรือ ไม่มีสิทธิ์
                        $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
                    } else {
                        // Database
                        $db = $this->db();
                        // Table
                        $table_edocument = $this->getTableName('edocument');
                        if ($save['document_no'] == '') {
                            // ไม่ได้กรอกเลขที่เอกสาร
                            $save['document_no'] = \Index\Number\Model::get($index->id, 'edocument_format_no', $table_edocument, 'document_no', self::$cfg->edocument_prefix);
                        } else {
                            // ค้นหาเลขที่เอกสารซ้ำ
                            $search = $db->first($table_edocument, array('document_no', $save['document_no']));
                            if ($search && ($index->id == 0 || $index->id != $search->id)) {
                                $ret['ret_document_no'] = Language::replace('This :name already exist', array(':name' => Language::get('Document number')));
                            }
                        }
                        if (empty($ret)) {
                            if (empty($save['receiver'])) {
                                // receiver
                                $ret['ret_receiver'] = Language::replace('Please select :name at least one item', array(':name' => Language::get('Recipient')));
                            } elseif ($save['detail'] == '') {
                                // detail
                                $ret['ret_detail'] = 'Please fill in';
                            } else {
                                // อัปโหลดไฟล์
                                $dir = ROOT_PATH.DATA_FOLDER.'edocument/';
                                foreach ($request->getUploadedFiles() as $item => $file) {
                                    /* @var $file \Kotchasan\Http\UploadedFile */
                                    if ($file->hasUploadFile()) {
                                        if (!File::makeDirectory($dir)) {
                                            // ไดเรคทอรี่ไม่สามารถสร้างได้
                                            $ret['ret_'.$item] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'edocument/');
                                        } elseif (!$file->validFileExt(self::$cfg->edocument_file_typies)) {
                                            // ชนิดของไฟล์ไม่ถูกต้อง
                                            $ret['ret_'.$item] = Language::get('The type of file is invalid');
                                        } elseif ($file->getSize() > self::$cfg->edocument_upload_size) {
                                            // ขนาดของไฟล์ใหญ่เกินไป
                                            $ret['ret_'.$item] = Language::get('The file size larger than the limit');
                                        } else {
                                            $save['ext'] = $file->getClientFileExt();
                                            $file_name = str_replace('.'.$save['ext'], '', $file->getClientFilename());
                                            if ($file_name == '' && $save['topic'] == '') {
                                                $ret['ret_topic'] = 'Please fill in';
                                            } else {
                                                // อัปโหลด
                                                $mktime = time();
                                                $save['file'] = $mktime.'.'.$save['ext'];
                                                while (file_exists($dir.$save['file'])) {
                                                    ++$mktime;
                                                    $save['file'] = $mktime.'.'.$save['ext'];
                                                }
                                                try {
                                                    $file->moveTo($dir.$save['file']);
                                                    $save['size'] = $file->getSize();
                                                    if ($save['topic'] == '') {
                                                        $save['topic'] = $file_name;
                                                    }
                                                    if (!empty($index->file) && $save['file'] != $index->file) {
                                                        @unlink($dir.$index->file);
                                                    }
                                                } catch (\Exception $exc) {
                                                    // ไม่สามารถอัปโหลดได้
                                                    $ret['ret_'.$item] = Language::get($exc->getMessage());
                                                }
                                            }
                                        }
                                    } elseif ($file->hasError()) {
                                        // ข้อผิดพลาดการอัปโหลด
                                        $ret['ret_'.$item] = Language::get($file->getErrorMessage());
                                    } elseif ($index->id == 0) {
                                        // ใหม่ ต้องมีไฟล์
                                        $ret['ret_'.$item] = 'Please browse file';
                                    }
                                }
                            }
                            if (empty($ret)) {
                                $save['last_update'] = time();
                                $receiver = $save['receiver'];
                                $save['receiver'] = ','.implode(',', $receiver).',';
                                $save['ip'] = $request->getClientIp();
                                $save['topic'] = preg_replace('/[,;:_]{1,}/', '_', $save['topic']);
                                if ($index->id == 0) {
                                    // ใหม่
                                    $save['sender_id'] = $login['id'];
                                    $db->insert($table_edocument, $save);
                                } else {
                                    // แก้ไข
                                    $db->update($table_edocument, $index->id, $save);
                                }
                                if ($request->post('send_mail')->toInt() == 1 && self::$cfg->noreply_email != '') {
                                    // ข้อมูลอีเมล
                                    $subject = Language::replace('There are new documents sent to you at %WEBTITLE%', array('%WEBTITLE%' => self::$cfg->web_title));
                                    $msg = Language::replace('You received a new document %URL%', array('%URL%' => WEB_URL.'index.php?module=edocument-received'));
                                    // query สมาชิกสถานะที่เลือก
                                    $query = $db->createQuery()
                                        ->select('name', 'username')
                                        ->from('user')
                                        ->where(array(
                                            array('status', $receiver),
                                            array('username', '!=', ''),
                                            array('id', '!=', (int) $login['id'])
                                        ))
                                        ->toArray();
                                    foreach ($query->execute() as $item) {
                                        $err = Email::send($item['username'].'<'.$item['name'].'>', self::$cfg->noreply_email, $subject, $msg);
                                        if ($err->error()) {
                                            // คืนค่า error
                                            $ret[] = $err->getErrorMessage();
                                        }
                                    }
                                    if (empty($ret)) {
                                        $ret['alert'] = Language::get('Save and email completed');
                                    } else {
                                        // ไม่มีจดหมายต้องส่ง
                                        $ret['alert'] = implode("\n", $ret);
                                    }
                                } else {
                                    // ไม่ต้องส่งอีเมล
                                    $ret['alert'] = Language::get('Saved successfully');
                                }
                                $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'edocument-sent'));
                            }
                        }
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
