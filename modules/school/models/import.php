<?php
/**
 * @filesource modules/school/models/import.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Import;

use Kotchasan\Language;
use Kotchasan\Text;

/**
 * นำเข้านักเรียน รายวิชา และผลการเรียนจากไฟล์ CSV (กติกาเดียวกับระบบเดิม)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * @var \Kotchasan\DB
     */
    private $db;

    /**
     * @var array
     */
    private $header = [];

    /**
     * ค่าที่เลือกในฟอร์ม (หมวดหมู่ตั้งต้นของนักเรียน)
     *
     * @var array
     */
    private $params = [];

    /**
     * @var object
     */
    private $login;

    /**
     * จำนวนที่นำเข้าสำเร็จ
     *
     * @var int
     */
    public $row = 0;

    /**
     * จำนวนที่ไม่ได้เก็บเบอร์โทรเพราะซ้ำกับสมาชิกคนอื่น
     *
     * @var int
     */
    public $phoneSkipped = 0;

    /**
     * นำเข้าไฟล์
     *
     * @param string $type student|course|grade
     * @param string $file
     * @param array $params
     * @param object $login
     *
     * @return static
     */
    public static function import($type, $file, array $params, $login)
    {
        $obj = new static();
        $obj->db = \Kotchasan\DB::create();
        $obj->params = $params;
        $obj->login = $login;
        $obj->header = \School\Csv\Model::$type();
        \Kotchasan\Csv::read($file, [$obj, 'import'.ucfirst($type)], $obj->header, self::$cfg->csv_language);

        return $obj;
    }

    /**
     * ไฟล์ตัวอย่าง
     *
     * @param string $type
     * @param array $params
     *
     * @return array แถวข้อมูล (ไม่รวมหัวคอลัมน์)
     */
    public static function sample($type, array $params)
    {
        if ($type === 'student') {
            $birthday = ((int) date('Y') + \Personnel\Account\Model::BUDDHIST_YEAR).'-01-31';
            $rows = [
                [1, '1000', 'นาย สมชาย มาดแมน', '', $birthday, '0123456789', 'm', '', '', ''],
                [2, '1001', 'นางสาว สมหญิง สวยงาม', '', $birthday, '0123456788', 'f', '', '', '']
            ];
            foreach (array_keys(\School\Category\Model::studentTypies()) as $key) {
                $rows[0][] = (int) ($params[$key] ?? 0);
                $rows[1][] = (int) ($params[$key] ?? 0);
            }

            return $rows;
        }
        if ($type === 'course') {
            $teacher_id = (int) ($params['teacher_id'] ?? 0);

            return [[
                '',
                '',
                '',
                '',
                (int) ($params['typ'] ?? 0),
                (int) ($params['class'] ?? 0),
                (int) ($params['year'] ?? 0),
                (int) ($params['term'] ?? 0),
                $teacher_id == 0 ? '' : $teacher_id
            ]];
        }
        // grade : รายชื่อนักเรียนในห้องที่เลือกของชั้นของรายวิชา ไม่มีก็ใส่ตัวอย่าง 2 แถว
        $course = (string) ($params['course'] ?? '');
        $room = (int) ($params['room'] ?? 0);
        $year = (int) ($params['year'] ?? 0);
        $term = (int) ($params['term'] ?? 0);
        $gradeOnly = \School\Score\Model::gradeOnly();
        $rows = [];
        foreach (\School\Students\Model::lists($course, $room) as $item) {
            $rows[] = $gradeOnly
                ? [$course, $item['number'], $item['student_id'], '', $room, $year, $term]
                : [$course, $item['number'], $item['student_id'], '', '', '', $room, $year, $term];
        }
        if (empty($rows)) {
            $rows = $gradeOnly ? [
                [$course, 1, 1000, 4, $room, $year, $term],
                [$course, 2, 1001, 'ร.', $room, $year, $term]
            ] : [
                [$course, 1, 1000, 50, 50, 4, $room, $year, $term],
                [$course, 2, 1001, 0, 0, 'ร.', $room, $year, $term]
            ];
        }

        return $rows;
    }

    /**
     * นำเข้านักเรียน 1 แถว
     * ซ้ำ (เลขประชาชนหรือรหัสนักเรียน) = ข้าม
     *
     * @param array $data
     *
     * @return void
     */
    public function importStudent($data)
    {
        $h = $this->header;
        $user = [
            'name' => Text::topic($data[$h[2]] ?? ''),
            'phone' => \Personnel\Account\Model::digits($data[$h[5]] ?? ''),
            'sex' => in_array($data[$h[6]] ?? '', ['f', 'm'], true) ? $data[$h[6]] : null,
            'birthday' => \Personnel\Account\Model::parseBirthday($data[$h[4]] ?? '')
        ];
        if ($user['name'] === '') {
            return;
        }
        $student = [
            'number' => (int) ($data[$h[0]] ?? 0),
            'student_id' => Text::topic($data[$h[1]] ?? ''),
            'id_card' => \Personnel\Account\Model::digits($data[$h[3]] ?? ''),
            'address' => Text::topic($data[$h[7]] ?? ''),
            'parent' => Text::topic($data[$h[8]] ?? ''),
            'parent_phone' => Text::topic($data[$h[9]] ?? '')
        ];
        foreach (\School\Category\Model::studentTypies() as $key => $label) {
            // ช่องในไฟล์ว่าง ใช้ค่าที่เลือกไว้ในฟอร์มนำเข้า
            $value = isset($data[$label]) ? trim($data[$label]) : '';
            $student[$key] = $value === '' ? (int) ($this->params[$key] ?? 0) : (int) $value;
        }
        // ตรวจซ้ำ เลขประชาชน หรือ รหัสนักเรียน
        $where = [];
        if ($student['id_card'] !== '') {
            $where[] = ['id_card', $student['id_card']];
        }
        if ($student['student_id'] !== '') {
            $where[] = ['student_id', $student['student_id']];
        }
        if (!empty($where)) {
            $exists = \Kotchasan\Model::createQuery()
                ->select('id')
                ->from('student')
                ->where($where, 'OR')
                ->first();
            if ($exists) {
                return;
            }
        }
        $username = '';
        $password = '';
        if ($student['id_card'] !== '' && $user['birthday'] !== '') {
            $username = $student['id_card'];
            $password = \Personnel\Account\Model::birthdayPassword($user['birthday']);
            if (!\Personnel\Account\Model::isUnique('username', $username)) {
                return;
            }
        }
        if (!\Personnel\Account\Model::isUnique('phone', $user['phone'])) {
            $user['phone'] = '';
            ++$this->phoneSkipped;
        }
        $user['status'] = (int) self::$cfg->student_status;
        $student['id'] = \Personnel\Account\Model::create($this->db, $user, $username, $password);
        $student['id_card'] = $student['id_card'] === '' ? null : $student['id_card'];
        $this->db->insert('student', $student);
        ++$this->row;
    }

    /**
     * นำเข้ารายวิชา 1 แถว
     * ไม่ระบุครู = รายวิชาต้นแบบ (ปีการศึกษา/ภาคเรียนเป็น 0) ซ้ำ = ข้าม
     *
     * @param array $data
     *
     * @return void
     */
    public function importCourse($data)
    {
        $h = $this->header;
        $course = [
            'course_code' => Text::topic($data[$h[0]] ?? ''),
            'course_name' => Text::topic($data[$h[1]] ?? ''),
            'credit' => round((float) ($data[$h[2]] ?? 0), 1),
            'period' => (int) ($data[$h[3]] ?? 0),
            'type' => (int) ($data[$h[4]] ?? 0),
            'class' => (int) ($data[$h[5]] ?? 0),
            'year' => (int) ($data[$h[6]] ?? 0),
            'term' => (int) ($data[$h[7]] ?? 0),
            'teacher_id' => (int) ($data[$h[8]] ?? 0)
        ];
        if ($course['course_code'] === '' || $course['course_name'] === '') {
            return;
        }
        if ($course['teacher_id'] > 0 && !\Gcms\Api::hasPermission($this->login, 'can_manage_course')) {
            // ครูนำเข้าได้เฉพาะรายวิชาของตัวเอง
            $course['teacher_id'] = (int) $this->login->id;
        }
        $where = [['course_code', $course['course_code']]];
        if ($course['teacher_id'] === 0) {
            $course['year'] = 0;
            $course['term'] = 0;
        } else {
            $where[] = ['teacher_id', $course['teacher_id']];
            $where[] = ['year', $course['year']];
            $where[] = ['term', $course['term']];
        }
        if ($this->db->first('course', $where, ['id'])) {
            return;
        }
        $this->db->insert('course', $course);
        ++$this->row;
    }

    /**
     * นำเข้าผลการเรียน 1 แถว (ลงทะเบียน + ผลการเรียน)
     * รายวิชาในปีการศึกษา/ภาคเรียนนั้นยังไม่มี แต่มีรหัสวิชานี้อยู่ = สร้างรายวิชาของปีนั้นให้
     * นำเข้าซ้ำ = ปรับปรุงผลการเรียนล่าสุด
     *
     * @param array $data
     *
     * @return void
     */
    public function importGrade($data)
    {
        $h = $this->header;
        $code = Text::topic($data[$h[0]] ?? '');
        $grade = [
            'number' => (int) ($data[$h[1]] ?? 0),
            'student_id' => Text::topic($data[$h[2]] ?? '')
        ];
        if (\School\Score\Model::gradeOnly()) {
            $grade['midterm'] = 0;
            $grade['final'] = 0;
            $grade['grade'] = Text::topic($data[$h[3]] ?? '');
            $grade['room'] = (int) ($data[$h[4]] ?? 0);
            $year = (int) ($data[$h[5]] ?? 0);
            $term = (int) ($data[$h[6]] ?? 0);
        } else {
            $grade['midterm'] = (int) ($data[$h[3]] ?? 0);
            $grade['final'] = (int) ($data[$h[4]] ?? 0);
            $grade['grade'] = Text::topic($data[$h[5]] ?? '');
            $grade['room'] = (int) ($data[$h[6]] ?? 0);
            $year = (int) ($data[$h[7]] ?? 0);
            $term = (int) ($data[$h[8]] ?? 0);
        }
        if ($code === '' || $grade['student_id'] === '') {
            return;
        }
        $student = $this->db->first('student', [['student_id', $grade['student_id']]], ['id']);
        $template = $this->db->first('course', [['course_code', $code]]);
        if (!$student || !$template) {
            return;
        }
        $isManager = \Gcms\Api::hasPermission($this->login, 'can_manage_course');
        $course = $this->db->first('course', [['course_code', $code], ['year', $year], ['term', $term]]);
        if ($course) {
            if (!$isManager && (int) $course->teacher_id !== (int) $this->login->id) {
                // ครูนำเข้าผลการเรียนได้เฉพาะรายวิชาที่ตัวเองสอน
                return;
            }
            $course_id = (int) $course->id;
        } else {
            // ลงทะเบียนรายวิชาใหม่ของปีการศึกษา/ภาคเรียนนี้จากรายวิชาที่มีรหัสเดียวกัน
            $save = (array) $template;
            unset($save['id']);
            $save['year'] = $year;
            $save['term'] = $term;
            $save['teacher_id'] = (int) $this->login->status === (int) self::$cfg->teacher_status ? (int) $this->login->id : 0;
            $course_id = (int) $this->db->insert('course', $save);
        }
        $grade['course_id'] = $course_id;
        $grade['student_id'] = (int) $student->id;
        $type = array_search($grade['grade'], (array) Language::get('SCHOOL_TYPIES', []), true);
        if ($type === false || $type === 0) {
            $grade['type'] = 0;
            $grade['grade'] = \School\Score\Model::toGrade(0, $grade['midterm'], $grade['final'], $grade['grade']);
        } else {
            $grade['type'] = (int) $type;
        }
        $exists = $this->db->first('grade', [
            ['student_id', $grade['student_id']],
            ['course_id', $grade['course_id']],
            ['room', $grade['room']]
        ], ['id']);
        if ($exists) {
            $this->db->update('grade', [['id', (int) $exists->id]], $grade);
        } else {
            $this->db->insert('grade', $grade);
        }
        ++$this->row;
    }
}
