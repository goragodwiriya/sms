<?php
/**
 * @filesource modules/school/views/export.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Export;

use Kotchasan\Language;
use Kotchasan\Template;

/**
 * แสดงหน้าสำหรับพิมพ์
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * พิมพ์เกรด
     *
     * @param object $student
     * @param array  $header
     * @param array  $datas
     * @param float  $credit
     * @param float  $grade
     */
    public static function render($student, $header, $datas, $credit, $grade)
    {
        $thead = '';
        foreach ($header as $item) {
            $thead .= '<th>'.$item.'</th>';
        }
        $content = '';
        foreach ($datas as $items) {
            $content .= '<tr>';
            foreach ($items as $k => $item) {
                $class = $k == 1 ? '' : ' class=center';
                $content .= '<td'.$class.'>'.$item.'</td>';
            }
            $content .= '</tr>';
        }
        $category = \School\Category\Model::init();
        // template
        $template = Template::createFromFile(ROOT_PATH.'modules/school/views/mygrade.html');
        $template->add(array(
            '/%CREDITS%/' => $credit,
            '/%GRADES%/' => $grade,
            '/%STUDENT%/' => $student->student_id,
            '/%NAME%/' => $student->name,
            '/%NUMBER%/' => $student->number,
            '/%DEPARTMENT%/' => $student->department,
            '/%CLASS%/' => $category->get('class', $student->class),
            '/%ROOM%/' => $category->get('room', $student->room),
            '/%YEAR%/' => $student->year,
            '/%TERM%/' => $student->term,
            '/%SCHOOLNAME%/' => self::$cfg->school_name,
            '/%SCHOOLPROVINCE%/' => \Kotchasan\Province::get(self::$cfg->provinceID),
            '/%THEAD%/' => $thead,
            '/%TBODY%/' => $content,
            '/{LANGUAGE}/' => Language::name(),
            '/{WEBURL}/' => WEB_URL
        ));
        echo Language::trans($template->render());
        return true;
    }
}
