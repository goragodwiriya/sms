<?php
/**
 * ชุดทดสอบโมดูล edocument — รันผ่าน modules/school/tests/run.php
 * ข้อมูลตั้งต้นมาจาก modules/school/tests/fixtures/legacy_*.sql ที่ผ่านตัวปรับรุ่นจริงแล้ว
 *
 *   1 แอดมิน · 2 ครู (can_upload_edocument) · 3 ครู (can_upload_edocument) · 4 ผอ. สถานะ 3 (can_handle_all_edocument)
 *   6 ครู (ไม่มีสิทธิ์ส่ง) · 10 นักเรียน
 *   หนังสือ 1 ผู้ส่ง 1 ผู้รับ ,1,2,3, · หนังสือ 2 ผู้ส่ง 1 ผู้รับ ,1, (ข้อมูลตัวอย่างของตัวติดตั้งเดิม)
 *   หนังสือ 3 ผู้ส่ง 2 ผู้รับ ,2,3, · หนังสือ 4 ผู้ส่ง 4 ผู้รับ ,0,2,
 */
require getenv('SMS_BOOT');

/**
 * เมนูของผู้ใช้ (ลบ key ออกแล้ว เหมือนที่ API ส่ง)
 */
function edocument_menus($memberId)
{
    $login = \Index\Auth\Model::getUserByToken(\Index\Auth\Model::generateTokens($memberId)['access_token']);

    return \Index\Menus\Controller::getMenus($login);
}
/**
 * url ทั้งหมดใต้เมนูหนึ่ง (ค้นจากชื่อเมนู)
 */
function edocument_urls(array $menus, $title = null)
{
    $urls = [];
    foreach ($menus as $menu) {
        if ($title !== null && ($menu['title'] ?? '') !== $title) {
            continue;
        }
        array_walk_recursive($menu, function ($v, $k) use (&$urls) {
            if ($k === 'url') {
                $urls[] = $v;
            }
        });
    }

    return $urls;
}
/**
 * รายการในตาราง
 */
function edocument_ids($res)
{
    return array_map('intval', array_column($res['data']['data'] ?? [], 'id'));
}
/**
 * แถวในตาราง [id => แถว]
 */
function edocument_rows($res)
{
    return array_column($res['data']['data'] ?? [], null, 'id');
}

echo "edocument: สิทธิ์\n";
$values = array_column(\Edocument\Init\Controller::initPermission([]), 'value');
ok('ประกาศสิทธิ์ของโมดูล', $values, ['can_handle_all_edocument', 'can_upload_edocument']);

echo "edocument: เมนู (ตั้งค่าของโมดูลอยู่ใต้เมนูของโมดูลเท่านั้น)\n";
$menus = edocument_menus(1);
ok('ลำดับเมนู: หน้าแรก โรงเรียน บุคลากร งานสารบรรณ', array_slice(array_column($menus, 'title'), 0, 4), ['Dashboard', '{LNG_School}', '{LNG_Personnel}', '{LNG_E-Document}']);
ok('งานสารบรรณ: รับ ส่ง ตั้งค่า', edocument_urls($menus, '{LNG_E-Document}'), ['/edocument', '/edocument-sent', '/edocument-settings']);
$personnel = edocument_urls($menus, '{LNG_Personnel}');
ok('บุคลากร: ตั้งค่าโมดูล ตำแหน่ง แผนก อยู่ใต้เมนูบุคลากร', [
    in_array('/personnel-settings', $personnel, true),
    in_array('/personnel-categories?type=position', $personnel, true),
    in_array('/personnel-categories?type=department', $personnel, true)
], [true, true, true]);
$school = edocument_urls($menus, '{LNG_School}');
ok('โรงเรียน: ตั้งค่าโมดูล การคิดเกรด ประเภทผลการเรียน ชั้น ห้อง ภาคเรียน อยู่ใต้เมนูโรงเรียน', [
    in_array('/school-settings', $school, true),
    in_array('/school-gradesettings', $school, true),
    in_array('/language?key=SCHOOL_TYPIES', $school, true),
    in_array('/school-categories?type=class', $school, true),
    in_array('/school-categories?type=room', $school, true),
    in_array('/school-categories?type=term', $school, true)
], [true, true, true, true, true, true]);
$core = edocument_urls($menus, 'Settings');
ok('เมนูตั้งค่าของแกนไม่มีหน้าของโมดูล', array_values(preg_grep('#^/(personnel|school|edocument)-#', $core)), []);
ok('เมนูตั้งค่าของแกนไม่มีหน้าแผนกของแกน (ใช้หน้าหลายภาษาของบุคลากร)', in_array('/categories?type=department', $core, true), false);
$settingsGroup = array_values(array_filter(array_column($menus, 'children'), function ($children) {
    return in_array('/personnel-import', array_column($children, 'url'), true);
}))[0] ?? [];
ok('ตั้งค่าของบุคลากรเป็นกลุ่มย่อย "ตั้งค่า"', array_column($settingsGroup, 'title'), ['{LNG_Personnel list}', '{LNG_Import} {LNG_Personnel list}', '{LNG_Settings}']);
$menus = edocument_menus(6);
ok('ครูที่ส่งไม่ได้: เมนูเดียวคือเอกสารรับ', array_values(array_filter($menus, fn($m) => ($m['url'] ?? '') === '/edocument')) !== [], true);
ok('ครูที่ส่งไม่ได้: ไม่มีเอกสารส่ง', in_array('/edocument-sent', edocument_urls($menus), true), false);
ok('ครูที่ส่งได้: มีเอกสารส่ง ไม่มีตั้งค่า', [in_array('/edocument-sent', edocument_urls(edocument_menus(2)), true), in_array('/edocument-settings', edocument_urls(edocument_menus(2)), true)], [true, false]);
ok('นักเรียนมีเอกสารรับ (เหมือนระบบเดิม)', in_array('/edocument', edocument_urls(edocument_menus(10)), true));

echo "edocument: เอกสารรับ\n";
$res = sms_call(new \Edocument\Inbox\Controller(), 'index', sms_request(6, 'GET', ['sort' => 'id asc']));
$rows = edocument_rows($res);
ok('ครูเห็นหนังสือที่ส่งถึงครู', edocument_ids($res), [1, 3, 4]);
ok('ยังไม่ได้รับทุกฉบับ', array_column($rows, 'is_new', 'id'), [1 => 1, 3 => 1, 4 => 1]);
ok('ข้อความความเร่งด่วน', [$rows[3]['urgency_text'] ?? null, $rows[4]['urgency_text'] ?? null], ['ด่วนมาก', 'ด่วน']);
// ชื่ออ่านจากฐาน (ชุดทดสอบ personnel ที่รันก่อนแก้ชื่อสมาชิกบางคน)
$names = array_column(sms_rows('SELECT id, name FROM {prefix}_user WHERE id IN (2, 3, 6)'), 'name', 'id');
ok('ชื่อผู้ส่ง', $rows[3]['sender'] ?? null, $names[2]);
$senders = array_column($res['data']['filters']['sender'] ?? [], 'value');
sort($senders);
ok('ตัวกรองผู้ส่ง = ผู้ที่เคยส่งหนังสือ', $senders, ['1', '2', '4']);
$res = sms_call(new \Edocument\Inbox\Controller(), 'index', sms_request(10, 'GET', ['sort' => 'id asc']));
ok('นักเรียนเห็นเฉพาะหนังสือที่ส่งถึงนักเรียน', edocument_ids($res), [4]);
$res = sms_call(new \Edocument\Inbox\Controller(), 'index', sms_request(4, 'GET', ['sort' => 'id asc']));
ok('ผู้บริหารเห็นเฉพาะหนังสือที่ส่งถึงผู้บริหาร', edocument_ids($res), [1, 3]);
$res = sms_call(new \Edocument\Inbox\Controller(), 'action', sms_request(6, 'POST', ['action' => 'detail', 'id' => 3]));
ok('รายละเอียดเปิด modal', sms_action($res, 'modal')['template'] ?? null, 'edocument/view.html');
ok('รายละเอียดมีเลขที่และสถานะยังไม่ได้รับ', [sms_data($res)['document_no'] ?? null, sms_data($res)['is_new'] ?? null], ['DOC-0003', 1]);
$res = sms_call(new \Edocument\Inbox\Controller(), 'action', sms_request(10, 'POST', ['action' => 'detail', 'id' => 3]));
ok('นักเรียนเปิดหนังสือที่ไม่ได้ส่งถึงไม่ได้', $res['__status'], 404);

echo "edocument: ดาวน์โหลด = ลงชื่อรับ\n";
$res = sms_call(new \Edocument\View\Controller(), 'download', sms_request(6, 'POST', ['id' => 3]));
ok('ดาวน์โหลดได้', [$res['success'] ?? false, (bool) sms_action($res, 'download')], [true, true]);
sms_call(new \Edocument\View\Controller(), 'download', sms_request(6, 'POST', ['id' => 3]));
ok('บันทึกการรับ หนึ่งแถว นับจำนวนครั้ง', sms_rows('SELECT downloads FROM {prefix}_edocument_download WHERE document_id = 3 AND member_id = 6'), [['downloads' => 2]]);
$res = sms_call(new \Edocument\View\Controller(), 'download', sms_request(10, 'POST', ['id' => 3]));
ok('ดาวน์โหลดหนังสือที่ไม่ได้ส่งถึงไม่ได้', $res['__status'], 404);
$res = sms_call(new \Edocument\Inbox\Controller(), 'index', sms_request(6, 'GET', ['sort' => 'id asc']));
ok('หลังดาวน์โหลด ฉบับ 3 ได้รับแล้ว', array_column(edocument_rows($res), 'is_new', 'id'), [1 => 1, 3 => 0, 4 => 1]);
$teacher = \Index\Auth\Model::getUserByToken(\Index\Auth\Model::generateTokens(6)['access_token']);
$cards = \Edocument\Init\Controller::initDashboardCards([], null, $teacher);
ok('การ์ดหน้าแรก: เอกสารใหม่ 2 ฉบับ', [$cards[0]['title'] ?? null, $cards[0]['value'] ?? null, $cards[0]['url'] ?? null], ['งานสารบรรณ', '2', '/edocument']);
sms_call(new \Edocument\View\Controller(), 'download', sms_request(6, 'POST', ['id' => 1]));
sms_call(new \Edocument\View\Controller(), 'download', sms_request(6, 'POST', ['id' => 4]));
ok('ไม่มีเอกสารใหม่ ไม่แสดงการ์ด (เหมือนระบบเดิม)', \Edocument\Init\Controller::initDashboardCards([], null, $teacher), []);

echo "edocument: เอกสารส่ง\n";
$res = sms_call(new \Edocument\Outbox\Controller(), 'index', sms_request(6, 'GET'));
ok('ไม่มีสิทธิ์ส่ง เปิดไม่ได้', $res['__status'], 403);
$res = sms_call(new \Edocument\Outbox\Controller(), 'index', sms_request(2, 'GET', ['sort' => 'id asc']));
ok('ผู้ส่งเห็นเฉพาะหนังสือของตัวเอง', edocument_ids($res), [3]);
ok('จำนวนผู้รับแล้ว (3 จากระบบเดิม + 6)', $res['data']['data'][0]['downloads'] ?? null, 2);
$res = sms_call(new \Edocument\Outbox\Controller(), 'index', sms_request(4, 'GET', ['sort' => 'id asc']));
ok('ผู้จัดการงานสารบรรณเห็นทุกฉบับ', edocument_ids($res), [1, 2, 3, 4]);
$res = sms_call(new \Edocument\Outbox\Controller(), 'action', sms_request(2, 'POST', ['action' => 'edit', 'id' => 4]));
ok('แก้ไขหนังสือของคนอื่นไม่ได้', $res['__status'], 403);
$res = sms_call(new \Edocument\Outbox\Controller(), 'action', sms_request(2, 'POST', ['action' => 'edit', 'id' => 3]));
ok('แก้ไขหนังสือของตัวเอง', sms_action($res, 'redirect')['url'] ?? null, '/edocument-write?id=3');

echo "edocument: ประวัติการดาวน์โหลด\n";
$res = sms_call(new \Edocument\Downloads\Controller(), 'index', sms_request(2, 'GET', ['id' => 3, 'sort' => 'name asc']));
$rows = array_column($res['data']['data'] ?? [], null, 'name');
ok('รายชื่อผู้รับ (3 จากระบบเดิม + 6)', array_keys($rows) == [$names[3], $names[6]] || array_keys($rows) == [$names[6], $names[3]]);
ok('จำนวนครั้งและวันที่', [$rows[$names[6]]['downloads'] ?? null, ($rows[$names[6]]['date'] ?? '') !== ''], [2, true]);
ok('หัวของหน้า', $res['data']['options']['document']['document_no'] ?? null, 'DOC-0003');
$res = sms_call(new \Edocument\Downloads\Controller(), 'index', sms_request(3, 'GET', ['id' => 3]));
ok('ผู้ส่งคนอื่นดูไม่ได้', $res['__status'], 403);
$res = sms_call(new \Edocument\Downloads\Controller(), 'index', sms_request(4, 'GET', ['id' => 3]));
ok('ผู้จัดการงานสารบรรณดูได้', $res['success'] ?? false);
$res = sms_call(new \Edocument\Downloads\Controller(), 'index', sms_request(2, 'GET', ['id' => 999]));
ok('ไม่พบหนังสือ', $res['__status'], 404);

echo "edocument: ส่งหนังสือ\n";
$pdf = tempnam(sys_get_temp_dir(), 'edoc');
file_put_contents($pdf, "%PDF-1.4 test\n");
$res = sms_call(new \Edocument\Document\Controller(), 'save', sms_request(6, 'POST', ['id' => 0, 'detail' => 'x', 'receiver' => [2]], ['file' => ['path' => $pdf, 'name' => 'ทดสอบ.pdf']]));
ok('ไม่มีสิทธิ์ส่ง', $res['__status'], 403);
$res = sms_call(new \Edocument\Document\Controller(), 'get', sms_request(2, 'GET', ['id' => 0]));
ok('ฟอร์มใหม่: ผู้รับตั้งต้นทุกสถานะยกเว้นนักเรียน', array_column(sms_data($res)['receivers'] ?? [], 'value'), ['1', '2', '3']);
$res = sms_call(new \Edocument\Document\Controller(), 'save', sms_request(2, 'POST', ['id' => 0, 'detail' => 'รายละเอียด', 'urgency' => 1, 'receiver' => [2, 3, 0]], ['file' => ['path' => $pdf, 'name' => 'คำสั่งทดสอบ.pdf']]));
ok('บันทึกแล้วกลับไปเอกสารส่ง', sms_action($res, 'redirect')['url'] ?? null, '/edocument-sent');
$row = sms_row('SELECT * FROM {prefix}_edocument ORDER BY id DESC LIMIT 1');
ok('ไม่กรอกชื่อเรื่องใช้ชื่อไฟล์ · ผู้รับตัดนักเรียนออก · ออกเลขอัตโนมัติ', [$row['topic'], $row['receiver'], $row['ext'], $row['document_no'] !== ''], ['คำสั่งทดสอบ', ',2,3,', 'pdf', true]);
ok('ไฟล์ถูกเก็บ', is_file(ROOT_PATH.DATA_FOLDER.'edocument/'.$row['file']));

echo "edocument: ตั้งค่า\n";
$res = sms_call(new \Edocument\Settings\Controller(), 'get', sms_request(2, 'GET'));
ok('ไม่ใช่ผู้ดูแลเปิดไม่ได้', $res['__status'], 403);
$res = sms_call(new \Edocument\Settings\Controller(), 'get', sms_request(1, 'GET'));
ok('อ่านค่าตั้งค่า', [sms_data($res)['edocument_file_typies'] ?? null, isset($res['data']['options']['edocument_upload_size'])], [implode(',', (array) \Kotchasan\Config::create()->edocument_file_typies), true]);
$res = sms_call(new \Edocument\Settings\Controller(), 'save', sms_request(1, 'POST', ['edocument_file_typies' => 'pdf,docxxx', 'edocument_upload_size' => 1048576]));
ok('ชนิดไฟล์ยาวเกิน 4 ตัวอักษร', isset($res['data']['errors']['edocument_file_typies']) || isset($res['errors']['edocument_file_typies']));
$res = sms_call(new \Edocument\Settings\Controller(), 'save', sms_request(1, 'POST', [
    'edocument_prefix' => 'ที่ ศธ%Y-',
    'edocument_format_no' => '%05d',
    'edocument_file_typies' => 'PDF, docx,jpg',
    'edocument_upload_size' => 1048576,
    'edocument_download_action' => 1
]));
ok('บันทึกสำเร็จ', $res['success'] ?? false);
$conf = include ROOT_PATH.'settings/config.php';
ok('ค่าที่บันทึก', [$conf['edocument_prefix'], $conf['edocument_format_no'], $conf['edocument_file_typies'], $conf['edocument_upload_size'], $conf['edocument_download_action'], $conf['edocument_send_mail']], ['ที่ ศธ%Y-', '%05d', ['pdf', 'docx', 'jpg'], 1048576, 1, 0]);

summary();
