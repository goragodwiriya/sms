<?php
/**
 * @filesource modules/personnel/models/import.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Import;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * module=personnel-import
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @var int
     */
    private $row = 0;
    /**
     * @var array
     */
    private $personnel_details;
    /**
     * @var array
     */
    private $categories;
    /**
     * @var array
     */
    private $header = [];

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (import.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, can_config
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            // สามารถจัดการรายชื่อบุคลากรได้
            if ($login['active'] == 1 && Login::checkPermission($login, 'can_manage_personnel')) {
                // อัปโหลดไฟล์ csv
                foreach ($request->getUploadedFiles() as $item => $file) {
                    /* @var $file \Kotchasan\Http\UploadedFile */
                    if ($file->hasUploadFile()) {
                        if (!$file->validFileExt(array('csv'))) {
                            // ชนิดของไฟล์ไม่ถูกต้อง
                            $ret['ret_'.$item] = Language::get('The type of file is invalid');
                        } else {
                            // header
                            $this->header[] = Language::trans('{LNG_Name} *');
                            $this->header[] = Language::trans('{LNG_Identification No.} **');
                            $this->header[] = Language::get('Birthday');
                            $this->header[] = Language::get('Phone');
                            // หมวดหมู่ของบุคลากร
                            $this->categories = Language::get('CATEGORIES');
                            foreach ($this->categories as $key => $label) {
                                $this->header[] = $label;
                            }
                            $this->header[] = Language::get('Class');
                            $this->header[] = Language::get('Room');
                            // รายละเอียดของบุคลากร
                            $this->personnel_details = Language::get('PERSONNEL_DETAILS', []);
                            foreach ($this->personnel_details as $key => $label) {
                                $this->header[] = $label;
                            }
                            try {
                                // import ข้อมูล
                                \Kotchasan\Csv::read(
                                    $file->getTempFileName(),
                                    array($this, 'importPersonnel'),
                                    $this->header,
                                    self::$cfg->csv_language
                                );
                                // ส่งค่ากลับ
                                $ret['alert'] = Language::replace('Successfully imported :count items', array(':count' => $this->row));
                                $ret['location'] = WEB_URL.'index.php?module=personnel-setup';
                                // log
                                \Index\Log\Model::add(0, 'personnel', 'Import', $ret['alert'], $login['id']);
                            } catch (\Throwable $th) {
                                $ret['ret_'.$item] = $th->getMessage();
                            }
                        }
                    } elseif ($file->hasError()) {
                        // upload Error
                        $ret['ret_'.$item] = $file->getErrorMessage();
                    } else {
                        // ไม่ได้เลือกไฟล์
                        $ret['ret_'.$item] = 'Please browse file';
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }

    /**
     * ฟังก์ชั่นรับค่าจากการอ่าน CSV
     *
     * @param array $data
     */
    public function importPersonnel($data)
    {
        $user = array(
            'name' => Text::topic($data[$this->header[0]]),
            'phone' => preg_replace('/[^0-9]+/', '', $data[$this->header[3]])
        );
        // birthday
        $year_offset = (int) Language::get('YEAR_OFFSET');
        if (preg_match('/([0-9]{4,4})[\-\/]([0-9]{1,2})[\-\/]([0-9]{1,2})/', $data[$this->header[2]], $match)) {
            $user['birthday'] = ((int) $match[1] - $year_offset).'-'.$match[2].'-'.$match[3];
            $password = $match[1].sprintf('%02d', $match[2]).sprintf('%02d', $match[3]);
        } elseif (preg_match('/([0-9]{1,2})[\-\/]([0-9]{1,2})[\-\/]([0-9]{4,4})/', $data[$this->header[2]], $match)) {
            $user['birthday'] = ((int) $match[3] - $year_offset).'-'.$match[2].'-'.$match[1];
            $password = $match[3].sprintf('%02d', $match[2]).sprintf('%02d', $match[1]);
        }
        $personnel = array(
            'id_card' => preg_replace('/[^0-9]+/', '', $data[$this->header[1]]),
            'class' => (int) preg_replace('/[^0-9]+/', '', $data[$this->header[6]]),
            'room' => (int) preg_replace('/[^0-9]+/', '', $data[$this->header[7]]),
            'order' => 0
        );
        foreach ($this->categories as $k => $label) {
            $personnel[$k] = (int) $data[$label];
        }
        $custom = [];
        foreach ($this->personnel_details as $k => $label) {
            $custom[$k] = Text::topic($data[$label]);
        }
        if ($user['name'] != '') {
            $personnel['custom'] = empty($custom) ? '' : serialize($custom);
            if ($personnel['id_card'] != '' && isset($password)) {
                $user['username'] = $personnel['id_card'];
                $user['password'] = $password;
            }
            // ตรวจสอบ id_card หรือชื่อซ้ำ
            $query = $this->db()->createQuery()
                ->from('personnel P')
                ->join('user U', 'INNER', array('U.id', 'P.id'))
                ->toArray();
            if ($personnel['id_card'] != '') {
                $query->where(array('P.id_card', $personnel['id_card']));
            } else {
                $query->where(array('U.name', $user['name']));
            }
            $search = $query->first('P.id');
            if (!$search) {
                // สถานะครู
                $user['status'] = isset(self::$cfg->teacher_status) ? self::$cfg->teacher_status : 0;
                // register
                $user = \Index\Register\Model::execute($this, $user);
                // id ของ personnel
                $personnel['id'] = $user['id'];
                // บันทึก personnel
                $table_name = $this->getTableName('personnel');
                $this->db()->delete($table_name, array('id', $personnel['id']));
                $this->db()->insert($table_name, $personnel);
                // นำเข้าข้อมูลสำเร็จ
                ++$this->row;
            }
        }
    }
}
