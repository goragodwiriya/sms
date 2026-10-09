<?php
/**
 * @filesource modules/school/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Init;

use Gcms\Api as ApiController;
use Kotchasan\Language;

/**
 * เมนู สิทธิ์ และการ์ดหน้าแรกของโมดูลโรงเรียน
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * สิทธิ์ของโมดูล (ชื่อเดียวกับระบบเดิม ผู้ใช้เดิมไม่ต้องตั้งค่าใหม่)
     *
     * @param array $permissions
     * @param mixed $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initPermission($permissions, $params = null, $login = null)
    {
        $permissions[] = ['value' => 'can_rate_student', 'text' => '{LNG_Can rate students in responsible courses}'];
        $permissions[] = ['value' => 'can_teacher', 'text' => '{LNG_Teachers can manage their own courses}'];
        $permissions[] = ['value' => 'can_manage_student', 'text' => '{LNG_Can manage students}'];
        $permissions[] = ['value' => 'can_manage_course', 'text' => '{LNG_Can manage all courses}'];

        return $permissions;
    }

    /**
     * เป็นนักเรียน (สถานะสมาชิกตามที่ตั้งไว้ในหน้าตั้งค่าโรงเรียน)
     *
     * @param object $login
     *
     * @return bool
     */
    public static function isStudent($login)
    {
        return $login && (int) $login->status === (int) self::$cfg->student_status;
    }

    /**
     * ลิงก์รายงานผลการเรียนของนักเรียน (ปีการศึกษา/ภาคเรียนปัจจุบัน)
     *
     * @param int $id
     *
     * @return string
     */
    public static function transcriptUrl($id)
    {
        return '/school-grade?id='.(int) $id.'&year='.(int) self::$cfg->academic_year.'&term='.(int) self::$cfg->term;
    }

    /**
     * ลิงก์รายวิชาของปีการศึกษา/ภาคเรียนปัจจุบัน (ตัวกรองของหน้าจะแสดงค่าที่ใช้อยู่)
     *
     * @return string
     */
    public static function coursesUrl()
    {
        return '/school-courses?year='.(int) self::$cfg->academic_year.'&term='.(int) self::$cfg->term;
    }

    /**
     * เมนูของโมดูล
     *
     * @param array $menus
     * @param mixed $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initMenus($menus, $params = null, $login = null)
    {
        if (!$login) {
            return $menus;
        }
        if (self::isStudent($login)) {
            // นักเรียน: รายงานผลการเรียนของตัวเอง + แก้ไขข้อมูลนักเรียนของตัวเอง
            return parent::insertMenuByKey($menus, 'school', [
                'title' => '{LNG_School}',
                'icon' => 'icon-elearning',
                'children' => [
                    ['title' => '{LNG_Grade Report}', 'url' => self::transcriptUrl($login->id), 'icon' => 'icon-elearning'],
                    ['title' => '{LNG_Student information}', 'url' => '/school-student?id='.(int) $login->id, 'icon' => 'icon-profile']
                ]
            ], 'dashboard');
        }
        $children = [];
        $view = \School\Students\Controller::VIEW_PERMISSIONS;
        if (ApiController::hasPermission($login, $view)) {
            $children[] = ['title' => '{LNG_Student list}', 'url' => '/school-students', 'icon' => 'icon-users'];
        }
        if (ApiController::hasPermission($login, \School\Import\Controller::PERMISSIONS['student'])) {
            $children[] = ['title' => '{LNG_Import} {LNG_Student list}', 'url' => '/school-import?type=student', 'icon' => 'icon-import'];
        }
        if (ApiController::hasPermission($login, $view)) {
            $children[] = ['title' => '{LNG_Course}', 'url' => self::coursesUrl(), 'icon' => 'icon-elearning'];
        }
        if (ApiController::hasPermission($login, \School\Import\Controller::PERMISSIONS['course'])) {
            $children[] = ['title' => '{LNG_Import} {LNG_Course}', 'url' => '/school-import?type=course', 'icon' => 'icon-import'];
        }
        if (ApiController::hasPermission($login, \School\Import\Controller::PERMISSIONS['grade'])) {
            $children[] = ['title' => '{LNG_Import} {LNG_Grade}', 'url' => '/school-import?type=grade', 'icon' => 'icon-import'];
        }
        if (!empty($children)) {
            $menus = parent::insertMenuByKey($menus, 'school', [
                'title' => '{LNG_School}',
                'icon' => 'icon-elearning',
                'children' => $children
            ], 'dashboard');
        }

        if (ApiController::hasPermission($login, 'can_config')) {
            $settings = [
                ['title' => '{LNG_Module Settings}', 'url' => '/school-settings', 'icon' => 'icon-cog'],
                ['title' => '{LNG_Grade calculation}', 'url' => '/school-gradesettings', 'icon' => 'icon-number'],
                // ประเภทผลการเรียน (ร. มส. มผ. ผ.) แก้ที่คีย์ภาษา SCHOOL_TYPIES เหมือนระบบเดิม
                ['title' => '{LNG_Grade settings}', 'url' => '/language?key=SCHOOL_TYPIES', 'icon' => 'icon-language']
            ];
            foreach (\School\Category\Model::items() as $type => $label) {
                // แผนกใช้หน้าหมวดหมู่ของบุคลากร (ชุดเดียวกัน)
                if ($type !== 'department') {
                    $settings[] = ['title' => $label, 'url' => '/school-categories?type='.$type, 'icon' => 'icon-tags'];
                }
            }

            $menus = parent::insertMenuChildren($menus, [
                [
                    'title' => '{LNG_School}',
                    'icon' => 'icon-elearning',
                    'children' => $settings
                ]
            ], 'settings', null, 1);
        }

        return $menus;
    }

    /**
     * การ์ดหน้าแรก (เดิมอยู่ใน School\Home\Controller::addCard/addMenu)
     *
     * @param array $cards
     * @param mixed $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initDashboardCards($cards, $params = null, $login = null)
    {
        if (!$login) {
            return $cards;
        }
        $period = (int) self::$cfg->academic_year.'/'.(int) self::$cfg->term;
        if (self::isStudent($login)) {
            $cards[] = [
                'title' => Language::get('Grade Report'),
                'value' => $period,
                'unit' => '',
                'icon' => 'icon-elearning',
                'url' => self::transcriptUrl($login->id),
                'hint' => Language::get('Academic year'),
                'class' => 'positive'
            ];

            return $cards;
        }
        if (ApiController::hasPermission($login, \School\Students\Controller::EDIT_PERMISSIONS)) {
            $count = \Kotchasan\Model::createQuery()
                ->selectCount('S.id')
                ->from('student S')
                ->join('user U', [['U.id', 'S.id']], 'INNER')
                ->where([
                    ['U.active', 1],
                    ['U.status', (int) self::$cfg->student_status]
                ])
                ->first();
            $cards[] = [
                'title' => Language::get('Student'),
                'value' => number_format($count ? (int) $count->count : 0),
                'unit' => '',
                'icon' => 'icon-users',
                'url' => '/school-students',
                'hint' => Language::get('Student list'),
                'class' => 'positive'
            ];
        }
        if (ApiController::hasPermission($login, \School\Students\Controller::VIEW_PERMISSIONS)) {
            $where = [
                ['year', (int) self::$cfg->academic_year],
                ['term', (int) self::$cfg->term]
            ];
            if (!ApiController::hasPermission($login, 'can_manage_course')) {
                $where[] = ['teacher_id', (int) $login->id];
            }
            $cards[] = [
                'title' => Language::get('Course'),
                'value' => number_format(\Kotchasan\DB::create()->count('course', $where)),
                'unit' => '',
                'icon' => 'icon-elearning',
                'url' => self::coursesUrl(),
                'hint' => Language::get('Academic year').' '.$period,
                'class' => 'positive'
            ];
        }

        return $cards;
    }
}
