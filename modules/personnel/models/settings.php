<?php
/**
 * @filesource modules/personnel/models/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Settings;

use Gcms\Config;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=personnel-settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * รับค่าจาก (settings.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, can_config
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if ($login['active'] == 1 && Login::checkPermission($login, 'can_config')) {
                // โหลด config
                $config = Config::load(ROOT_PATH.'settings/config.php');
                $config->personnel_w = max(100, $request->post('personnel_w')->toInt());
                $config->personnel_h = max(100, $request->post('personnel_h')->toInt());
                // save config
                if (Config::save($config, ROOT_PATH.'settings/config.php')) {
                    // log
                    \Index\Log\Model::add(0, 'personnel', 'Save', '{LNG_Module settings} {LNG_Personnel}', $login['id']);
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
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
