<?php
/**
 * @filesource modules/school/controllers/init.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Init;

/**
 * Init Module
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * รายการ permission ของโมดูล.
     *
     * @param array $permissions
     *
     * @return array
     */
    public static function updatePermissions($permissions)
    {
        $permissions['can_rate_student'] = '{LNG_Can rate students in responsible courses}';
        $permissions['can_teacher'] = '{LNG_Teachers can manage their own courses}';
        $permissions['can_manage_student'] = '{LNG_Can manage students}';
        $permissions['can_manage_course'] = '{LNG_Can manage all courses}';

        return $permissions;
    }
}
