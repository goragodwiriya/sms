<?php
/**
 * @filesource modules/school/controllers/gradesettings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Gradesettings;

use Gcms\Api as ApiController;
use Gcms\Config;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * api/school/gradesettings/get|save
 * เกณฑ์การคำนวณเกรด — เดิมคือ module=school-gradesettings
 * ลบออกทุกแถว = กรอกเกรดเอง (ไม่มีช่องคะแนนกลางภาค/ปลายภาค)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * เกณฑ์มาตรฐาน (ปุ่ม "ใช้ค่าเริ่มต้น")
     */
    const DEFAULTS = [49 => '0', 54 => '1', 59 => '1.5', 64 => '2', 69 => '2.5', 74 => '3', 79 => '3.5', 100 => '4'];

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
            $scores = $request->get('default')->toBoolean() ? self::DEFAULTS : \School\Score\Model::scores();
            $rows = [];
            foreach ($scores as $score => $grade) {
                $rows[] = ['score' => (string) $score, 'grade' => (string) $grade];
            }
            if (empty($rows)) {
                $rows[] = ['score' => '', 'grade' => ''];
            }

            return $this->successResponse([
                'data' => [
                    'options' => [
                        'columns' => [
                            ['field' => 'score', 'label' => Language::get('Score less than or equal to'), 'cellElement' => 'number', 'min' => 0, 'max' => 1000, 'size' => 8],
                            ['field' => 'grade', 'label' => Language::get('Grade'), 'cellElement' => 'text', 'size' => 8, 'maxLength' => 10]
                        ],
                        'data' => $rows
                    ]
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
            $scores = $request->post('score', [])->toInt();
            $grades = $request->post('grade', [])->topic();
            $result = [];
            foreach ((array) $scores as $key => $score) {
                $grade = is_array($grades) && isset($grades[$key]) ? trim((string) $grades[$key]) : '';
                if ($score > 0 && $grade !== '') {
                    $result[$score] = $grade;
                }
            }
            ksort($result, SORT_NUMERIC);
            $config = Config::load(ROOT_PATH.'settings/config.php');
            $config->school_grade_caculations = $result;
            if (!Config::save($config, ROOT_PATH.'settings/config.php')) {
                return $this->errorResponse(Language::replace('File %s cannot be created or is read-only.', 'settings/config.php'), 500);
            }
            \Index\Log\Model::add(0, 'school', 'Save', '{LNG_Grade calculation}', $login->id);

            return $this->redirectResponse('/school-gradesettings', 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
