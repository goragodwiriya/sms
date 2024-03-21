<?php
/**
 * @filesource modules/personnel/models/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Setup;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * โมเดลสำหรับแสดงรายการบุคลากร (setup.php)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * รับค่าจาก action (setup.php)
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = [];
        // session, referer, member
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            // รับค่าจากการ POST
            $action = $request->post('action')->toString();
            if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                if ($action == 'view') {
                    // ดูรายละเอียดบุคลากร
                    $search = \Personnel\User\Model::get((int) $match[1][0]);
                    if ($search) {
                        $ret['modal'] = Language::trans(\Personnel\Personnelinfo\View::create()->render($search, $login));
                    }
                } elseif ($login['active'] == 1 && Login::checkPermission($login, 'can_manage_personnel')) {
                    // Model
                    $model = new \Kotchasan\Model();
                    if ($action === 'delete') {
                        // ลบ
                        $ids = [];
                        $query = $model->db()->createQuery()
                            ->select('id')
                            ->from('user')
                            ->where(array('id', $match[1]))
                            ->toArray();
                        foreach ($query->execute() as $item) {
                            if ($item['id'] != 1) {
                                $ids[] = $item['id'];
                                if (is_file(ROOT_PATH.DATA_FOLDER.'personnel/'.$item['id'].'.jpg')) {
                                    // ลบไฟล์
                                    unlink(ROOT_PATH.DATA_FOLDER.'personnel/'.$item['id'].'.jpg');
                                }
                            }
                        }
                        // ลบข้อมูล
                        $model->db()->createQuery()->delete('personnel', array('id', $ids))->execute();
                        $model->db()->createQuery()->delete('user', array('id', $ids))->execute();
                        // Log
                        \Index\Log\Model::add(0, 'personnel', 'Delete', '{LNG_Delete} {LNG_Personnel} ID : '.implode(', ', $match[1]), $login['id']);
                        // reload
                        $ret['location'] = 'reload';
                    } else {
                        $table = $model->getTableName('personnel');
                        $id = (int) $match[1][0];
                        if ($action === 'order') {
                            // update order
                            $model->db()->update($table, $id, array(
                                'order' => $request->post('value')->toInt()
                            ));
                            // Log
                            \Index\Log\Model::add(0, 'personnel', 'Status', '{LNG_Order} ID : '.$id, $login['id']);
                            // ป้องกันการแจ้งเตือน
                            $ret['save'] = true;
                        } elseif (preg_match('/^active_([01])$/', $action, $match)) {
                            // update active
                            $table = $model->getTableName('user');
                            $search = $model->db()->first($table, $id);
                            if ($search) {
                                $value = $search->active == 1 ? 0 : 1;
                                $model->db()->update($table, $search->id, array(
                                    'active' => $value
                                ));
                                // คืนค่า
                                $ret['elem'] = $action.'_'.$search->id;
                                $ret['title'] = Language::get('PERSONNEL_STATUS', null, $value);
                                $ret['class'] = 'icon-valid '.($value == 1 ? 'access' : 'disabled');
                                // Log
                                \Index\Log\Model::add(0, 'personnel', 'Status', $ret['title'].' ID : '.$search->id, $login['id']);
                            }
                        }
                    }
                }
            } elseif ($action == 'export') {
                // export รายชื่อ
                $params = $request->getParsedBody();
                unset($params['action']);
                unset($params['src']);
                $params['module'] = 'personnel-download';
                $ret['location'] = WEB_URL.'export.php?'.http_build_query($params);
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
