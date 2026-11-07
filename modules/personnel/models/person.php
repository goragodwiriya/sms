<?php
/**
 * @filesource modules/personnel/models/person.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Person;

use Kotchasan\Language;

/**
 * ข้อมูลบุคลากรหนึ่งคน (personnel + user)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านบุคลากรตาม id (0 = รายการใหม่)
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function get($id)
    {
        $id = (int) $id;
        if ($id === 0) {
            $item = (object) [
                'id' => 0,
                'name' => '',
                'id_card' => '',
                'birthday' => '',
                'phone' => '',
                'order' => 0,
                'class' => 0,
                'room' => 0,
                'active' => 1,
                'permission' => '',
                'custom' => []
            ];
            foreach (\Personnel\Category\Model::items() as $type => $label) {
                $item->$type = 0;
            }

            return $item;
        }
        $item = static::createQuery()
            ->select('P.*', 'U.name', 'U.birthday', 'U.phone', 'U.sex', 'U.permission', 'U.active', 'U.status', 'U.username')
            ->from('personnel P')
            ->join('user U', [['U.id', 'P.id']], 'INNER')
            ->where([['P.id', $id]])
            ->first();
        if ($item) {
            $item->custom = self::decodeCustom($item->custom);
        }

        return $item;
    }

    /**
     * ข้อมูลเพิ่มเติม (JSON) เป็น array
     * ระบบเดิมเก็บเป็น PHP serialize — ตัวปรับรุ่นแปลงให้แล้ว แต่ยังอ่านได้เผื่อแถวที่หลงเหลือ
     *
     * @param string|null $custom
     *
     * @return array
     */
    public static function decodeCustom($custom)
    {
        if ($custom === null || $custom === '') {
            return [];
        }
        $data = json_decode($custom, true);
        if (!is_array($data) && strpos($custom, 'a:') === 0) {
            $data = @unserialize($custom, ['allowed_classes' => false]);
        }

        return is_array($data) ? $data : [];
    }

    /**
     * ช่องข้อมูลเพิ่มเติมของบุคลากร (คีย์ภาษา PERSONNEL_DETAILS) [key => label]
     *
     * @return array
     */
    public static function details()
    {
        $details = Language::get('PERSONNEL_DETAILS', []);

        return is_array($details) ? $details : [];
    }

    /**
     * เลขประชาชนนี้มีบุคลากรคนอื่นใช้อยู่หรือไม่
     *
     * @param int $id
     * @param string $id_card
     *
     * @return bool
     */
    public static function idCardExists($id, $id_card)
    {
        if ($id_card === '') {
            return false;
        }
        $search = \Kotchasan\DB::create()->first('personnel', [['id_card', $id_card]], ['id']);

        return $search && (int) $search->id !== (int) $id;
    }

    /**
     * รูปของบุคลากร
     *
     * @param int $id
     *
     * @return string|null URL ของรูป หรือ null ถ้าไม่มี
     */
    public static function picture($id)
    {
        $file = DATA_FOLDER.'personnel/'.(int) $id.self::$cfg->stored_img_type;

        return is_file(ROOT_PATH.$file) ? WEB_URL.$file : null;
    }

    /**
     * ครูประจำชั้น เช่น "มัธยมศึกษาปีที่ 1 1/1" (ชั้น + ห้อง)
     *
     * @param object $school ผลของ \School\Category\Model::init()
     * @param int $class
     * @param int $room
     *
     * @return string
     */
    public static function classTeacher($school, $class, $room)
    {
        if (!$school) {
            return '';
        }
        return trim($school->get('class', $class).' '.$school->get('room', $room));
    }

    /**
     * หมวดหมู่ของชั้นเรียน ถ้าโมดูล school ถูกถอดออก จะคืน null
     *
     * @return object|null
     */
    public static function schoolCategory()
    {
        return class_exists('\\School\\Category\\Model') ? \School\Category\Model::init() : null;
    }

    /**
     * ลบบุคลากร (ทั้ง personnel, user และรูป) ยกเว้นผู้ดูแลระบบสูงสุด id 1
     *
     * @param array $ids
     *
     * @return array id ที่ลบจริง
     */
    public static function remove(array $ids)
    {
        $ids = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 1));
        if (empty($ids)) {
            return [];
        }
        $db = \Kotchasan\DB::create();
        $exists = [];
        foreach ($db->select('personnel', [['id', $ids]], [], ['id']) as $item) {
            $exists[] = (int) $item->id;
        }
        if (empty($exists)) {
            return [];
        }
        foreach ($exists as $id) {
            $file = ROOT_PATH.DATA_FOLDER.'personnel/'.$id.self::$cfg->stored_img_type;
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $db->delete('personnel', [['id', $exists]], 0);
        $db->delete('user_meta', [['member_id', $exists]], 0);
        $db->delete('user', [['id', $exists]], 0);
        foreach ($exists as $id) {
            \Index\Auth\Model::logoutAllSessions($id);
        }

        return $exists;
    }
}
