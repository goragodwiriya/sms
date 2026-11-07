<?php
/**
 * @filesource modules/personnel/models/import.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Import;

use Kotchasan\Language;
use Kotchasan\Text;

/**
 * นำเข้ารายชื่อบุคลากรจากไฟล์ CSV
 *
 * หัวคอลัมน์และกติกาเหมือนระบบเดิม
 *   ชื่อ *  ต้องกรอก
 *   เลขประจำตัวประชาชน ** ห้ามซ้ำ (ไม่มีเลขประชาชนจะตรวจซ้ำจากชื่อแทน)
 *   หมวดหมู่ (ตำแหน่ง แผนก) ชั้น ห้อง กรอกเป็น ID
 *   กรอกเลขประชาชนและวันเกิดครบ = เข้าระบบได้ (ชื่อผู้ใช้ = เลขประชาชน รหัสผ่าน = วันเกิด)
 *   รายการที่ซ้ำหรือไม่ถูกต้องจะถูกข้าม นำเข้าซ้ำได้ ระบบข้ามรายการที่เคยนำเข้าแล้ว
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * @var \Kotchasan\DB
     */
    private $db;

    /**
     * @var array
     */
    private $header = [];

    /**
     * @var array
     */
    private $categories = [];

    /**
     * @var array
     */
    private $details = [];

    /**
     * จำนวนที่นำเข้าสำเร็จ
     *
     * @var int
     */
    public $row = 0;

    /**
     * จำนวนที่ไม่ได้เก็บเบอร์โทรเพราะซ้ำกับสมาชิกคนอื่น
     *
     * @var int
     */
    public $phoneSkipped = 0;

    /**
     * หัวคอลัมน์ของไฟล์นำเข้า (ใช้ทั้งไฟล์ตัวอย่างและตอนอ่าน)
     *
     * @return array
     */
    public static function header()
    {
        $header = [
            Language::trans('{LNG_Full Name} *'),
            Language::trans('{LNG_Identification No.} **'),
            Language::get('Birthday'),
            Language::get('Phone')
        ];
        foreach (\Personnel\Category\Model::items() as $label) {
            $header[] = $label;
        }
        $header[] = Language::get('Class');
        $header[] = Language::get('Room');
        foreach (\Personnel\Person\Model::details() as $label) {
            $header[] = $label;
        }

        return $header;
    }

    /**
     * ไฟล์ตัวอย่าง (2 แถวเหมือนระบบเดิม)
     *
     * @return array
     */
    public static function sample()
    {
        $birthday = ((int) date('Y') + \Personnel\Account\Model::BUDDHIST_YEAR).date('-m-d');
        $rows = [
            ['นายสมชาย โนนกระโทก', '', $birthday, ''],
            ['นางสมศรี รักงานดี', '', $birthday, '']
        ];
        foreach (\Personnel\Category\Model::items() as $label) {
            $rows[0][] = 1;
            $rows[1][] = 1;
        }
        foreach (['', ''] as $value) {
            $rows[0][] = $value;
            $rows[1][] = $value;
        }
        foreach (\Personnel\Person\Model::details() as $label) {
            $rows[0][] = '';
            $rows[1][] = '';
        }

        return $rows;
    }

    /**
     * นำเข้าไฟล์
     *
     * @param string $file
     * @param string $charset
     *
     * @return static
     */
    public static function import($file, $charset)
    {
        $obj = new static();
        $obj->db = \Kotchasan\DB::create();
        $obj->header = self::header();
        $obj->categories = \Personnel\Category\Model::items();
        $obj->details = \Personnel\Person\Model::details();
        \Kotchasan\Csv::read($file, [$obj, 'importRow'], $obj->header, $charset);

        return $obj;
    }

    /**
     * นำเข้า 1 แถว
     *
     * @param array $data
     *
     * @return void
     */
    public function importRow($data)
    {
        $user = [
            'name' => Text::topic($data[$this->header[0]] ?? ''),
            'phone' => \Personnel\Account\Model::digits($data[$this->header[3]] ?? '')
        ];
        if ($user['name'] === '') {
            return;
        }
        $user['birthday'] = \Personnel\Account\Model::parseBirthday($data[$this->header[2]] ?? '');
        $personnel = [
            'id_card' => \Personnel\Account\Model::digits($data[$this->header[1]] ?? ''),
            'order' => 0
        ];
        foreach ($this->categories as $key => $label) {
            $personnel[$key] = (int) ($data[$label] ?? 0);
        }
        $n = 4 + count($this->categories);
        $personnel['class'] = (int) \Personnel\Account\Model::digits($data[$this->header[$n]] ?? '');
        $personnel['room'] = (int) \Personnel\Account\Model::digits($data[$this->header[$n + 1]] ?? '');
        $custom = [];
        foreach ($this->details as $key => $label) {
            $custom[$key] = Text::topic($data[$label] ?? '');
        }
        $personnel['custom'] = json_encode($custom, JSON_UNESCAPED_UNICODE);

        // ตรวจซ้ำจากเลขประชาชน หรือจากชื่อถ้าไม่มีเลขประชาชน
        $query = \Kotchasan\Model::createQuery()
            ->select('P.id')
            ->from('personnel P')
            ->join('user U', [['U.id', 'P.id']], 'INNER');
        if ($personnel['id_card'] !== '') {
            $query->where([['P.id_card', $personnel['id_card']]]);
        } else {
            $query->where([['U.name', $user['name']]]);
        }
        if ($query->first()) {
            return;
        }
        $username = '';
        $password = '';
        if ($personnel['id_card'] !== '' && $user['birthday'] !== '') {
            $username = $personnel['id_card'];
            $password = \Personnel\Account\Model::birthdayPassword($user['birthday']);
            if (!\Personnel\Account\Model::isUnique('username', $username)) {
                // มีสมาชิกใช้ชื่อผู้ใช้นี้อยู่แล้ว (ไม่ใช่บุคลากร) ถือว่าซ้ำ
                return;
            }
        }
        if (!\Personnel\Account\Model::isUnique('phone', $user['phone'])) {
            // เบอร์โทรในตารางสมาชิกห้ามซ้ำ นำเข้าคนนี้โดยไม่เก็บเบอร์โทร
            $user['phone'] = '';
            ++$this->phoneSkipped;
        }
        $user['status'] = (int) self::$cfg->teacher_status;
        $personnel['id'] = \Personnel\Account\Model::create($this->db, $user, $username, $password);
        $this->db->insert('personnel', $personnel);
        ++$this->row;
    }
}
