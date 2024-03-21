<?php
/**
 * @filesource modules/edocument/controllers/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Write;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=edocument-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มสร้าง/แก้ไข เอกสาร
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // สมาชิก
        $login = Login::isMember();
        // ตรวจสอบรายการที่เลือก
        $index = \Edocument\Write\Model::get($request->request('id')->toInt(), $login);
        // ข้อความ title bar
        $this->title = Language::trans(empty($index->id) ? '{LNG_Send Document}' : '{LNG_Edit} {LNG_Document}');
        // เลือกเมนู
        $this->menu = 'edocument';
        // สามารถอัปโหลดได้
        if ($index && Login::checkPermission($login, 'can_upload_edocument')) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-edocument">{LNG_E-Document}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=edocument-sent}">{LNG_Sent document}</a></li>');
            $ul->appendChild('<li><span>{LNG_'.(empty($index->id) ? 'Send Document' : 'Edit').'}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงฟอร์ม
            $div->appendChild(\Edocument\Write\View::create()->render($index, $login));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
