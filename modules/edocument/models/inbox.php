<?php
/**
 * @filesource modules/edocument/models/inbox.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Inbox;

use Kotchasan\Database\Sql;

/**
 * หนังสือรับ (หนังสือที่ส่งถึงสถานะของสมาชิก)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ห้ามมี orderBy — Gcms\Table ห่อ query นี้เป็น COUNT(*) subquery
     *
     * @param array $params search, sender
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable(array $params, $login)
    {
        // จำนวนครั้งที่ผู้ใช้คนนี้ดาวน์โหลด (0 = ยังไม่ได้รับ)
        $downloads = static::createQuery()
            ->selectRaw('COALESCE(SUM(D.`downloads`), 0)')
            ->from('edocument_download D')
            ->where([
                ['D.document_id', Sql::column('A.id')],
                ['D.member_id', (int) $login->id]
            ]);
        $query = static::createQuery()
            ->select('A.id', 'A.document_no', 'A.urgency', 'A.ext', 'A.topic', 'A.sender_id', 'A.size', 'A.last_update', [$downloads, 'downloads'])
            ->from('edocument A')
            ->where([['A.receiver', 'LIKE', '%,'.(int) $login->status.',%']]);
        if (!empty($params['sender'])) {
            $query->where([['A.sender_id', (int) $params['sender']]]);
        }
        if (!empty($params['search'])) {
            $keyword = '%'.$params['search'].'%';
            $query->where([
                ['A.topic', 'LIKE', $keyword],
                ['A.document_no', 'LIKE', $keyword]
            ], 'OR');
        }

        return $query;
    }

    /**
     * จำนวนหนังสือใหม่ที่ยังไม่ได้ดาวน์โหลด (การ์ดหน้าแรก)
     *
     * @param object $login
     *
     * @return int
     */
    public static function countNew($login)
    {
        $result = static::createQuery()
            ->selectCount('A.id')
            ->from('edocument A')
            ->where([['A.receiver', 'LIKE', '%,'.(int) $login->status.',%']])
            ->whereNotExists('edocument_download D', [
                ['D.document_id', 'A.id'],
                ['D.member_id', (int) $login->id]
            ])
            ->first();

        return $result ? (int) $result->count : 0;
    }

    /**
     * ผู้ส่ง [{value, text}] (เฉพาะคนที่เคยส่งหนังสือ)
     *
     * @param int $only 0 = ทุกคน, อื่น ๆ = เฉพาะคนนี้
     *
     * @return array
     */
    public static function senders($only = 0)
    {
        $query = static::createQuery()
            ->select('U.id', 'U.name')
            ->from('user U')
            ->whereExists('edocument E', [['E.sender_id', 'U.id']])
            ->orderBy('U.name');
        if ($only > 0) {
            $query->where([['U.id', (int) $only]]);
        }
        $result = [];
        foreach ($query->fetchAll() as $item) {
            $result[] = ['value' => (string) $item->id, 'text' => $item->name];
        }

        return $result;
    }
}
