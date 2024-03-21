<?php
/**
 * @filesource modules/personnel/controllers/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Write;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=personnel-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มสร้าง/แก้ไข บุคลากร
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // อ่านข้อมูลรายการที่เลือก
        $index = \Personnel\User\Model::getForWrite($request->request('id')->toInt());
        // ข้อความ title bar
        $title = '{LNG_'.(empty($index->id) ? 'Add' : 'Edit').'}';
        $this->title = Language::trans($title.' {LNG_Personnel}');
        // เลือกเมนู
        $this->menu = 'settings';
        // member
        $login = Login::isMember();
        // ตัวเอง หรือสามารถจัดการรายชื่อบุคลากรได้
        if ($index && $login && ($login['id'] == $index->id || Login::checkPermission($login, 'can_manage_personnel'))) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-customer">{LNG_Settings}</span></li>');
            $ul->appendChild('<li><span>{LNG_Personnel}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=personnel-setup&id=0}">{LNG_Personnel list}</a></li>');
            $ul->appendChild('<li><span>'.$title.'</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-category">'.$this->title.'</h2>'
            ));
            // menu
            $section->appendChild(\Index\Tabmenus\View::render($request, 'settings', 'personnel'));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงฟอร์ม
            $div->appendChild(\Personnel\Write\View::create()->render($index));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
