<?php
/**
 * @filesource modules/school/controllers/student.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Student;

use Gcms\Api as ApiController;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * api/school/student/get|save|view
 * ฟอร์มเพิ่ม/แก้ไขนักเรียน — เดิมคือ module=school-student
 * นักเรียนแก้ไขข้อมูลของตัวเองได้ (เดิมทำผ่านหน้าแก้ไขข้อมูลส่วนตัว) แต่เปลี่ยนรหัสนักเรียนและชั้น/ห้องไม่ได้
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
            $params = [];
            foreach (array_keys(\School\Category\Model::studentTypies()) as $type) {
                $params[$type] = $request->get($type)->toInt();
            }
            $index = Model::getForWrite($request->get('id')->toInt(), $params);
            if (!$index) {
                return $this->redirectResponse('/404', 'Sorry, Item not found It&#39;s may be deleted', 404);
            }
            if (!self::canEdit($login, $index->id)) {
                return $this->errorResponse('Permission required', 403);
            }
            $isSelf = (int) $index->id === (int) $login->id;
            $category = \School\Category\Model::init();
            $picture = Model::picture($index->id);
            $data = [
                'id' => (int) $index->id,
                'is_self' => $isSelf,
                'name' => $index->name,
                'student_id' => (string) $index->student_id,
                'id_card' => (string) $index->id_card,
                'birthday' => (string) $index->birthday,
                'sex' => (string) $index->sex,
                'phone' => (string) $index->phone,
                'address' => (string) $index->address,
                'parent' => (string) $index->parent,
                'parent_phone' => (string) $index->parent_phone,
                'picture' => [[
                    'url' => $picture ?? WEB_URL.'images/no-image.webp',
                    'name' => $picture ? $index->id.self::$cfg->stored_img_type : 'Choose file'
                ]],
                'picture_comment' => Language::replace('Browse image uploaded, type :type size :width*:height pixel (automatic resize)', [
                    ':type' => implode(', ', self::$cfg->member_img_typies),
                    ':width' => self::$cfg->student_w,
                    ':height' => self::$cfg->student_h
                ])
            ];
            $options = [
                'sex' => \Gcms\Controller::arrayToOptions(Language::get('SEXES', []))
            ];
            foreach (array_keys(\School\Category\Model::studentTypies()) as $type) {
                $data[$type] = (string) (int) $index->$type;
                $options[$type] = array_merge([['value' => '0', 'text' => Language::get('Please select')]], $category->toOptions($type));
            }

            return $this->successResponse([
                'data' => $data,
                'options' => $options
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
            $index = Model::getForWrite($request->post('id')->toInt());
            if (!$index) {
                return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
            }
            if (!self::canEdit($login, $index->id) || (int) $login->active !== 1 || !ApiController::isNotDemoMode($login)) {
                return $this->errorResponse('Permission required', 403);
            }
            $isSelf = (int) $index->id === (int) $login->id;

            $user = [
                'name' => $request->post('name')->topic(),
                'phone' => \Personnel\Account\Model::digits($request->post('phone')->topic()),
                'birthday' => $request->post('birthday')->date(),
                'sex' => $request->post('sex')->filter('a-z')
            ];
            $student = [
                'id_card' => \Personnel\Account\Model::digits($request->post('id_card')->topic()),
                'student_id' => $request->post('student_id')->topic(),
                'address' => $request->post('address')->topic(),
                'parent' => $request->post('parent')->topic(),
                'parent_phone' => $request->post('parent_phone')->topic()
            ];
            if ($isSelf) {
                // นักเรียนเปลี่ยนรหัสนักเรียนและแผนก/ชั้น/ห้องของตัวเองไม่ได้
                unset($student['student_id']);
            } else {
                foreach (array_keys(\School\Category\Model::studentTypies()) as $type) {
                    $student[$type] = $request->post($type)->toInt();
                }
            }
            $updatePassword = $index->id == 0 || $request->post('updatepassword')->toBoolean();

            $errors = [];
            if ($user['name'] === '') {
                $errors['name'] = 'Please fill in';
            }
            if ($student['id_card'] !== '' && !preg_match('/^[0-9]{13}$/', $student['id_card'])) {
                $errors['id_card'] = Language::replace('Invalid :name', [':name' => Language::get('Identification No.')]);
            }
            if ($index->id > 0 && $updatePassword) {
                if ($student['id_card'] === '') {
                    $errors['id_card'] = 'Please fill in';
                }
                if ($user['birthday'] === '') {
                    $errors['birthday'] = 'Please fill in';
                }
            }
            $duplicate = Model::exists($index->id, $student);
            if ($duplicate && empty($errors[$duplicate])) {
                $errors[$duplicate] = Language::replace('This :name already exist', [
                    ':name' => Language::get($duplicate === 'student_id' ? 'Student ID' : 'Identification No.')
                ]);
            }
            $username = null;
            $password = '';
            if ($updatePassword && $student['id_card'] !== '' && $user['birthday'] !== '') {
                $username = $student['id_card'];
                $password = \Personnel\Account\Model::birthdayPassword($user['birthday']);
                if (empty($errors['id_card']) && !\Personnel\Account\Model::isUnique('username', $username, $index->id)) {
                    $errors['id_card'] = Language::replace('This :name already exist', [':name' => Language::get('Username')]);
                }
            }
            if (!\Personnel\Account\Model::isUnique('phone', $user['phone'], $index->id)) {
                $errors['phone'] = Language::replace('This :name already exist', [':name' => Language::get('Phone')]);
            }
            $picture = null;
            $files = $request->getUploadedFiles();
            if (isset($files['picture'])) {
                /** @var \Kotchasan\Http\UploadedFile $file */
                $file = $files['picture'];
                if ($file->hasUploadFile()) {
                    if (!$file->validFileExt(self::$cfg->member_img_typies)) {
                        $errors['picture'] = 'The type of file is invalid';
                    } else {
                        $picture = $file;
                    }
                } elseif ($file->hasError()) {
                    $errors['picture'] = $file->getErrorMessage();
                }
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            $db = \Kotchasan\DB::create();
            if ($index->id == 0) {
                $user['status'] = (int) self::$cfg->student_status;
                $student['id'] = \Personnel\Account\Model::create($db, $user, (string) $username, $password);
                $db->insert('student', $student);
            } else {
                $student['id'] = (int) $index->id;
                \Personnel\Account\Model::update($db, $index->id, $user, $username, $password);
                $db->update('student', [['id', (int) $index->id]], $student);
            }
            if ($picture) {
                $dir = ROOT_PATH.DATA_FOLDER.'school/';
                if (!File::makeDirectory($dir)) {
                    return $this->formErrorResponse(['picture' => Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'school/')], 400);
                }
                try {
                    $picture->cropImage(self::$cfg->member_img_typies, $dir.$student['id'].self::$cfg->stored_img_type, self::$cfg->student_w, self::$cfg->student_h);
                } catch (\Exception $exc) {
                    return $this->formErrorResponse(['picture' => Language::get($exc->getMessage())], 400);
                }
            }
            \Index\Log\Model::add($student['id'], 'school', 'Save', '{LNG_Student} ID : '.$student['id'], $login->id);

            if ($isSelf) {
                return $this->redirectResponse('reload', 'Saved successfully');
            }

            return $this->redirectResponse('/school-students', 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * modal รายละเอียดนักเรียน (หน้าผลการเรียนและหน้าลงทะเบียนเรียกที่นี่)
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function view(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            if (!ApiController::hasPermission($login, \School\Students\Controller::VIEW_PERMISSIONS)) {
                return $this->errorResponse('Permission required', 403);
            }

            return \School\Students\Controller::viewResponse($this, $request->get('id')->toInt(), $login);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ครู ผู้จัดการนักเรียน ผู้จัดการรายวิชา แก้ไขได้ทุกคน นักเรียนแก้ไขได้เฉพาะของตัวเอง
     *
     * @param object $login
     * @param int $id
     *
     * @return bool
     */
    public static function canEdit($login, $id)
    {
        if ((int) $id > 0 && (int) $id === (int) $login->id) {
            return true;
        }

        return (int) $id !== 1 && ApiController::hasPermission($login, \School\Students\Controller::EDIT_PERMISSIONS);
    }
}
