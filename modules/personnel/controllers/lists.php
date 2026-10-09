<?php
/**
 * @filesource modules/personnel/controllers/lists.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Lists;

use Kotchasan\Http\Request;

/**
 * api/personnel/lists
 * รายชื่อบุคลากรปัจจุบันสำหรับสมาชิกทุกคน — เดิมคือ module=personnel-setup ของผู้ที่ไม่มีสิทธิ์จัดการ
 * ดูรายละเอียดได้ทุกคน แก้ไขได้เฉพาะข้อมูลของตัวเอง
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Personnel\Setup\Controller
{
    /**
     * @var array
     */
    protected $allowedSortColumns = ['name', 'order', 'position', 'department'];

    /**
     * สมาชิกทุกคนที่เข้าระบบ
     *
     * @param Request $request
     * @param object $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        return $login ? true : $this->errorResponse('Unauthorized', 401);
    }

    /**
     * เฉพาะบุคลากรปัจจุบัน
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        $params = parent::getCustomParams($request, $login);
        $params['active'] = 1;

        return $params;
    }

    /**
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getFilters(array $params, $login)
    {
        $filters = parent::getFilters($params, $login);
        unset($filters['active']);

        return $filters;
    }

    /**
     * หน้ารายชื่อไม่มีปุ่มดาวน์โหลด (ระบบเดิมให้เฉพาะหน้าจัดการ)
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleCsvExport(Request $request, $login)
    {
        return $this->errorResponse('Permission required', 403);
    }
}
