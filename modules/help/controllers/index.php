<?php
/**
 * @filesource modules/help/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Help\Index;

use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=help
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ตั้งค่าระบบอีเมล.
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('Help');
        // เลือกเมนู
        $this->menu = 'help';
        // แสดงผล
        $section = Html::create('section');
        // breadcrumbs
        $breadcrumbs = $section->add('nav', array(
            'class' => 'breadcrumbs'
        ));
        $ul = $breadcrumbs->add('ul');
        $ul->appendChild('<li><span class="icon-home">{LNG_Home}</span></li>');
        $ul->appendChild('<li><span>{LNG_Help}</span></li>');
        $div = $section->add('div', array(
            'class' => 'content_bg'
        ));
        $div->appendChild(file_get_contents(ROOT_PATH.'modules/help/views/index.html'));
        // คืนค่า HTML
        return $section->render();
    }
}
