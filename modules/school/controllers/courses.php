<?php
/**
 * @filesource modules/school/controllers/courses.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Courses;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * api/school/courses
 * รายวิชา — เดิมคือ module=school-courses
 *
 * ผู้จัดการรายวิชาเห็นทุกรายวิชา ครูคนอื่นเห็นเฉพาะรายวิชาที่ตัวเองสอน
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Table
{
    /**
     * @var array
     */
    protected $allowedSortColumns = ['id', 'course_code', 'course_name', 'type', 'teacher_id', 'year', 'term', 'class'];

    /**
     * @param Request $request
     * @param object $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        if (!ApiController::hasPermission($login, \School\Students\Controller::VIEW_PERMISSIONS)) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
    }

    /**
     * ปีการศึกษาและภาคเรียนเริ่มต้นเป็นของปัจจุบัน (0 = ทั้งหมด)
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        $params = [
            'teacher' => $request->get('teacher')->toInt(),
            'year' => $request->get('year', (string) self::$cfg->academic_year)->toInt(),
            'term' => $request->get('term', (string) self::$cfg->term)->toInt(),
            'class' => $request->get('class')->toInt()
        ];
        if (!ApiController::hasPermission($login, 'can_manage_course')) {
            // ครูเห็นเฉพาะรายวิชาของตัวเอง
            $params['teacher'] = (int) $login->id;
        }

        return $params;
    }

    /**
     * @param array $params
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable(array $params, $login)
    {
        return Model::toDataTable($params);
    }

    /**
     * @param array $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $canEdit = self::canEditCourses($login);
        $teacher = \School\Teacher\Model::init();
        foreach ($datas as $item) {
            // ไม่ระบุผู้สอน = รายวิชาต้นแบบ ไม่มีปีการศึกษา
            $item->year_text = empty($item->teacher_id) ? '' : $item->year.'/'.$item->term;
            $item->teacher = $teacher->get($item->teacher_id);
            $item->period = empty($item->period) ? '' : (int) $item->period;
            $item->student = (int) $item->student;
            $item->has_students = !empty($item->teacher_id) || $item->student > 0 ? 1 : 0;
            $item->can_edit = $canEdit ? 1 : 0;
        }

        return $datas;
    }

    /**
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getFilters(array $params, $login)
    {
        $category = \School\Category\Model::init();
        $isManager = ApiController::hasPermission($login, 'can_manage_course');
        $years = [];
        foreach (Model::academicYears() as $year) {
            $years[] = ['value' => (string) $year, 'text' => (string) $year];
        }
        $filters = [
            'teacher' => \School\Teacher\Model::init()->toOptions($isManager ? 0 : (int) $login->id),
            'year' => $years,
            'term' => $category->toOptions('term'),
            'class' => $category->toOptions('class')
        ];

        return $filters;
    }

    /**
     * ปุ่มลบรายการที่เลือกเฉพาะผู้ที่แก้ไขรายวิชาได้
     *
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getOptions(array $params, $login)
    {
        $canEdit = self::canEditCourses($login);

        return [
            'type' => \Gcms\Controller::arrayToOptions(Language::get('COURSE_TYPIES', [])),
            '_table' => [
                'showCheckbox' => $canEdit,
                'actions' => $canEdit ? ['delete' => Language::get('Delete')] : []
            ]
        ];
    }

    /**
     * ผู้จัดการรายวิชาหรือครู
     *
     * @param object $login
     *
     * @return bool
     */
    public static function canEditCourses($login)
    {
        return ApiController::hasPermission($login, ['can_manage_course', 'can_teacher']);
    }

    /**
     * ลบรายวิชา (ครูลบได้เฉพาะรายวิชาของตัวเอง)
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if ((int) $login->active !== 1 || !ApiController::canModify($login, ['can_manage_course', 'can_teacher'])) {
            return $this->errorResponse('Permission required', 403);
        }
        $ids = $request->post('ids', [])->toInt();
        if (empty($ids)) {
            $ids = [$request->post('id')->toInt()];
        }
        $where = [['id', $ids]];
        if (!ApiController::hasPermission($login, 'can_manage_course')) {
            $where[] = ['teacher_id', (int) $login->id];
        }
        $db = \Kotchasan\DB::create();
        $exists = [];
        foreach ($db->select('course', $where, [], ['id']) as $item) {
            $exists[] = (int) $item->id;
        }
        if (empty($exists)) {
            return $this->errorResponse('Delete action failed', 400);
        }
        $db->delete('course', [['id', $exists]], 0);
        \Index\Log\Model::add(0, 'school', 'Delete', '{LNG_Delete} {LNG_Course} ID : '.implode(', ', $exists), $login->id);

        return $this->redirectResponse('reload', 'Deleted '.count($exists).' item(s) successfully', 200, 0, 'table');
    }

    /**
     * ไปหน้าแก้ไข
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleEditAction(Request $request, $login)
    {
        if (!self::canEditCourses($login)) {
            return $this->errorResponse('Permission required', 403);
        }

        return $this->redirectResponse('/school-course?id='.$request->post('id')->toInt());
    }

    /**
     * ดาวน์โหลดรายวิชา (CSV) ตามตัวกรองที่เลือก ในรูปแบบเดียวกับไฟล์นำเข้า
     *
     * @param Request $request
     * @param object $login
     *
     * @return void
     */
    protected function handleCsvExport(Request $request, $login)
    {
        $params = $this->parseParams($request, $login);
        $query = Model::toDataTable($params)
            ->select('C.course_code', 'C.course_name', 'C.credit', 'C.period', 'C.type', 'C.class', 'C.year', 'C.term', 'C.teacher_id')
            ->orderBy('C.year', 'DESC')
            ->orderBy('C.term', 'DESC')
            ->orderBy('C.teacher_id', 'DESC');
        $rows = [];
        foreach ($query->fetchAll(true) as $item) {
            $rows[] = array_values($item);
        }
        \Kotchasan\Csv::send('course', \School\Csv\Model::course(), $rows, self::$cfg->csv_language);
    }
}
