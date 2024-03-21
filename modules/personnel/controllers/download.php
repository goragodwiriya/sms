<?php
/**
 * @filesource modules/personnel/controllers/download.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Download;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * export.php?module=personnel-download
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * ส่งออกไฟล์ person.csv
     *
     * @param Request $request
     */
    public function export(Request $request)
    {
        // ส่วนหัวของ CSV
        $header = [];
        $header['no'] = '#';
        $header['name'] = Language::trans('{LNG_Name}');
        // ข้อมูลบุคลากร
        $person = array('no' => 0, 'name' => '');
        if (Login::checkPermission(Login::isMember(), 'can_manage_personnel')) {
            $header['id_card'] = Language::get('Identification No.');
            $person['id_card'] = '';
        }
        $header['phone'] = Language::get('Phone');
        $person['phone'] = '';
        $params = array('active' => $request->get('active', -1)->toInt());
        // หมวดหมู่
        $category = \Index\Category\Model::init();
        foreach ($category->typies() as $type) {
            $params[$type] = $request->get($type)->toInt();
            $header[$type] = $category->label($type);
            $person[$type] = '';
        }
        $school_category = \School\Category\Model::init();
        $header['class'] = Language::get('Class teacher');
        $person['class'] = '';
        // custom item
        foreach (Language::get('PERSONNEL_DETAILS', []) as $k => $v) {
            $header[$k] = $v;
            $person[$k] = '';
        }
        $datas = [];
        // query personnel
        foreach (\Personnel\Download\Model::getAll($params) as $item) {
            ++$person['no'];
            $person['name'] = $item['name'];
            if (isset($person['id_card'])) {
                $person['id_card'] = $item['id_card'];
            }
            $person['phone'] = $item['phone'];
            foreach ($category->typies() as $type) {
                $person[$type] = $category->get($type, $item[$type]);
            }
            $person['class'] = $school_category->get('class', $item['class']).' '.$school_category->get('room', $item['room'], '/');
            $item['custom'] = @unserialize($item['custom']);
            if (is_array($item['custom'])) {
                foreach ($item['custom'] as $k => $v) {
                    if (isset($header[$k])) {
                        $person[$k] = $v;
                    }
                }
            }
            $datas[] = $person;
        }
        // export
        return \Kotchasan\Csv::send('person', $header, $datas, self::$cfg->csv_language);
    }
}
