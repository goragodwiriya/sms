<?php
/**
 * @filesource modules/school/models/register.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Register;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-register
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * query นักเรียน สำหรับใส่ลงในตารางลงทะเบียนเรียน (register.php)
     *
     * @param int $course_id
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($course_id)
    {
        $model = new static;
        $q2 = $model->db()->createQuery()
            ->select('id')
            ->from('grade')
            ->where(array(
                array('course_id', $course_id),
                array('student_id', 'S.id')
            ))
            ->limit(1);

        return $model->db()->createQuery()
            ->select('S.id', 'S.number', 'S.student_id', 'U.name', array($q2, 'grade'), 'S.class', 'S.room')
            ->from('student S')
            ->join('user U', 'INNER', array('U.id', 'S.id'))
            ->where(array('U.active', 1))
            ->order('S.class', 'S.room', 'S.number', 'S.id', 'S.id DESC');
    }

    /**
     * รับค่าจาก action
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = [];
        // session, referer, member
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if ($login['active'] == 1) {
                // รับค่าจากการ POST
                $action = $request->post('action')->toString();
                // id ที่ส่งมา
                if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                    // Model
                    $model = new \Kotchasan\Model();
                    // ตาราง
                    $table = $model->getTableName('grade');
                    if (preg_match('/register_([0-9]+)/', $action, $match2)) {
                        // ลงทะเบียนเรียน
                        $course_id = (int) $match2[1];
                        $query = $model->db()->createQuery()
                            ->select('student_id')
                            ->from('grade')
                            ->where(array('course_id', $course_id))
                            ->toArray();
                        $grade = [];
                        foreach ($query->execute() as $item) {
                            $grade[$item['student_id']] = $item;
                        }
                        // อ่านข้อมูลนักเรียนที่เลือก
                        $query = $model->db()->createQuery()
                            ->select('id', 'number', 'room')
                            ->from('student')
                            ->where(array('id', $match[1]))
                            ->toArray()
                            ->order('number');
                        foreach ($query->execute() as $item) {
                            if (!isset($grade[$item['id']])) {
                                $model->db()->insert($table, array(
                                    'student_id' => $item['id'],
                                    'course_id' => $course_id,
                                    'number' => $item['number'],
                                    'room' => $item['room']
                                ));
                            }
                        }
                        // log
                        \Index\Log\Model::add($course_id, 'school', 'Save', '{LNG_Register course} ID : '.implode(', ', $match[1]), $login['id']);
                        // คืนค่า
                        $ret['alert'] = Language::get('Saved successfully');
                        $ret['location'] = 'reload';
                    } elseif ($action === 'view') {
                        // ดูรายละเอียดนักเรียน
                        $search = \School\User\Model::get((int) $match[1][0]);
                        if ($search) {
                            $ret['modal'] = Language::trans(\School\Studentinfo\View::create()->render($search, $login));
                        }
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่า JSON
        echo json_encode($ret);
    }
}
