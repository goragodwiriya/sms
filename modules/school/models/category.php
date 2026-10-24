<?php
/**
 * @filesource modules/school/models/category.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Category;

use Kotchasan\Language;

/**
 * หมวดหมู่ของนักเรียน (แผนก ชั้นเรียน ห้องเรียน) + ภาคเรียน เหมือนระบบเดิม
 * ชนิดของหมวดหมู่มาจากคีย์ภาษา SCHOOL_CATEGORY
 * อ่านแถวตามภาษาแบบเดียวกับ \Personnel\Category\Model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Personnel\Category\Model
{
    /**
     * @return array
     */
    protected function categories()
    {
        $categories = Language::get('SCHOOL_CATEGORY', []);

        return (is_array($categories) ? $categories : []) + ['term' => Language::get('Term')];
    }

    /**
     * ชนิดหมวดหมู่ของนักเรียนที่เก็บในตาราง student (ไม่รวมภาคเรียน)
     *
     * @return array [type => label]
     */
    public static function studentTypies()
    {
        $categories = Language::get('SCHOOL_CATEGORY', []);

        return is_array($categories) ? $categories : [];
    }
}
