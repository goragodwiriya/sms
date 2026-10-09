<?php
/**
 * @filesource modules/school/controllers/import.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Import;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\UploadedFile;
use Kotchasan\Language;

/**
 * api/school/import/get|save|sample?type=student|course|grade
 * นำเข้านักเรียน รายวิชา ผลการเรียน จาก CSV — เดิมคือ module=school-import&type=
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * สิทธิ์ของการนำเข้าแต่ละชนิด (ตามเมนูของระบบเดิม)
     */
    const PERMISSIONS = [
        'student' => ['can_manage_student'],
        'course' => ['can_teacher', 'can_manage_student', 'can_manage_course'],
        'grade' => ['can_teacher', 'can_manage_course', 'can_rate_student']
    ];

    /**
     * ชนิดที่ขอมา
     *
     * @param Request $request
     *
     * @return string ค่าว่างถ้าไม่ถูกต้อง
     */
    private static function type(Request $request)
    {
        $type = $request->request('type')->filter('a-z');

        return isset(self::PERMISSIONS[$type]) ? $type : '';
    }

    /**
     * ข้อมูลของฟอร์มนำเข้า
     *
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
            $type = self::type($request);
            if ($type === '') {
                return $this->redirectResponse('/404', 'No data available', 404);
            }
            if (!ApiController::hasPermission($login, self::PERMISSIONS[$type])) {
                return $this->errorResponse('Permission required', 403);
            }
            $isManager = ApiController::hasPermission($login, 'can_manage_course');
            $category = \School\Category\Model::init();
            $titles = [
                'student' => '{LNG_Import} {LNG_Student list}',
                'course' => '{LNG_Import} {LNG_Course}',
                'grade' => '{LNG_Import} {LNG_Grade}'
            ];
            $data = [
                'type' => $type,
                'title' => Language::trans($titles[$type]),
                'encoding' => Language::get('CSV_ENCODING', '', self::$cfg->csv_language),
                'max_size' => UploadedFile::getUploadSize(),
                'year' => (int) self::$cfg->academic_year,
                'term' => (string) (int) self::$cfg->term,
                'teacher_id' => $isManager ? '0' : (string) (int) $login->id,
                'is_manager' => $isManager,
                'categories' => []
            ];
            $options = [
                'term' => $category->toOptions('term'),
                'class' => $category->toOptions('class'),
                'room' => $category->toOptions('room'),
                'typ' => \Gcms\Controller::arrayToOptions(Language::get('COURSE_TYPIES', [])),
                'teacher_id' => array_merge([['value' => '0', 'text' => Language::get('Please select')]], \School\Teacher\Model::init()->toOptions($isManager ? 0 : (int) $login->id)),
                'course' => \School\Courses\Model::codeOptions($isManager ? 0 : (int) $login->id)
            ];
            foreach (\School\Category\Model::studentTypies() as $key => $label) {
                $data['categories'][] = ['url' => '/school-categories?type='.$key, 'text' => $label];
                $data[$key] = (string) $category->getFirstKey($key);
                $options[$key] = $category->toOptions($key);
            }
            $data['class'] = (string) $category->getFirstKey('class');
            $data['room'] = (string) $category->getFirstKey('room');

            return $this->successResponse([
                'data' => $data,
                'options' => $options
            ], 'OK');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ไฟล์ตัวอย่าง (เติมค่าตามตัวเลือกในฟอร์มให้แล้ว)
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response|void
     */
    public function sample(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            $type = self::type($request);
            if ($type === '' || !ApiController::hasPermission($login, self::PERMISSIONS[$type])) {
                return $this->errorResponse('Permission required', 403);
            }
            $params = [];
            foreach (['course', 'room', 'year', 'term', 'typ', 'class', 'teacher_id', 'department'] as $key) {
                $params[$key] = $key === 'course' ? $request->get($key)->topic() : $request->get($key)->toInt();
            }
            if ($type === 'course' && !ApiController::hasPermission($login, 'can_manage_course')) {
                $params['teacher_id'] = (int) $login->id;
            }
            $header = \School\Csv\Model::$type();
            \Kotchasan\Csv::send($type, $header, Model::sample($type, $params), self::$cfg->csv_language);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * นำเข้าไฟล์ CSV
     *
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
            $type = self::type($request);
            if ($type === '') {
                return $this->errorResponse('Invalid action', 400);
            }
            if ((int) $login->active !== 1 || !ApiController::canModify($login, self::PERMISSIONS[$type])) {
                return $this->errorResponse('Permission required', 403);
            }
            $files = $request->getUploadedFiles();
            $file = isset($files['import']) ? $files['import'] : null;
            /** @var UploadedFile|null $file */
            if (!$file || !$file->hasUploadFile()) {
                $message = $file && $file->hasError() ? $file->getErrorMessage() : 'Please browse file';

                return $this->formErrorResponse(['import' => $message], 400);
            }
            if (!$file->validFileExt(['csv'])) {
                return $this->formErrorResponse(['import' => 'The type of file is invalid'], 400);
            }
            $params = [];
            foreach (array_keys(\School\Category\Model::studentTypies()) as $key) {
                $params[$key] = $request->post($key)->toInt();
            }
            try {
                $result = Model::import($type, $file->getTempFileName(), $params, $login);
            } catch (\Throwable $th) {
                return $this->formErrorResponse(['import' => $th->getMessage()], 400);
            }
            $message = Language::replace('Successfully imported :count items', [':count' => $result->row]);
            if ($result->phoneSkipped > 0) {
                $message .= ' ('.Language::replace(':count items did not keep the phone number because it is already used by another member', [':count' => $result->phoneSkipped]).')';
            }
            \Index\Log\Model::add(0, 'school', 'Import', ucfirst($type).' '.$message, $login->id);

            return $this->redirectResponse($type === 'student' ? '/school-students' : '/school-courses', $message, 200, 2000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
