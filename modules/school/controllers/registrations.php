<?php
/**
 * @filesource modules/school/controllers/registrations.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Registrations;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * api/school/registrations?subject=
 * ลงทะเบียนเรียน เลือกนักเรียนเข้ารายวิชา — เดิมคือ module=school-register
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
    protected $allowedSortColumns = ['number', 'student_id', 'name', 'class', 'room', 'registered'];

    /**
     * @var object|null
     */
    protected $course;

    /**
     * ผู้จัดการรายวิชาลงทะเบียนได้ทุกรายวิชา ครูลงทะเบียนได้เฉพาะรายวิชาที่ตัวเองสอน
     *
     * @param object $login
     * @param object $course
     *
     * @return bool
     */
    public static function canRegister($login, $course)
    {
        if (ApiController::hasPermission($login, 'can_manage_course')) {
            return true;
        }

        return ApiController::hasPermission($login, 'can_teacher') && (int) $course->teacher_id === (int) $login->id;
    }

    /**
     * @param Request $request
     * @param object $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        $this->course = \School\Course\Model::find($request->request('subject')->toInt());
        if (!$this->course) {
            return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
        }
        if (!self::canRegister($login, $this->course)) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
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
            'class' => $request->get('class')->toInt(),
            'room' => $request->get('room')->toInt()
        ];
    }

    /**
     * นักเรียนที่กำลังศึกษา พร้อมสถานะว่าลงทะเบียนรายวิชานี้แล้วหรือยัง
     *
     * @param array $params
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable(array $params, $login)
    {
        $registered = \Kotchasan\Model::createQuery()
            ->selectRaw('COUNT(*)')
            ->from('grade G')
            ->where([
                ['G.course_id', (int) $params['subject']],
                ['G.student_id', \Kotchasan\Database\Sql::column('S.id')]
            ]);
        $where = [['U.active', 1]];
        foreach (['class', 'room'] as $key) {
            if (!empty($params[$key])) {
                $where[] = ['S.'.$key, (int) $params[$key]];
            }
        }
        $query = \Kotchasan\Model::createQuery()
            ->select('S.id', 'S.number', 'S.student_id', 'U.name', [$registered, 'registered'], 'S.class', 'S.room')
            ->from('student S')
            ->join('user U', [['U.id', 'S.id']], 'INNER')
            ->where($where);
        if (!empty($params['search'])) {
            $keyword = '%'.$params['search'].'%';
            $query->where([
                ['U.name', 'LIKE', $keyword],
                ['S.student_id', 'LIKE', $keyword]
            ], 'OR');
        }

        return $query;
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
            $item->registered = (int) $item->registered > 0 ? 1 : 0;
            $item->number = $item->number === null ? '' : (int) $item->number;
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

        return [
            'class' => $category->toOptions('class'),
            'room' => $category->toOptions('room')
        ];
    }

    /**
     * ชื่อรายวิชาสำหรับหัวข้อของหน้า
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

            return $this->successResponse([
                'id' => (int) $course->id,
                'title' => Language::get('Register course').' '.Language::get('Course').' '.$course->course_name.($course->course_code !== '' ? ' ('.$course->course_code.')' : '')
            ], 'OK');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ลงทะเบียนนักเรียนที่เลือก (ข้ามคนที่ลงทะเบียนแล้ว) ใช้เลขที่และห้องของนักเรียน
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleRegisterAction(Request $request, $login)
    {
        $auth = $this->checkAuthorization($request, $login);
        if ($auth !== true) {
            return $auth;
        }
        if ((int) $login->active !== 1 || !ApiController::isNotDemoMode($login)) {
            return $this->errorResponse('Permission required', 403);
        }
        $ids = $request->post('ids', [])->toInt();
        if (empty($ids)) {
            return $this->errorResponse('No items selected', 400);
        }
        $db = \Kotchasan\DB::create();
        $exists = [];
        foreach ($db->select('grade', [['course_id', (int) $this->course->id]], [], ['student_id']) as $item) {
            $exists[(int) $item->student_id] = true;
        }
        $count = 0;
        foreach ($db->select('student', [['id', $ids]], ['orderBy' => 'number'], ['id', 'number', 'room']) as $item) {
            if (!isset($exists[(int) $item->id])) {
                $db->insert('grade', [
                    'student_id' => (int) $item->id,
                    'course_id' => (int) $this->course->id,
                    'number' => $item->number,
                    'room' => (int) $item->room,
                    'type' => 0
                ]);
                ++$count;
            }
        }
        \Index\Log\Model::add((int) $this->course->id, 'school', 'Save', '{LNG_Register course} ID : '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Saved successfully', 200, 0, 'table');
    }
}
