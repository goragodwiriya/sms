<?php
/**
 * @filesource modules/school/controllers/grades.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Grades;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * api/school/grades?subject=
 * นักเรียนที่ลงทะเบียนและผลการเรียนของรายวิชา — เดิมคือ module=school-grades
 *
 * ผู้จัดการนักเรียน/รายวิชาเปิดได้ทุกรายวิชา ครูและผู้ให้คะแนนเปิดได้เฉพาะรายวิชาที่ตัวเองสอน
 * แก้เลขที่และห้องได้: ครู ผู้จัดการนักเรียน ผู้จัดการรายวิชา
 * ให้คะแนน/ผลการเรียนได้: + ผู้ให้คะแนน (can_rate_student)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Table
{
    /**
     * สิทธิ์ให้คะแนน
     */
    const RATE_PERMISSIONS = ['can_manage_student', 'can_manage_course', 'can_teacher', 'can_rate_student'];

    /**
     * @var array
     */
    protected $allowedSortColumns = ['room', 'number', 'student_id', 'name', 'grade'];

    /**
     * รายวิชาที่กำลังเปิด
     *
     * @var object|null
     */
    protected $course;

    /**
     * @param Request $request
     * @param object $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        $this->course = \School\Course\Model::find($request->get('subject')->toInt());
        if (!$this->course) {
            return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
        }
        if (!self::canOpen($login, $this->course)) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
    }

    /**
     * ผู้จัดการนักเรียน/รายวิชาเปิดได้ทุกรายวิชา คนอื่นต้องเป็นครูผู้สอนของรายวิชานี้
     *
     * @param object $login
     * @param object $course
     *
     * @return bool
     */
    public static function canOpen($login, $course)
    {
        if (ApiController::hasPermission($login, ['can_manage_student', 'can_manage_course'])) {
            return true;
        }

        return ApiController::hasPermission($login, ['can_teacher', 'can_rate_student']) && (int) $course->teacher_id === (int) $login->id;
    }

    /**
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'subject' => $request->get('subject')->toInt(),
            'room' => $request->get('room')->toInt()
        ];
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
        foreach ($datas as $item) {
            $item->number = $item->number === null ? '' : (int) $item->number;
            $item->room = (string) (int) $item->room;
            $item->type = (string) (int) $item->type;
            $item->midterm = $item->midterm === null ? '' : (int) $item->midterm;
            $item->final = $item->final === null ? '' : (int) $item->final;
            $item->grade = (string) $item->grade;
        }

        return $datas;
    }

    /**
     * คอลัมน์ขึ้นกับสิทธิ์และโหมดการคิดเกรด
     * ไม่ได้ตั้งเกณฑ์คำนวณ = ไม่มีช่องคะแนนกลางภาค/ปลายภาค เหมือนระบบเดิม
     *
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getColumns(array $params, $login)
    {
        $canManage = ApiController::hasPermission($login, \School\Students\Controller::EDIT_PERMISSIONS);
        $canRate = ApiController::hasPermission($login, self::RATE_PERMISSIONS);
        $columns = [
            ['field' => 'number', 'label' => Language::get('Number'), 'sort' => 'number', 'class' => 'center', 'cellClass' => 'center'],
            ['field' => 'student_id', 'label' => Language::get('Student ID'), 'sort' => 'student_id'],
            ['field' => 'name', 'label' => Language::get('Full Name'), 'sort' => 'name'],
            ['field' => 'room', 'label' => Language::get('Room'), 'sort' => 'room', 'class' => 'center', 'cellClass' => 'center',
                'filter' => true, 'type' => 'select', 'showAll' => 'true', 'allValue' => '', 'format' => 'lookup'],
            ['field' => 'type', 'label' => '', 'class' => 'center', 'cellClass' => 'center', 'format' => 'lookup', 'optionsKey' => 'type']
        ];
        if ($canManage) {
            $columns[0]['cellElement'] = 'number';
            $columns[0]['min'] = 0;
            $columns[0]['size'] = 5;
            $columns[3]['cellElement'] = 'select';
            $columns[3]['optionsKey'] = 'room';
        }
        if ($canRate) {
            $columns[4]['cellElement'] = 'select';
        }
        if (!\School\Score\Model::gradeOnly()) {
            foreach (['midterm' => 'Midterm', 'final' => 'Final'] as $field => $label) {
                $column = ['field' => $field, 'label' => Language::get($label), 'class' => 'center', 'cellClass' => 'center'];
                if ($canRate) {
                    $column['cellElement'] = 'number';
                    $column['min'] = 0;
                    $column['max'] = 100;
                    $column['size'] = 5;
                }
                $columns[] = $column;
            }
        }
        $columns[] = ['field' => 'grade', 'label' => Language::get('Grade'), 'sort' => 'grade', 'class' => 'center', 'cellClass' => 'center',
            'template' => "<span id='school-grade-\${id}' class='school-grade'>\${grade}</span>"];

        return $columns;
    }

    /**
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getFilters(array $params, $login)
    {
        return [
            'room' => \School\Category\Model::init()->toOptions('room')
        ];
    }

    /**
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getOptions(array $params, $login)
    {
        $canManage = ApiController::hasPermission($login, \School\Students\Controller::EDIT_PERMISSIONS);

        return [
            'room' => \School\Category\Model::init()->toOptions('room'),
            'type' => \Gcms\Controller::arrayToOptions(Language::get('SCHOOL_TYPIES', [])),
            '_table' => [
                'showCheckbox' => $canManage,
                'actions' => $canManage ? ['delete' => Language::get('Delete')] : []
            ]
        ];
    }

    /**
     * หัวข้อของหน้า (ชื่อรายวิชา ปีการศึกษา) และปุ่มที่ใช้ได้
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function info(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            $auth = $this->checkAuthorization($request, $login);
            if ($auth !== true) {
                return $auth;
            }
            $course = $this->course;
            $title = Language::get('Grade').' '.Language::get('Course').' '.$course->course_name.($course->course_code !== '' ? ' ('.$course->course_code.')' : '');
            if (!empty($course->teacher_id)) {
                $title .= ' '.Language::get('Academic year').' '.$course->year.'/'.$course->term;
            }

            return $this->successResponse([
                'id' => (int) $course->id,
                'title' => $title,
                'course_name' => $course->course_name,
                'class' => (int) $course->class,
                'can_register' => \School\Registrations\Controller::canRegister($login, $course) ? 1 : 0
            ], 'OK');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * อ่านแถวผลการเรียนพร้อมรายวิชา และตรวจว่าเปิดรายวิชานี้ได้
     *
     * @param int $id
     * @param object $login
     *
     * @return object|null
     */
    protected function gradeOf($id, $login)
    {
        $grade = \Kotchasan\DB::create()->first('grade', [['id', (int) $id]]);
        if (!$grade) {
            return null;
        }
        $course = \School\Course\Model::find($grade->course_id);

        return $course && self::canOpen($login, $course) ? $grade : null;
    }

    /**
     * แก้ไขในตาราง เลขที่ ห้อง ประเภท คะแนน (คำนวณเกรดใหม่ทันที)
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleUpdateAction(Request $request, $login)
    {
        $field = $request->post('field')->filter('a-z_');
        $ids = $request->post('ids', [])->toInt();
        $need = in_array($field, ['number', 'room'], true) ? \School\Students\Controller::EDIT_PERMISSIONS : self::RATE_PERMISSIONS;
        if (!in_array($field, ['number', 'room', 'type', 'midterm', 'final'], true) || empty($ids)) {
            return $this->errorResponse('Invalid action', 400);
        }
        if ((int) $login->active !== 1 || !ApiController::canModify($login, $need)) {
            return $this->errorResponse('Permission required', 403);
        }
        $grade = $this->gradeOf($ids[0], $login);
        if (!$grade) {
            return $this->errorResponse('Permission required', 403);
        }
        $raw = trim($request->post('value')->toString());
        $value = $raw === '' ? null : (int) $raw;
        if ($field === 'midterm' || $field === 'final') {
            $value = $value === null ? null : max(0, min(100, $value));
        } elseif ($field !== 'number') {
            $value = (int) $value;
        }
        $save = [$field => $value];
        if (in_array($field, ['type', 'midterm', 'final'], true)) {
            $grade->$field = $value;
            $current = (string) $grade->grade;
            // กรอกเกรดเอง: เปลี่ยนกลับเป็น "เกรด" ให้คงเกรดเดิมไว้ (ถ้าไม่ใช่ ร. มส. ฯลฯ)
            if (in_array($current, (array) Language::get('SCHOOL_TYPIES', []), true)) {
                $current = null;
            }
            $save['grade'] = \School\Score\Model::toGrade($grade->type, $grade->midterm, $grade->final, $current);
        }
        \Kotchasan\DB::create()->update('grade', [['id', (int) $grade->id]], $save);
        \Index\Log\Model::add((int) $grade->id, 'school', 'Save', '{LNG_'.ucfirst($field).'} {LNG_Grade} ID : '.(int) $grade->id, $login->id);

        $actions = [];
        if (array_key_exists('grade', $save)) {
            $actions[] = ['type' => 'update', 'target' => '#school-grade-'.(int) $grade->id, 'method' => 'text', 'content' => (string) $save['grade']];
        }

        return $this->successResponse(['actions' => $actions, 'grade' => $save['grade'] ?? $grade->grade], 'Saved successfully');
    }

    /**
     * ลบนักเรียนออกจากรายวิชา (ลบผลการเรียน)
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if ((int) $login->active !== 1 || !ApiController::canModify($login, \School\Students\Controller::EDIT_PERMISSIONS)) {
            return $this->errorResponse('Permission required', 403);
        }
        $ids = $request->post('ids', [])->toInt();
        if (empty($ids)) {
            $ids = [$request->post('id')->toInt()];
        }
        $removed = [];
        foreach ($ids as $id) {
            if ($this->gradeOf($id, $login)) {
                $removed[] = (int) $id;
            }
        }
        if (empty($removed)) {
            return $this->errorResponse('Delete action failed', 400);
        }
        \Kotchasan\DB::create()->delete('grade', [['id', $removed]], 0);
        \Index\Log\Model::add(0, 'school', 'Delete', '{LNG_Delete} {LNG_Grade} ID : '.implode(', ', $removed), $login->id);

        return $this->redirectResponse('reload', 'Deleted '.count($removed).' item(s) successfully', 200, 0, 'table');
    }

    /**
     * ดาวน์โหลดผลการเรียนของรายวิชา (CSV)
     *
     * @param Request $request
     * @param object $login
     *
     * @return void
     */
    protected function handleCsvExport(Request $request, $login)
    {
        $category = \School\Category\Model::init();
        $header = [
            Language::get('Number'),
            Language::get('Student ID'),
            Language::get('Full Name'),
            Language::get('Course Code'),
            Language::get('Academic year'),
            Language::get('Term'),
            Language::get('Class'),
            Language::get('Room'),
            Language::get('Grade')
        ];
        $rows = [];
        foreach (Model::export($this->course->id, $request->get('room')->toInt()) as $item) {
            $item['room'] = $category->get('room', $item['room']);
            $item['class'] = $category->get('class', $item['class']);
            $rows[] = array_values($item);
        }
        \Kotchasan\Csv::send('grade', $header, $rows, self::$cfg->csv_language);
    }
}
