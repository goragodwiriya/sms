<?php
/**
 * @filesource modules/personnel/controllers/import.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Import;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\UploadedFile;
use Kotchasan\Language;

/**
 * api/personnel/import/get|save|sample
 * นำเข้ารายชื่อบุคลากรจาก CSV — เดิมคือ module=personnel-import
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * ข้อมูลประกอบหน้านำเข้า (ลิงก์หมวดหมู่ รหัสอักขระ ขนาดไฟล์)
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
            if (!ApiController::hasPermission($login, 'can_manage_personnel')) {
                return $this->errorResponse('Permission required', 403);
            }
            $categories = [];
            foreach (\Personnel\Category\Model::items() as $type => $label) {
                $categories[] = ['url' => '/personnel-categories?type='.$type, 'text' => $label];
            }

            return $this->successResponse([
                'data' => [
                    'categories' => $categories,
                    'encoding' => Language::get('CSV_ENCODING', '', self::$cfg->csv_language),
                    'max_size' => UploadedFile::getUploadSize()
                ]
            ], 'OK');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ไฟล์ตัวอย่าง personnel.csv
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
            if (!ApiController::hasPermission($login, 'can_manage_personnel')) {
                return $this->errorResponse('Permission required', 403);
            }
            \Kotchasan\Csv::send('personnel', Model::header(), Model::sample(), self::$cfg->csv_language);
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
            if ((int) $login->active !== 1 || !ApiController::canModify($login, ['can_manage_personnel'])) {
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
            try {
                $result = Model::import($file->getTempFileName(), self::$cfg->csv_language);
            } catch (\Throwable $th) {
                return $this->formErrorResponse(['import' => $th->getMessage()], 400);
            }
            $message = Language::replace('Successfully imported :count items', [':count' => $result->row]);
            if ($result->phoneSkipped > 0) {
                $message .= ' ('.Language::replace(':count items did not keep the phone number because it is already used by another member', [':count' => $result->phoneSkipped]).')';
            }
            \Index\Log\Model::add(0, 'personnel', 'Import', $message, $login->id);

            return $this->redirectResponse('/personnel-setup', $message, 200, 2000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
