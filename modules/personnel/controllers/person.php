<?php
/**
 * @filesource modules/personnel/controllers/person.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Person;

use Gcms\Api as ApiController;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * api/personnel/person/get|save
 * ฟอร์มเพิ่ม/แก้ไขบุคลากร — เดิมคือ module=personnel-write
 * ผู้มีสิทธิ์ can_manage_personnel แก้ไขได้ทุกคน คนอื่นแก้ไขได้เฉพาะของตัวเอง
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
            $index = Model::get($request->get('id')->toInt());
            if (!$index) {
                return $this->redirectResponse('/404', 'Sorry, Item not found It&#39;s may be deleted', 404);
            }
            if (!self::canEdit($login, $index->id)) {
                return $this->errorResponse('Permission required', 403);
            }
            $isAdmin = self::canSetPermission($login, $index->id);
            $category = \Personnel\Category\Model::init();
            $school = Model::schoolCategory();

            $details = [];
            foreach (Model::details() as $key => $label) {
                $details[] = [
                    'name' => 'custom['.$key.']',
                    'label' => $label,
                    'value' => isset($index->custom[$key]) ? (string) $index->custom[$key] : ''
                ];
            }
            $picture = Model::picture($index->id);
            $data = [
                'id' => (int) $index->id,
                'name' => $index->name,
                'id_card' => (string) $index->id_card,
                'birthday' => (string) $index->birthday,
                'phone' => (string) $index->phone,
                'order' => (int) $index->order,
                'class' => (string) (int) $index->class,
                'room' => (string) (int) $index->room,
                'details' => $details,
                'picture' => [[
                    'url' => $picture ?? WEB_URL.'images/no-image.webp',
                    'name' => $picture ? $index->id.self::$cfg->stored_img_type : 'Choose file'
                ]],
                'picture_comment' => Language::replace('Browse image uploaded, type :type size :width*:height pixel (automatic resize)', [
                    ':type' => implode(', ', self::$cfg->member_img_typies),
                    ':width' => self::$cfg->personnel_w,
                    ':height' => self::$cfg->personnel_h
                ]),
                'isAdmin' => $isAdmin,
                'permission' => $isAdmin ? \Index\Auth\Model::parsePermission($index->permission) : []
            ];
            $options = [
                'class' => array_merge([['value' => '0', 'text' => '-']], $school ? $school->toOptions('class') : []),
                'room' => array_merge([['value' => '0', 'text' => '-']], $school ? $school->toOptions('room') : [])
            ];
            // ตำแหน่ง แผนก (หมวดหมู่ตามคีย์ภาษา CATEGORIES)
            foreach ($category->typies() as $type) {
                $data[$type] = (string) (int) $index->$type;
                $options[$type] = array_merge([['value' => '0', 'text' => Language::get('Please select')]], $category->toOptions($type));
            }
            if ($isAdmin) {
                $options['permission'] = \Gcms\Controller::getPermissionOptions($login);
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
            $index = Model::get($request->post('id')->toInt());
            if (!$index) {
                return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
            }
            if (!self::canEdit($login, $index->id) || !ApiController::isNotDemoMode($login) || (int) $login->active !== 1) {
                return $this->errorResponse('Permission required', 403);
            }
            $canManage = ApiController::hasPermission($login, 'can_manage_personnel');

            $user = [
                'name' => $request->post('name')->topic(),
                'phone' => \Personnel\Account\Model::digits($request->post('phone')->topic()),
                'birthday' => $request->post('birthday')->date()
            ];
            $personnel = [
                'id_card' => \Personnel\Account\Model::digits($request->post('id_card')->topic()),
                'order' => max(0, min(255, $request->post('order')->toInt())),
                'class' => $request->post('class')->toInt(),
                'room' => $request->post('room')->toInt()
            ];
            foreach (array_keys(\Personnel\Category\Model::items()) as $type) {
                $personnel[$type] = $request->post($type)->toInt();
            }
            $postedCustom = $request->post('custom', [])->topic();
            $custom = [];
            foreach (array_keys(Model::details()) as $key) {
                $custom[$key] = is_array($postedCustom) && isset($postedCustom[$key]) ? $postedCustom[$key] : '';
            }
            $updatePassword = $index->id == 0 || $request->post('updatepassword')->toBoolean();

            $errors = [];
            if ($user['name'] === '') {
                $errors['name'] = 'Please fill in';
            }
            if ($personnel['id_card'] !== '' && !preg_match('/^[0-9]{13}$/', $personnel['id_card'])) {
                $errors['id_card'] = Language::replace('Invalid :name', [':name' => Language::get('Identification No.')]);
            }
            if ($index->id > 0 && $updatePassword) {
                // อัปเดตชื่อผู้ใช้และรหัสผ่านด้วยเลขประชาชนและวันเกิด ต้องกรอกทั้งสองช่อง
                if ($personnel['id_card'] === '') {
                    $errors['id_card'] = 'Please fill in';
                }
                if ($user['birthday'] === '') {
                    $errors['birthday'] = 'Please fill in';
                }
            }
            if (empty($errors['id_card']) && Model::idCardExists($index->id, $personnel['id_card'])) {
                $errors['id_card'] = Language::replace('This :name already exist', [':name' => Language::get('Identification No.')]);
            }
            $username = null;
            $password = '';
            if ($updatePassword && $personnel['id_card'] !== '' && $user['birthday'] !== '') {
                $username = $personnel['id_card'];
                $password = \Personnel\Account\Model::birthdayPassword($user['birthday']);
                if (empty($errors['id_card']) && !\Personnel\Account\Model::isUnique('username', $username, $index->id)) {
                    $errors['id_card'] = Language::replace('This :name already exist', [':name' => Language::get('Username')]);
                }
            }
            if (!\Personnel\Account\Model::isUnique('phone', $user['phone'], $index->id)) {
                $errors['phone'] = Language::replace('This :name already exist', [':name' => Language::get('Phone')]);
            }
            // ไฟล์รูป ตรวจก่อนบันทึก
            $picture = null;
            foreach ($request->getUploadedFiles() as $item => $file) {
                if ($item !== 'picture') {
                    continue;
                }
                /** @var \Kotchasan\Http\UploadedFile $file */
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
            $personnel['custom'] = json_encode($custom, JSON_UNESCAPED_UNICODE);
            if (self::canSetPermission($login, $index->id)) {
                $permission = $request->post('permission', [])->filter('a-z0-9_');
                $user['permission'] = empty($permission) ? '' : ','.implode(',', (array) $permission).',';
            }
            if ($index->id == 0) {
                // บุคลากรใหม่ได้สถานะครู-อาจารย์
                $user['status'] = (int) self::$cfg->teacher_status;
                $personnel['id'] = \Personnel\Account\Model::create($db, $user, (string) $username, $password);
                $db->insert('personnel', $personnel);
            } else {
                $personnel['id'] = (int) $index->id;
                \Personnel\Account\Model::update($db, $index->id, $user, $username, $password);
                $db->update('personnel', [['id', (int) $index->id]], $personnel);
            }
            if ($picture) {
                $dir = ROOT_PATH.DATA_FOLDER.'personnel/';
                if (!File::makeDirectory($dir)) {
                    return $this->formErrorResponse(['picture' => Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'personnel/')], 400);
                }
                try {
                    $picture->cropImage(self::$cfg->member_img_typies, $dir.$personnel['id'].self::$cfg->stored_img_type, self::$cfg->personnel_w, self::$cfg->personnel_h);
                } catch (\Exception $exc) {
                    return $this->formErrorResponse(['picture' => Language::get($exc->getMessage())], 400);
                }
            }
            \Index\Log\Model::add($personnel['id'], 'personnel', 'Save', '{LNG_'.($index->id == 0 ? 'Add' : 'Edit').'} {LNG_Personnel} ID : '.$personnel['id'], $login->id);

            // กลับไปหน้ารายการที่มาจาก
            $back = $canManage ? '/personnel-setup' : '/personnel';

            return $this->redirectResponse($back, 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ผู้มีสิทธิ์จัดการบุคลากรแก้ไขได้ทุกคน คนอื่นแก้ไขได้เฉพาะของตัวเอง
     * บัญชี id 1 แก้ไขได้โดยตัวเองเท่านั้น (กฎเดียวกับหน้าสมาชิกของแกน)
     *
     * @param object $login
     * @param int $id
     *
     * @return bool
     */
    public static function canEdit($login, $id)
    {
        $id = (int) $id;
        if ($id === (int) $login->id) {
            return true;
        }

        return $id !== 1 && ApiController::hasPermission($login, 'can_manage_personnel');
    }

    /**
     * กำหนดสิทธิ์การใช้งานได้เฉพาะผู้ดูแลระบบ (กฎเดียวกับฟอร์มสมาชิกของแกน)
     *
     * @param object $login
     * @param int $id
     *
     * @return bool
     */
    public static function canSetPermission($login, $id)
    {
        return \Index\Users\Model::canManage($login) && \Index\Users\Model::canEdit($login, $id);
    }
}
