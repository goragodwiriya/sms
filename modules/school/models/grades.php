<?php
/**
 * @filesource modules/school/models/grades.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Grades;

/**
 * ผลการเรียนของรายวิชา (นักเรียนที่ลงทะเบียน)
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
     * @param array $params subject room search
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable(array $params)
    {
        $where = [['G.course_id', (int) $params['subject']]];
        if (!empty($params['room'])) {
            $where[] = ['G.room', (int) $params['room']];
        }
        $query = static::createQuery()
            ->select('G.id', 'G.number', 'S.student_id', 'U.name', 'G.room', 'G.type', 'G.midterm', 'G.final', 'G.grade', 'G.student_id student')
            ->from('grade G')
            ->join('student S', [['S.id', 'G.student_id']], 'LEFT')
            ->join('user U', [['U.id', 'G.student_id']], 'LEFT')
            ->where($where);
        if (!empty($params['search'])) {
            $keyword = '%'.$params['search'].'%';
            $query->where([
                ['S.student_id', 'LIKE', $keyword],
                ['U.name', 'LIKE', $keyword]
            ], 'OR');
        }

        return $query;
    }

    /**
     * ผลการเรียนของรายวิชา สำหรับดาวน์โหลด (เหมือนระบบเดิม)
     *
     * @param int $course_id
     * @param int $room
     *
     * @return array
     */
    public static function export($course_id, $room)
    {
        $where = [['G.course_id', (int) $course_id]];
        if ($room > 0) {
            $where[] = ['G.room', (int) $room];
        }

        return static::createQuery()
            ->select('G.number', 'S.student_id', 'U.name', 'C.course_code', 'C.year', 'C.term', 'C.class', 'G.room', 'G.grade')
            ->from('grade G')
            ->join('course C', [['C.id', 'G.course_id']], 'INNER')
            ->join('student S', [['S.id', 'G.student_id']], 'LEFT')
            ->join('user U', [['U.id', 'G.student_id']], 'LEFT')
            ->where($where)
            ->orderBy('G.room')
            ->orderBy('G.number')
            ->orderBy('G.student_id')
            ->fetchAll(true);
    }
}
