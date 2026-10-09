<?php
/**
 * @filesource modules/personnel/models/category.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Category;

use Kotchasan\Language;

/**
 * หมวดหมู่ของบุคลากร (ตำแหน่ง แผนก) อ่านชนิดจากคีย์ภาษา CATEGORIES เหมือนระบบเดิม
 * ผู้ดูแลเพิ่มชนิดหมวดหมู่ได้จากหน้าแก้ภาษาโดยไม่ต้องแก้โค้ด
 *
 * ⚠️ ไม่ได้ extends \Gcms\Category เพราะหมวดหมู่ของ SMS เก็บแยกแถวตามภาษา
 * (language = th/en) — Gcms\Category::init(multiple_language) อ่านเฉพาะแถวของ
 * ภาษาปัจจุบัน แถวที่บันทึกแบบไม่ระบุภาษา (language = '' จากหน้าหมวดหมู่ของแกน)
 * จึงหายไปทั้งแถว และ $datas เป็น private สืบทอดไปแก้ไม่ได้
 * คลาสนี้เลือกแถวของภาษาปัจจุบันก่อน รองลงมาคือแถวที่ไม่ระบุภาษา แล้วจึงภาษาอื่น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model
{
    /**
     * ข้อมูลหมวดหมู่ [type][category_id] = topic
     *
     * @var array
     */
    protected $datas = [];

    /**
     * ชนิดของหมวดหมู่ [type => label]
     *
     * @return array
     */
    protected function categories()
    {
        return Language::get('CATEGORIES', []);
    }

    /**
     * ชนิดของหมวดหมู่ทั้งหมด [type => label]
     *
     * @return array
     */
    public static function items()
    {
        $categories = (new static())->categories();

        return is_array($categories) ? $categories : [];
    }

    /**
     * รายชื่อชนิดของหมวดหมู่
     *
     * @return array
     */
    public function typies()
    {
        return array_keys(static::items());
    }

    /**
     * ชื่อของชนิดหมวดหมู่
     *
     * @param string $type
     *
     * @return string
     */
    public function name($type)
    {
        $categories = static::items();

        return isset($categories[$type]) ? $categories[$type] : '';
    }

    /**
     * โหลดหมวดหมู่ทุกชนิดของคลาสนี้
     *
     * @return static
     */
    public static function init()
    {
        $obj = new static();
        $types = $obj->typies();
        if (empty($types)) {
            return $obj;
        }
        $language = Language::name();
        $rank = [];
        $query = \Kotchasan\Model::createQuery()
            ->select('type', 'category_id', 'language', 'topic')
            ->from('category')
            ->where([['type', $types]]);
        foreach ($query->fetchAll() as $item) {
            // ภาษาปัจจุบัน > ไม่ระบุภาษา > ภาษาอื่น
            $score = $item->language === $language ? 3 : ($item->language === '' ? 2 : 1);
            if (!isset($rank[$item->type][$item->category_id]) || $score > $rank[$item->type][$item->category_id]) {
                $rank[$item->type][$item->category_id] = $score;
                $obj->datas[$item->type][$item->category_id] = $item->topic;
            }
        }
        foreach ($obj->datas as $type => $items) {
            uksort($items, 'strnatcmp');
            $obj->datas[$type] = $items;
        }

        return $obj;
    }

    /**
     * ตัวเลือกของหมวดหมู่ [{value, text}]
     *
     * @param string $type
     *
     * @return array
     */
    public function toOptions($type)
    {
        $result = [];
        foreach ($this->toArray($type) as $id => $topic) {
            $result[] = ['value' => (string) $id, 'text' => $topic];
        }

        return $result;
    }

    /**
     * หมวดหมู่ [category_id => topic]
     *
     * @param string $type
     *
     * @return array
     */
    public function toArray($type)
    {
        return isset($this->datas[$type]) ? $this->datas[$type] : [];
    }

    /**
     * ชื่อหมวดหมู่จาก category_id
     *
     * @param string $type
     * @param mixed $category_id
     * @param string $default
     *
     * @return string
     */
    public function get($type, $category_id, $default = '')
    {
        return isset($this->datas[$type][$category_id]) && $this->datas[$type][$category_id] !== '' ? $this->datas[$type][$category_id] : $default;
    }

    /**
     * category_id ตัวแรกของชนิดนี้
     *
     * @param string $type
     *
     * @return string|null
     */
    public function getFirstKey($type)
    {
        if (empty($this->datas[$type])) {
            return null;
        }

        return (string) array_key_first($this->datas[$type]);
    }

    /**
     * มีหมวดหมู่นี้หรือไม่
     *
     * @param string $type
     * @param mixed $category_id
     *
     * @return bool
     */
    public function exists($type, $category_id)
    {
        return isset($this->datas[$type][$category_id]);
    }
}
