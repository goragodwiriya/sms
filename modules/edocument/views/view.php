<?php
/**
 * @filesource modules/edocument/views/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\View;

use Kotchasan\Date;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * แสดงรายละเอียดของเอกสาร (modal)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงฟอร์ม Modal สำหรับแสดงรายละเอียดของเอกสาร
     *
     * @param object $index
     * @param array  $login
     *
     * @return string
     */
    public function render($index, $login)
    {
        $urgencies = Language::get('URGENCIES');
        $urgencies = array_map(array('Edocument\View\View', 'urgencyStyle'), array_keys($urgencies), array_values($urgencies));
        $content = [];
        $content[] = '<article class=edocument_view>';
        $content[] = '<header><h3 class=icon-file>{LNG_Details of} {LNG_Document}</h3></header>';
        $content[] = '<div class="table fullwidth">';
        $content[] = '<p class=tr><span class="td icon-number">{LNG_Document No.}</span><span class=td>:</span><span class=td>'.$index->document_no.'</span></p>';
        $content[] = '<p class=tr><span class="td icon-rocket">{LNG_Urgency}</span><span class=td>:</span><span class=td>'.(isset($urgencies[$index->urgency]) ? $urgencies[$index->urgency] : '').'</span></p>';
        $content[] = '<p class=tr><span class="td icon-file">{LNG_Document title}</span><span class=td>:</span><span class=td>'.$index->topic.'</span></p>';
        $content[] = '<p class=tr><span class="td icon-customer">{LNG_Sender}</span><span class=td>:</span><span class=td>'.\Edocument\Sender\Model::init()->get($index->sender_id).'</span></p>';
        $content[] = '<p class=tr><span class="td icon-calendar">{LNG_Date}</span><span class=td>:</span><span class=td>'.($index->last_update == 0 ? '' : Date::format($index->last_update)).'</span></p>';
        $content[] = '<p class=tr><span class="td icon-edit">{LNG_Detail}</span><span class=td>:</span><span class=td>'.$index->detail.'</span></p>';
        $content[] = '<p class=tr><span class="td icon-star0">{LNG_Status}</span><span class=td>:</span>';
        if (empty($index->new)) {
            $content[] = '<span class="td icon-email-unread color-red">{LNG_New}';
        } else {
            $content[] = '<span class="td icon-email-read color-green">{LNG_Received}';
        }
        $content[] = '</span></p>';
        $content[] = '</div>';
        $content[] = '<div class="margin-top-right-bottom-left"><a class="button purple icon-download" id=download_'.$index->id.'>{LNG_Download}</a> ({LNG_Size of} {LNG_File} '.Text::formatFileSize($index->size).')</div>';
        $content[] = '</article>';
        $content[] = '<script>initEdocumentView("download_'.$index->id.'")</script>';
        // คืนค่า HTML
        return implode('', $content);
    }

    /**
     * @param int $key
     * @param string $value
     *
     * @return string
     */
    public static function urgencyStyle($key, $value)
    {
        if ($key < 2) {
            return '<em>'.$value.'</em>';
        }
        return $value;
    }
}
