<?php
/**
 * @filesource modules/edocument/controllers/downloads.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Downloads;

use Gcms\Api as ApiController;
use Kotchasan\Date;
use Kotchasan\Http\Request;

/**
 * api/edocument/downloads?id= — ประวัติการดาวน์โหลด (ลงชื่อรับ) ของหนังสือหนึ่งฉบับ
 * (หน้า edocument-report ของระบบเดิม) ผู้ส่งหรือผู้จัดการงานสารบรรณเท่านั้น
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
    protected $allowedSortColumns = ['status', 'name', 'last_update', 'downloads'];

    /**
     * หนังสือที่กำลังดู
     *
     * @var object|null
     */
    protected $document = null;

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
        $this->document = \Edocument\Document\Model::get($request->get('id')->toInt());
        if (!$this->document) {
            return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
        }
        if (!\Edocument\Document\Model::canManage($login, $this->document)) {
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
        return ['id' => $this->document ? (int) $this->document->id : 0];
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
     * @param object|null $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        foreach ($datas as $item) {
            $item->date = empty($item->last_update) ? '' : Date::format((int) $item->last_update);
            $item->downloads = (int) $item->downloads;
        }

        return $datas;
    }

    /**
     * สถานะสมาชิก และหนังสือที่กำลังดู (หัวของหน้า)
     *
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getOptions(array $params, $login)
    {
        return [
            'status' => \Gcms\Controller::getUserStatusOptions(),
            'document' => $this->document ? [
                'id' => (int) $this->document->id,
                'document_no' => $this->document->document_no,
                'topic' => $this->document->topic
            ] : null
        ];
    }
}
