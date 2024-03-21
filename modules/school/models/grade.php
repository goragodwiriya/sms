<?php
/**
 * @filesource modules/school/models/grade.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Grade;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-grade
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * query ผลการเรียน นักเรียนที่เลือก (grade.php).
     *
     * @param object $student
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($student)
    {
        return static::createQuery()
            ->select('G.id', 'C.course_code', 'C.course_name', 'C.type', 'C.credit', 'G.midterm', 'G.final', 'G.grade')
            ->from('grade G')
            ->join('course C', 'INNER', array('C.id', 'G.course_id'))
            ->where(array(
                array('G.student_id', $student->id),
                array('C.year', $student->year),
                array('C.term', $student->term)
            ));
    }

    /**
     * รับค่าจาก action (grade.php)
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
                    // ตาราง
                    $table = $this->getTableName('grade');
                    if ($action === 'delete') {
                        // ลบ
                        $this->db()->delete($table, array('id', $match[1]), 0);
                        // log
                        \Index\Log\Model::add(0, 'school', 'Delete', '{LNG_Delete} {LNG_Grade} ID : '.implode(', ', $match[1]), $login['id']);
                        // reload
                        $ret['location'] = 'reload';
                    } elseif ($action === 'grade' || $action === 'number' || $action === 'room') {
                        // อัปเดตข้อมูล
                        $value = $request->post('value')->topic();
                        $id = (int) $match[1][0];
                        $this->db()->update($table, $id, array($action => $value));
                        // log
                        \Index\Log\Model::add(0, 'school', 'Save', '{LNG_'.ucfirst($action).'} {LNG_Grade} ID : '.$match[1][0], $login['id']);
                        // คืนค่า
                        $ret[$action.'_'.$id] = $value;
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
