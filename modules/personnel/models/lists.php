<?php
/**
 * @filesource modules/personnel/models/lists.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Lists;

/**
 * query ของตารางบุคลากร (ใช้ทั้งหน้าจัดการและหน้ารายชื่อ)
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
     * @param array $params search, active (-1 = ทั้งหมด) และหมวดหมู่ของบุคลากร
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable(array $params)
    {
        $where = [];
        if (isset($params['active']) && ($params['active'] === 0 || $params['active'] === 1)) {
            $where[] = ['U.active', $params['active']];
        }
        $select = ['P.id', 'U.name', 'U.phone', 'U.active', 'P.order', 'P.class', 'P.room'];
        foreach (\Personnel\Category\Model::items() as $type => $label) {
            if (!empty($params[$type])) {
                $where[] = ['P.'.$type, (int) $params[$type]];
            }
            $select[] = 'P.'.$type;
        }
        $query = static::createQuery()
            ->select(...$select)
            ->from('personnel P')
            ->join('user U', [['U.id', 'P.id']], 'INNER')
            ->where($where);
        if (!empty($params['search'])) {
            $query->where([['U.name', 'LIKE', '%'.$params['search'].'%']]);
        }

        return $query;
    }
}
