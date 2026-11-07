<?php
/**
 * @filesource modules/school/controllers/categories.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Categories;

/**
 * api/school/categories/get|save
 * หมวดหมู่ของนักเรียน (ชั้นเรียน ห้องเรียน) และภาคเรียน แบบหลายภาษา เหมือนระบบเดิม
 * แผนกใช้หน้าของโมดูล personnel (หมวดหมู่ชุดเดียวกัน)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Index\Categories\Controller
{
    /**
     * หมวดหมู่ของ SMS เก็บแยกแถวตามภาษา (th/en)
     *
     * @var bool
     */
    protected $multiLanguage = true;

    /**
     * @return array
     */
    protected function categories()
    {
        return \School\Category\Model::items();
    }
}
