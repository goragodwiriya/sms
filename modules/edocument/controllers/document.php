<?php
/**
 * @filesource modules/edocument/controllers/document.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Document;

use Gcms\Api as ApiController;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * api/edocument/document/get|save
 * ส่งหนังสือ/แก้ไขหนังสือ — เดิมคือ module=edocument-write
 * ส่งได้: can_upload_edocument · แก้ไขได้: ผู้ส่ง หรือผู้จัดการงานสารบรรณ
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
            if (!ApiController::hasPermission($login, 'can_upload_edocument')) {
                return $this->errorResponse('Permission required', 403);
            }
            $id = $request->get('id')->toInt();
            $index = $id > 0 ? Model::get($id) : Model::newDocument($login);
            if (!$index) {
                return $this->redirectResponse('/404', 'Sorry, Item not found It&#39;s may be deleted', 404);
            }
            if ($index->id > 0 && !Model::canManage($login, $index)) {
                return $this->errorResponse('Permission required', 403);
            }
            $receivers = [];
            foreach (Model::receiverOptions() as $status => $label) {
                $receivers[] = [
                    'value' => (string) $status,
                    'text' => $label,
                    'checked' => in_array((int) $status, $index->receiver, true)
                ];
            }
            $urgencies = [];
            foreach (Language::get('URGENCIES', []) as $key => $label) {
                $urgencies[] = ['value' => (string) $key, 'text' => $label, 'checked' => (int) $key === (int) $index->urgency];
            }
            $types = (array) self::$cfg->edocument_file_typies;

            return $this->successResponse([
                'data' => [
                    'id' => (int) $index->id,
                    'document_no' => (string) $index->document_no,
                    'topic' => (string) $index->topic,
                    'detail' => (string) $index->detail,
                    'receivers' => $receivers,
                    'urgencies' => $urgencies,
                    'file_name' => $index->id > 0 ? $index->topic.'.'.$index->ext : '',
                    'accept' => '.'.implode(',.', $types),
                    'file_comment' => Language::replace('Upload :type files no larger than :size', [
                        ':type' => implode(', ', $types),
                        ':size' => Text::formatFileSize(Model::uploadSize())
                    ]),
                    // ส่งอีเมลแจ้งผู้รับ ติ๊กไว้ก่อนเฉพาะหนังสือใหม่เมื่อเปิดใช้ในตั้งค่า
                    'send_mail' => !empty(self::$cfg->edocument_send_mail) && $index->id == 0
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
            if ((int) $login->active !== 1 || !ApiController::canModify($login, ['can_upload_edocument'])) {
                return $this->errorResponse('Permission required', 403);
            }
            $id = $request->post('id')->toInt();
            $index = $id > 0 ? Model::get($id) : Model::newDocument($login);
            if (!$index) {
                return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
            }
            if ($index->id > 0 && !Model::canManage($login, $index)) {
                return $this->errorResponse('Permission required', 403);
            }
            $allowed = array_keys(Model::receiverOptions());
            $receiver = [];
            foreach ((array) $request->post('receiver', [])->toInt() as $status) {
                if (in_array((int) $status, $allowed, true)) {
                    $receiver[(int) $status] = (int) $status;
                }
            }
            $save = [
                'document_no' => $request->post('document_no')->topic(),
                'urgency' => $request->post('urgency')->toInt(),
                'topic' => $request->post('topic')->topic(),
                'detail' => $request->post('detail')->textarea()
            ];
            $errors = [];
            $db = \Kotchasan\DB::create();
            if ($save['document_no'] !== '') {
                $search = $db->first('edocument', [['document_no', $save['document_no']]], ['id']);
                if ($search && (int) $search->id !== (int) $index->id) {
                    $errors['document_no'] = Language::replace('This :name already exist', [':name' => Language::get('Document No.')]);
                }
            }
            if (empty($receiver)) {
                $errors['receiver'] = Language::replace('Please select :name at least one item', [':name' => Language::get('Recipient')]);
            }
            if ($save['detail'] === '') {
                $errors['detail'] = 'Please fill in';
            }
            $upload = null;
            $files = $request->getUploadedFiles();
            $file = isset($files['file']) ? $files['file'] : null;
            /** @var \Kotchasan\Http\UploadedFile|null $file */
            if ($file && $file->hasUploadFile()) {
                if (!$file->validFileExt((array) self::$cfg->edocument_file_typies)) {
                    $errors['file'] = 'The type of file is invalid';
                } elseif ($file->getSize() > Model::uploadSize()) {
                    $errors['file'] = 'The file size larger than the limit';
                } else {
                    $upload = $file;
                }
            } elseif ($file && $file->hasError()) {
                $errors['file'] = $file->getErrorMessage();
            } elseif ($index->id == 0) {
                // หนังสือใหม่ต้องมีไฟล์
                $errors['file'] = 'Please browse file';
            }
            if ($upload) {
                $save['ext'] = strtolower($upload->getClientFileExt());
                $name = preg_replace('/\.'.preg_quote($save['ext'], '/').'$/i', '', $upload->getClientFilename());
                if ($save['topic'] === '') {
                    // ไม่กรอกชื่อเรื่อง ใช้ชื่อไฟล์ที่อัปโหลด
                    $save['topic'] = Text::topic($name);
                }
            }
            if ($save['topic'] === '') {
                $errors['topic'] = 'Please fill in';
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }
            if ($upload) {
                $dir = Model::dir();
                if (!File::makeDirectory($dir)) {
                    return $this->formErrorResponse(['file' => Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'edocument/')], 400);
                }
                $mktime = time();
                $save['file'] = $mktime.'.'.$save['ext'];
                while (file_exists($dir.$save['file'])) {
                    ++$mktime;
                    $save['file'] = $mktime.'.'.$save['ext'];
                }
                try {
                    $save['size'] = $upload->getSize();
                    $upload->moveTo($dir.$save['file']);
                } catch (\Exception $exc) {
                    return $this->formErrorResponse(['file' => Language::get($exc->getMessage())], 400);
                }
                if (!empty($index->file) && $index->file !== $save['file'] && is_file($dir.$index->file)) {
                    @unlink($dir.$index->file);
                }
            }
            if ($save['document_no'] === '') {
                // ไม่กรอกเลขที่ ออกเลขอัตโนมัติตามรูปแบบในตั้งค่า
                $save['document_no'] = \Index\Number\Model::get(0, (string) self::$cfg->edocument_format_no, 'edocument', 'document_no', (string) self::$cfg->edocument_prefix);
            }
            // ชื่อเรื่องใช้เป็นชื่อไฟล์ตอนดาวน์โหลด ตัดอักขระที่ใช้ไม่ได้ เหมือนระบบเดิม
            $save['topic'] = preg_replace('/[,;:_]{1,}/', '_', $save['topic']);
            $save['receiver'] = ','.implode(',', array_values($receiver)).',';
            $save['last_update'] = time();
            if ($index->id == 0) {
                $save['sender_id'] = (int) $login->id;
                $save['ip'] = $request->getClientIp();
                $id = (int) $db->insert('edocument', $save);
            } else {
                $id = (int) $index->id;
                $db->update('edocument', [['id', $id]], $save);
            }
            \Index\Log\Model::add($id, 'edocument', 'Save', '{LNG_E-Document} '.$save['document_no'], $login->id);

            $message = 'Saved successfully';
            if ($request->post('send_mail')->toBoolean()) {
                $error = Model::notify(array_values($receiver), $login->id);
                $message = $error === '' ? 'Save and email completed' : $error;
            }

            return $this->redirectResponse('/edocument-sent', $message, 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
