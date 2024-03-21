<?php
/**
 * @filesource modules/school/controllers/download.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Download;

use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-download
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * ส่งออกไฟล์ csv
     *
     * @param Request $request
     */
    public function export(Request $request)
    {
        if ($request->isReferer()) {
            // ค่าที่ส่งมา
            $type = $request->get('type')->toString();
            if ($type == 'student') {
                $this->student($request);
            } elseif ($type == 'grade') {
                $this->grade($request);
            }
        } else {
            // 404
            header('HTTP/1.0 404 Not Found');
        }
        exit;
    }

    /**
     * ส่งออกรายชื่อนักเรียน.
     *
     * @param Request $request
     */
    public function student(Request $request)
    {
        $header = [];
        $header[] = Language::get('Number');
        $header[] = Language::get('Student ID');
        $header[] = Language::trans('{LNG_Name}');
        $header[] = Language::get('Identification No.');
        $header[] = Language::get('Sex');
        $header[] = Language::get('Phone');
        $header[] = Language::get('Address');
        $header[] = Language::trans('{LNG_Name} ({LNG_Parent})');
        $header[] = Language::trans('{LNG_Phone} ({LNG_Parent})');
        $params = [];
        foreach (Language::get('SCHOOL_CATEGORY') as $k => $v) {
            $params[$k] = $request->get($k)->toInt();
            $header[] = $v;
        }
        $sexes = Language::get('SEXES');
        $category = \School\Category\Model::init();
        $datas = [];
        foreach (\School\Download\Model::student($params, $request->get('active')->toInt()) as $item) {
            foreach ($params as $k => $v) {
                $item[$k] = $category->get($k, $item[$k]);
            }
            if (isset($sexes[$item['sex']])) {
                $item['sex'] = $sexes[$item['sex']];
            }
            $datas[] = $item;
        }
        return \Kotchasan\Csv::send('student', $header, $datas, self::$cfg->csv_language);
    }

    /**
     * ส่งออกผลการเรียน
     *
     * @param Request $request
     */
    public function grade(Request $request)
    {
        $header = [];
        $header[] = Language::get('Number');
        $header[] = Language::get('Student ID');
        $header[] = Language::get('Name');
        $header[] = Language::get('Course Code');
        $header[] = Language::get('Academic year');
        $header[] = Language::get('Term');
        $header[] = Language::get('Class');
        $header[] = Language::get('Room');
        $header[] = Language::get('Grade');
        $params = array(
            'subject' => $request->get('subject')->toInt(),
            'room' => $request->get('room')->toInt()
        );
        $category = \School\Category\Model::init();
        $datas = [];
        foreach (\School\Download\Model::grade($params) as $item) {
            $item['room'] = $category->get('room', $item['room']);
            $item['class'] = $category->get('class', $item['class']);
            $datas[] = $item;
        }
        return \Kotchasan\Csv::send('grade', $header, $datas, self::$cfg->csv_language);
    }
}
