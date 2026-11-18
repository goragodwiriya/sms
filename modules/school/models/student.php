<?php
/**
 * @filesource modules/school/models/student.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Student;

/**
 * ข้อมูลนักเรียนหนึ่งคน (student + user)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านนักเรียนตาม id
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function get($id)
    {
        return static::createQuery()
            ->select('S.*', 'U.name', 'U.birthday', 'U.phone', 'U.sex', 'U.active', 'U.status', 'U.username')
            ->from('student S')
            ->join('user U', [['U.id', 'S.id']], 'INNER')
            ->where([['S.id', (int) $id]])
            ->first();
    }

    /**
     * อ่านนักเรียนสำหรับฟอร์ม (0 = รายการใหม่ เติมรหัสนักเรียนถัดไปและหมวดหมู่ที่เลือกไว้)
     *
     * @param int $id
     * @param array $params หมวดหมู่ที่กรองอยู่ในหน้ารายชื่อ
     *
     * @return object|null
     */
    public static function getForWrite($id, array $params = [])
    {
        if ((int) $id > 0) {
            return self::get($id);
        }
        $next = static::createQuery()
            ->selectRaw('MAX(CAST(`student_id` AS UNSIGNED)) AS `max`')
            ->from('student')
            ->first();
        $item = (object) [
            'id' => 0,
            'student_id' => (string) (($next ? (int) $next->max : 0) + 1),
            'name' => '',
            'id_card' => '',
            'birthday' => '',
            'sex' => 'f',
            'phone' => '',
            'address' => '',
            'parent' => '',
            'parent_phone' => '',
            'number' => null,
            'active' => 1
        ];
        foreach (array_keys(\School\Category\Model::studentTypies()) as $type) {
            $item->$type = isset($params[$type]) ? (int) $params[$type] : 0;
        }

        return $item;
    }

    /**
     * เลขประชาชนหรือรหัสนักเรียนซ้ำกับนักเรียนคนอื่นหรือไม่
     *
     * @param int $id
     * @param array $student id_card, student_id
     *
     * @return string|false ชื่อช่องที่ซ้ำ หรือ false
     */
    public static function exists($id, array $student)
    {
        foreach (['student_id', 'id_card'] as $field) {
            if (!empty($student[$field])) {
                $search = \Kotchasan\DB::create()->first('student', [[$field, $student[$field]]], ['id']);
                if ($search && (int) $search->id !== (int) $id) {
                    return $field;
                }
            }
        }

        return false;
    }

    /**
     * รูปของนักเรียน
     *
     * @param int $id
     *
     * @return string|null
     */
    public static function picture($id)
    {
        $file = DATA_FOLDER.'school/'.(int) $id.self::$cfg->stored_img_type;

        return is_file(ROOT_PATH.$file) ? WEB_URL.$file : null;
    }

    /**
     * ลบนักเรียน เฉพาะคนที่ยังไม่มีผลการเรียน (เหมือนระบบเดิม) ยกเว้น id 1
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
        $query = static::createQuery()
            ->select('S.id')
            ->from('student S')
            ->where([['S.id', $ids]])
            ->whereNotExists('grade G', [['G.student_id', 'S.id']]);
        $removed = [];
        foreach ($query->fetchAll() as $item) {
            $removed[] = (int) $item->id;
            $file = ROOT_PATH.DATA_FOLDER.'school/'.(int) $item->id.self::$cfg->stored_img_type;
            if (is_file($file)) {
                @unlink($file);
            }
        }
        if (!empty($removed)) {
            $db = \Kotchasan\DB::create();
            $db->delete('student', [['id', $removed]], 0);
            $db->delete('user_meta', [['member_id', $removed]], 0);
            $db->delete('user', [['id', $removed]], 0);
            foreach ($removed as $id) {
                \Index\Auth\Model::logoutAllSessions($id);
            }
        }

        return $removed;
    }

    /**
     * ปีการศึกษาที่นักเรียนคนนี้มีผลการเรียน + ปีปัจจุบัน
     *
     * @param int $student_id
     *
     * @return array
     */
    public static function academicYears($student_id)
    {
        $query = static::createQuery()
            ->select('C.year')
            ->from('grade G')
            ->join('course C', [['C.id', 'G.course_id']], 'INNER')
            ->where([
                ['G.student_id', (int) $student_id],
                ['C.year', '>', 0]
            ])
            ->groupBy('C.year');
        $years = [];
        foreach ($query->fetchAll() as $item) {
            $years[(int) $item->year] = (int) $item->year;
        }
        $years[(int) self::$cfg->academic_year] = (int) self::$cfg->academic_year;
        ksort($years);

        return $years;
    }
}
