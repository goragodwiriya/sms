<?php
/**
 * @filesource modules/edocument/controllers/report.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Report;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=edocument-report
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * แสดงประวัติการดาวน์โหลด
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // เลือกเมนู
        $this->menu = 'edocument';
        // ข้อความ title bar
        $this->title = Language::trans('{LNG_Recipient}-{LNG_Download history}');
        // ตรวจสอบรายการที่เลือก
        $index = \Edocument\Report\Model::get($request->request('id')->toInt());
        // สมาชิก
        $login = Login::isMember();
        if ($index && $login) {
            // ข้อความ title bar
            $this->title .= ' '.$index->topic;
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-edocument">{LNG_E-Document}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=edocument-sent&id=0}">{LNG_Sent document}</a></li>');
            $ul->appendChild('<li><span>'.$index->topic.'</span></li>');
            $ul->appendChild('<li><span>{LNG_Recipient}-{LNG_Download history}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-list">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // รายละเอียดการรับหนังสือ
            $div->appendChild(\Edocument\Report\View::create()->render($request, $index, $login));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
