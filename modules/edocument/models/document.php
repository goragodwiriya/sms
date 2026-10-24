<?php
/**
 * @filesource modules/edocument/models/document.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Document;

use Gcms\Api as ApiController;
use Kotchasan\Language;

/**
 * หนังสือหนึ่งฉบับ และกติกาว่าใครทำอะไรกับหนังสือได้
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * โฟลเดอร์เก็บไฟล์ (เหมือนระบบเดิม)
     *
     * @return string
     */
    public static function dir()
    {
        return ROOT_PATH.DATA_FOLDER.'edocument/';
    }

    /**
     * อ่านหนังสือ
     *
     * @param int $id
     *
     * @return object|null receiver เป็น array ของสถานะสมาชิก
     */
    public static function get($id)
    {
        $item = \Kotchasan\DB::create()->first('edocument', [['id', (int) $id]]);
        if ($item) {
            $item->receiver = self::parseReceiver($item->receiver);
        }

        return $item;
    }

    /**
     * หนังสือใหม่ ผู้รับเริ่มต้นคือทุกสถานะยกเว้นนักเรียน เหมือนระบบเดิม
     *
     * @param object $login
     *
     * @return object
     */
    public static function newDocument($login)
    {
        return (object) [
            'id' => 0,
            'document_no' => '',
            'sender_id' => (int) $login->id,
            'receiver' => array_keys(self::receiverOptions()),
            'urgency' => 2,
            'topic' => '',
            'detail' => '',
            'file' => '',
            'ext' => '',
            'size' => 0
        ];
    }

    /**
     * ",0,2,3," -> [0, 2, 3]
     *
     * @param string $receiver
     *
     * @return array
     */
    public static function parseReceiver($receiver)
    {
        $result = [];
        foreach (explode(',', trim((string) $receiver, ',')) as $status) {
            if ($status !== '') {
                $result[] = (int) $status;
            }
        }

        return $result;
    }

    /**
     * สถานะสมาชิกที่เลือกเป็นผู้รับได้ (ทุกสถานะยกเว้นนักเรียน) [status => ชื่อ]
     *
     * @return array
     */
    public static function receiverOptions()
    {
        $statuses = self::$cfg->member_status;
        if (isset(self::$cfg->student_status)) {
            unset($statuses[(int) self::$cfg->student_status]);
        }

        return $statuses;
    }

    /**
     * ผู้จัดการงานสารบรรณ
     *
     * @param object $login
     *
     * @return bool
     */
    public static function canHandleAll($login)
    {
        return ApiController::hasPermission($login, 'can_handle_all_edocument');
    }

    /**
     * ผู้ส่งหรือผู้จัดการ แก้ไข/ลบ/ดูประวัติการดาวน์โหลดได้
     *
     * @param object $login
     * @param object $document
     *
     * @return bool
     */
    public static function canManage($login, $document)
    {
        return self::canHandleAll($login) || (int) $document->sender_id === (int) $login->id;
    }

    /**
     * ผู้รับ (สถานะอยู่ในรายชื่อผู้รับ) ผู้ส่ง หรือผู้จัดการ เปิดและดาวน์โหลดได้
     *
     * @param object $login
     * @param object $document
     *
     * @return bool
     */
    public static function canRead($login, $document)
    {
        $receiver = is_array($document->receiver) ? $document->receiver : self::parseReceiver($document->receiver);

        return in_array((int) $login->status, $receiver, true) || self::canManage($login, $document);
    }

    /**
     * ความเร่งด่วน [{value, text}]
     *
     * @return array
     */
    public static function urgencyOptions()
    {
        return \Gcms\Controller::arrayToOptions(Language::get('URGENCIES', []));
    }

    /**
     * ไอคอนชนิดไฟล์ (images/ext ของโปรเจ็ค)
     *
     * @param string $ext
     *
     * @return string
     */
    public static function extIcon($ext)
    {
        $ext = preg_replace('/[^a-z0-9]/', '', strtolower((string) $ext));

        return WEB_URL.'images/ext/'.(is_file(ROOT_PATH.'images/ext/'.$ext.'.png') ? $ext : 'file').'.png';
    }

    /**
     * ขนาดไฟล์ไม่เกินที่ตั้งไว้ และไม่เกินที่เซิร์ฟเวอร์รับได้
     *
     * @return int ไบต์
     */
    public static function uploadSize()
    {
        $max = \Kotchasan\Http\UploadedFile::getUploadSize(true);
        $size = (int) self::$cfg->edocument_upload_size;

        return $size > 0 && $size < $max ? $size : $max;
    }

    /**
     * ลบหนังสือ ไฟล์ และประวัติการดาวน์โหลด
     *
     * @param array $ids
     *
     * @return void
     */
    public static function remove(array $ids)
    {
        if (empty($ids)) {
            return;
        }
        $db = \Kotchasan\DB::create();
        foreach ($db->select('edocument', [['id', $ids]], [], ['file']) as $item) {
            if ($item->file !== '' && is_file(self::dir().$item->file)) {
                @unlink(self::dir().$item->file);
            }
        }
        $db->delete('edocument', [['id', $ids]], 0);
        $db->delete('edocument_download', [['document_id', $ids]], 0);
    }

    /**
     * บันทึกการดาวน์โหลดของสมาชิก (หนึ่งแถวต่อเอกสารต่อคน นับจำนวนครั้ง)
     * การดาวน์โหลดถือเป็นการลงชื่อรับหนังสือ
     *
     * @param int $document_id
     * @param int $member_id
     *
     * @return void
     */
    public static function recordDownload($document_id, $member_id)
    {
        $db = \Kotchasan\DB::create();
        $exists = $db->first('edocument_download', [['document_id', (int) $document_id], ['member_id', (int) $member_id]]);
        if ($exists) {
            $db->update('edocument_download', [['id', (int) $exists->id]], [
                'downloads' => (int) $exists->downloads + 1,
                'last_update' => time()
            ]);
        } else {
            $db->insert('edocument_download', [
                'document_id' => (int) $document_id,
                'member_id' => (int) $member_id,
                'downloads' => 1,
                'last_update' => time()
            ]);
        }
    }

    /**
     * ส่งอีเมลแจ้งผู้รับ (สมาชิกที่มีสถานะตามผู้รับ มีอีเมล และไม่ใช่ผู้ส่ง)
     *
     * @param array $receiver สถานะ
     * @param int $sender_id
     *
     * @return string ข้อผิดพลาด (ค่าว่าง = สำเร็จ)
     */
    public static function notify(array $receiver, $sender_id)
    {
        if (empty(self::$cfg->noreply_email) || empty($receiver)) {
            return '';
        }
        $subject = Language::replace('There are new documents sent to you at %WEBTITLE%', ['%WEBTITLE%' => strip_tags(self::$cfg->web_title)]);
        $msg = Language::replace('You received a new document %URL%', ['%URL%' => WEB_URL.'edocument']);
        $query = static::createQuery()
            ->select('name', 'username')
            ->from('user')
            ->where([
                ['status', $receiver],
                ['active', 1],
                ['username', '!=', ''],
                ['id', '!=', (int) $sender_id]
            ]);
        $errors = [];
        foreach ($query->fetchAll() as $item) {
            if (!filter_var($item->username, FILTER_VALIDATE_EMAIL)) {
                // ชื่อผู้ใช้ของครู/นักเรียนเป็นเลขประชาชน ไม่ใช่อีเมล
                continue;
            }
            $err = \Kotchasan\Email::send($item->name.'<'.$item->username.'>', self::$cfg->noreply_email, $subject, $msg);
            if ($err->error()) {
                $errors[] = strip_tags($err->getErrorMessage());
            }
        }

        return implode("\n", array_unique($errors));
    }
}
