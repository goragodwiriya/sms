<?php
/**
 * install/sample.php — ตัวสร้างข้อมูลตัวอย่างของ SMS (ดูคำอธิบายที่ install/cli-sample.php)
 *
 * ไฟล์นี้ถูก include จากสองที่: install/cli-sample.php (บรรทัดคำสั่ง) และ install/step4.php (ตอนติดตั้ง)
 * ผู้เรียกต้องเตรียมให้ก่อน: เฟรมเวิร์กโหลดแล้ว (Kotchasan::createWebApplication), ROOT_PATH,
 * $root (ที่อยู่โปรเจ็คไม่มี / ท้าย), $database (ค่าจาก settings/database.php) และ
 * $options = ['settings' => bool, 'remove' => bool]
 * ไม่ใช้ exit — จบงานด้วย return เพื่อให้ตัวติดตั้งทำงานต่อได้
 */
if (!defined('ROOT_PATH') || !isset($options, $root, $database)) {
    exit;
}

use Kotchasan\Language;
use Personnel\Account\Model as Account;
use School\Score\Model as Score;

const MARK = '[ตัวอย่าง]';
const ID_PREFIX = '99';
const LEVELS = 6;
const ROOMS_PER_CLASS = 2;
const STUDENTS_PER_ROOM = 15;

$started = microtime(true);
$db = \Kotchasan\DB::create();
$cfg = \Gcms\Config::create();
echo 'ฐาน '.$database['mysql']['dbname'].' (prefix '.$database['mysql']['prefix'].")\n";
// ค่าคงที่ของชุดตัวอย่าง (mt_srand ให้ผลเหมือนเดิมทุกครั้ง)
mt_srand(20261004);
$now = time();

// -----------------------------------------------------------------------------
// ลบของตัวอย่างเดิม
// -----------------------------------------------------------------------------
/**
 * id ของแถวที่เลขประชาชนเป็นของตัวอย่าง
 */
function sampleIds(string $table): array
{
    $ids = [];
    foreach (\Kotchasan\DB::create()->select($table, [['id_card', 'LIKE', ID_PREFIX.'%']], [], ['id']) as $row) {
        if ((int) $row->id > 1) {
            $ids[] = (int) $row->id;
        }
    }

    return $ids;
}
$personnelIds = sampleIds('personnel');
$studentIds = sampleIds('student');
$userIds = array_merge($personnelIds, $studentIds);
// บัญชีที่ค้างอยู่โดยไม่มีแถวบุคลากร/นักเรียน (รันครั้งก่อนหยุดกลางทาง)
foreach ($db->select('user', [['username', 'LIKE', ID_PREFIX.'%']], [], ['id', 'username']) as $row) {
    if ((int) $row->id > 1 && preg_match('/^'.ID_PREFIX.'[0-9]{11}$/', (string) $row->username)) {
        $userIds[] = (int) $row->id;
    }
}
$userIds = array_values(array_unique($userIds));
$courseIds = empty($personnelIds) ? [] : array_map('intval', array_column($db->select('course', [['teacher_id', $personnelIds]], [], ['id']), 'id'));
$removed = ['user' => count($userIds), 'course' => count($courseIds), 'edocument' => 0];
if (!empty($courseIds)) {
    $db->delete('grade', [['course_id', $courseIds]], 0);
    $db->delete('course', [['id', $courseIds]], 0);
}
if (!empty($studentIds)) {
    $db->delete('grade', [['student_id', $studentIds]], 0);
}
$documents = $db->select('edocument', [['detail', 'LIKE', '%'.MARK.'%']], [], ['id', 'file']);
if (!empty($documents)) {
    $dir = \Edocument\Document\Model::dir();
    foreach ($documents as $row) {
        if ($row->file !== '' && is_file($dir.$row->file)) {
            @unlink($dir.$row->file);
        }
    }
    $ids = array_map('intval', array_column($documents, 'id'));
    $db->delete('edocument_download', [['document_id', $ids]], 0);
    $db->delete('edocument', [['id', $ids]], 0);
    $removed['edocument'] = count($ids);
}
if (!empty($userIds)) {
    foreach (['edocument_download', 'logs', 'user_session', 'user_meta'] as $table) {
        $db->delete($table, [['member_id', $userIds]], 0);
    }
    $db->delete('personnel', [['id', $userIds]], 0);
    $db->delete('student', [['id', $userIds]], 0);
    $db->delete('user', [['id', $userIds]], 0);
}
$db->delete('logs', [['topic', 'LIKE', '%'.MARK.'%']], 0);
echo 'ลบของตัวอย่างเดิม: สมาชิก '.$removed['user'].' · รายวิชา '.$removed['course'].' · หนังสือเวียน '.$removed['edocument']."\n";
if ($options['remove'] === true) {
    return;
}

// -----------------------------------------------------------------------------
// ข้อมูลโรงเรียน (ทางเลือก)
// -----------------------------------------------------------------------------
if ($options['settings'] === true) {
    $file = $root.'/settings/config.php';
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($file, true);
    }
    $loaded = is_file($file) ? include $file : null;
    // ต้องได้ค่ากำหนดทั้งชุดกลับมาจริง ๆ ก่อนเขียนไฟล์ทับ — ถ้าได้อย่างอื่น (เช่น ค่า 1 จากแคชของไฟล์ที่เพิ่งถูกเขียน)
    // ห้ามบันทึก ไม่งั้นค่ากำหนดทั้งระบบหายเหลือแค่ข้อมูลโรงเรียน
    if (!is_array($loaded) || empty($loaded['version'])) {
        echo "ข้อมูลโรงเรียนใน settings/config.php: อ่านค่ากำหนดเดิมไม่ได้ จึงไม่แก้ไฟล์ (กรอกที่เมนู โรงเรียน → การตั้งค่า → ตั้งค่าโมดูล)\n";
    } else {
        $filled = [];
        foreach (include __DIR__.'/sample-school.php' as $key => $value) {
            if (empty($loaded[$key])) {
                $loaded[$key] = $value;
                $cfg->$key = $value;
                $filled[] = $key;
            }
        }
        if (!empty($filled) && !\Gcms\Config::save((object) $loaded, $file)) {
            echo "บันทึก settings/config.php ไม่ได้\n";
        } else {
            echo 'ข้อมูลโรงเรียนใน settings/config.php: '.(empty($filled) ? 'มีครบแล้ว ไม่แก้' : 'เติม '.implode(', ', $filled))."\n";
        }
    }
}

// -----------------------------------------------------------------------------
// ตัวช่วย
// -----------------------------------------------------------------------------
/**
 * หาหมวดหมู่จากชื่อ (ภาษาใดก็ได้) ถ้าไม่มีเพิ่มใหม่ให้ครบทุกภาษาที่หมวดหมู่ชนิดนี้ใช้อยู่ คืน category_id
 *
 * @param array $topics [language => topic]
 */
function ensureCategory(string $type, array $topics): int
{
    $db = \Kotchasan\DB::create();
    $languages = [];
    $max = 0;
    foreach ($db->select('category', [['type', $type]], [], ['category_id', 'language', 'topic']) as $row) {
        if (in_array($row->topic, $topics, true)) {
            return (int) $row->category_id;
        }
        $languages[$row->language] = true;
        $max = max($max, (int) $row->category_id);
    }
    $id = $max + 1;
    foreach (empty($languages) ? array_keys($topics) : array_keys($languages) as $language) {
        $db->insert('category', [
            'type' => $type,
            'category_id' => (string) $id,
            'language' => $language,
            'topic' => $topics[$language] ?? $topics['th'],
            'color' => null,
            'is_active' => 1
        ]);
    }

    return $id;
}
function pick(array $items)
{
    return $items[mt_rand(0, count($items) - 1)];
}
/**
 * สุ่มทศนิยม 0–1
 */
function rnd(): float
{
    return mt_rand() / mt_getrandmax();
}
/**
 * เลขประชาชนตัวอย่าง 13 หลัก (ขึ้นต้นด้วย ID_PREFIX หลักสุดท้ายเป็นเลขตรวจสอบที่ถูกต้อง)
 */
function sampleIdCard(array &$used): string
{
    do {
        $base = ID_PREFIX.sprintf('%05d%05d', mt_rand(0, 99999), mt_rand(0, 99999));
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $base[$i] * (13 - $i);
        }
        $idCard = $base.((11 - $sum % 11) % 10);
    } while (isset($used[$idCard]));
    $used[$idCard] = true;

    return $idCard;
}
/**
 * เบอร์มือถือที่ยังไม่มีใครใช้ (user.phone เป็น UNIQUE)
 */
function samplePhone(array &$used): string
{
    do {
        $phone = '0'.pick(['6', '8', '9']).sprintf('%08d', mt_rand(0, 99999999));
    } while (isset($used[$phone]));
    $used[$phone] = true;

    return $phone;
}
/**
 * วันที่สุ่มระหว่างสองวัน (Y-m-d)
 */
function randomDate(string $from, string $to): string
{
    return date('Y-m-d', mt_rand(strtotime($from), strtotime($to)));
}
/**
 * เวลาสุ่มในวันทำการ (เลี่ยงเสาร์-อาทิตย์) ของวันที่กำหนด ไม่เกินเวลาปัจจุบัน
 */
function workTime(string $date, int $fromHour = 8, int $toHour = 16): int
{
    $time = strtotime($date);
    while ((int) date('N', $time) >= 6) {
        $time -= 86400;
    }
    $time += mt_rand($fromHour * 3600, $toHour * 3600);

    return min($time, time() - 3600);
}

$maleNames = ['สมชาย', 'สมศักดิ์', 'ประเสริฐ', 'วิชัย', 'สุรชัย', 'ธนากร', 'กิตติพงษ์', 'ณัฐพล', 'ภูมิพัฒน์', 'ธีรภัทร', 'ปิยะพงษ์', 'อนุชา', 'วรวุฒิ', 'ศุภชัย', 'ชยพล', 'พีรพัฒน์', 'ธนภัทร', 'กฤษดา', 'เอกชัย', 'สุทธิพงษ์', 'นพดล', 'ปกรณ์', 'จิรายุ', 'ภาคิน', 'ปัณณวัฒน์', 'ธนวัฒน์', 'กันตพงศ์', 'ณัฐวุฒิ', 'ศิวกร', 'อภิสิทธิ์', 'รัชพล', 'ชนาธิป', 'พงศกร', 'วีรภัทร', 'ณภัทร', 'ปวริศ', 'ภานุวัฒน์', 'ธนดล', 'กษิดิ์เดช', 'ตะวัน'];
$femaleNames = ['สมหญิง', 'สุภาพร', 'วันดี', 'กาญจนา', 'ปิยะนุช', 'ศิริพร', 'จันทร์เพ็ญ', 'อรุณี', 'พรทิพย์', 'นภัสสร', 'ณัฐธิดา', 'กมลชนก', 'ชนิดา', 'พิมพ์ชนก', 'ธนพร', 'สุนิสา', 'วิภาวดี', 'อัญชลี', 'เบญจมาศ', 'รัตนา', 'ปวีณา', 'ศศิธร', 'ชุติมา', 'ภัทราภรณ์', 'กัญญาณัฐ', 'ณิชาภัทร', 'พัชราภา', 'ปุณยวีร์', 'ชญานิษฐ์', 'อริสา', 'ธัญชนก', 'กชกร', 'ปภาวรินทร์', 'วรรณิศา', 'ญาณิศา', 'ภัทรนันท์', 'สิรินทรา', 'มนัสนันท์', 'เกวลิน', 'ลลิตา'];
$surnames = ['ใจดี', 'ศรีสุข', 'มีสุข', 'ทองดี', 'แก้วมณี', 'สุขสวัสดิ์', 'รักษ์ไทย', 'บุญมา', 'ศรีวงศ์', 'พรหมมา', 'จันทร์แก้ว', 'วงศ์ใหญ่', 'สายทอง', 'ทองคำ', 'นาคประเสริฐ', 'เพชรรัตน์', 'สมบูรณ์ชัย', 'ประเสริฐศรี', 'อินทร์แก้ว', 'บุญเรือง', 'ชัยมงคล', 'ศักดิ์สิทธิ์', 'พงษ์พานิช', 'ธนสาร', 'กิจเจริญ', 'รุ่งเรืองศรี', 'แสงอรุณ', 'ดวงดี', 'มั่นคง', 'เจริญสุข', 'ทรัพย์มาก', 'วัฒนกุล', 'พูลสวัสดิ์', 'สิทธิโชค', 'เกตุแก้ว', 'บุญประเสริฐ', 'อ่อนศรี', 'คงสมบูรณ์', 'ศรีประเสริฐ', 'ปัญญาดี', 'หอมจันทร์', 'นิลสุวรรณ', 'เรืองศรี', 'สุวรรณรัตน์', 'ทองสุข', 'ภักดีวงศ์', 'ชูศรี', 'กาญจนวงศ์', 'ศรีทอง', 'มณีวงศ์'];
$tambons = ['พระปฐมเจดีย์', 'บ่อพลับ', 'สนามจันทร์', 'ห้วยจรเข้', 'ธรรมศาลา', 'ดอนยายหอม', 'สวนป่าน', 'ทัพหลวง', 'หนองปากโลง', 'ลำพยา', 'นครปฐม', 'บางแขม'];
$usedNames = [];
/**
 * ชื่อ-สกุลที่ไม่ซ้ำ คืน [ชื่อเต็มพร้อมคำนำหน้า, ชื่อ, สกุล]
 */
$personName = function (string $sex, string $title) use (&$usedNames, $maleNames, $femaleNames, $surnames): array {
    do {
        $first = pick($sex === 'm' ? $maleNames : $femaleNames);
        $last = pick($surnames);
    } while (isset($usedNames[$first.$last]));
    $usedNames[$first.$last] = true;

    return [$title.$first.' '.$last, $first, $last];
};
$address = function () use ($tambons): string {
    return mt_rand(1, 299).(mt_rand(0, 2) === 0 ? '/'.mt_rand(1, 40) : '').' หมู่ '.mt_rand(1, 12).' ต.'.pick($tambons).' อ.เมืองนครปฐม จ.นครปฐม 73000';
};
// ค่าที่ห้ามซ้ำกับของที่มีอยู่แล้วในฐาน
$usedIdCards = [];
foreach (['personnel', 'student'] as $table) {
    foreach ($db->select($table, [['id_card', '!=', '']], [], ['id_card']) as $row) {
        $usedIdCards[(string) $row->id_card] = true;
    }
}
foreach ($db->select('user', [['username', '!=', '']], [], ['username']) as $row) {
    $usedIdCards[(string) $row->username] = true;
}
$usedPhones = [];
foreach ($db->select('user', [['phone', '!=', '']], [], ['phone']) as $row) {
    $usedPhones[(string) $row->phone] = true;
}
$usedStudentIds = [];
foreach ($db->select('student', [], [], ['student_id']) as $row) {
    $usedStudentIds[(string) $row->student_id] = true;
}

// -----------------------------------------------------------------------------
// หมวดหมู่
// -----------------------------------------------------------------------------
// กลุ่มสาระ [ชื่อแผนก th, en, อักษรรหัสวิชา, ชื่อรายวิชาพื้นฐาน, ชั่วโมง ม.ต้น, ชั่วโมง ม.ปลาย]
$subjects = [
    ['วิทยาศาสตร์และเทคโนโลยี', 'Science and Technology', 'ว', 'วิทยาศาสตร์', 60, 40],
    ['คณิตศาสตร์', 'Mathematics', 'ค', 'คณิตศาสตร์', 60, 40],
    ['ภาษาไทย', 'Thai Language', 'ท', 'ภาษาไทย', 60, 40],
    ['สังคมศึกษาศาสนาและวัฒนธรรม', 'Social Studies, Religion and Culture', 'ส', 'สังคมศึกษา ศาสนาและวัฒนธรรม', 60, 40],
    ['สุขศึกษาและพลศึกษา', 'Health and Physical Education', 'พ', 'สุขศึกษาและพลศึกษา', 40, 20],
    ['การงานอาชีพ', 'Occupations', 'ง', 'การงานอาชีพ', 40, 20],
    ['ศิลปะ', 'Arts', 'ศ', 'ศิลปะ', 40, 20],
    ['ภาษาต่างประเทศ', 'Foreign Languages', 'อ', 'ภาษาอังกฤษ', 60, 40]
];
$deptIds = [];
foreach ($subjects as $d => $subject) {
    $deptIds[$d] = ensureCategory('department', ['th' => $subject[0], 'en' => $subject[1]]);
}
$classIds = [];
$roomIds = [];
for ($level = 1; $level <= LEVELS; $level++) {
    $classIds[$level] = ensureCategory('class', ['th' => 'มัธยมศึกษาปีที่ '.$level, 'en' => 'Class '.($level + 6)]);
    for ($n = 1; $n <= ROOMS_PER_CLASS; $n++) {
        $roomIds[$level][$n] = ensureCategory('room', ['th' => $level.'/'.$n, 'en' => $level.'/'.$n]);
    }
}
ensureCategory('term', ['th' => 'เทอม 1', 'en' => 'Term 1']);
ensureCategory('term', ['th' => 'เทอม 2', 'en' => 'Term 2']);
$positions = [
    'director' => ensureCategory('position', ['th' => 'ผู้อำนวยการโรงเรียน', 'en' => 'Director']),
    'vice' => ensureCategory('position', ['th' => 'รองผู้อำนวยการโรงเรียน', 'en' => 'Vice-Director']),
    'teacher' => ensureCategory('position', ['th' => 'ครู', 'en' => 'Teacher']),
    'assistant' => ensureCategory('position', ['th' => 'ครูผู้ช่วย', 'en' => 'Assistant Teacher']),
    'contract' => ensureCategory('position', ['th' => 'ครูอัตราจ้าง', 'en' => 'Contract Teacher']),
    'clerk' => ensureCategory('position', ['th' => 'เจ้าหน้าที่ธุรการ', 'en' => 'Administrative Officer'])
];
echo 'หมวดหมู่: กลุ่มสาระ '.count($deptIds).' · ชั้น '.count($classIds).' · ห้อง '.(LEVELS * ROOMS_PER_CLASS).' · ตำแหน่ง '.count($positions)."\n";

// -----------------------------------------------------------------------------
// บุคลากร
// -----------------------------------------------------------------------------
$academicYear = (int) $cfg->academic_year;
$currentTerm = max(1, min(2, (int) $cfg->term));
$teacherStatus = (int) $cfg->teacher_status;
$studentStatus = (int) $cfg->student_status;
$memberStatus = (array) $cfg->member_status;
$executiveStatus = isset($memberStatus[3]) && $teacherStatus !== 3 && $studentStatus !== 3 ? 3 : $teacherStatus;
$thisYear = (int) date('Y');
$plan = [
    ['role' => 'director', 'position' => 'director', 'dept' => -1, 'status' => $executiveStatus, 'age' => [52, 59], 'title' => 'ดร.',
        'permission' => ['can_manage_personnel', 'can_manage_student', 'can_manage_course', 'can_upload_edocument', 'can_handle_all_edocument', 'can_view_usage_history']],
    ['role' => 'vice_academic', 'position' => 'vice', 'dept' => -1, 'status' => $executiveStatus, 'age' => [45, 56],
        'permission' => ['can_manage_student', 'can_manage_course', 'can_upload_edocument']],
    ['role' => 'vice_personnel', 'position' => 'vice', 'dept' => -1, 'status' => $executiveStatus, 'age' => [45, 56],
        'permission' => ['can_manage_personnel', 'can_upload_edocument', 'can_handle_all_edocument']]
];
foreach ($subjects as $d => $subject) {
    for ($k = 0; $k < 3; $k++) {
        $permission = ['can_teacher', 'can_rate_student'];
        if ($k === 0) {
            // หัวหน้ากลุ่มสาระ ส่งหนังสือได้
            $permission[] = 'can_upload_edocument';
        }
        if ($d === 2 && $k === 1) {
            // งานทะเบียนและวัดผล
            $permission[] = 'can_manage_student';
            $permission[] = 'can_manage_course';
        }
        $plan[] = [
            'role' => 'teacher', 'slot' => $k, 'dept' => $d, 'status' => $teacherStatus, 'permission' => $permission,
            'position' => $k < 2 ? 'teacher' : ($d % 2 === 0 ? 'assistant' : 'contract'),
            'age' => $k === 0 ? [40, 58] : ($k === 1 ? [28, 50] : [23, 30])
        ];
    }
}
$plan[] = ['role' => 'clerk', 'position' => 'clerk', 'dept' => -1, 'status' => $teacherStatus, 'age' => [25, 45],
    'permission' => ['can_upload_edocument', 'can_handle_all_edocument']];
$plan[] = ['role' => 'former', 'position' => 'teacher', 'dept' => 4, 'status' => $teacherStatus, 'age' => [58, 62], 'active' => 0,
    'permission' => ['can_teacher', 'can_rate_student']];
$plan[] = ['role' => 'former', 'position' => 'teacher', 'dept' => 1, 'status' => $teacherStatus, 'age' => [30, 40], 'active' => 0,
    'permission' => ['can_teacher', 'can_rate_student']];

// ครูประจำชั้น: ครูของแต่ละกลุ่มสาระเรียงสลับกัน คนละห้อง
$homeroomSlots = [];
for ($level = 1; $level <= LEVELS; $level++) {
    for ($n = 1; $n <= ROOMS_PER_CLASS; $n++) {
        $homeroomSlots[] = [$level, $n];
    }
}
echo 'บุคลากร '.count($plan).' คน ';
$people = [];
$deptTeachers = [];
$homeroom = [];
$roles = [];
$teacherIndex = 0;
foreach ($plan as $order => $spec) {
    $sex = mt_rand(1, 100) <= 60 ? 'f' : 'm';
    [$minAge, $maxAge] = $spec['age'];
    $birthday = randomDate(($thisYear - $maxAge).'-01-01', ($thisYear - $minAge).'-12-31');
    $title = $spec['title'] ?? ($sex === 'm' ? 'นาย' : ($thisYear - (int) substr($birthday, 0, 4) > 30 && mt_rand(0, 1) ? 'นาง' : 'นางสาว'));
    [$name] = $personName($sex, $title);
    $idCard = sampleIdCard($usedIdCards);
    $active = $spec['active'] ?? 1;
    $homeroomOf = null;
    if ($spec['role'] === 'teacher' && $active === 1 && $teacherIndex % 2 === 0 && !empty($homeroomSlots)) {
        // ทุกคนที่สองของครูเป็นครูประจำชั้น (12 ห้อง จากครู 24 คน)
        $homeroomOf = array_shift($homeroomSlots);
    }
    $id = Account::create($db, [
        'name' => $name,
        'sex' => $sex,
        'birthday' => $birthday,
        'phone' => samplePhone($usedPhones),
        'status' => $spec['status'],
        'active' => $active,
        'permission' => ','.implode(',', $spec['permission']).','
    ], $idCard, Account::birthdayPassword($birthday));
    $db->update('user', [['id', $id]], ['created_at' => randomDate('2018-05-01', ($academicYear - 544).'-04-30').' 09:00:00']);
    // ข้อมูลเพิ่มเติมครบทุกช่องของ PERSONNEL_DETAILS แบบเดียวกับที่ฟอร์มบันทึก
    $custom = [];
    foreach (array_keys(\Personnel\Person\Model::details()) as $key) {
        $custom[$key] = $key === 'address' ? $address() : '';
    }
    $db->insert('personnel', [
        'id' => $id,
        'position' => $positions[$spec['position']],
        'department' => $spec['dept'] >= 0 ? $deptIds[$spec['dept']] : 0,
        'order' => $spec['role'] === 'former' ? 90 + $order : $order + 1,
        'custom' => json_encode($custom, JSON_UNESCAPED_UNICODE),
        'id_card' => $idCard,
        'class' => $homeroomOf ? $classIds[$homeroomOf[0]] : 0,
        'room' => $homeroomOf ? $roomIds[$homeroomOf[0]][$homeroomOf[1]] : 0
    ]);
    if ($homeroomOf) {
        $homeroom[$homeroomOf[0]][$homeroomOf[1]] = $id;
    }
    $person = ['id' => $id, 'name' => $name, 'username' => $idCard, 'password' => Account::birthdayPassword($birthday), 'status' => $spec['status'], 'active' => $active, 'role' => $spec['role'], 'position' => $spec['position']];
    $people[$id] = $person;
    if ($spec['role'] === 'teacher') {
        $deptTeachers[$spec['dept']][$spec['slot']] = $id;
        ++$teacherIndex;
    }
    if (!isset($roles[$spec['role']])) {
        $roles[$spec['role']] = $id;
    }
    if ($spec['role'] === 'teacher' && in_array('can_manage_course', $spec['permission'], true)) {
        $roles['registrar'] = $id;
    }
    echo '.';
}
echo "\n";

// -----------------------------------------------------------------------------
// นักเรียน
// -----------------------------------------------------------------------------
$total = LEVELS * ROOMS_PER_CLASS * STUDENTS_PER_ROOM;
echo "นักเรียน $total คน ";
$students = [];
$studentSeq = [];
$yearCE = $academicYear - 543;
for ($level = 1; $level <= LEVELS; $level++) {
    $entryYear = $academicYear - ($level - 1);
    for ($n = 1; $n <= ROOMS_PER_CLASS; $n++) {
        $roster = [];
        for ($i = 0; $i < STUDENTS_PER_ROOM; $i++) {
            $sex = mt_rand(0, 1) ? 'm' : 'f';
            $title = $level <= 3 ? ($sex === 'm' ? 'เด็กชาย' : 'เด็กหญิง') : ($sex === 'm' ? 'นาย' : 'นางสาว');
            [$name, $first, $last] = $personName($sex, $title);
            $roster[] = ['sex' => $sex, 'name' => $name, 'first' => $first, 'last' => $last];
        }
        // เลขที่: ชายก่อนหญิง แล้วเรียงตามชื่อ
        usort($roster, function ($a, $b) {
            return [$a['sex'] === 'm' ? 0 : 1, $a['first']] <=> [$b['sex'] === 'm' ? 0 : 1, $b['first']];
        });
        foreach ($roster as $i => $kid) {
            // อายุ 12 ปีเต็มตอนเข้า ม.1 (เกิดช่วงพฤษภาคมถึงพฤษภาคมของปีถัดไป)
            $birthday = randomDate(($yearCE - 12 - $level).'-05-17', ($yearCE - 11 - $level).'-05-16');
            $idCard = sampleIdCard($usedIdCards);
            do {
                $studentSeq[$entryYear] = ($studentSeq[$entryYear] ?? 0) + 1;
                $studentId = sprintf('%02d%03d', $entryYear % 100, $studentSeq[$entryYear]);
            } while (isset($usedStudentIds[$studentId]));
            $usedStudentIds[$studentId] = true;
            $hasPhone = mt_rand(1, 100) <= ($level >= 4 ? 70 : 25);
            $id = Account::create($db, [
                'name' => $kid['name'],
                'sex' => $kid['sex'],
                'birthday' => $birthday,
                'phone' => $hasPhone ? samplePhone($usedPhones) : null,
                'status' => $studentStatus,
                'active' => 1,
                'permission' => ''
            ], $idCard, Account::birthdayPassword($birthday));
            $db->update('user', [['id', $id]], ['created_at' => ($entryYear - 543).'-05-0'.mt_rand(1, 9).' 10:00:00']);
            $parentSex = mt_rand(1, 100) <= 65 ? 'f' : 'm';
            $parentFirst = pick($parentSex === 'm' ? $maleNames : $femaleNames);
            $db->insert('student', [
                'id' => $id,
                'student_id' => $studentId,
                'address' => $address(),
                'parent' => ($parentSex === 'm' ? 'นาย' : 'นาง').$parentFirst.' '.$kid['last'],
                'parent_phone' => '0'.pick(['6', '8', '9']).sprintf('%08d', mt_rand(0, 99999999)),
                // แผนการเรียน: ห้อง 1 วิทย์-คณิต ห้อง 2 ภาษา
                'department' => $deptIds[$n === 1 ? 0 : 7],
                'class' => $classIds[$level],
                'room' => $roomIds[$level][$n],
                'number' => $i + 1,
                'id_card' => $idCard
            ]);
            $students[] = [
                'id' => $id, 'level' => $level, 'n' => $n, 'number' => $i + 1, 'name' => $kid['name'],
                'username' => $idCard, 'password' => Account::birthdayPassword($birthday),
                // ความสามารถของนักเรียน ค่าเฉลี่ย 0 ส่วนใหญ่อยู่ระหว่าง -1.5 ถึง 1.5
                'ability' => (rnd() + rnd() + rnd() - 1.5) * 2
            ];
            if (count($students) % 10 === 0) {
                echo '.';
            }
        }
    }
}
echo "\n";

// -----------------------------------------------------------------------------
// รายวิชา
// -----------------------------------------------------------------------------
// ภาคเรียน: ปีการศึกษาก่อนหน้าทั้งสองภาค + ปีปัจจุบันจนถึงภาคปัจจุบัน (ภาคสุดท้ายยังไม่จบ)
$periods = [[$academicYear - 1, 1], [$academicYear - 1, 2]];
for ($term = 1; $term <= $currentTerm; $term++) {
    $periods[] = [$academicYear, $term];
}
/**
 * วันเปิดภาคเรียน (ค.ศ.)
 */
function termStart(int $year, int $term): string
{
    return ($year - 543).($term === 1 ? '-05-16' : '-11-01');
}
/**
 * วันปิดภาคเรียน (ค.ศ.)
 */
function termEnd(int $year, int $term): string
{
    return $term === 1 ? ($year - 543).'-09-30' : ($year - 542).'-03-15';
}
// รายวิชาเพิ่มเติม [กลุ่มสาระ, อักษรรหัส, ชื่อ, ชั่วโมง ม.ต้น/ม.ปลาย, ลำดับรหัส, ห้องที่เรียน (0 = ทุกห้อง), ระดับ]
$additional = [
    [1, 'ค', 'คณิตศาสตร์เพิ่มเติม', 40, 1, 0, 'lower'],
    [7, 'อ', 'ภาษาอังกฤษเพื่อการสื่อสาร', 40, 1, 0, 'lower'],
    [0, 'ว', 'ฟิสิกส์', 60, 1, 1, 'upper'],
    [1, 'ค', 'คณิตศาสตร์เพิ่มเติม', 60, 1, 1, 'upper'],
    [7, 'จ', 'ภาษาจีน', 60, 1, 2, 'upper'],
    [7, 'อ', 'ภาษาอังกฤษเพื่อการสื่อสาร', 40, 1, 2, 'upper']
];
$activities = [['ก', 'กิจกรรมแนะแนว', 1, 1], ['ก', 'กิจกรรมชุมนุม', 11, 2]];
$courses = [];
$logs = [];
$lastPeriod = end($periods);
foreach ($periods as [$year, $term]) {
    $gap = $academicYear - $year;
    $completed = [$year, $term] !== $lastPeriod;
    // เฉพาะชั้นที่นักเรียนปัจจุบันเคยเรียน (ปีก่อน ม.1–ม.5 = ม.2–ม.6 ของปีนี้)
    for ($level = 1; $level <= LEVELS - $gap; $level++) {
        $stage = $level <= 3 ? 2 : 3;
        $inStage = $level <= 3 ? $level : $level - 3;
        $seq = ($inStage - 1) * 2 + $term;
        $teacherSlot = intdiv($level - 1, 2);
        $items = [];
        foreach ($subjects as $d => $subject) {
            $hours = $stage === 2 ? $subject[4] : $subject[5];
            $items[] = [
                'name' => $subject[3].' '.$seq, 'code' => $subject[2].$stage.$inStage.'1'.sprintf('%02d', $term),
                'teacher' => $deptTeachers[$d][$teacherSlot], 'hours' => $hours, 'type' => 1, 'room' => 0
            ];
        }
        foreach ($additional as [$d, $letter, $name, $hours, $codeSeq, $roomOnly, $range]) {
            if (($range === 'lower') !== ($stage === 2)) {
                continue;
            }
            $items[] = [
                'name' => $name.' '.$seq, 'code' => $letter.$stage.$inStage.'2'.sprintf('%02d', $codeSeq + $term - 1),
                'teacher' => $deptTeachers[$d][$letter === 'จ' ? ($teacherSlot + 1) % 3 : $teacherSlot], 'hours' => $hours, 'type' => 2, 'room' => $roomOnly
            ];
        }
        foreach ($activities as [$letter, $name, $codeSeq, $homeroomOf]) {
            $items[] = [
                'name' => $name, 'code' => $letter.$stage.$inStage.'9'.sprintf('%02d', $codeSeq + $term - 1),
                'teacher' => $homeroom[$level][$homeroomOf] ?? $deptTeachers[3][$teacherSlot], 'hours' => 20, 'type' => 9, 'room' => 0
            ];
        }
        foreach ($items as $item) {
            $id = (int) $db->insert('course', [
                'course_name' => $item['name'],
                'course_code' => $item['code'],
                'teacher_id' => $item['teacher'],
                'class' => $classIds[$level],
                'period' => $item['hours'],
                'credit' => $item['type'] === 9 ? 0 : $item['hours'] / 40,
                'type' => $item['type'],
                'year' => $year,
                'term' => $term
            ]);
            $start = termStart($year, $term);
            $courses[] = $item + [
                'id' => $id, 'level' => $level, 'year' => $year, 'term' => $term, 'completed' => $completed,
                // ภาคเรียนปัจจุบัน: ราว 60% ของรายวิชาส่งคะแนนปลายภาคแล้ว
                'final' => $completed || mt_rand(1, 100) <= 60
            ];
            $logs[] = [$id, 'school', 'Save', '{LNG_Course} ID : '.$id, $item['teacher'], workTime(date('Y-m-d', strtotime($start.' -'.mt_rand(3, 10).' days')))];
        }
    }
}
echo 'รายวิชา '.count($courses).' วิชา ใน '.count($periods)." ภาคเรียน\n";

// -----------------------------------------------------------------------------
// ลงทะเบียนและผลการเรียน
// -----------------------------------------------------------------------------
/**
 * เกรดตามเกณฑ์มาตรฐาน 8 ระดับ ใช้เมื่อไม่ได้ตั้งเกณฑ์คำนวณ (โหมดกรอกเกรดเอง)
 */
function standardGrade(int $score): string
{
    foreach ([80 => '4', 75 => '3.5', 70 => '3', 65 => '2.5', 60 => '2', 55 => '1.5', 50 => '1'] as $min => $grade) {
        if ($score >= $min) {
            return $grade;
        }
    }

    return '0';
}
/**
 * ผลการเรียนหนึ่งแถว type midterm final grade
 */
function gradeRow(array $course, float $ability): array
{
    $pending = !$course['completed'] && !$course['final'];
    if ($course['type'] === 9) {
        if ($pending) {
            return ['type' => 0, 'midterm' => null, 'final' => null, 'grade' => null];
        }
        // กิจกรรม: ผ่าน (ผ.) ส่วนน้อยไม่ผ่าน (มผ.)
        $type = mt_rand(1, 100) <= 3 ? 3 : 4;

        return ['type' => $type, 'midterm' => null, 'final' => null, 'grade' => Score::toGrade($type, null, null)];
    }
    $total = max(12, min(98, (int) round(66 + 11 * $ability + mt_rand(-9, 9))));
    $midterm = (int) round($total * (0.55 + mt_rand(0, 10) / 100));
    $final = $total - $midterm;
    if ($pending) {
        return ['type' => 0, 'midterm' => $midterm, 'final' => null, 'grade' => null];
    }
    $roll = mt_rand(1, 1000);
    if ($roll <= 12 && $course['completed']) {
        // มส. (เวลาเรียนไม่ถึงเกณฑ์) ไม่มีสิทธิ์สอบปลายภาค
        return ['type' => 2, 'midterm' => $midterm, 'final' => null, 'grade' => Score::toGrade(2, $midterm, null)];
    }
    if ($roll <= 20) {
        // ร. (ส่งงานไม่ครบ)
        return ['type' => 1, 'midterm' => $midterm, 'final' => null, 'grade' => Score::toGrade(1, $midterm, null)];
    }

    return ['type' => 0, 'midterm' => $midterm, 'final' => $final, 'grade' => Score::toGrade(0, $midterm, $final, standardGrade($total))];
}
$coursesOf = [];
foreach ($courses as $course) {
    $coursesOf[$course['year'].'/'.$course['term'].'/'.$course['level']][] = $course;
}
$gradeCount = 0;
$registered = [];
$firstGrade = [];
$db->beginTransaction();
try {
    foreach ($students as $student) {
        foreach ($periods as [$year, $term]) {
            $level = $student['level'] - ($academicYear - $year);
            if ($level < 1) {
                continue;
            }
            foreach ($coursesOf[$year.'/'.$term.'/'.$level] ?? [] as $course) {
                if ($course['room'] > 0 && $course['room'] !== $student['n']) {
                    continue;
                }
                // ห้องและเลขที่ของปีนั้น (เลื่อนชั้นขึ้นมาห้องเดิม)
                $gradeId = (int) $db->insert('grade', [
                    'student_id' => $student['id'],
                    'course_id' => $course['id'],
                    'number' => $student['number'],
                    'room' => $roomIds[$level][$student['n']]
                ] + gradeRow($course, $student['ability']));
                $registered[$course['id']][] = $student['id'];
                $firstGrade[$course['id']] = $firstGrade[$course['id']] ?? $gradeId;
                ++$gradeCount;
            }
        }
    }
    $db->commit();
} catch (\Throwable $e) {
    $db->rollback();
    throw $e;
}
foreach ($courses as $course) {
    if (empty($registered[$course['id']])) {
        continue;
    }
    $start = termStart($course['year'], $course['term']);
    $logs[] = [$course['id'], 'school', 'Save', '{LNG_Register course} ID : '.implode(', ', $registered[$course['id']]), $course['teacher'], workTime(date('Y-m-d', strtotime($start.' +'.mt_rand(0, 6).' days')))];
    if ($course['final']) {
        $end = $course['completed'] ? termEnd($course['year'], $course['term']) : date('Y-m-d', $now - mt_rand(1, 6) * 86400);
        $logs[] = [$firstGrade[$course['id']], 'school', 'Save', '{LNG_Final} {LNG_Grade} ID : '.$firstGrade[$course['id']], $course['teacher'], workTime($end)];
    }
}
echo "ผลการเรียน $gradeCount แถว\n";

// -----------------------------------------------------------------------------
// หนังสือเวียน + ไฟล์ + ประวัติการดาวน์โหลด
// -----------------------------------------------------------------------------
/**
 * ตัดข้อความเป็นบรรทัดตามความกว้าง (ตัดที่ช่องว่างก่อน คำที่ยาวเกินตัดตามตัวอักษร)
 */
function wrapText(string $text, string $font, float $size, int $width): array
{
    $measure = function ($line) use ($font, $size) {
        $box = imagettfbbox($size, 0, $font, $line);

        return $box[2] - $box[0];
    };
    $lines = [];
    foreach (explode("\n", $text) as $paragraph) {
        $line = '';
        foreach (preg_split('/(?<= )/u', $paragraph) as $word) {
            if ($measure($line.$word) <= $width) {
                $line .= $word;
                continue;
            }
            if ($line !== '') {
                $lines[] = rtrim($line);
                $line = '';
            }
            preg_match_all('/\X/u', $word, $clusters);
            foreach ($clusters[0] as $cluster) {
                if ($line !== '' && $measure($line.$cluster) > $width) {
                    $lines[] = $line;
                    $line = '';
                }
                $line .= $cluster;
            }
        }
        $lines[] = rtrim($line);
    }

    return $lines;
}
/**
 * วันที่ไทยแบบเต็ม เช่น 5 ตุลาคม 2569
 */
function thaiDate(int $time): string
{
    $months = [1 => 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];

    return (int) date('j', $time).' '.$months[(int) date('n', $time)].' '.((int) date('Y', $time) + 543);
}
/**
 * หน้าบันทึกข้อความ A4 (100 dpi) เป็น JPEG
 */
function memoJpeg(array $doc, string $font): string
{
    $w = 827;
    $h = 1169;
    $im = imagecreatetruecolor($w, $h);
    $white = imagecolorallocate($im, 255, 255, 255);
    $black = imagecolorallocate($im, 25, 25, 25);
    $grey = imagecolorallocate($im, 120, 120, 120);
    $red = imagecolorallocate($im, 200, 30, 30);
    $mark = imagecolorallocatealpha($im, 200, 200, 200, 80);
    imagefilledrectangle($im, 0, 0, $w, $h, $white);
    imagettftext($im, 110, 35, 120, 900, $mark, $font, 'ตัวอย่าง');
    if ($doc['urgency'] < 2) {
        imagesetthickness($im, 3);
        imagerectangle($im, 60, 50, 220, 105, $red);
        imagettftext($im, 22, 0, 80, 90, $red, $font, $doc['urgency_text']);
    }
    $box = imagettfbbox(30, 0, $font, 'บันทึกข้อความ');
    imagettftext($im, 30, 0, (int) (($w - ($box[2] - $box[0])) / 2), 100, $black, $font, 'บันทึกข้อความ');
    $y = 165;
    foreach ([
        'ส่วนราชการ  '.$doc['school'],
        'ที่  '.$doc['document_no'].str_repeat(' ', 12).'วันที่  '.thaiDate($doc['time']),
        'เรื่อง  '.$doc['topic']
    ] as $text) {
        foreach (wrapText($text, $font, 15, $w - 140) as $line) {
            imagettftext($im, 15, 0, 70, $y, $black, $font, $line);
            $y += 34;
        }
    }
    imageline($im, 70, $y - 14, $w - 70, $y - 14, $grey);
    $y += 26;
    imagettftext($im, 15, 0, 70, $y, $black, $font, 'เรียน  '.$doc['to']);
    $y += 50;
    foreach (wrapText(str_repeat(' ', 12).$doc['body'], $font, 15, $w - 160) as $line) {
        imagettftext($im, 15, 0, 80, $y, $black, $font, $line);
        $y += 34;
    }
    $y += 16;
    imagettftext($im, 15, 0, 130, $y, $black, $font, 'จึงเรียนมาเพื่อโปรดทราบและถือปฏิบัติ');
    $y += 110;
    foreach (['('.$doc['sender'].')', $doc['sender_position']] as $line) {
        $box = imagettfbbox(15, 0, $font, $line);
        imagettftext($im, 15, 0, (int) (560 - ($box[2] - $box[0]) / 2), $y, $black, $font, $line);
        $y += 34;
    }
    imagettftext($im, 11, 0, 70, $h - 40, $grey, $font, 'เอกสารตัวอย่างสร้างโดย install/cli-sample.php สำหรับทดสอบระบบเท่านั้น');
    ob_start();
    imagejpeg($im, null, 72);

    return ob_get_clean();
}
/**
 * ประกอบ PDF หนึ่งหน้าจาก JPEG (ภาษาไทยแสดงได้โดยไม่ต้องฝังฟอนต์)
 */
function jpegPdf(string $jpeg, string $title): string
{
    [$w, $h] = getimagesizefromstring($jpeg);
    $content = 'q 595 0 0 842 0 0 cm /Im1 Do Q';
    $objects = [
        1 => '<< /Type /Catalog /Pages 2 0 R >>',
        2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /XObject << /Im1 4 0 R >> >> /Contents 5 0 R >>',
        4 => "<< /Type /XObject /Subtype /Image /Width $w /Height $h /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($jpeg)." >>\nstream\n".$jpeg."\nendstream",
        5 => '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream",
        6 => '<< /Title <FEFF'.strtoupper(bin2hex(mb_convert_encoding($title, 'UTF-16BE', 'UTF-8'))).'> /Producer (SMS cli-sample) >>'
    ];

    return pdfFile($objects, 6);
}
/**
 * PDF ข้อความ ASCII ล้วน (ใช้เมื่อเครื่องไม่มี GD หรือฟอนต์ไทย)
 */
function textPdf(array $lines): string
{
    $content = "BT /F1 14 Tf 60 780 Td 20 TL\n";
    foreach ($lines as $line) {
        $content .= '('.addcslashes($line, '()\\').") Tj T*\n";
    }
    $content .= 'ET';
    $objects = [
        1 => '<< /Type /Catalog /Pages 2 0 R >>',
        2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
        4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        5 => '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream"
    ];

    return pdfFile($objects, 0);
}
function pdfFile(array $objects, int $info): string
{
    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [];
    foreach ($objects as $n => $body) {
        $offsets[$n] = strlen($pdf);
        $pdf .= "$n 0 obj\n$body\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
    foreach ($offsets as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }

    return $pdf.'trailer << /Size '.(count($objects) + 1).' /Root 1 0 R'.($info > 0 ? " /Info $info 0 R" : '')." >>\nstartxref\n$xref\n%%EOF\n";
}

$font = '';
if (function_exists('imagettftext')) {
    foreach ([$root.'/Now/css/fonts/leelawad.ttf', '/usr/share/fonts/truetype/tlwg/Garuda.ttf', '/usr/share/fonts/truetype/noto/NotoSansThai-Regular.ttf'] as $file) {
        if (is_file($file)) {
            $font = $file;
            break;
        }
    }
}
$receiverOptions = \Edocument\Document\Model::receiverOptions();
$receiverSets = [
    'all' => [array_keys($receiverOptions), 'คณะครูและบุคลากรทุกท่าน'],
    'staff' => [[$teacherStatus, $executiveStatus], 'คณะครูและผู้บริหาร'],
    'teachers' => [[$teacherStatus], 'คณะครู'],
    'exec' => [[$executiveStatus], 'ผู้อำนวยการโรงเรียน']
];
$senderOf = [
    'director' => $roles['director'], 'vice_academic' => $roles['vice_academic'], 'vice_personnel' => $roles['vice_personnel'], 'clerk' => $roles['clerk']
];
foreach ($subjects as $d => $subject) {
    $senderOf['head:'.$d] = $deptTeachers[$d][0];
}
$duty = 'ตารางเวรรักษาการณ์กลางวันและกลางคืนประจำเดือน ผู้ที่ติดภารกิจให้แลกเวรกันเองแล้วแจ้งงานธุรการล่วงหน้า';
// [วันที่ (เดือน-วัน ของภาคเรียนที่ 1), เรื่อง, รายละเอียด, ผู้รับ, ความเร่งด่วน, ผู้ส่ง, ชนิดไฟล์]
$docs = [
    ['05-08', 'คำสั่งแต่งตั้งครูประจำชั้น ปีการศึกษา {Y}', 'แต่งตั้งครูประจำชั้นทุกระดับชั้น ให้ดูแลนักเรียน ติดตามการมาเรียน และประสานงานกับผู้ปกครองตลอดปีการศึกษา', 'staff', 2, 'director', 'pdf'],
    ['05-11', 'ประกาศปฏิทินการศึกษา ปีการศึกษา {Y}', 'กำหนดวันเปิด-ปิดภาคเรียน วันสอบกลางภาคและปลายภาค และกิจกรรมสำคัญของโรงเรียน ขอให้ทุกท่านใช้ประกอบการวางแผนการสอน', 'all', 2, 'vice_academic', 'pdf'],
    ['05-12', 'ขอเชิญประชุมครูก่อนเปิดภาคเรียนที่ 1/{Y}', 'ขอเชิญคณะครูทุกท่านประชุมเตรียมความพร้อมก่อนเปิดภาคเรียน ณ ห้องประชุมใหญ่ เวลา 09.00 น.', 'all', 1, 'director', 'pdf'],
    ['05-14', 'แจ้งตารางสอน ภาคเรียนที่ 1/{Y}', 'ส่งตารางสอนรายบุคคลและตารางเรียนของทุกห้อง ตรวจสอบความถูกต้องแล้วแจ้งฝ่ายวิชาการภายใน 3 วัน', 'teachers', 1, 'vice_academic', 'pdf'],
    ['05-20', 'ขอความร่วมมือส่งแผนการจัดการเรียนรู้', 'ขอให้ครูผู้สอนทุกรายวิชาส่งแผนการจัดการเรียนรู้ภาคเรียนที่ 1 ที่ฝ่ายวิชาการภายในวันที่ 15 มิถุนายน', 'teachers', 1, 'vice_academic', 'pdf'],
    ['05-27', 'แจ้งเวรรักษาการณ์ประจำเดือนมิถุนายน', $duty, 'staff', 2, 'clerk', 'jpg'],
    ['06-02', 'ขออนุมัติจัดกิจกรรมวันไหว้ครู', 'กลุ่มสาระการเรียนรู้ภาษาไทยขออนุมัติจัดกิจกรรมวันไหว้ครู ประจำปีการศึกษา {Y} ในวันพฤหัสบดีที่ 11 มิถุนายน', 'exec', 2, 'head:2', 'pdf'],
    ['06-05', 'ขอเชิญประชุมครูประจำเดือนมิถุนายน', 'วาระการประชุม: รายงานผลการเปิดภาคเรียน การคัดกรองนักเรียน และการเตรียมการประชุมผู้ปกครอง', 'all', 1, 'director', 'pdf'],
    ['06-10', 'ขอความร่วมมือคัดกรองนักเรียนกลุ่มเสี่ยง', 'ขอให้ครูประจำชั้นคัดกรองนักเรียนตามระบบดูแลช่วยเหลือนักเรียน และส่งแบบสรุปที่งานแนะแนวภายในสิ้นเดือน', 'teachers', 1, 'vice_personnel', 'pdf'],
    ['06-15', 'แบบฟอร์มแผนพัฒนาตนเองรายบุคคล (ID Plan)', 'แบบฟอร์มแผนพัฒนาตนเองรายบุคคล ประจำปีงบประมาณ กรอกให้ครบถ้วนแล้วส่งที่งานบุคคล', 'staff', 2, 'vice_personnel', 'jpg'],
    ['06-19', 'แจ้งกำหนดการประชุมผู้ปกครองนักเรียน', 'กำหนดประชุมผู้ปกครองนักเรียนทุกระดับชั้นวันเสาร์ที่ 27 มิถุนายน ขอให้ครูประจำชั้นเตรียมเอกสารและห้องประชุม', 'teachers', 2, 'vice_academic', 'pdf'],
    ['06-26', 'แจ้งเวรรักษาการณ์ประจำเดือนกรกฎาคม', $duty, 'staff', 2, 'clerk', 'jpg'],
    ['07-01', 'มาตรการป้องกันโรคไข้หวัดใหญ่ในสถานศึกษา', 'ให้ทุกห้องเรียนคัดกรองอาการนักเรียนทุกเช้า ทำความสะอาดห้องเรียน และแจ้งผู้ปกครองให้นักเรียนที่ป่วยพักรักษาตัวที่บ้าน', 'all', 0, 'director', 'pdf'],
    ['07-03', 'ขอเชิญประชุมครูประจำเดือนกรกฎาคม', 'วาระการประชุม: การเตรียมสอบกลางภาค โครงการสัปดาห์วิทยาศาสตร์ และการเบิกจ่ายงบประมาณ', 'all', 1, 'director', 'pdf'],
    ['07-08', 'คำสั่งแต่งตั้งคณะกรรมการดำเนินการสอบกลางภาค ภาคเรียนที่ 1/{Y}', 'แต่งตั้งกรรมการออกข้อสอบ กรรมการคุมสอบ และกรรมการตรวจข้อสอบกลางภาค ตามบัญชีรายชื่อแนบท้ายคำสั่ง', 'teachers', 1, 'vice_academic', 'pdf'],
    ['07-15', 'สำนักงานเขตพื้นที่การศึกษาแจ้งโครงการอบรมพัฒนาครู', 'สำนักงานเขตพื้นที่การศึกษาเปิดรับสมัครครูเข้าร่วมอบรมการจัดการเรียนรู้เชิงรุก (Active Learning) ผู้สนใจสมัครได้ที่งานบุคคล', 'teachers', 2, 'clerk', 'pdf'],
    ['07-22', 'ประกาศรับสมัครนักเรียนเข้าร่วมกิจกรรมชุมนุม', 'ขอให้ครูที่ปรึกษาชุมนุมส่งชื่อชุมนุมและจำนวนนักเรียนที่รับ เพื่อเปิดให้นักเรียนลงทะเบียน', 'teachers', 2, 'vice_academic', 'jpg'],
    ['07-28', 'แจ้งเวรรักษาการณ์ประจำเดือนสิงหาคม', $duty, 'staff', 2, 'clerk', 'jpg'],
    ['08-03', 'ขอเชิญร่วมงานวันแม่แห่งชาติ', 'ขอเชิญคณะครูและบุคลากรร่วมกิจกรรมวันแม่แห่งชาติ วันที่ 11 สิงหาคม เวลา 08.00 น. ณ หอประชุมโรงเรียน', 'all', 2, 'clerk', 'pdf'],
    ['08-06', 'ขออนุมัติโครงการสัปดาห์วิทยาศาสตร์แห่งชาติ', 'กลุ่มสาระการเรียนรู้วิทยาศาสตร์และเทคโนโลยีขออนุมัติโครงการสัปดาห์วิทยาศาสตร์ วันที่ 17-21 สิงหาคม พร้อมงบประมาณ', 'exec', 2, 'head:0', 'pdf'],
    ['08-07', 'ขอเชิญประชุมครูประจำเดือนสิงหาคม', 'วาระการประชุม: สรุปผลการสอบกลางภาค นักเรียนที่มีผลการเรียนต่ำ และกิจกรรมสัปดาห์วิทยาศาสตร์', 'all', 1, 'director', 'pdf'],
    ['08-14', 'แนวปฏิบัติการลาของข้าราชการครูและบุคลากรทางการศึกษา', 'สรุประเบียบการลาป่วย ลากิจ และลาพักผ่อน พร้อมแบบฟอร์มใบลา ขอให้ยื่นใบลาล่วงหน้าตามระเบียบ', 'staff', 2, 'vice_personnel', 'pdf'],
    ['08-20', 'รายงานผลการแข่งขันทักษะทางวิชาการ', 'กลุ่มสาระการเรียนรู้คณิตศาสตร์รายงานผลการแข่งขันทักษะคณิตศาสตร์ระดับเขตพื้นที่ นักเรียนได้รับรางวัลเหรียญทอง 2 รายการ', 'exec', 2, 'head:1', 'pdf'],
    ['08-27', 'แจ้งเวรรักษาการณ์ประจำเดือนกันยายน', $duty, 'staff', 2, 'clerk', 'jpg'],
    ['09-01', 'กำหนดการทัศนศึกษานอกสถานที่ ระดับชั้น ม.3', 'กลุ่มสาระการเรียนรู้สังคมศึกษาฯ กำหนดพานักเรียนชั้น ม.3 ทัศนศึกษาที่อุทยานประวัติศาสตร์ ขอครูร่วมเดินทาง 4 ท่าน', 'teachers', 2, 'head:3', 'pdf'],
    ['09-04', 'ขอเชิญประชุมครูประจำเดือนกันยายน', 'วาระการประชุม: การเตรียมสอบปลายภาค การส่งผลการเรียน และการประเมินผลการปฏิบัติงาน', 'all', 1, 'director', 'pdf'],
    ['09-09', 'คำสั่งแต่งตั้งคณะกรรมการดำเนินการสอบปลายภาค ภาคเรียนที่ 1/{Y}', 'แต่งตั้งกรรมการดำเนินการสอบปลายภาค ตามบัญชีรายชื่อแนบท้ายคำสั่ง ขอให้ปฏิบัติหน้าที่อย่างเคร่งครัด', 'teachers', 1, 'vice_academic', 'pdf'],
    ['09-15', 'แบบสำรวจความต้องการวัสดุการเรียนการสอน ภาคเรียนที่ 2/{Y}', 'ขอให้กลุ่มสาระการเรียนรู้สำรวจความต้องการวัสดุและครุภัณฑ์ แล้วส่งแบบสำรวจที่งานพัสดุภายในสิ้นเดือน', 'teachers', 2, 'vice_personnel', 'jpg'],
    ['09-18', 'แจ้งกำหนดส่งผลการเรียน ภาคเรียนที่ 1/{Y}', 'ขอให้ครูผู้สอนบันทึกคะแนนและผลการเรียนในระบบให้ครบถ้วนภายในวันที่ 10 ตุลาคม', 'teachers', 0, 'vice_academic', 'pdf'],
    ['09-24', 'สรุปผลการประเมินคุณภาพภายในสถานศึกษา', 'สรุปผลการประเมินคุณภาพภายในตามมาตรฐานการศึกษาของสถานศึกษา พร้อมข้อเสนอแนะเพื่อการพัฒนา', 'all', 2, 'director', 'pdf'],
    ['09-25', 'รายงานการใช้จ่ายงบประมาณไตรมาสที่ 4', 'รายงานการเบิกจ่ายงบประมาณตามโครงการ ประจำไตรมาสที่ 4 ของปีงบประมาณ', 'exec', 2, 'clerk', 'pdf'],
    ['09-28', 'แจ้งเวรรักษาการณ์ประจำเดือนตุลาคม', $duty, 'staff', 2, 'clerk', 'jpg'],
    ['09-30', 'ประกาศรายชื่อนักเรียนที่ได้รับทุนการศึกษา', 'ประกาศรายชื่อนักเรียนที่ได้รับทุนการศึกษาประจำปี ขอให้ครูประจำชั้นแจ้งนักเรียนและผู้ปกครอง', 'all', 2, 'clerk', 'pdf'],
    ['10-01', 'แจ้งการใช้งานระบบสารสนเทศโรงเรียน', 'โรงเรียนเปิดใช้ระบบสารสนเทศสำหรับบันทึกผลการเรียนและรับหนังสือราชการ ชื่อผู้ใช้คือเลขประจำตัวประชาชน รหัสผ่านคือวันเกิด', 'all', 1, 'director', 'pdf'],
    ['10-02', 'ขอเชิญประชุมครูประจำเดือนตุลาคม', 'วาระการประชุม: สรุปผลการเรียนภาคเรียนที่ 1 การปิดภาคเรียน และการเตรียมการภาคเรียนที่ 2', 'all', 0, 'director', 'pdf']
];
$positionNames = [
    'director' => 'ผู้อำนวยการ'.($cfg->school_name ?: 'โรงเรียน'), 'vice' => 'รองผู้อำนวยการโรงเรียน', 'teacher' => 'หัวหน้ากลุ่มสาระการเรียนรู้',
    'clerk' => 'เจ้าหน้าที่ธุรการ'
];
$dir = \Edocument\Document\Model::dir();
\Kotchasan\File::makeDirectory($dir);
$urgencies = (array) Language::get('URGENCIES', []);
$docCount = 0;
$downloadCount = 0;
foreach ($docs as [$date, $topic, $detail, $to, $urgency, $sender, $ext]) {
    $time = workTime($yearCE.'-'.$date);
    if (strtotime($yearCE.'-'.$date) > $now) {
        continue;
    }
    $topic = str_replace('{Y}', (string) $academicYear, $topic);
    $detail = str_replace('{Y}', (string) $academicYear, $detail);
    $senderId = $senderOf[$sender];
    [$receiver, $toText] = $receiverSets[$to];
    $receiver = array_values(array_unique(array_intersect($receiver, array_keys($receiverOptions))));
    $documentNo = \Index\Number\Model::get(0, (string) $cfg->edocument_format_no, 'edocument', 'document_no', (string) $cfg->edocument_prefix);
    $file = $time.'.'.$ext;
    while (is_file($dir.$file)) {
        ++$time;
        $file = $time.'.'.$ext;
    }
    $page = [
        'school' => $cfg->school_name ?: 'โรงเรียนตัวอย่างวิทยาคม', 'document_no' => $documentNo, 'time' => $time, 'topic' => $topic,
        'to' => $toText, 'body' => $detail, 'urgency' => $urgency, 'urgency_text' => $urgencies[$urgency] ?? '',
        'sender' => preg_replace('/^(นาย|นางสาว|นาง|ดร\.)/u', '', $people[$senderId]['name']), 'sender_position' => $positionNames[$people[$senderId]['position']] ?? 'ครู'
    ];
    if ($font !== '') {
        $jpeg = memoJpeg($page, $font);
        $content = $ext === 'jpg' ? $jpeg : jpegPdf($jpeg, $topic);
    } else {
        $ext = 'pdf';
        $file = $time.'.pdf';
        $content = textPdf(['SMS sample e-document', 'No. '.$documentNo, 'Date: '.date('Y-m-d', $time), 'Generated by install/cli-sample.php']);
    }
    file_put_contents($dir.$file, $content);
    $id = (int) $db->insert('edocument', [
        'sender_id' => $senderId,
        'receiver' => ','.implode(',', $receiver).',',
        'last_update' => $time,
        'document_no' => $documentNo,
        'detail' => $detail."\n\n".MARK,
        'topic' => preg_replace('/[,;:_]{1,}/', '_', $topic),
        'ext' => $ext,
        'size' => strlen($content),
        'file' => $file,
        'ip' => '192.168.1.'.mt_rand(20, 250),
        'urgency' => $urgency
    ]);
    $logs[] = [$id, 'edocument', 'Save', '{LNG_E-Document} '.$documentNo, $senderId, $time];
    ++$docCount;
    // ผู้รับดาวน์โหลด (= ลงชื่อรับ) หนังสือเก่าเกือบครบ หนังสือใหม่ยังน้อย
    $age = max(0, $now - $time);
    $chance = min(0.95, 0.25 + $age / (60 * 86400));
    foreach ($people as $memberId => $person) {
        if ($memberId === $senderId || $person['active'] !== 1 || !in_array($person['status'], $receiver, true) || rnd() > $chance) {
            continue;
        }
        $roll = mt_rand(1, 10);
        $db->insert('edocument_download', [
            'document_id' => $id,
            'member_id' => $memberId,
            'downloads' => $roll <= 7 ? 1 : ($roll <= 9 ? 2 : 3),
            'last_update' => $time + mt_rand(600, (int) max(1200, min($age, 5 * 86400))),
            'department_id' => null
        ]);
        ++$downloadCount;
    }
}
echo "หนังสือเวียน $docCount ฉบับ (".($font !== '' ? 'ไฟล์ PDF/JPG ภาษาไทย' : 'ไฟล์ PDF ข้อความ ASCII เพราะไม่มี GD/ฟอนต์ไทย').") · ดาวน์โหลด $downloadCount รายการ\n";

// -----------------------------------------------------------------------------
// ประวัติการใช้งาน
// -----------------------------------------------------------------------------
// เข้าระบบ 30 วันล่าสุด: บุคลากรเกือบทุกวันทำการ นักเรียนบางคนสองสามครั้ง
for ($day = 30; $day >= 0; $day--) {
    $date = date('Y-m-d', $now - $day * 86400);
    if ((int) date('N', strtotime($date)) >= 6) {
        continue;
    }
    foreach ($people as $memberId => $person) {
        if ($person['active'] === 1 && mt_rand(1, 100) <= 60) {
            $logs[] = [$memberId, 'index', 'Auth', 'Login successful IP: 192.168.1.'.mt_rand(20, 250), $memberId, workTime($date, 7, 9)];
        }
    }
}
foreach ($students as $student) {
    if (mt_rand(1, 100) <= 35) {
        for ($k = mt_rand(1, 3); $k > 0; $k--) {
            $logs[] = [$student['id'], 'index', 'Auth', 'Login successful IP: 10.0.'.mt_rand(0, 9).'.'.mt_rand(2, 250), $student['id'], workTime(date('Y-m-d', $now - mt_rand(0, 30) * 86400), 16, 21)];
        }
    }
}
usort($logs, function ($a, $b) {
    return $a[5] <=> $b[5];
});
$db->beginTransaction();
try {
    foreach ($logs as [$srcId, $module, $action, $topic, $memberId, $time]) {
        $db->insert('logs', [
            'src_id' => $srcId,
            'module' => $module,
            'action' => $action,
            'created_at' => date('Y-m-d H:i:s', $time),
            'reason' => null,
            'member_id' => $memberId,
            'topic' => $topic,
            'datas' => null
        ]);
    }
    $db->commit();
} catch (\Throwable $e) {
    $db->rollback();
    throw $e;
}
echo 'ประวัติการใช้งาน '.count($logs)." รายการ\n";

// -----------------------------------------------------------------------------
// บัญชีสำหรับทดสอบ
// -----------------------------------------------------------------------------
echo "\nบัญชีสำหรับทดสอบ (ชื่อผู้ใช้ / รหัสผ่าน)\n";
$show = [
    'ผู้อำนวยการ' => $people[$roles['director']],
    'รองฯ วิชาการ' => $people[$roles['vice_academic']],
    'รองฯ บุคคล' => $people[$roles['vice_personnel']],
    'งานทะเบียน' => $people[$roles['registrar']],
    'ครูประจำชั้น ม.1/1' => $people[$homeroom[1][1]],
    'ธุรการ' => $people[$roles['clerk']],
    'นักเรียน ม.'.$students[count($students) - 1]['level'].'/'.$students[count($students) - 1]['n'] => $students[count($students) - 1]
];
foreach ($show as $label => $person) {
    echo '  '.$person['username'].' / '.$person['password'].'  '.$label.' ('.$person['name'].")\n";
}
printf("\nเสร็จใน %.1f วินาที\n", microtime(true) - $started);
