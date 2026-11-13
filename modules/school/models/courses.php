<?php
/**
 * @filesource modules/school/models/courses.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Courses;

use Kotchasan\Database\Sql;

/**
 * query ของรายวิชา
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
     * @param array $params teacher year term class search
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable(array $params)
    {
        $where = [];
        foreach (['teacher' => 'teacher_id', 'year' => 'year', 'term' => 'term', 'class' => 'class'] as $key => $field) {
            if (!empty($params[$key])) {
                $where[] = ['C.'.$field, (int) $params[$key]];
            }
        }
        $students = static::createQuery()
            ->selectRaw('COUNT(*)')
            ->from('grade G')
            ->where([['G.course_id', Sql::column('C.id')]]);
        $query = static::createQuery()
            ->select('C.id', 'C.course_code', 'C.course_name', 'C.type', 'C.teacher_id', 'C.year', 'C.term', 'C.class', 'C.credit', 'C.period', [$students, 'student'])
            ->from('course C')
            ->where($where);
        if (!empty($params['search'])) {
            $keyword = '%'.$params['search'].'%';
            $query->where([
                ['C.course_code', 'LIKE', $keyword],
                ['C.course_name', 'LIKE', $keyword]
            ], 'OR');
        }

        return $query;
    }

    /**
     * ปีการศึกษาที่มีรายวิชา + ปีปัจจุบัน
     *
     * @return array
     */
    public static function academicYears()
    {
        $years = [];
        $query = static::createQuery()
            ->select('year')
            ->from('course')
            ->where([['year', '>', 0]])
            ->groupBy('year');
        foreach ($query->fetchAll() as $item) {
            $years[(int) $item->year] = (int) $item->year;
        }
        $years[(int) self::$cfg->academic_year] = (int) self::$cfg->academic_year;
        ksort($years);

        return $years;
    }

    /**
     * รายวิชา (ไม่ซ้ำรหัส) สำหรับเลือกตอนนำเข้าผลการเรียน
     *
     * @param int $teacher_id 0 = ทุกรายวิชา
     *
     * @return array [{value, text}]
     */
    public static function codeOptions($teacher_id = 0)
    {
        $query = static::createQuery()
            ->select('course_code', 'course_name')
            ->from('course')
            ->where([['course_code', '!=', '']])
            ->groupBy(['course_code', 'course_name'])
            ->orderBy('course_code');
        if ($teacher_id > 0) {
            $query->where([['teacher_id', (int) $teacher_id]]);
        }
        $result = [];
        foreach ($query->fetchAll() as $item) {
            if (!isset($result[$item->course_code])) {
                $result[$item->course_code] = ['value' => $item->course_code, 'text' => $item->course_name.' ('.$item->course_code.')'];
            }
        }

        return array_values($result);
    }
}
