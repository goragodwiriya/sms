<?php
/**
 * @filesource modules/school/controllers/course.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Course;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * api/school/course/get|save|find
 * ฟอร์มเพิ่ม/แก้ไขรายวิชา — เดิมคือ module=school-course
 *
 * ผู้จัดการรายวิชาแก้ไขได้ทุกรายวิชาและเลือกครูผู้สอนได้
 * ครู (can_teacher) เพิ่มรายวิชาของตัวเองและแก้ไขได้เฉพาะรายวิชาที่ตัวเองสอน
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            $isManager = ApiController::hasPermission($login, 'can_manage_course');
            $teacher_id = $isManager ? $request->get('teacher')->toInt() : (int) $login->id;
            $index = Model::getForWrite($request->get('id')->toInt(), $teacher_id, $request->get('class')->toInt());
            if (!$index) {
                return $this->redirectResponse('/404', 'Sorry, Item not found It&#39;s may be deleted', 404);
            }
            if (!self::canEdit($login, $index)) {
                return $this->errorResponse('Permission required', 403);
            }
            $category = \School\Category\Model::init();

            return $this->successResponse([
                'data' => [
                    'id' => (int) $index->id,
                    'course_code' => (string) $index->course_code,
                    'course_name' => (string) $index->course_name,
                    'credit' => $index->credit === '' ? '' : (string) $index->credit,
                    'period' => $index->period === '' ? '' : (int) $index->period,
                    'type' => (string) (int) $index->type,
                    'class' => (string) (int) $index->class,
                    'teacher_id' => (string) (int) $index->teacher_id,
                    'year' => (int) $index->year,
                    'term' => (string) (int) $index->term,
                    'is_manager' => $isManager
                ],
                'options' => [
                    'type' => \Gcms\Controller::arrayToOptions(Language::get('COURSE_TYPIES', [])),
                    'class' => $category->toOptions('class'),
                    'term' => $category->toOptions('term'),
                    'teacher_id' => array_merge([['value' => '0', 'text' => Language::get('Please select')]], \School\Teacher\Model::init()->toOptions())
                ]
            ], 'OK');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }
            if ((int) $login->active !== 1 || !ApiController::isNotDemoMode($login)) {
                return $this->errorResponse('Permission required', 403);
            }
            $isManager = ApiController::hasPermission($login, 'can_manage_course');
            $index = Model::getForWrite($request->post('id')->toInt(), $isManager ? 0 : (int) $login->id);
            if (!$index) {
                return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
            }
            if (!self::canEdit($login, $index)) {
                return $this->errorResponse('Permission required', 403);
            }
            // ช่องรหัสวิชาเป็นแบบเติมอัตโนมัติ ค่าที่พิมพ์เองอยู่ใน course_code_text
            $code = $request->post('course_code')->topic();
            if ($code === '') {
                $code = $request->post('course_code_text')->topic();
            }
            $save = [
                'course_code' => $code,
                'course_name' => $request->post('course_name')->topic(),
                'class' => $request->post('class')->toInt(),
                'type' => $request->post('type')->toInt(),
                'period' => $request->post('period')->toInt(),
                'credit' => round($request->post('credit')->toDouble(), 1),
                'year' => $request->post('year')->toInt(),
                'term' => $request->post('term')->toInt(),
                // ผู้จัดการเลือกครูได้ ครูเป็นผู้สอนของรายวิชาตัวเองเสมอ
                'teacher_id' => $isManager ? $request->post('teacher_id')->toInt() : (int) $index->teacher_id
            ];
            if ($save['teacher_id'] === 0) {
                // รายวิชาต้นแบบไม่มีปีการศึกษาและภาคเรียน
                $save['year'] = 0;
                $save['term'] = 0;
            }
            $errors = [];
            if ($save['course_name'] === '') {
                $errors['course_name'] = 'Please fill in';
            }
            if ($save['credit'] > 9.9 || $save['credit'] < 0) {
                $errors['credit'] = Language::replace('Invalid :name', [':name' => Language::get('Credit')]);
            }
            if (Model::exists($index->id, $save)) {
                $errors['course_code'] = Language::replace('This :name already exist', [':name' => Language::get('Course Code')]);
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }
            $db = \Kotchasan\DB::create();
            if ($index->id == 0) {
                $id = (int) $db->insert('course', $save);
            } else {
                $id = (int) $index->id;
                $db->update('course', [['id', $id]], $save);
            }
            \Index\Log\Model::add($id, 'school', 'Save', '{LNG_Course} ID : '.$id, $login->id);

            return $this->redirectResponse('/school-courses', 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * รายวิชาที่รหัสขึ้นต้นด้วยคำค้น (data-autocomplete ยิง GET ?q=)
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function find(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            if (!\School\Courses\Controller::canEditCourses($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            return $this->successResponse(Model::suggest($request->get('q')->topic()), 'Search completed');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ผู้จัดการรายวิชาแก้ไขได้ทุกรายวิชา ครูแก้ไขได้เฉพาะรายวิชาที่ตัวเองสอน
     *
     * @param object $login
     * @param object $course
     *
     * @return bool
     */
    public static function canEdit($login, $course)
    {
        if (ApiController::hasPermission($login, 'can_manage_course')) {
            return true;
        }

        return ApiController::hasPermission($login, 'can_teacher') && (int) $course->teacher_id === (int) $login->id;
    }
}
