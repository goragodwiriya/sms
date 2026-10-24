<?php
/**
 * @filesource modules/edocument/controllers/view.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\View;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * api/edocument/view/download (POST) และ api/edocument/view/file (GET)
 *
 * ดาวน์โหลดหนังสือ = ลงชื่อรับหนังสือ (ผู้รับยืนยันก่อนดาวน์โหลด เหมือนระบบเดิม)
 *   download บันทึกการดาวน์โหลดของผู้รับ แล้วตอบลิงก์ไฟล์กลับไป
 *   file     ส่งไฟล์ ตรวจสิทธิ์ทุกครั้ง (ผู้รับ ผู้ส่ง ผู้จัดการ) ลิงก์ที่หลุดไปจึงใช้กับคนอื่นไม่ได้
 * ตั้งค่า "เมื่อคลิกดาวน์โหลด" = เปิดไฟล์ จะเปิด pdf/รูปในเบราว์เซอร์แทนการดาวน์โหลด
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * ชนิดไฟล์ที่เปิดในเบราว์เซอร์ได้ (ระบบเดิม)
     */
    const INLINE = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function download(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            $document = \Edocument\Document\Model::get($request->post('id')->toInt());
            if (!$document || !\Edocument\Document\Model::canRead($login, $document)) {
                return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
            }
            if ($document->file === '' || !is_file(\Edocument\Document\Model::dir().$document->file)) {
                return $this->errorResponse('File not found', 404);
            }
            // นับเฉพาะผู้รับ (ผู้ส่ง/ผู้จัดการดาวน์โหลดจากหนังสือส่งไม่นับเป็นการรับ เหมือนระบบเดิม)
            if (in_array((int) $login->status, $document->receiver, true) && ApiController::isNotDemoMode($login)) {
                \Edocument\Document\Model::recordDownload($document->id, $login->id);
            }
            $url = WEB_URL.'api/edocument/view/file?id='.(int) $document->id;
            if (self::opensInline($document)) {
                $action = ['type' => 'redirect', 'url' => $url, 'target' => '_blank'];
            } else {
                $action = ['type' => 'download', 'url' => $url, 'filename' => self::filename($document)];
            }

            return $this->successResponse([
                'actions' => [
                    $action,
                    ['type' => 'modal', 'action' => 'close'],
                    ['type' => 'redirect', 'url' => 'reload', 'target' => 'edocumentInbox']
                ]
            ], 'OK');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ส่งไฟล์
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response|void
     */
    public function file(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            $document = \Edocument\Document\Model::get($request->get('id')->toInt());
            if (!$document || !\Edocument\Document\Model::canRead($login, $document)) {
                return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
            }
            $file = \Edocument\Document\Model::dir().$document->file;
            if ($document->file === '' || !is_file($file)) {
                return $this->errorResponse('File not found', 404);
            }
            while (ob_get_level()) {
                ob_end_clean();
            }
            if (self::opensInline($document)) {
                header('Content-Type: '.\Kotchasan\Mime::get($document->ext));
                header('Content-Disposition: inline');
            } else {
                $name = self::filename($document);
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.str_replace(['"', "\r", "\n"], '', $name).'"; filename*=UTF-8\'\''.rawurlencode($name));
            }
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: private, must-revalidate');
            header('Pragma: public');
            header('Content-Length: '.filesize($file));
            readfile($file);
            exit;
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * เปิดไฟล์ในเบราว์เซอร์หรือไม่ (ตั้งค่า + ชนิดไฟล์)
     *
     * @param object $document
     *
     * @return bool
     */
    private static function opensInline($document)
    {
        return (int) self::$cfg->edocument_download_action === 1 && in_array(strtolower($document->ext), self::INLINE, true);
    }

    /**
     * ชื่อไฟล์ตอนดาวน์โหลด = ชื่อเรื่อง.นามสกุล
     *
     * @param object $document
     *
     * @return string
     */
    private static function filename($document)
    {
        return $document->topic.'.'.$document->ext;
    }
}
