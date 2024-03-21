<?php
/**
 * @filesource modules/index/models/category.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Category;

use Kotchasan\Database\Sql;
use Kotchasan\Language;

/**
 * คลาสสำหรับอ่านข้อมูลหมวดหมู่
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model
{
    /**
     * @var array
     */
    private $categories = [];
    /**
     * @var array
     */
    private $datas = [];

    public function __construct()
    {
        // หมวดหมู่
        $this->categories = Language::get('CATEGORIES');
    }

    /**
     * คืนค่าหมวดหมู่ (key) ทั้งหมด
     *
     * @return array
     */
    public function typies()
    {
        return array_keys($this->categories);
    }

    /**
     * คืนค่าชื่อหมวดหมู่.
     *
     * @return array
     */
    public function label($type)
    {
        return isset($this->categories[$type]) ? $this->categories[$type] : '';
    }

    /**
     * @return static
     */
    public static function init()
    {
        $obj = new static;
        // Query ข้อมูลหมวดหมู่จากตาราง category
        $query = \Kotchasan\Model::createQuery()
            ->select('category_id', 'topic', 'type')
            ->from('category')
            ->where(array('type', $obj->typies()))
            ->order('category_id')
            ->cacheOn();
        // ภาษาที่ใช้งานอยู่
        $lng = Language::name();
        foreach ($query->execute() as $item) {
            $topic = @unserialize($item->topic);
            if (isset($topic[$lng])) {
                $obj->datas[$item->type][$item->category_id] = $topic[$lng];
            }
        }
        return $obj;
    }

    /**
     * คืนค่าประเภทหมวดหมู่
     *
     * @return array
     */
    public function items()
    {
        return $this->categories;
    }

    /**
     * คืนค่าชื่อหมวดหมู่
     * ไม่พบคืนค่าว่าง
     *
     * @param string $type
     *
     * @return string
     */
    public function name($type)
    {
        return isset($this->categories[$type]) ? $this->categories[$type] : '';
    }

    /**
     * ลิสต์รายการหมวดหมู่
     * สำหรับใส่ลงใน select
     *
     * @param string $type
     *
     * @return array
     */
    public function toSelect($type)
    {
        return empty($this->datas[$type]) ? [] : $this->datas[$type];
    }

    /**
     * คืนค่ารายการที่ต้องการ
     *
     * @param string $type
     * @param int    $category_id
     */
    public function get($type, $category_id)
    {
        return empty($this->datas[$type][$category_id]) ? '' : $this->datas[$type][$category_id];
    }

    /**
     * คืนค่า true ถ้าไม่มีข้อมูลใน $type ที่เลือก
     *
     * @param string $type
     *
     * @return bool
     */
    public function isEmpty($type)
    {
        return empty($this->datas[$type]);
    }

    /**
     * ฟังก์ชั่นอ่านหมวดหมู่ หรือ บันทึก ถ้าไม่มีหมวดหมู่
     * คืนค่า category_id
     *
     * @param string $type
     * @param string $topic
     *
     * @return int
     */
    public static function save($type, $topic)
    {
        $topic = trim($topic);
        if ($topic == '') {
            return 0;
        } else {
            // Model
            $model = new \Kotchasan\Model;
            // Database
            $db = $model->db();
            // table
            $table = $model->getTableName('category');
            // ตรวจสอบรายการที่มีอยู่แล้ว
            $search = $db->first($table, array(
                array('type', $type),
                array('topic', '%"'.$topic.'"%')
            ));
            if ($search) {
                // มีหมวดหมู่อยู่แล้ว
                return $search->category_id;
            } else {
                // ไม่มีหมวดหมู่ ตรวจสอบ category_id ใหม่
                $search = $model->createQuery()
                    ->from('category')
                    ->where(array('type', $type))
                    ->first(Sql::create('MAX(CAST(`category_id` AS INT)) AS `category_id`'));
                $category_id = empty($search->category_id) ? 1 : (1 + (int) $search->category_id);
                $topics = [];
                foreach (Language::installedLanguage() as $lng) {
                    $topics[$lng] = $topic;
                }
                // save
                $db->insert($table, array(
                    'type' => $type,
                    'category_id' => $category_id,
                    'topic' => serialize($topics)
                ));
                return $category_id;
            }
        }
    }
}
