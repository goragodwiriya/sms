<?php
/**
 * @filesource modules/edocument/controllers/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Home;

use Kotchasan\Http\Request;

/**
 * Controller สำหรับการแสดงผลหน้า Home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * ฟังก์ชั่นสร้าง card
     *
     * @param Request               $request
     * @param \Kotchasan\Collection $card
     * @param array                 $login
     */
    public static function addCard(Request $request, $card, $login)
    {
        if ($login && $login['status'] != self::$cfg->student_status) {
            $new = \Edocument\Home\Model::getNew($login);
            if ($new > 0) {
                \Index\Home\Controller::renderCard($card, 'icon-edocument', 'E-Document', number_format($new), '{LNG_New document}', 'index.php?module=edocument');
            }
        }
    }
}
