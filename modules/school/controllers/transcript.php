<?php
/**
 * @filesource modules/school/controllers/transcript.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace School\Transcript;

use Gcms\Api as ApiController;
use Kotchasan\Currency;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Number;

/**
 * api/school/transcript?id=&year=&term=
 * รายงานผลการเรียนของนักเรียนรายภาคเรียน — เดิมคือ module=school-grade
 * ดาวน์โหลด CSV (export?type=csv) และพิมพ์แบบรายงานประจำตัวนักเรียน (export?type=print)
 *
 * เปิดได้: ครู ผู้จัดการนักเรียน/รายวิชา ผู้ให้คะแนน และนักเรียนเจ้าของผลการเรียนเอง
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Table
{
    /**
     * @var array
     */
    protected $allowedSortColumns = ['type', 'course_code', 'course_name'];

    /**
     * @var object|null
     */
    protected $student;

    /**
     * @param Request $request
     * @param object $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        $id = $request->get('id')->toInt();
        $this->student = $id > 0 ? \School\Student\Model::get($id) : null;
        if ($this->student && self::canView($login, $this->student)) {
            return true;
        }

        return $this->errorResponse('You are not enrolled Please contact your teacher', 403);
    }

    /**
     * ครูและผู้จัดการดูได้ทุกคน นักเรียนดูได้เฉพาะของตัวเอง
     *
     * @param object $login
     * @param object $student
     *
     * @return bool
     */
    public static function canView($login, $student)
    {
        if (ApiController::hasPermission($login, \School\Students\Controller::VIEW_PERMISSIONS)) {
            return true;
        }

        return (int) $login->id === (int) $student->id;
    }

    /**
     * ปีการศึกษาและภาคเรียนเริ่มต้นเป็นของปัจจุบัน
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'id' => $request->get('id')->toInt(),
            'year' => $request->get('year', (string) self::$cfg->academic_year)->toInt(),
            'term' => $request->get('term', (string) self::$cfg->term)->toInt()
        ];
    }

    /**
     * @param array $params
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable(array $params, $login)
    {
        return \Kotchasan\Model::createQuery()
            ->select('G.id', 'C.course_code', 'C.course_name', 'C.type', 'C.credit', 'G.midterm', 'G.final', 'G.grade')
            ->from('grade G')
            ->join('course C', [['C.id', 'G.course_id']], 'INNER')
            ->where([
                ['G.student_id', (int) $params['id']],
                ['C.year', (int) $params['year']],
                ['C.term', (int) $params['term']]
            ]);
    }

    /**
     * จัดรูปแบบแถว แล้วต่อท้ายด้วยแถวสรุปหน่วยกิตและเกรดเฉลี่ย (เหมือน footer ของระบบเดิม)
     *
     * @param array $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $summary = self::summarize($datas);
        $datas[] = (object) [
            'id' => 0,
            'course_code' => '',
            'course_name' => '',
            'type' => Language::get('Academic results'),
            'credit' => $summary['credit'],
            'midterm' => '',
            'final' => '',
            'grade' => $summary['gpa'],
            'is_summary' => 1
        ];

        return $datas;
    }

    /**
     * แปลงแถวให้พร้อมแสดงผล และคำนวณหน่วยกิตรวม/เกรดเฉลี่ย
     *
     * @param array $datas แถวผลการเรียน (แก้ในที่)
     *
     * @return array ['credit' => หน่วยกิตรวม, 'gpa' => เกรดเฉลี่ย 2 ตำแหน่ง]
     */
    public static function summarize(array &$datas)
    {
        $types = Language::get('COURSE_TYPIES', []);
        $credit = 0;
        $total = 0;
        foreach ($datas as $item) {
            if (empty((float) $item->credit)) {
                $item->credit = '';
            } else {
                $credit += (float) $item->credit;
                // เกรดที่ไม่ใช่ตัวเลข (ร. มส. ฯลฯ) นับหน่วยกิตแต่ได้ 0 คะแนน เหมือนระบบเดิม
                $total += (is_numeric($item->grade) ? (float) $item->grade : 0) * (float) $item->credit;
            }
            $item->type = isset($types[$item->type]) ? $types[$item->type] : '';
            $item->midterm = $item->midterm === null ? '' : $item->midterm;
            $item->final = $item->final === null ? '' : $item->final;
            $item->grade = (string) $item->grade;
            $item->is_summary = 0;
        }

        return [
            'credit' => $credit == (int) $credit ? (int) $credit : $credit,
            'gpa' => Currency::format(Number::division($total, $credit), 2, ',', false)
        ];
    }

    /**
     * ตัวเลือกปีการศึกษา (ปีที่นักเรียนมีผลการเรียน) และภาคเรียน
     *
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getFilters(array $params, $login)
    {
        $years = [];
        foreach (\School\Student\Model::academicYears($params['id']) as $year) {
            $years[] = ['value' => (string) $year, 'text' => (string) $year];
        }

        return [
            'year' => $years,
            'term' => \School\Category\Model::init()->toOptions('term')
        ];
    }

    /**
     * ข้อมูลหัวของหน้า (ชื่อนักเรียน) และค่าเริ่มต้นของตัวกรอง
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function info(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            $auth = $this->checkAuthorization($request, $login);
            if ($auth !== true) {
                return $auth;
            }

            return $this->successResponse([
                'id' => (int) $this->student->id,
                'name' => $this->student->name,
                'student_id' => (string) $this->student->student_id,
                'title' => Language::get('Grade Report').' '.$this->student->name
            ], 'OK');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * แถวผลการเรียนสำหรับดาวน์โหลด/พิมพ์
     *
     * @param array $params
     *
     * @return array [$rows, $summary]
     */
    private function exportRows(array $params)
    {
        $query = $this->toDataTable($params, null)
            ->orderBy('C.type')
            ->orderBy('C.course_code');
        $datas = $query->fetchAll();
        $summary = self::summarize($datas);

        return [$datas, $summary];
    }

    /**
     * หัวตารางของรายงาน
     *
     * @return array
     */
    private static function header()
    {
        return [
            Language::get('Course Code'),
            Language::get('Course Name'),
            Language::get('Type'),
            Language::get('Credit'),
            Language::get('Midterm'),
            Language::get('Final'),
            Language::get('Grade')
        ];
    }

    /**
     * ดาวน์โหลดผลการเรียน (CSV) รูปแบบเดียวกับระบบเดิม
     *
     * @param Request $request
     * @param object $login
     *
     * @return void
     */
    protected function handleCsvExport(Request $request, $login)
    {
        $params = $this->parseParams($request, $login);
        list($datas, $summary) = $this->exportRows($params);
        $rows = [
            [Language::get('Full Name'), $this->student->name],
            [Language::get('Student ID'), $this->student->student_id],
            [Language::get('Academic year'), $params['year'].'/'.$params['term']],
            self::header()
        ];
        foreach ($datas as $item) {
            $rows[] = [$item->course_code, $item->course_name, $item->type, $item->credit, $item->midterm, $item->final, $item->grade];
        }
        $rows[] = [Language::get('Credits in this semester'), $summary['credit']];
        $rows[] = [Language::get('Grades in this semester'), $summary['gpa']];
        $file = implode('_', [$this->student->student_id, $this->student->name, $params['year'], $params['term']]);
        \Kotchasan\Csv::send($file, [], $rows, self::$cfg->csv_language);
    }

    /**
     * พิมพ์แบบรายงานประจำตัวนักเรียน
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handlePrintExport(Request $request, $login)
    {
        $params = $this->parseParams($request, $login);
        list($datas, $summary) = $this->exportRows($params);
        $esc = fn($text) => htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
        $thead = '';
        foreach (self::header() as $label) {
            $thead .= '<th>'.$esc($label).'</th>';
        }
        $tbody = '';
        foreach ($datas as $item) {
            $tbody .= '<tr>';
            foreach ([$item->course_code, $item->course_name, $item->type, $item->credit, $item->midterm, $item->final, $item->grade] as $k => $value) {
                $tbody .= '<td'.($k == 1 ? '' : ' class="center"').'>'.$esc($value).'</td>';
            }
            $tbody .= '</tr>';
        }
        $category = \School\Category\Model::init();
        $content = strtr(file_get_contents(ROOT_PATH.'modules/school/views/transcript.html'), [
            '%CREDITS%' => $esc($summary['credit']),
            '%GRADES%' => $esc($summary['gpa']),
            '%STUDENT%' => $esc($this->student->student_id),
            '%NAME%' => $esc($this->student->name),
            '%NUMBER%' => $esc($this->student->number),
            '%DEPARTMENT%' => $esc($category->get('department', $this->student->department)),
            '%CLASS%' => $esc($category->get('class', $this->student->class)),
            '%ROOM%' => $esc($category->get('room', $this->student->room)),
            '%YEAR%' => $esc($params['year']),
            '%TERM%' => $esc($params['term']),
            '%SCHOOLNAME%' => $esc(self::$cfg->school_name),
            '%SCHOOLPROVINCE%' => $esc(\Kotchasan\Province::get((string) self::$cfg->provinceID, '', empty(self::$cfg->country) ? 'TH' : self::$cfg->country)),
            '%THEAD%' => $thead,
            '%TBODY%' => $tbody
        ]);

        return \Export\Export\Controller::printHtml(Language::get('Student Identification Form'), $content, [
            'stylesheets' => ['modules/school/views/print.css'],
            'body_class' => 'school-transcript'
        ]);
    }
}
