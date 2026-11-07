<?php
/**
 * @filesource modules/edocument/controllers/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Settings;

use Gcms\Api as ApiController;
use Gcms\Config;
use Kotchasan\Http\Request;
use Kotchasan\Http\UploadedFile;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * api/edocument/settings — ตั้งค่าโมดูลหนังสือเวียน (เหมือนหน้า edocument-settings ของระบบเดิม)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * ค่าตั้งต้น (ชุดเดียวกับ modules/edocument/install/upgrade.php)
     */
    const DEFAULTS = [
        'edocument_prefix' => '',
        'edocument_format_no' => '%04d',
        'edocument_send_mail' => 1,
        'edocument_file_typies' => ['doc', 'ppt', 'pptx', 'docx', 'rar', 'zip', 'jpg', 'pdf'],
        'edocument_upload_size' => 2097152,
        'edocument_download_action' => 0
    ];

    /**
     * ค่าที่ตั้งไว้ ถ้ายังไม่เคยตั้งใช้ค่าตั้งต้น
     *
     * @param string $key
     *
     * @return mixed
     */
    public static function value($key)
    {
        return isset(self::$cfg->$key) ? self::$cfg->$key : self::DEFAULTS[$key];
    }

    /**
     * อ่านค่าตั้งค่า
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
            if (!ApiController::hasPermission($login, 'can_config')) {
                return $this->errorResponse('Permission required', 403);
            }
            $uploadMax = UploadedFile::getUploadSize(true);

            return $this->successResponse([
                'data' => (object) [
                    'edocument_prefix' => (string) self::value('edocument_prefix'),
                    'edocument_format_no' => (string) self::value('edocument_format_no'),
                    'edocument_send_mail' => !empty(self::value('edocument_send_mail')),
                    'edocument_file_typies' => implode(',', (array) self::value('edocument_file_typies')),
                    'edocument_upload_size' => (int) self::value('edocument_upload_size'),
                    'edocument_download_action' => (int) self::value('edocument_download_action'),
                    'upload_size_comment' => str_replace(
                        ':upload_max_filesize',
                        Text::formatFileSize($uploadMax),
                        Language::get('The size of the files can be uploaded. (Should not exceed the value of the Server :upload_max_filesize.)')
                    )
                ],
                'options' => (object) [
                    'edocument_upload_size' => self::sizeOptions($uploadMax, (int) self::value('edocument_upload_size')),
                    'edocument_download_action' => \Gcms\Controller::arrayToOptions(Language::get('DOWNLOAD_ACTIONS', []))
                ]
            ], 'OK');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * บันทึกค่าตั้งค่า
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
            if ((int) $login->active !== 1 || !ApiController::canModify($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }
            // ชนิดไฟล์: อังกฤษพิมพ์เล็กและตัวเลข 2-4 ตัว (คอลัมน์ ext เก็บได้ 4 ตัว) เหมือนระบบเดิม
            $typies = [];
            $errors = [];
            foreach (explode(',', strtolower($request->post('edocument_file_typies')->filter('a-zA-Z0-9,'))) as $typ) {
                if ($typ === '') {
                    continue;
                }
                if (strlen($typ) < 2 || strlen($typ) > 4) {
                    $errors['edocument_file_typies'] = 'Invalid data';
                }
                $typies[$typ] = $typ;
            }
            if (empty($typies)) {
                $errors['edocument_file_typies'] = 'Please fill in';
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }
            $config = Config::load(ROOT_PATH.'settings/config.php');
            $config->edocument_prefix = $request->post('edocument_prefix')->topic();
            $config->edocument_format_no = $request->post('edocument_format_no')->topic();
            $config->edocument_send_mail = $request->post('edocument_send_mail')->toBoolean() ? 1 : 0;
            $config->edocument_file_typies = array_keys($typies);
            $config->edocument_upload_size = max(0, $request->post('edocument_upload_size')->toInt());
            $config->edocument_download_action = $request->post('edocument_download_action')->toInt() === 1 ? 1 : 0;
            if (!Config::save($config, ROOT_PATH.'settings/config.php')) {
                return $this->errorResponse(Language::replace('File %s cannot be created or is read-only.', 'settings/config.php'), 500);
            }
            \Index\Log\Model::add(0, 'edocument', 'Save', '{LNG_Module Settings} {LNG_E-Document}', $login->id);

            return $this->redirectResponse('reload', 'Saved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ตัวเลือกขนาดไฟล์อัปโหลด ไม่เกินที่เซิร์ฟเวอร์รับได้ (เหมือนระบบเดิม)
     *
     * @param int $uploadMax
     * @param int $current
     *
     * @return array
     */
    public static function sizeOptions($uploadMax, $current)
    {
        $sizes = [];
        foreach ([1, 2, 4, 6, 8, 16, 32, 64, 128, 256, 512, 1024, 2048] as $i) {
            $size = $i * 1048576;
            if ($size <= $uploadMax) {
                $sizes[$size] = Text::formatFileSize($size);
            }
        }
        if (!isset($sizes[$uploadMax])) {
            $sizes[$uploadMax] = Text::formatFileSize($uploadMax);
        }
        if ($current > 0 && !isset($sizes[$current])) {
            $sizes[$current] = Text::formatFileSize($current);
        }
        ksort($sizes);

        return \Gcms\Controller::arrayToOptions($sizes);
    }
}
