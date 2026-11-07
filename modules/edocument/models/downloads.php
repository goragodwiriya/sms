<?php
/**
 * @filesource modules/edocument/models/downloads.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Downloads;

/**
 * ผู้รับ-ประวัติการดาวน์โหลดของหนังสือหนึ่งฉบับ
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
     * @param array $params id, search
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable(array $params)
    {
        $query = static::createQuery()
            ->select('D.id', 'U.status', 'U.name', 'D.last_update', 'D.downloads')
            ->from('edocument_download D')
            ->join('user U', [['U.id', 'D.member_id']], 'LEFT')
            ->where([['D.document_id', (int) $params['id']]]);
        if (!empty($params['search'])) {
            $query->where([['U.name', 'LIKE', '%'.$params['search'].'%']]);
        }

        return $query;
    }
}
