<?php
/**
 * @filesource modules/school/models/course.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Course;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-course
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * @var array
     */
    private $datas = [];

    /**
     * อ่านรายวิชาจากฐานข้อมูล
     *
     * @param int $teacher_id มากกว่า คืนค่ารายวิชาตามชั้นที่เลือก
     *
     * @return static
     */
    public static function init($teacher_id = 0)
    {
        $obj = new static;
        $query = \Kotchasan\Model::createQuery()
            ->select('course_name', 'course_code')
            ->from('course')
            ->groupBy('course_code')
            ->toArray()
            ->cacheOn();
        if ($teacher_id > 0) {
            $query->where(array('teacher_id', $teacher_id));
        }
        foreach ($query->execute() as $item) {
            $obj->datas[$item['course_code']] = $item['course_name'].' ('.$item['course_code'].')';
        }
        return $obj;
    }

    /**
     * คืนค่ารายชื่อรายวิชาใส่ลงใน select
     *
     * @return array
     */
    public function toSelect()
    {
        return $this->datas;
    }

    /**
     * อ่านข้อมูลรายการที่เลือก
     * ถ้าข้อมูลที่ส่งมา id = 0 หมายถึงรายการใหม่.
     *
     * @param Request $request
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function getForWrite(Request $request)
    {
        $id = $request->request('id')->toInt();
        if (empty($id)) {
            return (object) array(
                'id' => 0,
                'course_code' => '',
                'course_name' => '',
                'period' => '',
                'credit' => '',
                'type' => 1,
                'teacher_id' => $request->request('teacher')->toInt(),
                'class' => $request->request('class')->toInt(),
                'year' => self::$cfg->academic_year,
                'term' => self::$cfg->term
            );
        } else {
            // อ่านข้อมูลที่ $id
            return self::find($id);
        }
    }

    /**
     * อ่านข้อมูลที่ $id.
     *
     * @param int $id
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function find($id)
    {
        return \Kotchasan\Model::createQuery()
            ->from('course')
            ->where(array('id', $id))
            ->first();
    }

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (course.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, สมาชิก
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if ($login['active'] == 1) {
                try {
                    // ค่าที่ส่งมา
                    $save = array(
                        'course_name' => $request->post('course_name')->topic(),
                        'course_code' => $request->post('course_code')->topic(),
                        'class' => $request->post('class')->toInt(),
                        'type' => $request->post('type')->toInt(),
                        'period' => $request->post('period')->toInt(),
                        'credit' => $request->post('credit')->toDouble(),
                        'year' => $request->post('year')->toInt(),
                        'term' => $request->post('term')->toInt()
                    );
                    // ตรวจสอบรายการที่เลือก
                    $index = self::getForWrite($request);
                    if (!$index) {
                        $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
                    } else {
                        if (Login::checkPermission($login, 'can_manage_course')) {
                            // สามารถจัดการรายวิชาทั้งหมดได้ ใช้ครูจากที่เลือก
                            $save['teacher_id'] = $request->post('teacher_id')->toInt();
                        } elseif ($index->id == 0) {
                            // ใหม่ ใช้ครูจากที่ login
                            $save['teacher_id'] = $login['id'];
                        } elseif ($login['id'] != $index->teacher_id) {
                            // ครู แก้ไข ที่ตัวเองรับผิดชอบเท่านั้น
                            $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
                        }
                        if (empty($ret)) {
                            // ใหม่และไม่ได้ระบุผู้สอนไม่ต้องมีปีการศึกษาและเทอม
                            if ($index->id == 0 && empty($save['teacher_id'])) {
                                $save['year'] = 0;
                                $save['term'] = 0;
                            }
                            // course_name
                            if ($save['course_name'] == '') {
                                $ret['ret_course_name'] = 'Please fill in';
                            }
                            // Model
                            $model = new \Kotchasan\Model();
                            // ตรวจสอบข้อมูลซ้ำ
                            $search = $model->db()->createQuery()
                                ->select('id', 'teacher_id')
                                ->from('course')
                                ->where(array(
                                    array('course_code', $save['course_code']),
                                    array('year', $save['year']),
                                    array('term', $save['term'])
                                ))
                                ->toArray();
                            $course_exists = false;
                            foreach ($search->execute() as $item) {
                                if (!empty($save['teacher_id']) && $item['teacher_id'] == $save['teacher_id'] && $item['id'] != $index->id) {
                                    $course_exists = true;
                                } elseif (empty($save['teacher_id']) && empty($item['teacher_id']) && $item['id'] != $index->id) {
                                    $course_exists = true;
                                }
                            }
                            if ($course_exists) {
                                $ret['ret_course_code'] = Language::replace('This :name already exist', array(':name' => Language::get('Course Code')));
                            }
                        }
                        if (empty($ret)) {
                            if ($index->id == 0) {
                                // ใหม่
                                $index->id = $model->db()->insert($model->getTableName('course'), $save);
                                // แสดงรายการใหม่
                                $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'school-courses', 'id' => 0, 'page' => 1, 'sort' => 'id desc'));
                            } else {
                                // แก้ไข
                                $model->db()->update($model->getTableName('course'), $index->id, $save);
                                // กลับไปหน้าก่อนหน้า
                                $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'school-courses', 'id' => 0));
                            }
                            // log
                            \Index\Log\Model::add($index->id, 'school', 'Save', '{LNG_Course} ID : '.$index->id, $login['id']);
                            // ส่งค่ากลับ
                            $ret['alert'] = Language::get('Saved successfully');
                        }
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
