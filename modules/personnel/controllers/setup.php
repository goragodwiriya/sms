<?php
/**
 * @filesource modules/personnel/controllers/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Setup;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * api/personnel/setup
 * ตารางจัดการบุคลากร (ผู้มีสิทธิ์ can_manage_personnel) — เดิมคือ module=personnel-setup
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Table
{
    /**
     * @var array
     */
    protected $allowedSortColumns = ['id', 'name', 'active', 'order', 'position', 'department'];

    /**
     * @param Request $request
     * @param object $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        if (!ApiController::hasPermission($login, 'can_manage_personnel')) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
    }

    /**
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        $params = [
            'active' => $request->get('active', -1)->toInt()
        ];
        foreach (\Personnel\Category\Model::items() as $type => $label) {
            $params[$type] = $request->get($type)->toInt();
        }

        return $params;
    }

    /**
     * @param array $params
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable(array $params, $login)
    {
        return \Personnel\Lists\Model::toDataTable($params);
    }

    /**
     * @param array $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $school = \Personnel\Person\Model::schoolCategory();
        $noImage = WEB_URL.'images/no-image.webp';
        $statuses = Language::get('PERSONNEL_STATUS', []);
        foreach ($datas as $item) {
            $item->picture = \Personnel\Person\Model::picture($item->id) ?? $noImage;
            $item->class_teacher = \Personnel\Person\Model::classTeacher($school, $item->class, $item->room);
            $item->active_text = isset($statuses[$item->active]) ? $statuses[$item->active] : '';
            $item->can_edit = $this->canEdit($login, $item->id) ? 1 : 0;
        }

        return $datas;
    }

    /**
     * ผู้จัดการแก้ไขได้ทุกคน คนอื่นแก้ไขได้เฉพาะของตัวเอง เหมือนระบบเดิม
     *
     * @param object $login
     * @param int $id
     *
     * @return bool
     */
    protected function canEdit($login, $id)
    {
        return ApiController::hasPermission($login, 'can_manage_personnel') || (int) $login->id === (int) $id;
    }

    /**
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getFilters(array $params, $login)
    {
        $category = \Personnel\Category\Model::init();
        $filters = [];
        foreach ($category->typies() as $type) {
            $filters[$type] = $category->toOptions($type);
        }
        $filters['active'] = \Gcms\Controller::arrayToOptions(Language::get('PERSONNEL_STATUS', []));

        return $filters;
    }

    /**
     * ดูรายละเอียดของบุคลากร (modal)
     * เปิดได้ทุกคนที่เห็นตาราง เลขประชาชนและข้อมูลเพิ่มเติมเห็นเฉพาะผู้จัดการ เหมือนระบบเดิม
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleViewAction(Request $request, $login)
    {
        $index = \Personnel\Person\Model::get($request->post('id')->toInt());
        if (!$index || $index->id == 0) {
            return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
        }
        $category = \Personnel\Category\Model::init();
        $categories = [];
        foreach ($category->typies() as $type) {
            $categories[] = [
                'label' => $category->name($type),
                'value' => $category->get($type, $index->$type)
            ];
        }
        $canManage = ApiController::hasPermission($login, 'can_manage_personnel');
        $details = [];
        if ($canManage) {
            foreach (\Personnel\Person\Model::details() as $key => $label) {
                $details[] = [
                    'label' => $label,
                    'value' => isset($index->custom[$key]) ? (string) $index->custom[$key] : ''
                ];
            }
        }
        $data = [
            'id' => (int) $index->id,
            'name' => $index->name,
            'phone' => (string) $index->phone,
            'picture' => \Personnel\Person\Model::picture($index->id) ?? WEB_URL.'images/no-image.webp',
            'picture_width' => (int) self::$cfg->personnel_w,
            'picture_height' => (int) self::$cfg->personnel_h,
            'categories' => $categories,
            'class_teacher' => \Personnel\Person\Model::classTeacher(\Personnel\Person\Model::schoolCategory(), $index->class, $index->room),
            'can_manage' => $canManage ? 1 : 0,
            'id_card' => $canManage ? (string) $index->id_card : '',
            'details' => $details
        ];

        return $this->successResponse([
            'data' => $data,
            'actions' => [
                [
                    'type' => 'modal',
                    'action' => 'open',
                    'template' => 'personnel/view.html',
                    'title' => '{LNG_Details of} {LNG_Personnel}'
                ]
            ]
        ], 'OK');
    }

    /**
     * ไปหน้าแก้ไข
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleEditAction(Request $request, $login)
    {
        $id = $request->post('id')->toInt();
        if (!$this->canEdit($login, $id)) {
            return $this->errorResponse('Permission required', 403);
        }

        return $this->redirectResponse('/personnel-edit?id='.$id);
    }

    /**
     * ลบบุคลากร (ลบบัญชีผู้ใช้ด้วย เหมือนระบบเดิม)
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_manage_personnel'])) {
            return $this->errorResponse('Permission required', 403);
        }
        $ids = $request->post('ids', [])->toInt();
        if (empty($ids)) {
            $ids = [$request->post('id')->toInt()];
        }
        $removed = \Personnel\Person\Model::remove($ids);
        if (empty($removed)) {
            return $this->errorResponse('Delete action failed', 400);
        }
        \Index\Log\Model::add(0, 'personnel', 'Delete', '{LNG_Delete} {LNG_Personnel} ID : '.implode(', ', $removed), $login->id);

        return $this->redirectResponse('reload', 'Deleted '.count($removed).' item(s) successfully', 200, 0, 'table');
    }

    /**
     * สลับสถานะ บุคลากรปัจจุบัน/บุคลากรในอดีต (user.active)
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleActiveAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_manage_personnel'])) {
            return $this->errorResponse('Permission required', 403);
        }
        $id = $request->post('id')->toInt();
        $db = \Kotchasan\DB::create();
        $user = $db->first('user', [['id', $id]]);
        if (!$user || !$db->first('personnel', [['id', $id]], ['id'])) {
            return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
        }
        // ผู้ดูแลระบบสูงสุดและบัญชีของตัวเองปิดไม่ได้ เหมือนตารางสมาชิกของแกน
        if ((int) $user->id === 1 || (int) $user->id === (int) $login->id) {
            return $this->errorResponse('The status of this account cannot be changed', 403);
        }
        $active = (int) $user->active === 1 ? 0 : 1;
        $db->update('user', [['id', $id]], ['active' => $active]);
        if ($active === 0) {
            \Index\Auth\Model::logoutAllSessions($id);
        }
        $title = Language::get('PERSONNEL_STATUS', '', $active);
        \Index\Log\Model::add($id, 'personnel', 'Status', $title.' ID : '.$id, $login->id);

        return $this->redirectResponse('reload', $title, 200, 0, 'table');
    }

    /**
     * แก้ไขลำดับในตาราง (ช่อง order)
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleUpdateAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_manage_personnel'])) {
            return $this->errorResponse('Permission required', 403);
        }
        $ids = $request->post('ids', [])->toInt();
        $field = $request->post('field')->filter('a-z_');
        if (empty($ids) || $field !== 'order') {
            return $this->errorResponse('Invalid action', 400);
        }
        $value = max(0, min(255, $request->post('value')->toInt()));
        \Kotchasan\DB::create()->update('personnel', [['id', (int) $ids[0]]], ['order' => $value]);
        \Index\Log\Model::add((int) $ids[0], 'personnel', 'Status', '{LNG_Sequence} ID : '.(int) $ids[0], $login->id);

        return $this->notificationResponse('Saved successfully');
    }

    /**
     * ดาวน์โหลดรายชื่อบุคลากร (CSV) ตามตัวกรองที่เลือก
     *
     * @param Request $request
     * @param object $login
     *
     * @return void
     */
    protected function handleCsvExport(Request $request, $login)
    {
        $params = $this->parseParams($request, $login);
        $category = \Personnel\Category\Model::init();
        $school = \Personnel\Person\Model::schoolCategory();
        $details = \Personnel\Person\Model::details();
        $canManage = ApiController::hasPermission($login, 'can_manage_personnel');

        $header = ['#', Language::get('Full Name')];
        if ($canManage) {
            $header[] = Language::get('Identification No.');
        }
        $header[] = Language::get('Phone');
        foreach ($category->typies() as $type) {
            $header[] = $category->name($type);
        }
        $header[] = Language::get('Class teacher');
        foreach ($details as $label) {
            $header[] = $label;
        }

        $query = \Personnel\Lists\Model::toDataTable($params)
            ->select('P.id', 'U.name', 'P.id_card', 'U.phone', 'P.custom', 'P.class', 'P.room', ...array_map(fn($type) => 'P.'.$type, $category->typies()))
            ->orderBy('P.position')
            ->orderBy('P.order');
        $rows = [];
        $no = 0;
        foreach ($query->fetchAll() as $item) {
            $row = [++$no, $item->name];
            if ($canManage) {
                $row[] = $item->id_card;
            }
            $row[] = $item->phone;
            foreach ($category->typies() as $type) {
                $row[] = $category->get($type, $item->$type);
            }
            $row[] = \Personnel\Person\Model::classTeacher($school, $item->class, $item->room);
            $custom = \Personnel\Person\Model::decodeCustom($item->custom);
            foreach ($details as $key => $label) {
                $row[] = isset($custom[$key]) ? $custom[$key] : '';
            }
            $rows[] = $row;
        }

        \Kotchasan\Csv::send('person', $header, $rows, self::$cfg->csv_language);
    }
}
