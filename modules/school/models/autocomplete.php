<?php
/**
 * @filesource modules/school/models/autocomplete.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Autocomplete;

use Gcms\Login;
use Kotchasan\Http\Request;

/**
 * Auto Complete
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ค้นหา รายวิชาจากรหัสวิชา
     *
     * @param Request $request
     */
    public function findCourse(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && Login::isMember()) {
            try {
                $result = $this->db()->createQuery()
                    ->select('course_code', 'course_name', 'period', 'credit', 'type')
                    ->from('course')
                    ->where(array('course_code', 'LIKE', $request->post('course_code')->topic().'%'))
                    ->order('teacher_id', 'course_code')
                    ->groupBy('course_code')
                    ->limit(10)
                    ->toArray()
                    ->execute();
                // คืนค่า JSON
                if (!empty($result)) {
                    echo json_encode($result);
                }
            } catch (\Kotchasan\InputItemException $e) {
            }
        }
    }
}
