<?php
/**
 * @filesource modules/edocument/models/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Settings;

use Gcms\Login;
use Kotchasan\Config;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=edocument-settings
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
        // session, token, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_config')) {
                try {
                    // รับค่าจากการ POST
                    $typies = [];
                    foreach (explode(',', strtolower($request->post('edocument_file_typies')->filter('a-zA-Z0-9,'))) as $typ) {
                        if ($typ != '') {
                            $typies[$typ] = $typ;
                        }
                    }
                    // โหลด config
                    $config = Config::load(ROOT_PATH.'settings/config.php');
                    $config->edocument_prefix = $request->post('edocument_prefix')->topic();
                    $config->edocument_format_no = $request->post('edocument_format_no')->topic();
                    $config->edocument_file_typies = array_keys($typies);
                    $config->edocument_upload_size = $request->post('edocument_upload_size')->toInt();
                    $config->edocument_download_action = $request->post('edocument_download_action')->toInt();
                    $config->edocument_send_mail = $request->post('edocument_send_mail')->toBoolean();
                    if (empty($config->edocument_file_typies)) {
                        // คืนค่า input ที่ error
                        $ret['ret_edocument_file_typies'] = 'this';
                    } else {
                        // save config
                        if (Config::save($config, ROOT_PATH.'settings/config.php')) {
                            // log
                            \Index\Log\Model::add(0, 'edocument', 'Save', '{LNG_Module settings} {LNG_E-Document}', $login['id']);
                            // คืนค่า
                            $ret['alert'] = Language::get('Saved successfully');
                            $ret['location'] = 'reload';
                            // เคลียร์
                            $request->removeToken();
                        } else {
                            // ไม่สามารถบันทึก config ได้
                            $ret['alert'] = Language::replace('File %s cannot be created or is read-only.', 'settings/config.php');
                        }
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
