<?php
/**
 * @filesource modules/school/models/students.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Students;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-students
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query ข้อมูลบุคลากรสำหรับส่งให้กับ DataTable
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable()
    {
        return static::createQuery()
            ->select('S.*', 'U.name', 'U.phone', 'U.active')
            ->from('student S')
            ->join('user U', 'INNER', array('U.id', 'S.id'));
    }

    /**
     * ลิสต์รายการนักเรียนตามชั้นเรียนและห้องที่เลือก
     *
     * @param string $course_code
     * @param int    $room
     *
     * @return array คืนค่าลิสต์ของรหัสนักเรียน
     */
    public static function lists($course_code, $room)
    {
        $model = new static;
        $q1 = $model->db()->createQuery()
            ->select('class')
            ->from('course')
            ->where(array('course_code', $course_code));

        return $model->db()->createQuery()
            ->select('number', 'student_id')
            ->from('student')
            ->where(array(
                array('class', 'IN', $q1),
                array('room', $room),
                array('student_id', '!=', '')
            ))
            ->order('number', 'student_id')
            ->toArray()
            ->cacheOn()
            ->execute();
    }

    /**
     * รับค่าจาก action.
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = [];
        // session, referer, member
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if ($login['active'] == 1) {
                $canManage = Login::checkPermission($login, array('can_manage_student', 'can_manage_course', 'can_teacher'));
                // รับค่าจากการ POST
                $action = $request->post('action')->toString();
                if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                    if ($action === 'delete' && $canManage) {
                        // ลบ
                        $ids = [];
                        $query = $this->db()->createQuery()
                            ->select('U.id')
                            ->from('user U')
                            ->where(array('id', $match[1]))
                            ->notExists('grade', array('student_id', 'U.id'))
                            ->toArray();
                        foreach ($query->execute() as $item) {
                            if ($item['id'] != 1) {
                                $ids[] = $item['id'];
                                if (is_file(ROOT_PATH.DATA_FOLDER.'school/'.$item['id'].'.jpg')) {
                                    // ลบไฟล์
                                    unlink(ROOT_PATH.DATA_FOLDER.'school/'.$item['id'].'.jpg');
                                }
                            }
                        }
                        if (!empty($ids)) {
                            // ลบข้อมูล
                            $this->db()->createQuery()->delete('student', array('id', $ids))->execute();
                            $this->db()->createQuery()->delete('grade', array('student_id', $ids))->execute();
                            $this->db()->createQuery()->delete('user', array('id', $ids))->execute();
                            // log
                            \Index\Log\Model::add(0, 'school', 'Delete', '{LNG_Delete} {LNG_Student list} ID : '.implode(', ', $ids), $login['id']);
                        }
                        // reload
                        $ret['location'] = 'reload';
                    } elseif ($action === 'number' && $canManage) {
                        // อัปเดตเลขที่
                        $value = $request->post('value')->toInt();
                        $id = (int) $match[1][0];
                        $this->db()->update($this->getTableName('student'), $id, array('number' => $value));
                        // log
                        \Index\Log\Model::add(0, 'school', 'Delete', '{LNG_Number} {LNG_Student list} ID : '.$id, $login['id']);
                        // คืนค่า
                        $ret['number_'.$id] = $value;
                    } elseif ($canManage && preg_match('/^(room|class|department)_([0-9]+)$/', $action, $match2)) {
                        // ห้อง, ชั้น, แผนก
                        $this->db()->update($this->getTableName('student'), array(
                            array('id', $match[1]),
                            array('id', '!=', '1')
                        ), array(
                            $match2[1] => (int) $match2[2]
                        ));
                        // log
                        \Index\Log\Model::add(0, 'school', 'Delete', '{LNG_'.ucfirst($match2[1]).'} {LNG_Student list} ID : '.$match[1], $login['id']);
                        // reload
                        $ret['location'] = 'reload';
                    } elseif ($canManage && ($action == 'graduate' || $action == 'studying')) {
                        // จบการศึกษา, กำลังเรียน
                        $this->db()->update($this->getTableName('user'), array(
                            array('id', $match[1]),
                            array('status', self::$cfg->student_status)
                        ), array(
                            'active' => $action == 'studying' ? 1 : 0
                        ));
                        // log
                        \Index\Log\Model::add(0, 'school', 'Delete', '{LNG_'.ucfirst($action).'} {LNG_Student list} ID : '.$match[1], $login['id']);
                        // reload
                        $ret['location'] = 'reload';
                    } elseif ($action === 'view') {
                        // ดูรายละเอียดนักเรียน
                        $search = \School\User\Model::get((int) $match[1][0]);
                        if ($search) {
                            $ret['modal'] = Language::trans(\School\Studentinfo\View::create()->render($search, $login));
                        }
                    }
                } elseif ($action == 'export') {
                    // export รายชื่อ
                    $params = $request->getParsedBody();
                    unset($params['action']);
                    unset($params['src']);
                    $params['module'] = 'school-download';
                    $params['type'] = 'student';
                    $ret['location'] = WEB_URL.'export.php?'.http_build_query($params);
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
