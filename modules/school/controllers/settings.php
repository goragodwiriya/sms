<?php
/**
 * @filesource modules/school/controllers/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Settings;

use Gcms\Api as ApiController;
use Gcms\Config;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * api/school/settings/get|save
 * ตั้งค่าโมดูลโรงเรียน — เดิมคือ module=school-settings
 * ข้อมูลโรงเรียน (ใช้ในแบบรายงานประจำตัวนักเรียน) ขนาดรูปนักเรียน รหัสอักขระ CSV
 * สถานะสมาชิกของครู/นักเรียน และปีการศึกษา/ภาคเรียนปัจจุบัน
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
            if (!ApiController::hasPermission($login, 'can_config')) {
                return $this->errorResponse('Permission required', 403);
            }
            $cfg = self::$cfg;
            // สถานะผู้ดูแลระบบ (1) ไม่ใช่ตัวเลือกของครูหรือนักเรียน เหมือนระบบเดิม
            $statuses = $cfg->member_status;
            unset($statuses[1]);
            $country = empty($cfg->country) ? 'TH' : $cfg->country;

            return $this->successResponse([
                'data' => [
                    'school_name' => (string) ($cfg->school_name ?? ''),
                    'phone' => (string) ($cfg->phone ?? ''),
                    'fax' => (string) ($cfg->fax ?? ''),
                    'address' => (string) ($cfg->address ?? ''),
                    'province' => (string) ($cfg->province ?? ''),
                    'provinceID' => (string) ($cfg->provinceID ?? ''),
                    'zipcode' => (string) ($cfg->zipcode ?? ''),
                    'country' => $country,
                    'student_w' => (int) $cfg->student_w,
                    'student_h' => (int) $cfg->student_h,
                    'csv_language' => (string) $cfg->csv_language,
                    'teacher_status' => (string) (int) $cfg->teacher_status,
                    'student_status' => (string) (int) $cfg->student_status,
                    'academic_year' => (int) $cfg->academic_year,
                    'term' => (string) (int) $cfg->term
                ],
                'options' => [
                    'provinceID' => \Kotchasan\Province::getOptions(in_array($country, \Kotchasan\Province::countries(), true) ? $country : 'TH'),
                    'country' => \Gcms\Controller::arrayToOptions(\Kotchasan\Country::all()),
                    'csv_language' => \Gcms\Controller::arrayToOptions(Language::get('CSV_ENCODING', [])),
                    'teacher_status' => \Gcms\Controller::arrayToOptions($statuses),
                    'student_status' => \Gcms\Controller::arrayToOptions($statuses),
                    'term' => \School\Category\Model::init()->toOptions('term')
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
            if ((int) $login->active !== 1 || !ApiController::canModify($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }
            $config = Config::load(ROOT_PATH.'settings/config.php');
            $config->school_name = $request->post('school_name')->topic();
            $config->phone = $request->post('phone')->topic();
            $config->fax = $request->post('fax')->topic();
            $config->address = $request->post('address')->topic();
            $config->provinceID = $request->post('provinceID')->number();
            $config->province = $request->post('province')->topic();
            $config->zipcode = $request->post('zipcode')->number();
            $config->country = $request->post('country')->filter('A-Z');
            $config->student_w = max(100, $request->post('student_w')->toInt());
            $config->student_h = max(100, $request->post('student_h')->toInt());
            $config->teacher_status = $request->post('teacher_status')->toInt();
            $config->student_status = $request->post('student_status')->toInt();
            $config->academic_year = $request->post('academic_year')->toInt();
            $config->term = $request->post('term')->toInt();
            $csv = $request->post('csv_language')->filter('A-Z0-9\-');
            $config->csv_language = array_key_exists($csv, (array) Language::get('CSV_ENCODING', [])) ? $csv : 'UTF-8';
            if (!Config::save($config, ROOT_PATH.'settings/config.php')) {
                return $this->errorResponse(Language::replace('File %s cannot be created or is read-only.', 'settings/config.php'), 500);
            }
            \Index\Log\Model::add(0, 'school', 'Save', '{LNG_Module Settings} {LNG_School}', $login->id);

            return $this->redirectResponse('reload', 'Saved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
