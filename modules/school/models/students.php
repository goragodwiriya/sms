<?php
/**
 * @filesource modules/school/models/students.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Students;

/**
 * query ของรายชื่อนักเรียน
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ห้ามมี orderBy — Gcms\Table ห่อ query นี้เป็น COUNT(*) subquery
     *
     * @param array $params search, active และหมวดหมู่ของนักเรียน
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable(array $params)
    {
        $where = [];
        if (isset($params['active']) && ($params['active'] === 0 || $params['active'] === 1)) {
            $where[] = ['U.active', $params['active']];
        }
        $select = ['S.id', 'S.number', 'S.student_id', 'U.name', 'U.phone', 'U.active'];
        foreach (array_keys(\School\Category\Model::studentTypies()) as $type) {
            if (!empty($params[$type])) {
                $where[] = ['S.'.$type, (int) $params[$type]];
            }
            $select[] = 'S.'.$type;
        }
        $query = static::createQuery()
            ->select(...$select)
            ->from('student S')
            ->join('user U', [['U.id', 'S.id']], 'INNER')
            ->where($where);
        if (!empty($params['search'])) {
            $keyword = '%'.$params['search'].'%';
            $query->where([
                ['U.name', 'LIKE', $keyword],
                ['S.student_id', 'LIKE', $keyword]
            ], 'OR');
        }

        return $query;
    }

    /**
     * รายชื่อนักเรียนที่เรียนรายวิชานี้ได้ (ชั้นเดียวกับรายวิชา) ในห้องที่เลือก
     * ใช้เติมไฟล์ตัวอย่างของการนำเข้าผลการเรียน
     *
     * @param string $course_code
     * @param int $room
     *
     * @return array
     */
    public static function lists($course_code, $room)
    {
        $classes = [];
        foreach (\Kotchasan\DB::create()->select('course', [['course_code', $course_code]], [], ['class']) as $item) {
            $classes[(int) $item->class] = (int) $item->class;
        }
        if (empty($classes)) {
            return [];
        }

        return static::createQuery()
            ->select('number', 'student_id')
            ->from('student')
            ->where([
                ['class', array_values($classes)],
                ['room', (int) $room],
                ['student_id', '!=', '']
            ])
            ->orderBy('number')
            ->orderBy('student_id')
            ->fetchAll(true);
    }
}
