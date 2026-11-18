<?php
/**
 * @filesource modules/personnel/models/account.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Account;

/**
 * บัญชีเข้าระบบของบุคลากรและนักเรียน
 *
 * ทั้งสองกลุ่มใช้กติกาเดียวกับระบบเดิม
 *   ชื่อผู้ใช้ = เลขประจำตัวประชาชน
 *   รหัสผ่าน  = วันเกิด ปี พ.ศ. ในรูป ปปปปดดวว (เช่น 25230515)
 * ไม่มีเลขประชาชนหรือวันเกิด = มีชื่อในระบบแต่เข้าระบบไม่ได้
 *
 * ⚠️ ระบบเดิมคำนวณ พ.ศ. ต่างกันสองแบบ (บุคลากรใช้ YEAR_OFFSET ของภาษาที่เปิดอยู่
 * นักเรียนใช้ 543 ตายตัว) รหัสผ่านของคนเดียวกันจึงเปลี่ยนตามภาษาหน้าจอได้
 * ที่นี่ใช้ 543 ทั้งสองกลุ่ม และเก็บรหัสผ่านด้วย Index\Auth\Model::hashPassword()
 * ของแกน (ระบบเดิมฝั่งบุคลากรเก็บเป็น sha1(รหัสผ่าน.ชื่อผู้ใช้) ซึ่งเข้าระบบไม่ได้)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * ส่วนต่าง ค.ศ. กับ พ.ศ.
     */
    const BUDDHIST_YEAR = 543;

    /**
     * รหัสผ่านจากวันเกิด (ค.ศ. Y-m-d) เป็น ปปปปดดวว ปี พ.ศ.
     *
     * @param string $birthday
     *
     * @return string ค่าว่างถ้าวันเกิดไม่ถูกต้อง
     */
    public static function birthdayPassword($birthday)
    {
        if (preg_match('/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})/', (string) $birthday, $match)) {
            return ((int) $match[1] + self::BUDDHIST_YEAR).sprintf('%02d%02d', $match[2], $match[3]);
        }

        return '';
    }

    /**
     * อ่านวันเกิดจากไฟล์นำเข้า รับ ปปปป-ดด-วว และ วว/ดด/ปปปป (ใช้ - หรือ / ก็ได้)
     * ปีที่กรอกเป็น พ.ศ. ตามไฟล์ตัวอย่าง ถ้าน้อยกว่า 2400 ถือว่าเป็น ค.ศ.
     *
     * @param string $text
     *
     * @return string วันเกิด ค.ศ. Y-m-d หรือค่าว่าง
     */
    public static function parseBirthday($text)
    {
        $text = trim((string) $text);
        if (preg_match('/^([0-9]{4})[\-\/]([0-9]{1,2})[\-\/]([0-9]{1,2})/', $text, $match)) {
            list($year, $month, $day) = [(int) $match[1], (int) $match[2], (int) $match[3]];
        } elseif (preg_match('/^([0-9]{1,2})[\-\/]([0-9]{1,2})[\-\/]([0-9]{4})/', $text, $match)) {
            list($year, $month, $day) = [(int) $match[3], (int) $match[2], (int) $match[1]];
        } else {
            return '';
        }
        if ($year >= 2400) {
            $year -= self::BUDDHIST_YEAR;
        }
        if (!checkdate($month, $day, $year)) {
            return '';
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * ตัวเลขล้วน (เลขประชาชน เบอร์โทร)
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function digits($value)
    {
        return preg_replace('/[^0-9]+/', '', (string) $value);
    }

    /**
     * ค่านี้ยังไม่ถูกใช้โดยสมาชิกคนอื่น (username / phone / id_card ของตาราง user เป็น UNIQUE)
     *
     * @param string $field
     * @param string $value
     * @param int $excludeId
     *
     * @return bool
     */
    public static function isUnique($field, $value, $excludeId = 0)
    {
        if ($value === '' || $value === null) {
            return true;
        }

        return \Index\UserRepository\Model::isFieldUnique($field, $value, (int) $excludeId);
    }

    /**
     * สร้างสมาชิกใหม่
     *
     * @param \Kotchasan\DB $db
     * @param array $user name phone birthday sex status ...
     * @param string $username ค่าว่าง = เข้าระบบไม่ได้
     * @param string $password
     *
     * @return int id ของสมาชิก
     */
    public static function create($db, array $user, $username = '', $password = '')
    {
        $hash = \Index\Auth\Model::hashPassword($password === '' ? \Kotchasan\Password::uniqid(16) : $password);
        $save = array_merge([
            'sex' => null,
            'birthday' => null,
            'phone' => null,
            'active' => 1,
            'social' => 'user',
            'permission' => ''
        ], $user, [
            'username' => $username === '' ? null : $username,
            'password' => $hash['hash'],
            'salt' => $hash['salt'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $save['phone'] = empty($save['phone']) ? null : $save['phone'];
        $save['birthday'] = empty($save['birthday']) ? null : $save['birthday'];

        return (int) $db->insert('user', $save);
    }

    /**
     * ปรับปรุงสมาชิก (ไม่ส่ง username = ไม่เปลี่ยนชื่อผู้ใช้และรหัสผ่าน)
     *
     * @param \Kotchasan\DB $db
     * @param int $id
     * @param array $user
     * @param string|null $username
     * @param string $password
     *
     * @return void
     */
    public static function update($db, $id, array $user, $username = null, $password = '')
    {
        if (array_key_exists('phone', $user)) {
            $user['phone'] = empty($user['phone']) ? null : $user['phone'];
        }
        if (array_key_exists('birthday', $user)) {
            $user['birthday'] = empty($user['birthday']) ? null : $user['birthday'];
        }
        if ($username !== null && $username !== '' && $password !== '') {
            $hash = \Index\Auth\Model::hashPassword($password);
            $user['username'] = $username;
            $user['password'] = $hash['hash'];
            $user['salt'] = $hash['salt'];
        }
        if (!empty($user)) {
            $db->update('user', [['id', (int) $id]], $user);
        }
    }
}
