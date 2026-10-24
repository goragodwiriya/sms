<?php
/**
 * @filesource modules/edocument/controllers/outbox.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Outbox;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * api/edocument/outbox
 * หนังสือส่ง — เดิมคือ module=edocument-sent
 * ผู้มีสิทธิ์อัปโหลดเห็นหนังสือของตัวเอง ผู้จัดการงานสารบรรณเห็นทุกฉบับ
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
    protected $allowedSortColumns = ['id', 'document_no', 'urgency', 'topic', 'sender_id', 'size', 'last_update', 'downloads'];

    /**
     * @param Request $request
     * @param object $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        if (!ApiController::hasPermission($login, 'can_upload_edocument')) {
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
        return [
            // ไม่ใช่ผู้จัดการ เห็นได้แค่หนังสือของตัวเอง
            'sender' => \Edocument\Document\Model::canHandleAll($login) ? $request->get('sender')->toInt() : (int) $login->id
        ];
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
        return \Edocument\Inbox\Controller::decorate($datas);
    }

    /**
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getFilters(array $params, $login)
    {
        $all = \Edocument\Document\Model::canHandleAll($login);

        return ['sender' => \Edocument\Inbox\Model::senders($all ? 0 : (int) $login->id)];
    }

    /**
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getOptions(array $params, $login)
    {
        return ['urgency' => \Edocument\Document\Model::urgencyOptions()];
    }

    /**
     * หนังสือที่จัดการได้จาก id ที่เลือก
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    private function manageableIds(Request $request, $login)
    {
        $ids = $request->post('ids', [])->toInt();
        if (empty($ids)) {
            $ids = [$request->post('id')->toInt()];
        }
        $where = [['id', array_values(array_filter((array) $ids))]];
        if (!\Edocument\Document\Model::canHandleAll($login)) {
            $where[] = ['sender_id', (int) $login->id];
        }
        $result = [];
        foreach (\Kotchasan\DB::create()->select('edocument', $where, [], ['id']) as $item) {
            $result[] = (int) $item->id;
        }

        return $result;
    }

    /**
     * ลบหนังสือ (ของตัวเอง หรือทุกฉบับถ้าเป็นผู้จัดการ)
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if ((int) $login->active !== 1 || !ApiController::canModify($login, ['can_upload_edocument'])) {
            return $this->errorResponse('Permission required', 403);
        }
        $ids = $this->manageableIds($request, $login);
        if (empty($ids)) {
            return $this->errorResponse('Delete action failed', 400);
        }
        \Edocument\Document\Model::remove($ids);
        \Index\Log\Model::add(0, 'edocument', 'Delete', '{LNG_Delete} {LNG_E-Document} ID : '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Deleted '.count($ids).' item(s) successfully', 200, 0, 'table');
    }

    /**
     * ดาวน์โหลดไฟล์ของหนังสือที่ส่ง (ไม่นับเป็นการรับหนังสือ)
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDownloadAction(Request $request, $login)
    {
        $document = \Edocument\Document\Model::get($request->post('id')->toInt());
        if (!$document || !\Edocument\Document\Model::canRead($login, $document)) {
            return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
        }
        if ($document->file === '' || !is_file(\Edocument\Document\Model::dir().$document->file)) {
            return $this->errorResponse('File not found', 404);
        }

        return $this->successResponse([
            'actions' => [[
                'type' => 'download',
                'url' => WEB_URL.'api/edocument/view/file?id='.(int) $document->id,
                'filename' => $document->topic.'.'.$document->ext
            ]]
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
        $document = \Edocument\Document\Model::get($request->post('id')->toInt());
        if (!$document || !\Edocument\Document\Model::canManage($login, $document)) {
            return $this->errorResponse('Permission required', 403);
        }

        return $this->redirectResponse('/edocument-write?id='.(int) $document->id);
    }
}
