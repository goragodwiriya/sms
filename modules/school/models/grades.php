<?php
/**
 * @filesource modules/school/models/grades.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Grades;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-grades
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * query นักเรียน ที่ลงทะเบียนเรียนแล้ว (grades.php)
     *
     * @param int $course_id
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($course_id)
    {
        return static::createQuery()
            ->select('G.id', 'G.number', 'S.student_id', 'U.name', 'G.room', 'G.type', 'G.midterm', 'G.final', 'G.grade', 'G.student_id student')
            ->from('grade G')
            ->join('student S', 'LEFT', array('S.id', 'G.student_id'))
            ->join('user U', 'LEFT', array('U.id', 'S.id'))
            ->where(array('G.course_id', $course_id));
    }

    /**
     * รับค่าจาก action (grades.php)
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
                    // Database
                    $db = $this->db();
                    // ตาราง
                    $table = $this->getTableName('grade');
                    if ($action === 'delete' && Login::checkPermission($login, array('can_manage_student', 'can_manage_course', 'can_teacher'))) {
                        // ลบ
                        $db->delete($table, array('id', $match[1]), 0);
                        // log
                        \Index\Log\Model::add(0, 'school', 'Delete', '{LNG_Delete} {LNG_Grade} ID : '.implode(', ', $match[1]), $login['id']);
                        // reload
                        $ret['location'] = 'reload';
                    } elseif (($action === 'number' || $action === 'room') && Login::checkPermission($login, array('can_manage_student', 'can_manage_course', 'can_teacher'))) {
                        // อัปเดตข้อมูล เลขที่ ห้อง
                        $value = $request->post('value')->topic();
                        $id = (int) $match[1][0];
                        $db->update($table, $id, array($action => $value));
                        // log
                        \Index\Log\Model::add(0, 'school', 'Save', '{LNG_'.ucfirst($action).'} {LNG_Grade} ID : '.$match[1][0], $login['id']);
                        // คืนค่า
                        $ret[$action.'_'.$id] = $value;
                    } elseif ($action === 'type' && Login::checkPermission($login, array('can_manage_student', 'can_manage_course', 'can_teacher', 'can_rate_student'))) {
                        // อัปเดตข้อมูล เกรด
                        $grade = $db->first($table, (int) $match[1][0]);
                        if ($grade) {
                            $value = $request->post('value')->topic();
                            $save = array(
                                'type' => $value,
                                'grade' => \School\Score\Model::toGrade($value, $grade->midterm, $grade->final)
                            );
                            $db->update($table, $grade->id, $save);
                            // log
                            \Index\Log\Model::add(0, 'school', 'Save', '{LNG_'.ucfirst($action).'} {LNG_Grade} ID : '.$match[1][0], $login['id']);
                            // คืนค่า
                            $ret['type_'.$grade->id] = $save['type'];
                            $ret['grade_'.$grade->id] = $save['grade'];
                        }
                    } elseif (($action === 'midterm' || $action === 'final') && Login::checkPermission($login, array('can_manage_student', 'can_manage_course', 'can_teacher', 'can_rate_student'))) {
                        // กรอกคะแนน
                        $grade = $db->first($table, (int) $match[1][0]);
                        if ($grade) {
                            $value = $request->post('value')->topic();
                            $grade->{$action} = $value;
                            $save = array(
                                $action => $value,
                                'grade' => \School\Score\Model::toGrade($grade->type, $grade->midterm, $grade->final)
                            );
                            $db->update($table, $grade->id, $save);
                            // log
                            \Index\Log\Model::add(0, 'school', 'Save', '{LNG_'.ucfirst($action).'} {LNG_Grade} ID : '.$match[1][0], $login['id']);
                            // คืนค่า
                            $ret['grade_'.$grade->id] = $save['grade'];
                        }
                    } elseif ($action === 'view') {
                        // ดูรายละเอียดนักเรียน
                        $search = \School\User\Model::get((int) $match[1][0]);
                        if ($search) {
                            $ret['modal'] = Language::trans(\School\Studentinfo\View::create()->render($search, $login));
                        }
                    }
                } elseif ($action == 'export') {
                    // export เกรด
                    $params = $request->getParsedBody();
                    unset($params['action']);
                    unset($params['src']);
                    $params['module'] = 'school-download';
                    $params['type'] = 'grade';
                    $ret['location'] = WEB_URL.'export.php?'.http_build_query($params);
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
