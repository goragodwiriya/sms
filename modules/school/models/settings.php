<?php
/**
 * @filesource modules/school/models/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Settings;

use Gcms\Config;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=school-settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * ตั้งค่าโมดูล (settings.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, can_config
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if ($login['active'] == 1 && Login::checkPermission($login, 'can_config')) {
                try {
                    // โหลด config
                    $config = Config::load(ROOT_PATH.'settings/config.php');
                    // รับค่าจากการ POST
                    $config->school_name = $request->post('school_name')->topic();
                    $config->phone = $request->post('phone')->topic();
                    $config->fax = $request->post('fax')->topic();
                    $config->address = $request->post('address')->topic();
                    $config->provinceID = $request->post('provinceID')->number();
                    $config->province = $request->post('province')->topic();
                    $config->zipcode = $request->post('zipcode')->number();
                    $config->country = $request->post('country')->filter('A-Z');
                    $config->student_w = max(100, $request->post('student_w')->toInt());
                    $config->student_h = max(100, $request->post('student_h')->toInt());
                    $config->teacher_status = $request->post('teacher_status')->toInt();
                    $config->student_status = $request->post('student_status')->toInt();
                    $config->academic_year = $request->post('academic_year')->toInt();
                    $config->term = $request->post('term')->toInt();
                    $config->csv_language = $request->post('csv_language')->filter('A-Z0-9\-');
                    // save config
                    if (Config::save($config, ROOT_PATH.'settings/config.php')) {
                        // log
                        \Index\Log\Model::add(0, 'school', 'Save', '{LNG_Module settings} {LNG_School}', $login['id']);
                        // คืนค่า
                        $ret['alert'] = Language::get('Saved successfully');
                        $ret['location'] = 'reload';
                        // เคลียร์
                        $request->removeToken();
                    } else {
                        // ไม่สามารถบันทึก config ได้
                        $ret['alert'] = Language::replace('File %s cannot be created or is read-only.', 'settings/config.php');
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
