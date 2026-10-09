<?php
/**
 * ชุดทดสอบโมดูล personnel — รันผ่าน modules/school/tests/run.php
 * ข้อมูลตั้งต้นมาจาก modules/school/tests/fixtures/legacy_*.sql ที่ผ่านตัวปรับรุ่นจริงแล้ว
 *
 *   1 แอดมิน · 2 ครู (can_teacher) · 3 ครู (can_manage_course) · 4 ผอ. (can_manage_personnel)
 *   5 ครูเกษียณ (active 0) · 6 ครู (can_rate_student) · 10-14 นักเรียน
 */
require getenv('SMS_BOOT');

echo "personnel: ตัวปรับรุ่น\n";
$p2 = sms_row('SELECT * FROM {prefix}_personnel WHERE id = 2');
ok('custom ของระบบเดิมถูกแปลงเป็น JSON', json_decode($p2['custom'], true), ['address' => '123 หมู่ 1 ต.ในเมือง']);
ok('แถวที่ไม่มี custom ยังเป็น NULL', sms_row('SELECT custom FROM {prefix}_personnel WHERE id = 3')['custom'], null);
ok('personnel ครบ 5 แถว', (int) sms_row('SELECT COUNT(*) c FROM {prefix}_personnel')['c'], 5);
$idx = array_column(sms_rows("SHOW INDEX FROM {prefix}_personnel"), 'Key_name');
ok('มีดัชนี position', in_array('position', $idx, true));

echo "personnel: หมวดหมู่หลายภาษา\n";
$cat = \Personnel\Category\Model::init();
ok('ตำแหน่ง id 1 เป็นภาษาไทย', $cat->get('position', 1), 'ผู้อำนวยการโรงเรียน');
ok('แผนก id 2', $cat->get('department', 2), 'คณิตศาสตร์');
ok('ตัวเลือกตำแหน่ง 3 รายการ', count($cat->toOptions('position')), 3);
$res = sms_call(new \Personnel\Categories\Controller(), 'get', sms_request(1, 'GET', ['type' => 'position']));
$rows = $res['data']['data']['options']['data'] ?? [];
$row1 = array_values(array_filter($rows, fn($r) => (string) $r['id'] === '1'))[0] ?? [];
ok('หน้าแก้หมวดหมู่ ช่อง th ได้ภาษาไทย', $row1['th'] ?? null, 'ผู้อำนวยการโรงเรียน');
ok('หน้าแก้หมวดหมู่ ช่อง en ได้ภาษาอังกฤษ (แก้บั๊กของแกน)', $row1['en'] ?? null, 'Director');
$res = sms_call(new \Personnel\Categories\Controller(), 'get', sms_request(2, 'GET', ['type' => 'position']));
ok('ครูทั่วไปเปิดหน้าหมวดหมู่ไม่ได้', $res['__status'], 403);

echo "personnel: ตารางรายชื่อ (สมาชิกทุกคน)\n";
$res = sms_call(new \Personnel\Lists\Controller(), 'index', sms_request(10, 'GET', ['sort' => 'position asc,order asc', 'pageSize' => 30]));
$data = $res['data']['data'] ?? [];
ok('นักเรียนเห็นรายชื่อ', $res['success'] ?? false);
ok('เห็นเฉพาะบุคลากรปัจจุบัน (ไม่มี id 5)', array_map('intval', array_column($data, 'id')), [4, 2, 3, 6]);
ok('ไม่มีตัวกรองสถานะ', isset($res['data']['filters']['active']), false);
ok('มีตัวกรองตำแหน่ง', count($res['data']['filters']['position'] ?? []), 3);
ok('ครูประจำชั้นของ id 2', array_values(array_filter($data, fn($r) => $r['id'] == 2))[0]['class_teacher'] ?? null, 'มัธยมศึกษาปีที่ 1 1/1');
ok('นักเรียนแก้ไขใครไม่ได้', array_sum(array_column($data, 'can_edit')), 0);
$res = sms_call(new \Personnel\Lists\Controller(), 'index', sms_request(2, 'GET', ['pageSize' => 30]));
$mine = array_values(array_filter($res['data']['data'], fn($r) => $r['can_edit'] == 1));
ok('ครูแก้ไขได้เฉพาะของตัวเอง', array_map('intval', array_column($mine, 'id')), [2]);
$res = sms_call(new \Personnel\Lists\Controller(), 'index', sms_request(10, 'GET', ['position' => 1]));
ok('กรองตำแหน่ง', array_map('intval', array_column($res['data']['data'], 'id')), [4]);
$res = sms_call(new \Personnel\Lists\Controller(), 'index', sms_request(10, 'GET', ['search' => 'สมหญิง']));
ok('ค้นหาชื่อ', array_map('intval', array_column($res['data']['data'], 'id')), [3]);

echo "personnel: ตารางจัดการ\n";
$res = sms_call(new \Personnel\Setup\Controller(), 'index', sms_request(10, 'GET'));
ok('นักเรียนเปิดหน้าจัดการไม่ได้', $res['__status'], 403);
$res = sms_call(new \Personnel\Setup\Controller(), 'index', sms_request(4, 'GET', ['sort' => 'id desc', 'pageSize' => 30]));
ok('ผอ. เห็นทุกคนรวมคนที่ปิดแล้ว', array_map('intval', array_column($res['data']['data'], 'id')), [6, 5, 4, 3, 2]);
ok('มีตัวกรองสถานะ', count($res['data']['filters']['active'] ?? []), 2);
$res = sms_call(new \Personnel\Setup\Controller(), 'index', sms_request(4, 'GET', ['active' => 0]));
ok('กรองบุคลากรในอดีต', array_map('intval', array_column($res['data']['data'], 'id')), [5]);
$res = sms_call(new \Personnel\Setup\Controller(), 'index', sms_request(1, 'GET', ['sort' => 'order desc']));
ok('เรียงตาม order (คำสงวน)', (int) ($res['data']['data'][0]['id'] ?? 0), 6);

echo "personnel: รายละเอียด (modal)\n";
$res = sms_call(new \Personnel\Lists\Controller(), 'action', sms_request(10, 'POST', ['action' => 'view', 'id' => 2]));
ok('เปิด modal', sms_action($res, 'modal')['template'] ?? null, 'personnel/view.html');
ok('นักเรียนไม่เห็นเลขประชาชน', $res['data']['data']['id_card'] ?? null, '');
ok('นักเรียนไม่เห็นข้อมูลเพิ่มเติม', $res['data']['data']['details'] ?? null, []);
$res = sms_call(new \Personnel\Setup\Controller(), 'action', sms_request(4, 'POST', ['action' => 'view', 'id' => 2]));
ok('ผู้จัดการเห็นเลขประชาชน', $res['data']['data']['id_card'] ?? null, '1100000000001');
ok('ผู้จัดการเห็นที่อยู่', $res['data']['data']['details'][0]['value'] ?? null, '123 หมู่ 1 ต.ในเมือง');

echo "personnel: ฟอร์ม\n";
$res = sms_call(new \Personnel\Person\Controller(), 'get', sms_request(2, 'GET', ['id' => 2]));
ok('ครูเปิดฟอร์มของตัวเอง', $res['data']['data']['name'] ?? null, 'ครูสมชาย ใจดี');
ok('ครูไม่ใช่แอดมิน ไม่เห็นช่องสิทธิ์', $res['data']['data']['isAdmin'] ?? null, false);
ok('ช่องข้อมูลเพิ่มเติม', $res['data']['data']['details'][0]['name'] ?? null, 'custom[address]');
ok('ตัวเลือกตำแหน่งมี "กรุณาเลือก"', $res['data']['options']['position'][0]['value'] ?? null, '0');
$res = sms_call(new \Personnel\Person\Controller(), 'get', sms_request(2, 'GET', ['id' => 3]));
ok('ครูเปิดฟอร์มของคนอื่นไม่ได้', $res['__status'], 403);
$res = sms_call(new \Personnel\Person\Controller(), 'get', sms_request(1, 'GET', ['id' => 2]));
ok('แอดมินเห็นช่องสิทธิ์', $res['data']['data']['isAdmin'] ?? null, true);
ok('สิทธิ์ของครู', $res['data']['data']['permission'] ?? null, ['can_teacher', 'can_upload_edocument', 'can_rate_student']);
ok('ตัวเลือกสิทธิ์มีของโมดูล', in_array('can_manage_personnel', array_column($res['data']['options']['permission'] ?? [], 'value'), true));

$new = [
    'id' => 0,
    'name' => 'ครูใหม่ ทดสอบ',
    'id_card' => '1100000000099',
    'birthday' => '1990-02-03',
    'phone' => '0899999999',
    'order' => 5,
    'position' => 3,
    'department' => 2,
    'class' => 1,
    'room' => 2,
    'custom' => ['address' => 'ที่อยู่ใหม่']
];
$res = sms_call(new \Personnel\Person\Controller(), 'save', sms_request(2, 'POST', $new));
ok('ครูทั่วไปเพิ่มบุคลากรไม่ได้', $res['__status'], 403);
$res = sms_call(new \Personnel\Person\Controller(), 'save', sms_request(4, 'POST', $new));
ok('ผอ. เพิ่มบุคลากร', $res['success'] ?? null, true);
$u = sms_row("SELECT * FROM {prefix}_user WHERE name = 'ครูใหม่ ทดสอบ'");
ok('สร้างสมาชิก ชื่อผู้ใช้ = เลขประชาชน', $u['username'] ?? null, '1100000000099');
ok('สถานะครู', (int) $u['status'], 2);
ok('รหัสผ่าน = วันเกิด พ.ศ.', \Index\Auth\Model::verifyPassword('25330203', $u['password'], $u['salt']));
$p = sms_row('SELECT * FROM {prefix}_personnel WHERE id = ?', [$u['id']]);
ok('บันทึก personnel', [(int) $p['position'], (int) $p['department'], (int) $p['order'], (int) $p['class'], (int) $p['room']], [3, 2, 5, 1, 2]);
ok('custom เป็น JSON', json_decode($p['custom'], true), ['address' => 'ที่อยู่ใหม่']);
$newId = (int) $u['id'];

$res = sms_call(new \Personnel\Person\Controller(), 'save', sms_request(4, 'POST', ['name' => 'ซ้ำ'] + $new));
ok('เลขประชาชนซ้ำ', isset($res['data']['errors']['id_card']) || isset($res['errors']['id_card']));
$res = sms_call(new \Personnel\Person\Controller(), 'save', sms_request(4, 'POST', ['name' => 'เบอร์ซ้ำ', 'id_card' => '', 'phone' => '0811111111'] + $new));
ok('เบอร์โทรซ้ำกับสมาชิกคนอื่น', isset($res['data']['errors']['phone']) || isset($res['errors']['phone']));
$res = sms_call(new \Personnel\Person\Controller(), 'save', sms_request(4, 'POST', ['name' => ''] + $new));
ok('ไม่กรอกชื่อ', isset($res['data']['errors']['name']) || isset($res['errors']['name']));

// แก้ไขของตัวเอง + อัปเดตรหัสผ่าน
$res = sms_call(new \Personnel\Person\Controller(), 'save', sms_request(2, 'POST', [
    'id' => 2, 'name' => 'ครูสมชาย ใจดีมาก', 'id_card' => '1100000000001', 'birthday' => '1980-05-16', 'phone' => '0811111111',
    'order' => 1, 'position' => 3, 'department' => 2, 'class' => 1, 'room' => 1, 'custom' => ['address' => 'ใหม่'],
    'updatepassword' => 1, 'permission' => ['can_config']
]));
ok('ครูแก้ไขข้อมูลตัวเอง', $res['success'] ?? null, true);
$u2 = sms_row('SELECT * FROM {prefix}_user WHERE id = 2');
ok('เปลี่ยนชื่อ', $u2['name'], 'ครูสมชาย ใจดีมาก');
ok('รหัสผ่านใหม่จากวันเกิด', \Index\Auth\Model::verifyPassword('25230516', $u2['password'], $u2['salt']));
ok('ครูแก้สิทธิ์ตัวเองไม่ได้', $u2['permission'], ',can_teacher,can_upload_edocument,can_rate_student,');
$res = sms_call(new \Personnel\Person\Controller(), 'save', sms_request(2, 'POST', ['id' => 3, 'name' => 'x'] + $new));
ok('ครูแก้ไขของคนอื่นไม่ได้', $res['__status'], 403);
// แอดมินกำหนดสิทธิ์
$res = sms_call(new \Personnel\Person\Controller(), 'save', sms_request(1, 'POST', [
    'id' => $newId, 'name' => 'ครูใหม่ ทดสอบ', 'id_card' => '1100000000099', 'birthday' => '1990-02-03', 'phone' => '0899999999',
    'order' => 5, 'position' => 3, 'department' => 2, 'class' => 1, 'room' => 2, 'permission' => ['can_teacher', 'can_rate_student']
]));
ok('แอดมินกำหนดสิทธิ์ได้', sms_row('SELECT permission FROM {prefix}_user WHERE id = ?', [$newId])['permission'], ',can_teacher,can_rate_student,');
ok('ไม่ติ๊กอัปเดตรหัสผ่าน รหัสเดิมยังใช้ได้', \Index\Auth\Model::verifyPassword('25330203', sms_row('SELECT password FROM {prefix}_user WHERE id = ?', [$newId])['password'], ''));

echo "personnel: การกระทำในตาราง\n";
$res = sms_call(new \Personnel\Setup\Controller(), 'action', sms_request(4, 'POST', ['action' => 'update', 'field' => 'order', 'ids' => [3], 'value' => 7]));
ok('แก้ลำดับ', (int) sms_row('SELECT `order` FROM {prefix}_personnel WHERE id = 3')['order'], 7);
$res = sms_call(new \Personnel\Setup\Controller(), 'action', sms_request(2, 'POST', ['action' => 'update', 'field' => 'order', 'ids' => [3], 'value' => 1]));
ok('ครูทั่วไปแก้ลำดับไม่ได้', $res['__status'], 403);
$res = sms_call(new \Personnel\Setup\Controller(), 'action', sms_request(4, 'POST', ['action' => 'active', 'id' => 3]));
ok('ปิดสถานะบุคลากร', (int) sms_row('SELECT active FROM {prefix}_user WHERE id = 3')['active'], 0);
sms_call(new \Personnel\Setup\Controller(), 'action', sms_request(4, 'POST', ['action' => 'active', 'id' => 3]));
ok('เปิดกลับ', (int) sms_row('SELECT active FROM {prefix}_user WHERE id = 3')['active'], 1);
$res = sms_call(new \Personnel\Setup\Controller(), 'action', sms_request(4, 'POST', ['action' => 'active', 'id' => 4]));
ok('ปิดสถานะตัวเองไม่ได้', $res['__status'], 403);
$res = sms_call(new \Personnel\Setup\Controller(), 'action', sms_request(4, 'POST', ['action' => 'edit', 'id' => 3]));
ok('ปุ่มแก้ไขพาไปหน้าฟอร์ม', sms_action($res, 'redirect')['url'] ?? null, '/personnel-edit?id=3');
$res = sms_call(new \Personnel\Lists\Controller(), 'action', sms_request(10, 'POST', ['action' => 'edit', 'id' => 3]));
ok('นักเรียนกดแก้ไขคนอื่นไม่ได้', $res['__status'], 403);
$res = sms_call(new \Personnel\Setup\Controller(), 'action', sms_request(4, 'POST', ['action' => 'delete', 'ids' => [5, 1]]));
ok('ลบบุคลากร', sms_row('SELECT COUNT(*) c FROM {prefix}_personnel WHERE id = 5')['c'] + sms_row('SELECT COUNT(*) c FROM {prefix}_user WHERE id = 5')['c'], 0);
ok('ลบ id 1 ไม่ได้', (int) sms_row('SELECT COUNT(*) c FROM {prefix}_user WHERE id = 1')['c'], 1);

echo "personnel: ตั้งค่า\n";
$res = sms_call(new \Personnel\Settings\Controller(), 'get', sms_request(2, 'GET'));
ok('ครูเปิดหน้าตั้งค่าไม่ได้', $res['__status'], 403);
$res = sms_call(new \Personnel\Settings\Controller(), 'save', sms_request(1, 'POST', ['personnel_w' => 50, 'personnel_h' => 300]));
$conf = include ROOT_PATH.'settings/config.php';
ok('บันทึกขนาดรูป (ต่ำสุด 100)', [$conf['personnel_w'], $conf['personnel_h']], [100, 300]);

echo "personnel: นำเข้า CSV\n";
$header = \Personnel\Import\Model::header();
ok('หัวคอลัมน์เหมือนระบบเดิม', $header, ['ชื่อ นามสกุล *', 'เลขประชาชน **', 'วันเกิด', 'โทรศัพท์', 'ตำแหน่ง', 'แผนก', 'ชั้น', 'ห้องเรียน', 'ที่อยู่']);
$csv = tempnam(sys_get_temp_dir(), 'csv');
$f = fopen($csv, 'w');
fwrite($f, "\xEF\xBB\xBF");
fputcsv($f, $header, ',', '"', '\\');
fputcsv($f, ['นำเข้า หนึ่ง', '1100000000201', '2530-01-15', '0877777771', 1, 2, 1, 1, 'ที่อยู่ 1'], ',', '"', '\\');
fputcsv($f, ['นำเข้า สอง', '', '', '0811111111', 3, 1, '', '', ''], ',', '"', '\\');
fputcsv($f, ['นำเข้า ซ้ำ', '1100000000001', '2530-01-15', '', 3, 1, '', '', ''], ',', '"', '\\');
fputcsv($f, ['', '1100000000202', '', '', 3, 1, '', '', ''], ',', '"', '\\');
fclose($f);
$res = sms_call(new \Personnel\Import\Controller(), 'save', sms_request(4, 'POST', [], ['import' => ['path' => $csv, 'name' => 'personnel.csv']]));
ok('นำเข้าสำเร็จ 2 รายการ', $res['success'] ?? null, true);
ok('ข้อความแจ้งจำนวน', strpos($res['message'] ?? '', '2') !== false);
$i1 = sms_row("SELECT U.*, P.position, P.department, P.custom FROM {prefix}_user U JOIN {prefix}_personnel P ON P.id = U.id WHERE U.name = 'นำเข้า หนึ่ง'");
ok('วันเกิด พ.ศ. ในไฟล์เป็น ค.ศ.', $i1['birthday'] ?? null, '1987-01-15');
ok('รหัสผ่านจากวันเกิด', \Index\Auth\Model::verifyPassword('25300115', $i1['password'], $i1['salt']));
ok('หมวดหมู่จาก ID', [(int) $i1['position'], (int) $i1['department']], [1, 2]);
$i2 = sms_row("SELECT * FROM {prefix}_user WHERE name = 'นำเข้า สอง'");
ok('เบอร์ซ้ำ นำเข้าแต่ไม่เก็บเบอร์', [$i2['phone'], $i2['username']], [null, null]);
ok('รายการซ้ำถูกข้าม', (int) sms_row("SELECT COUNT(*) c FROM {prefix}_user WHERE name = 'นำเข้า ซ้ำ'")['c'], 0);
$res = sms_call(new \Personnel\Import\Controller(), 'save', sms_request(4, 'POST', [], ['import' => ['path' => $csv, 'name' => 'personnel.csv']]));
ok('นำเข้าซ้ำ ข้ามทั้งหมด', (int) sms_row("SELECT COUNT(*) c FROM {prefix}_user WHERE name LIKE 'นำเข้า%'")['c'], 2);
$bad = tempnam(sys_get_temp_dir(), 'csv');
file_put_contents($bad, "a,b\n1,2\n");
$res = sms_call(new \Personnel\Import\Controller(), 'save', sms_request(4, 'POST', [], ['import' => ['path' => $bad, 'name' => 'x.csv']]));
ok('หัวคอลัมน์ไม่ตรง', isset($res['data']['errors']['import']) || isset($res['errors']['import']));
$res = sms_call(new \Personnel\Import\Controller(), 'save', sms_request(2, 'POST', [], ['import' => ['path' => $csv, 'name' => 'personnel.csv']]));
ok('ครูนำเข้าไม่ได้', $res['__status'], 403);

echo "personnel: เมนูและหน้าแรก\n";
$login = \Index\Auth\Model::getUserByToken(\Index\Auth\Model::generateTokens(1)['access_token']);
$menus = \Index\Menus\Controller::getMenus($login);
$urls = [];
array_walk_recursive($menus, function ($v, $k) use (&$urls) {
    if ($k === 'url') {
        $urls[] = $v;
    }
});
ok('แอดมินเห็นเมนูจัดการบุคลากร', in_array('/personnel-setup', $urls, true));
ok('หมวดหมู่ตำแหน่ง', in_array('/personnel-categories?type=position', $urls, true));
ok('แผนกใช้หน้าหลายภาษาของโมดูลแทนของแกน', [in_array('/personnel-categories?type=department', $urls, true), in_array('/categories?type=department', $urls, true)], [true, false]);
ok('นำเข้า', in_array('/personnel-import', $urls, true));
$student = \Index\Auth\Model::getUserByToken(\Index\Auth\Model::generateTokens(10)['access_token']);
$menus = \Index\Menus\Controller::getMenus($student);
$urls = [];
array_walk_recursive($menus, function ($v, $k) use (&$urls) {
    if ($k === 'url') {
        $urls[] = $v;
    }
});
ok('นักเรียนเห็นรายชื่อบุคลากร', in_array('/personnel', $urls, true));
$cards = \Personnel\Init\Controller::initDashboardCards([], null, $student);
$teachers = sms_row('SELECT COUNT(*) c FROM {prefix}_personnel P JOIN {prefix}_user U ON U.id = P.id WHERE U.active = 1 AND U.status = 2')['c'];
ok('การ์ดจำนวนครู', $cards[0]['value'] ?? null, (string) $teachers);

summary();
