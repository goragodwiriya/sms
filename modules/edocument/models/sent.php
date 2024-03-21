<?php
/**
 * @filesource modules/edocument/models/sent.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Sent;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=edocument-sent
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query ข้อมูลสำหรับส่งให้กับ DataTable
     *
     * @param int $id คืนค่าทุกคน, > คืนค่ารายการที่ $id
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($id)
    {
        $model = new static;
        $sql2 = $model->db()->createQuery()
            ->select(Sql::COUNT('E.id'))
            ->from('edocument_download E')
            ->where(array('E.document_id', 'A.id'));
        $query = $model->db()->createQuery()
            ->select('A.id', 'A.document_no', 'A.urgency', 'A.ext', 'A.topic', 'A.sender_id', 'A.size', 'A.last_update', array($sql2, 'downloads'))
            ->from('edocument A');
        if ($id > 0) {
            // ไม่ใช่ผู้ดูแลดูได้แค่เอกสารของตัวเอง
            $query->where(array('sender_id', $id));
        }
        return $query;
    }

    /**
     * รับค่าจาก action
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = [];
        // session, referer, member, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if (Login::notDemoMode($login)) {
                // รับค่าจากการ POST
                $id = $request->post('id')->toString();
                $action = $request->post('action')->toString();
                // ตรวจสอบค่าที่ส่งมา
                if (preg_match('/^[0-9,]+$/', $id)) {
                    if ($action === 'delete' && Login::checkPermission($login, 'can_upload_edocument')) {
                        // ลบ
                        $id = explode(',', $id);
                        $query = $this->db()->createQuery()
                            ->select('file')
                            ->from('edocument')
                            ->where(array(
                                array('id', $id),
                                array('file', '!=', '')
                            ))
                            ->toArray();
                        foreach ($query->execute() as $item) {
                            // ลบไฟล์
                            @unlink(ROOT_PATH.DATA_FOLDER.'edocument/'.$item['file']);
                        }
                        // ลบข้อมูล
                        $this->db()->createQuery()
                            ->delete('edocument', array('id', $id))
                            ->execute();
                        $this->db()->createQuery()
                            ->delete('edocument_download', array('document_id', $id))
                            ->execute();
                        // reload
                        $ret['location'] = 'reload';
                    } elseif ($action == 'download') {
                        // อ่านรายการที่เลือก
                        $result = $this->db()->createQuery()
                            ->from('edocument E')
                            ->where(array('E.id', (int) $id))
                            ->first('E.topic', 'E.file', 'E.ext', 'E.size');
                        if ($result) {
                            $file = ROOT_PATH.DATA_FOLDER.'edocument/'.$result->file;
                            if (is_file($file)) {
                                // id สำหรับไฟล์ดาวน์โหลด
                                $id = md5(uniqid());
                                // บันทึกรายละเอียดการดาวน์โหลดลง SESSION
                                $_SESSION[$id] = array(
                                    'file' => $file,
                                    'name' => $result->topic.'.'.$result->ext,
                                    'mime' => \Kotchasan\Mime::get($result->ext),
                                    'size' => $result->size
                                );
                                // คืนค่า
                                $ret['location'] = WEB_URL.'modules/edocument/filedownload.php?id='.$id;
                            } else {
                                // ไม่พบไฟล์
                                $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
                            }
                        }
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
}
