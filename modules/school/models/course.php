<?php
/**
 * @filesource modules/school/models/course.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Course;

/**
 * รายวิชาหนึ่งรายการ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @param int $id
     *
     * @return object|null
     */
    public static function find($id)
    {
        return \Kotchasan\DB::create()->first('course', [['id', (int) $id]]);
    }

    /**
     * รายวิชาสำหรับฟอร์ม (0 = รายการใหม่ ปีการศึกษาและภาคเรียนเป็นของปัจจุบัน)
     *
     * @param int $id
     * @param int $teacher_id
     * @param int $class
     *
     * @return object|null
     */
    public static function getForWrite($id, $teacher_id = 0, $class = 0)
    {
        if ((int) $id > 0) {
            return self::find($id);
        }

        return (object) [
            'id' => 0,
            'course_code' => '',
            'course_name' => '',
            'period' => '',
            'credit' => '',
            'type' => 1,
            'teacher_id' => (int) $teacher_id,
            'class' => (int) $class,
            'year' => (int) self::$cfg->academic_year,
            'term' => (int) self::$cfg->term
        ];
    }

    /**
     * รหัสวิชาซ้ำในปีการศึกษา/ภาคเรียนเดียวกันของครูคนเดียวกัน (หรือรายวิชาต้นแบบด้วยกัน)
     *
     * @param int $id
     * @param array $save
     *
     * @return bool
     */
    public static function exists($id, array $save)
    {
        $search = \Kotchasan\DB::create()->select('course', [
            ['course_code', $save['course_code']],
            ['year', (int) $save['year']],
            ['term', (int) $save['term']]
        ], [], ['id', 'teacher_id']);
        foreach ($search as $item) {
            if ((int) $item->id === (int) $id) {
                continue;
            }
            if ((int) $item->teacher_id === (int) $save['teacher_id']) {
                return true;
            }
        }

        return false;
    }

    /**
     * รายวิชาที่รหัสขึ้นต้นด้วยคำค้น (เติมข้อมูลอัตโนมัติในฟอร์มรายวิชา)
     *
     * @param string $search
     *
     * @return array
     */
    public static function suggest($search)
    {
        $query = static::createQuery()
            ->select('course_code', 'course_name', 'period', 'credit', 'type')
            ->from('course')
            ->where([['course_code', 'LIKE', $search.'%']])
            ->orderBy('teacher_id')
            ->orderBy('course_code')
            ->limit(30);
        $result = [];
        foreach ($query->fetchAll() as $item) {
            if (isset($result[$item->course_code]) || count($result) >= 10) {
                continue;
            }
            $result[$item->course_code] = [
                'value' => $item->course_code,
                'text' => $item->course_code.' '.$item->course_name,
                'code' => $item->course_code,
                'course_name' => $item->course_name,
                'period' => (int) $item->period,
                'credit' => (string) $item->credit,
                'type' => (string) $item->type
            ];
        }

        return array_values($result);
    }
}
