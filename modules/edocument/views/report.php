<?php
/**
 * @filesource modules/edocument/views/report.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Report;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;

/**
 * module=edocument-report
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * รายงานการดาวน์โหลด
     *
     * @param Request $request
     * @param object $index
     * @param array $login
     *
     * @return object
     */
    public function render(Request $request, $index, $login)
    {
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Edocument\Report\Model::toDataTable($index->id),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id'),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('edocumentReport_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => 'last_update DESC',
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'status' => array(
                    'text' => '{LNG_Recipient}'
                ),
                'name' => array(
                    'text' => '{LNG_Name}'
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
                'last_update' => array(
                    'class' => 'center'
                ),
                'downloads' => array(
                    'class' => 'center'
                )
            )
        ));
        // save cookie
        setcookie('edocumentReport_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
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
        $item['last_update'] = $item['last_update'] == 0 ? '' : Date::format($item['last_update']);
        $item['status'] = isset(self::$cfg->member_status[$item['status']]) ? '<span class=status'.$item['status'].'>'.self::$cfg->member_status[$item['status']].'</span>' : '';
        return $item;
    }
}
