<?php
/**
 * @filesource modules/school/models/import.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Import;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * นำเข้าข้อมูลจาก CSV
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @var int
     */
    private $row = 0;
    /**
     * @var array
     */
    private $params = [];
    /**
     * @var mixed
     */
    private $login;
    /**
     * @var array
     */
    private $header = [];
    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (import.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, can_manage_student
        if ($request->initSession() && $request->isSafe() && $this->login = Login::isMember()) {
            if ($this->login['active'] == 1) {
                // ค่าที่ส่งมา
                $type = $request->post('type')->toString();
                // อัปโหลดไฟล์ csv
                foreach ($request->getUploadedFiles() as $item => $file) {
                    /* @var $file \Kotchasan\Http\UploadedFile */
                    if ($file->hasUploadFile()) {
                        if (!$file->validFileExt(array('csv'))) {
                            // ชนิดของไฟล์ไม่ถูกต้อง
                            $ret['ret_'.$item] = Language::get('The type of file is invalid');
                        } else {
                            try {
                                // import data from CSV
                                if ($type == 'student' && Login::checkPermission($this->login, 'can_manage_student')) {
                                    // CSV Header
                                    $this->header = \School\Csv\Model::student();
                                    // หมวดหมู่ของนักเรียน
                                    $this->categories = Language::get('SCHOOL_CATEGORY', []);
                                    foreach ($this->categories as $key => $label) {
                                        $this->params[$key] = $request->post($key)->toInt();
                                    }
                                    // import ข้อมูล
                                    \Kotchasan\Csv::read(
                                        $file->getTempFileName(),
                                        array($this, 'importStudent'),
                                        $this->header,
                                        self::$cfg->csv_language
                                    );
                                    // ส่งค่ากลับ
                                    $ret_module = 'school-students';
                                } elseif ($type == 'grade' && Login::checkPermission($this->login, array('can_teacher', 'can_manage_course', 'can_rate_student'))) {
                                    // CSV Header
                                    $this->header = \School\Csv\Model::grade();
                                    // import ข้อมูล
                                    \Kotchasan\Csv::read(
                                        $file->getTempFileName(),
                                        array($this, 'importGrade'),
                                        $this->header,
                                        self::$cfg->csv_language
                                    );
                                    // ส่งค่ากลับ
                                    $ret_module = 'school-courses';
                                } elseif ($type == 'course' && Login::checkPermission($this->login, array('can_teacher', 'can_manage_student'))) {
                                    // CSV Header
                                    $this->header = \School\Csv\Model::course();
                                    // import ข้อมูล
                                    \Kotchasan\Csv::read(
                                        $file->getTempFileName(),
                                        array($this, 'importCourse'),
                                        $this->header,
                                        self::$cfg->csv_language
                                    );
                                    // ส่งค่ากลับ
                                    $ret_module = 'school-courses';
                                }
                                if (isset($ret_module)) {
                                    // คืนค่า
                                    $ret['location'] = $request->getUri()->postBack('index.php', array('module' => $ret_module, 'id' => 0));
                                    $ret['alert'] = Language::replace('Successfully imported :count items', array(':count' => ucfirst($type).' '.$this->row));
                                    // log
                                    \Index\Log\Model::add(0, 'personnel', 'Import', $ret['alert'], $this->login['id']);
                                }
                            } catch (\Exception $ex) {
                                $ret['ret_'.$item] = $ex->getMessage();
                            }
                        }
                    } elseif ($file->hasError()) {
                        // upload Error
                        $ret['ret_'.$item] = $file->getErrorMessage();
                    } else {
                        // ไม่ได้เลือกไฟล์
                        $ret['ret_'.$item] = 'Please browse file';
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }

    /**
     * ฟังก์ชั่นรับค่าจากการอ่าน CSV
     *
     * @param array $data
     */
    public function importCourse($data)
    {
        $course = array(
            'course_code' => Text::topic($data[$this->header[0]]),
            'course_name' => Text::topic($data[$this->header[1]]),
            'credit' => (float) $data[$this->header[2]],
            'period' => (int) $data[$this->header[3]],
            'type' => (int) $data[$this->header[4]],
            'class' => (int) $data[$this->header[5]],
            'year' => (int) $data[$this->header[6]],
            'term' => (int) $data[$this->header[7]],
            'teacher_id' => (int) $data[$this->header[8]]
        );
        if ($course['course_code'] != '' && $course['course_name'] != '') {
            $where = array(
                array('course_code', $course['course_code'])
            );
            if ($course['teacher_id'] == 0) {
                $course['year'] = 0;
                $course['term'] = 0;
            } else {
                $where[] = array('teacher_id', $course['teacher_id']);
                $where[] = array('year', $course['year']);
                $where[] = array('term', $course['term']);
            }
            // ตรวจสอบข้อมูลซ้ำ
            $search = $this->db()->createQuery()
                ->select('id', 'teacher_id')
                ->from('course')
                ->where($where)
                ->toArray()
                ->first();
            if (!$search) {
                // บันทึกเกรด
                $this->db()->insert($this->getTableName('course'), $course);
                // นำเข้าข้อมูลสำเร็จ
                ++$this->row;
            }
        }
    }

    /**
     * ฟังก์ชั่นรับค่าจากการอ่าน CSV
     *
     * @param array $data
     */
    public function importGrade($data)
    {
        $course = array(
            'course_code' => Text::topic($data[$this->header[0]])
        );
        $grade = array(
            'number' => (int) $data[$this->header[1]],
            'student_id' => Text::topic($data[$this->header[2]])
        );
        if (empty(self::$cfg->school_grade_caculations)) {
            $grade['midterm'] = 0;
            $grade['final'] = 0;
            $grade['grade'] = Text::topic($data[$this->header[3]]);
            $grade['room'] = (int) $data[$this->header[4]];
            $course['year'] = (int) $data[$this->header[5]];
            $course['term'] = (int) $data[$this->header[6]];
        } else {
            $grade['midterm'] = (int) $data[$this->header[3]];
            $grade['final'] = (int) $data[$this->header[4]];
            $grade['grade'] = Text::topic($data[$this->header[5]]);
            $grade['room'] = (int) $data[$this->header[6]];
            $course['year'] = (int) $data[$this->header[7]];
            $course['term'] = (int) $data[$this->header[8]];
        }
        // ตรวจสอบ id_card หรือ student_id ซ้ำ
        $q1 = $this->db()->createQuery()
            ->select('id')
            ->from('student')
            ->where(array('student_id', $grade['student_id']))
            ->limit(1);
        $q2 = $this->db()->createQuery()
            ->select('id')
            ->from('course')
            ->where(array(
                array('course_code', $course['course_code']),
                array('year', $course['year']),
                array('term', $course['term'])
            ))
            ->limit(1);
        $search = $this->db()->createQuery()
            ->from('course C')
            ->where(array('course_code', $course['course_code']))
            ->toArray()
            ->first('C.*', array($q1, 'student_id'), array($q2, 'course_id'));
        if ($search && $search['student_id']) {
            if (empty($search['course_id'])) {
                // ลงทะเบียนรายวิชาใหม่
                $save = $search;
                $save['year'] = $course['year'];
                $save['term'] = $course['term'];
                $save['teacher_id'] = $this->login['status'] == self::$cfg->teacher_status ? $this->login['id'] : 0;
                unset($save['id']);
                unset($save['course_id']);
                unset($save['student_id']);
                $grade['course_id'] = $this->db()->insert($this->getTableName('course'), $save);
            } else {
                // รายวิชาเดิม
                $grade['course_id'] = $search['course_id'];
            }
            $grade['student_id'] = $search['student_id'];
            $grade['type'] = array_search($grade['grade'], Language::get('SCHOOL_TYPIES'));
            if ($grade['type'] === false) {
                $grade['type'] = 0;
                $grade['grade'] = \School\Score\Model::toGrade($grade['type'], $grade['midterm'], $grade['final'], $grade['grade']);
            }
            // ตรวจสอบรายการซ้ำ
            $search = $this->db()->createQuery()
                ->from('grade')
                ->where(array(
                    array('student_id', $grade['student_id']),
                    array('course_id', $grade['course_id']),
                    array('room', $grade['room'])
                ))
                ->first('id');
            if (!$search) {
                // บันทึกเกรด
                $this->db()->insert($this->getTableName('grade'), $grade);
            } else {
                // อัปเดตเกรด
                $this->db()->update($this->getTableName('grade'), $search->id, $grade);
            }
            // นำเข้าข้อมูลสำเร็จ
            ++$this->row;
        }
    }

    /**
     * ฟังก์ชั่นรับค่าจากการอ่าน CSV
     *
     * @param array $data
     */
    public function importStudent($data)
    {
        $student = $this->params;
        $student['number'] = (int) $data[$this->header[0]];
        $student['student_id'] = Text::topic($data[$this->header[1]]);
        $student['id_card'] = preg_replace('/[^0-9]+/', '', $data[$this->header[3]]);
        $user = array(
            'name' => Text::topic($data[$this->header[2]]),
            'phone' => Text::topic($data[$this->header[5]]),
            'sex' => in_array($data[$this->header[6]], array('f', 'm')) ? $data[$this->header[6]] : '',
            'address' => Text::topic($data[$this->header[7]])
        );
        $student['parent'] = Text::topic($data[$this->header[8]]);
        $student['parent_phone'] = Text::topic($data[$this->header[9]]);
        foreach ($this->categories as $k => $label) {
            $student[$k] = (int) $data[$label];
        }
        // birthday
        if (preg_match('/([0-9]{4,4})[\-\/]([0-9]{1,2})[\-\/]([0-9]{1,2})/', $data[$this->header[4]], $match)) {
            $user['birthday'] = ((int) $match[1] - 543).'-'.$match[2].'-'.$match[3];
            $password = $match[1].sprintf('%02d', $match[2]).sprintf('%02d', $match[3]);
        } elseif (preg_match('/([0-9]{1,2})[\-\/]([0-9]{1,2})[\-\/]([0-9]{4,4})/', $data[$this->header[4]], $match)) {
            $user['birthday'] = ((int) $match[3] - 543).'-'.$match[2].'-'.$match[1];
            $password = $match[3].sprintf('%02d', $match[2]).sprintf('%02d', $match[1]);
        }
        if ($user['name'] != '') {
            if ($student['id_card'] != '' && isset($password)) {
                $user['username'] = $student['id_card'];
                $user['password'] = $password;
            }
            // ตรวจสอบ id_card หรือ student_id ซ้ำ
            $query = $this->db()->createQuery()
                ->from('student S')
                ->join('user U', 'INNER', array('U.id', 'S.id'))
                ->toArray();
            if ($student['id_card'] != '') {
                $query->where(array(
                    array('S.id_card', $student['id_card']),
                    array('S.student_id', $student['student_id'])
                ), 'OR');
            } else {
                $query->where(array('S.student_id', $student['student_id']));
            }
            $search = $query->first('S.id');
            if (!$search) {
                // สถานะนักเรียน
                $user['status'] = isset(self::$cfg->student_status) ? self::$cfg->student_status : 0;
                // register
                $user = \Index\Register\Model::execute($this, $user, []);
                // id ของ student
                $student['id'] = $user['id'];
                // บันทึก student
                $table_name = $this->getTableName('student');
                $this->db()->delete($table_name, array('id', $student['id']));
                $this->db()->insert($table_name, $student);
                // นำเข้าข้อมูลสำเร็จ
                ++$this->row;
            }
        }
    }
}
