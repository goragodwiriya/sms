<?php
/**
 * @filesource modules/personnel/controllers/categories.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Categories;

/**
 * api/personnel/categories/get|save
 * หมวดหมู่ของบุคลากร (ตำแหน่ง แผนก) แบบหลายภาษา เหมือนระบบเดิม
 * ชนิดของหมวดหมู่มาจากคีย์ภาษา CATEGORIES ผู้ดูแลเพิ่มชนิดได้จากหน้าแก้ภาษา
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
        return \Personnel\Category\Model::items();
    }
}
