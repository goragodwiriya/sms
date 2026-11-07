<?php
/**
 * @filesource modules/school/controllers/students.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Students;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * api/school/students
 * รายชื่อนักเรียน — เดิมคือ module=school-students
 *
 * เปิดได้: ครู ผู้จัดการนักเรียน ผู้จัดการรายวิชา ผู้ให้คะแนน
 * แก้ไขได้: ครู ผู้จัดการนักเรียน ผู้จัดการรายวิชา (ผู้ให้คะแนนอย่างเดียวดูได้อย่างเดียว)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Table
{
    /**
     * สิทธิ์ที่เปิดหน้านี้ได้
     */
    const VIEW_PERMISSIONS = ['can_manage_student', 'can_manage_course', 'can_teacher', 'can_rate_student'];

    /**
     * สิทธิ์ที่แก้ไขนักเรียนได้
     */
    const EDIT_PERMISSIONS = ['can_manage_student', 'can_manage_course', 'can_teacher'];

    /**
     * @var array
     */
    protected $allowedSortColumns = ['id', 'number', 'student_id', 'name', 'department', 'class', 'room'];

    /**
     * @param Request $request
     * @param object $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        if (!ApiController::hasPermission($login, self::VIEW_PERMISSIONS)) {
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
            'active' => $request->get('active', 1)->toInt() === 0 ? 0 : 1
        ];
        foreach (array_keys(\School\Category\Model::studentTypies()) as $type) {
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
        return Model::toDataTable($params);
    }

    /**
     * @param array $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $canEdit = ApiController::hasPermission($login, self::EDIT_PERMISSIONS);
        foreach ($datas as $item) {
            // แก้ไขได้เฉพาะนักเรียนที่กำลังศึกษา เหมือนระบบเดิม
            $item->can_edit = $canEdit && (int) $item->active === 1 ? 1 : 0;
            $item->number = $item->number === null ? '' : (int) $item->number;
            $item->report_url = \School\Init\Controller::transcriptUrl($item->id);
        }

        return $datas;
    }

    /**
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getFilters(array $params, $login)
    {
        $category = \School\Category\Model::init();
        $filters = [];
        foreach (array_keys(\School\Category\Model::studentTypies()) as $type) {
            $filters[$type] = $category->toOptions($type);
        }
        $filters['active'] = [
            ['value' => '1', 'text' => Language::get('Studying')],
            ['value' => '0', 'text' => Language::get('Graduate')]
        ];

        return $filters;
    }

    /**
     * ปุ่มทำกับรายการที่เลือก (ย้ายแผนก/ชั้น/ห้อง จบการศึกษา ลบ) ตามสิทธิ์ของผู้ใช้
     *
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getOptions(array $params, $login)
    {
        $canEdit = ApiController::hasPermission($login, self::EDIT_PERMISSIONS);
        $actions = [];
        if ($canEdit) {
            $category = \School\Category\Model::init();
            $moveTo = Language::get('move to');
            foreach (\School\Category\Model::studentTypies() as $type => $label) {
                foreach ($category->toArray($type) as $id => $topic) {
                    $actions[$type.'_'.$id] = $moveTo.' '.$label.' '.$topic;
                }
            }
            $actions['graduate'] = Language::get('Graduate');
            $actions['studying'] = Language::get('Studying');
            $actions['delete'] = Language::get('Delete');
        }

        return [
            '_table' => [
                'showCheckbox' => $canEdit,
                'actions' => $actions
            ]
        ];
    }

    /**
     * ย้ายแผนก/ชั้น/ห้อง ใช้ action ชื่อ <ชนิด>_<id> เหมือนระบบเดิม
     * ชื่อเมธอดสร้างจาก action ไม่ได้ (handleClass3Action) จึงดักไว้ก่อนถึง Gcms\Table::action()
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function action(Request $request)
    {
        $action = $request->request('action')->filter('a-z_0-9');
        $types = implode('|', array_map('preg_quote', array_keys(\School\Category\Model::studentTypies())));
        if ($types === '' || !preg_match('/^('.$types.')_([0-9]+)$/', $action, $match)) {
            return parent::action($request);
        }
        try {
            ApiController::validateMethod($request, 'POST');
            $this->initLanguage($request);
            $this->validateCsrfToken($request);
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            if (!$this->canModify($login)) {
                return $this->errorResponse('Permission required', 403);
            }
            $ids = $this->selectedIds($request);
            if (empty($ids)) {
                return $this->errorResponse('No items selected', 400);
            }
            \Kotchasan\DB::create()->update('student', [['id', $ids], ['id', '!=', 1]], [$match[1] => (int) $match[2]]);
            \Index\Log\Model::add(0, 'school', 'Save', '{LNG_move to} '.$match[1].' '.$match[2].' {LNG_Student list} ID : '.implode(', ', $ids), $login->id);

            return $this->redirectResponse('reload', 'Saved successfully', 200, 0, 'table');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * แก้ไขนักเรียนได้หรือไม่ (มีสิทธิ์ และไม่ใช่บัญชีตัวอย่าง)
     *
     * @param object $login
     *
     * @return bool
     */
    protected function canModify($login)
    {
        return (int) $login->active === 1 && ApiController::canModify($login, self::EDIT_PERMISSIONS);
    }

    /**
     * id ที่เลือก (ทั้ง bulk action และปุ่มในแถว)
     *
     * @param Request $request
     *
     * @return array
     */
    protected function selectedIds(Request $request)
    {
        $ids = $request->post('ids', [])->toInt();
        if (empty($ids)) {
            $id = $request->post('id')->toInt();
            $ids = $id > 0 ? [$id] : [];
        }

        return array_values(array_filter((array) $ids, fn($id) => $id > 0));
    }

    /**
     * จบการศึกษา (ปิดบัญชีนักเรียน)
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleGraduateAction(Request $request, $login)
    {
        return $this->setActive($request, $login, 0);
    }

    /**
     * กำลังศึกษา (เปิดบัญชีนักเรียนคืน)
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleStudyingAction(Request $request, $login)
    {
        return $this->setActive($request, $login, 1);
    }

    /**
     * @param Request $request
     * @param object $login
     * @param int $active
     *
     * @return \Kotchasan\Http\Response
     */
    private function setActive(Request $request, $login, $active)
    {
        if (!$this->canModify($login)) {
            return $this->errorResponse('Permission required', 403);
        }
        $ids = $this->selectedIds($request);
        if (empty($ids)) {
            return $this->errorResponse('No items selected', 400);
        }
        // เปลี่ยนได้เฉพาะบัญชีที่มีสถานะนักเรียน
        \Kotchasan\DB::create()->update('user', [
            ['id', $ids],
            ['status', (int) self::$cfg->student_status]
        ], ['active' => $active]);
        if ($active === 0) {
            foreach ($ids as $id) {
                \Index\Auth\Model::logoutAllSessions($id);
            }
        }
        \Index\Log\Model::add(0, 'school', 'Save', '{LNG_'.($active ? 'Studying' : 'Graduate').'} {LNG_Student list} ID : '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Saved successfully', 200, 0, 'table');
    }

    /**
     * ลบนักเรียน (เฉพาะคนที่ยังไม่มีผลการเรียน)
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if (!$this->canModify($login)) {
            return $this->errorResponse('Permission required', 403);
        }
        $ids = $this->selectedIds($request);
        $removed = \School\Student\Model::remove($ids);
        if (!empty($removed)) {
            \Index\Log\Model::add(0, 'school', 'Delete', '{LNG_Delete} {LNG_Student list} ID : '.implode(', ', $removed), $login->id);
        }
        if (count($removed) < count($ids)) {
            // ระบบเดิมข้ามไปเงียบ ๆ ที่นี่บอกผู้ใช้ว่ามีกี่คนที่ลบไม่ได้เพราะมีผลการเรียนแล้ว
            return $this->redirectResponse('reload', Language::replace('Deleted :count items, :skip items have academic results and cannot be deleted', [
                ':count' => count($removed),
                ':skip' => count($ids) - count($removed)
            ]), 200, 0, 'table');
        }

        return $this->redirectResponse('reload', 'Deleted '.count($removed).' item(s) successfully', 200, 0, 'table');
    }

    /**
     * แก้เลขที่ในตาราง
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleUpdateAction(Request $request, $login)
    {
        if (!$this->canModify($login)) {
            return $this->errorResponse('Permission required', 403);
        }
        $ids = $this->selectedIds($request);
        if (empty($ids) || $request->post('field')->filter('a-z_') !== 'number') {
            return $this->errorResponse('Invalid action', 400);
        }
        $value = $request->post('value')->toString();
        \Kotchasan\DB::create()->update('student', [['id', (int) $ids[0]]], ['number' => $value === '' ? null : (int) $value]);
        \Index\Log\Model::add((int) $ids[0], 'school', 'Save', '{LNG_Number} {LNG_Student list} ID : '.(int) $ids[0], $login->id);

        return $this->notificationResponse('Saved successfully');
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
        if (!ApiController::hasPermission($login, self::EDIT_PERMISSIONS)) {
            return $this->errorResponse('Permission required', 403);
        }

        return $this->redirectResponse('/school-student?id='.$request->post('id')->toInt());
    }

    /**
     * รายละเอียดของนักเรียน (modal) ใช้ร่วมกับหน้าผลการเรียนและหน้าลงทะเบียน
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleViewAction(Request $request, $login)
    {
        if (!ApiController::hasPermission($login, self::VIEW_PERMISSIONS)) {
            return $this->errorResponse('Permission required', 403);
        }
        $id = $request->post('student')->toInt() ?: $request->post('id')->toInt();

        return self::viewResponse($this, $id, $login);
    }

    /**
     * response ของ modal รายละเอียดนักเรียน
     *
     * @param \Kotchasan\ApiController $controller
     * @param int $id
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    public static function viewResponse($controller, $id, $login)
    {
        $index = \School\Student\Model::get($id);
        if (!$index) {
            return $controller->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
        }
        $category = \School\Category\Model::init();
        $categories = [];
        foreach (\School\Category\Model::studentTypies() as $type => $label) {
            $categories[] = ['label' => $label, 'value' => $category->get($type, $index->$type)];
        }
        // เลขประชาชนเห็นเฉพาะผู้ตั้งค่าระบบ เหมือนระบบเดิม
        $canConfig = ApiController::hasPermission($login, 'can_config');

        return $controller->successResponse([
            'data' => [
                'id' => (int) $index->id,
                'name' => $index->name,
                'student_id' => (string) $index->student_id,
                'picture' => \School\Student\Model::picture($index->id) ?? WEB_URL.'images/no-image.webp',
                'categories' => $categories,
                'can_config' => $canConfig ? 1 : 0,
                'id_card' => $canConfig ? (string) $index->id_card : '',
                'address' => (string) $index->address,
                'phone' => (string) $index->phone,
                'parent' => (string) $index->parent,
                'parent_phone' => (string) $index->parent_phone
            ],
            'actions' => [
                [
                    'type' => 'modal',
                    'action' => 'open',
                    'template' => 'school/studentview.html',
                    'title' => '{LNG_Details of} {LNG_Student}'
                ]
            ]
        ], 'OK');
    }

    /**
     * ดาวน์โหลดรายชื่อนักเรียน (CSV) ตามตัวกรองที่เลือก
     *
     * @param Request $request
     * @param object $login
     *
     * @return void
     */
    protected function handleCsvExport(Request $request, $login)
    {
        $params = $this->parseParams($request, $login);
        $types = \School\Category\Model::studentTypies();
        $category = \School\Category\Model::init();
        $sexes = Language::get('SEXES', []);
        $header = [
            Language::get('Number'),
            Language::get('Student ID'),
            Language::get('Full Name'),
            Language::get('Identification No.'),
            Language::get('Sex'),
            Language::get('Phone'),
            Language::get('Address'),
            Language::trans('{LNG_Full Name} ({LNG_Parent})'),
            Language::trans('{LNG_Phone} ({LNG_Parent})')
        ];
        foreach ($types as $label) {
            $header[] = $label;
        }
        $query = Model::toDataTable($params)
            ->select('S.number', 'S.student_id', 'U.name', 'S.id_card', 'U.sex', 'U.phone', 'S.address', 'S.parent', 'S.parent_phone', ...array_map(fn($type) => 'S.'.$type, array_keys($types)));
        foreach (array_keys($types) as $type) {
            $query->orderBy('S.'.$type);
        }
        $query->orderBy('S.number');
        $rows = [];
        foreach ($query->fetchAll(true) as $item) {
            foreach (array_keys($types) as $type) {
                $item[$type] = $category->get($type, $item[$type]);
            }
            if (isset($sexes[$item['sex']])) {
                $item['sex'] = $sexes[$item['sex']];
            }
            $rows[] = array_values($item);
        }
        \Kotchasan\Csv::send('student', $header, $rows, self::$cfg->csv_language);
    }
}
