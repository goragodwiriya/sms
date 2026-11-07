<?php
/**
 * @filesource modules/edocument/models/outbox.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Outbox;

use Kotchasan\Database\Sql;

/**
 * หนังสือส่ง
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
     * @param array $params search, sender (0 = ทุกคน)
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable(array $params)
    {
        // จำนวนผู้ที่ดาวน์โหลดแล้ว (ระบบเดิมนับแถวของ edocument_download)
        $downloads = static::createQuery()
            ->selectRaw('COUNT(*)')
            ->from('edocument_download D')
            ->where([['D.document_id', Sql::column('A.id')]]);
        $query = static::createQuery()
            ->select('A.id', 'A.document_no', 'A.urgency', 'A.ext', 'A.topic', 'A.sender_id', 'A.size', 'A.last_update', [$downloads, 'downloads'])
            ->from('edocument A');
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
}
