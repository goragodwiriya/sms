<?php
/**
 * @filesource modules/edocument/views/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Index;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=edocument
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var object
     */
    private $sender;
    /**
     * @var array
     */
    private $urgencies;

    /**
     * แสดงรายการเอกสารรับ
     *
     * @param Request $request
     * @param array $login
     *
     * @return string
     */
    public function render(Request $request, $login)
    {
        $urgencies = Language::get('URGENCIES');
        $this->urgencies = array_map(array('Edocument\View\View', 'urgencyStyle'), array_keys($urgencies), array_values($urgencies));
        // รายชื่อผู้ส่ง
        $this->sender = \Edocument\Sender\Model::init();
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Edocument\Index\Model::toDataTable($login),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('edocumentIndex_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => 'new,last_update DESC',
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id'),
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => array(
                'sender_id' => array(
                    'name' => 'sender',
                    'text' => '{LNG_Sender}',
                    'options' => array(0 => '{LNG_all items}') + $this->sender->toSelect(),
                    'default' => 0,
                    'value' => $request->request('sender')->toInt()
                )
            ),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/edocument/model/index/action',
            'actionCallback' => 'dataTableActionCallback',
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('topic', 'document_no'),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'document_no' => array(
                    'text' => '{LNG_Document No.}'
                ),
                'urgency' => array(
                    'text' => '{LNG_Urgency}',
                    'class' => 'center'
                ),
                'new' => array(
                    'text' => '',
                    'colspan' => 2
                ),
                'topic' => array(
                    'text' => '{LNG_Document title}'
                ),
                'sender_id' => array(
                    'text' => '{LNG_Sender}',
                    'class' => 'center'
                ),
                'size' => array(
                    'text' => '{LNG_Size of} {LNG_File}',
                    'class' => 'center'
                ),
                'last_update' => array(
                    'text' => '{LNG_Date}',
                    'class' => 'center'
                ),
                'downloads' => array(
                    'text' => '{LNG_Download}',
                    'class' => 'center'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'urgency' => array(
                    'class' => 'center'
                ),
                'new' => array(
                    'class' => 'center'
                ),
                'ext' => array(
                    'class' => 'center'
                ),
                'sender_id' => array(
                    'class' => 'center'
                ),
                'size' => array(
                    'class' => 'center'
                ),
                'last_update' => array(
                    'class' => 'center nowrap'
                ),
                'downloads' => array(
                    'class' => 'center visited'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'detail' => array(
                    'class' => 'icon-search button brown notext',
                    'id' => ':id',
                    'title' => '{LNG_Detail}'
                )
            )
        ));
        // save cookie
        setcookie('edocumentIndex_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array  $item ข้อมูลแถว
     * @param int    $o    ID ของข้อมูล
     * @param object $prop กำหนด properties ของ TR
     *
     * @return array คืนค่า $item กลับไป
     */
    public function onRow($item, $o, $prop)
    {
        if (empty($item['new'])) {
            $item['new'] = '<span class="icon-email-unread color-red notext" title="{LNG_New}"></span>';
        } else {
            $item['new'] = '<span class="icon-email-read color-green notext" title="{LNG_Received}"></span>';
        }
        $item['sender_id'] = $this->sender->get($item['sender_id']);
        $item['topic'] = '<a id="detail_'.$item['id'].'">'.$item['topic'].'</a>';
        $item['last_update'] = Date::format($item['last_update'], 'd M Y');
        $item['ext'] = '<img src="'.(is_file(ROOT_PATH.'skin/ext/'.$item['ext'].'.png') ? WEB_URL.'skin/ext/'.$item['ext'].'.png' : WEB_URL.'skin/ext/file.png').'" alt="'.$item['ext'].'">';
        $item['urgency'] = isset($this->urgencies[$item['urgency']]) ? $this->urgencies[$item['urgency']] : '';
        return $item;
    }
}
