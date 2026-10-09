<?php
/**
 * @filesource modules/edocument/controllers/inbox.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Inbox;

use Gcms\Api as ApiController;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * api/edocument/inbox
 * หนังสือรับ — เดิมคือ module=edocument สมาชิกทุกคนเห็นหนังสือที่ส่งถึงสถานะของตัวเอง
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
        return $login ? true : $this->errorResponse('Unauthorized', 401);
    }

    /**
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return ['sender' => $request->get('sender')->toInt()];
    }

    /**
     * @param array $params
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable(array $params, $login)
    {
        return Model::toDataTable($params, $login);
    }

    /**
     * @param array $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        return self::decorate($datas);
    }

    /**
     * ข้อมูลที่จัดรูปแบบแล้ว ใช้ร่วมกับหนังสือส่ง
     *
     * @param array $datas
     *
     * @return array
     */
    public static function decorate(array $datas)
    {
        $urgencies = Language::get('URGENCIES', []);
        $senders = [];
        foreach ($datas as $item) {
            $senders[(int) $item->sender_id] = (int) $item->sender_id;
        }
        $names = [];
        if (!empty($senders)) {
            foreach (\Kotchasan\DB::create()->select('user', [['id', array_values($senders)]], [], ['id', 'name']) as $user) {
                $names[(int) $user->id] = $user->name;
            }
        }
        foreach ($datas as $item) {
            $item->urgency = (int) $item->urgency;
            $item->urgency_text = isset($urgencies[$item->urgency]) ? $urgencies[$item->urgency] : '';
            $item->sender = $names[(int) $item->sender_id] ?? '';
            $item->ext_icon = \Edocument\Document\Model::extIcon($item->ext);
            $item->size_text = Text::formatFileSize((float) $item->size);
            $item->date = empty($item->last_update) ? '' : Date::format((int) $item->last_update, 'd M Y');
            $item->downloads = (int) $item->downloads;
            $item->is_new = $item->downloads === 0 ? 1 : 0;
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
        return ['sender' => Model::senders()];
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
     * รายละเอียดของหนังสือ (modal) พร้อมปุ่มดาวน์โหลด
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDetailAction(Request $request, $login)
    {
        $document = \Edocument\Document\Model::get($request->post('id')->toInt());
        if (!$document || !\Edocument\Document\Model::canRead($login, $document)) {
            return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
        }
        $mine = \Kotchasan\DB::create()->first('edocument_download', [['document_id', (int) $document->id], ['member_id', (int) $login->id]]);
        $sender = \Kotchasan\DB::create()->first('user', [['id', (int) $document->sender_id]], ['name']);
        $urgencies = Language::get('URGENCIES', []);

        return $this->successResponse([
            'data' => [
                'id' => (int) $document->id,
                'document_no' => $document->document_no,
                'urgency' => (int) $document->urgency,
                'urgency_text' => $urgencies[(int) $document->urgency] ?? '',
                'topic' => $document->topic,
                'sender' => $sender ? $sender->name : '',
                'date' => empty($document->last_update) ? '' : Date::format((int) $document->last_update),
                'detail' => $document->detail,
                'is_new' => $mine ? 0 : 1,
                'size_text' => Text::formatFileSize((float) $document->size)
            ],
            'actions' => [
                [
                    'type' => 'modal',
                    'action' => 'open',
                    'template' => 'edocument/view.html',
                    'title' => '{LNG_Details of} {LNG_Document}'
                ]
            ]
        ], 'OK');
    }
}
