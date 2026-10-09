<?php
/**
 * ชุดทดสอบโมดูล school — รันผ่าน modules/school/tests/run.php
 * ข้อมูลตั้งต้นมาจาก fixtures/legacy_*.sql ที่ผ่านตัวปรับรุ่นจริงแล้ว
 *
 *   1 แอดมิน · 2 ครู (can_teacher สอนวิชา 50, 53) · 3 ครู (can_manage_course + can_manage_student สอนวิชา 51)
 *   4 ผอ. · 6 ครู (can_rate_student อย่างเดียว)
 *   10-13 นักเรียนกำลังศึกษา · 14 จบการศึกษาแล้ว
 *   รายวิชา 50 ค21101 2567/1 (ครู 2) · 51 ท21101 2567/1 (ครู 3) · 52 ว21101 ต้นแบบ · 53 ค21101 2566/2 (ครู 2)
 *   ผลการเรียน 1:(10,50) 2:(11,50,ร.) 3:(10,51) 4:(13,50) 5:(10,53)
 */
require getenv('SMS_BOOT');

$ids = fn($res) => array_map('intval', array_column($res['data']['data'] ?? [], 'id'));
$err = fn($res, $field) => isset($res['data']['errors'][$field]) || isset($res['errors'][$field]);

echo "school: ตัวปรับรุ่น\n";
$col = sms_row("SHOW COLUMNS FROM {prefix}_course LIKE 'id'");
ok('course.id เป็น AUTO_INCREMENT', strpos($col['Extra'], 'auto_increment') !== false);
$idx = array_unique(array_column(sms_rows('SHOW INDEX FROM {prefix}_grade'), 'Key_name'));
ok('grade มีดัชนี student_id', in_array('student_id', $idx, true));
$idx = array_unique(array_column(sms_rows('SHOW INDEX FROM {prefix}_student'), 'Key_name'));
ok('student มีดัชนี class', in_array('class', $idx, true));
ok('ผลการเรียนเดิมครบ', (int) sms_row('SELECT COUNT(*) c FROM {prefix}_grade')['c'], 5);
$conf = include ROOT_PATH.'settings/config.php';
ok('ค่ากำหนดเดิมไม่ถูกทับ (ขนาดรูปนักเรียน)', [$conf['student_w'], $conf['student_h']], [200, 250]);
ok('เกณฑ์เกรดเดิมยังอยู่', count($conf['school_grade_caculations']), 8);

echo "school: หมวดหมู่\n";
$cat = \School\Category\Model::init();
ok('ชนิดหมวดหมู่ (SCHOOL_CATEGORY + ภาคเรียน)', $cat->typies(), ['department', 'class', 'room', 'term']);
ok('ชั้น 1', $cat->get('class', 1), 'มัธยมศึกษาปีที่ 1');
ok('ห้อง 2', $cat->get('room', 2), '1/2');
$res = sms_call(new \School\Categories\Controller(), 'get', sms_request(1, 'GET', ['type' => 'term']));
$row = array_values(array_filter($res['data']['data']['options']['data'] ?? [], fn($r) => (string) $r['id'] === '1'))[0] ?? [];
ok('หน้าแก้ภาคเรียน หลายภาษา', [$row['th'] ?? null, $row['en'] ?? null], ['เทอม 1', 'Term 1']);
$res = sms_call(new \School\Categories\Controller(), 'save', sms_request(1, 'POST', ['type' => 'term', 'id' => ['1', '2', '3'], 'th' => ['เทอม 1', 'เทอม 2', 'ฤดูร้อน'], 'en' => ['Term 1', 'Term 2', 'Summer']]));
ok('บันทึกภาคเรียน 3 ภาคพร้อมคำแปล', (int) sms_row("SELECT COUNT(*) c FROM {prefix}_category WHERE type = 'term'")['c'], 6);

echo "school: รายชื่อนักเรียน\n";
$res = sms_call(new \School\Students\Controller(), 'index', sms_request(10, 'GET'));
ok('นักเรียนเปิดรายชื่อไม่ได้', $res['__status'], 403);
$res = sms_call(new \School\Students\Controller(), 'index', sms_request(6, 'GET', ['sort' => 'id asc']));
ok('ผู้ให้คะแนนเห็นรายชื่อ', $ids($res), [10, 11, 12, 13]);
ok('ผู้ให้คะแนนแก้ไขไม่ได้', array_sum(array_column($res['data']['data'], 'can_edit')), 0);
ok('ผู้ให้คะแนนไม่มีปุ่มทำกับรายการที่เลือก', [$res['data']['options']['_table']['showCheckbox'] ?? null, $res['data']['options']['_table']['actions'] ?? null], [false, []]);
$res = sms_call(new \School\Students\Controller(), 'index', sms_request(2, 'GET', ['sort' => 'id asc']));
ok('ครูแก้ไขได้', array_sum(array_column($res['data']['data'], 'can_edit')), 4);
$actions = $res['data']['options']['_table']['actions'] ?? [];
ok('ย้ายชั้น/ห้อง/แผนก', [isset($actions['class_2']), isset($actions['room_1']), isset($actions['department_1']), isset($actions['graduate'])], [true, true, true, true]);
ok('ลิงก์รายงานผลการเรียน', $res['data']['data'][0]['report_url'] ?? null, '/school-grade?id=10&year=2567&term=1');
ok('ตัวกรองสถานะ', array_column($res['data']['filters']['active'] ?? [], 'value'), ['1', '0']);
$res = sms_call(new \School\Students\Controller(), 'index', sms_request(2, 'GET', ['active' => 0]));
ok('นักเรียนที่จบการศึกษา', $ids($res), [14]);
$res = sms_call(new \School\Students\Controller(), 'index', sms_request(2, 'GET', ['class' => 1, 'room' => 2]));
ok('กรองชั้น/ห้อง', $ids($res), [13]);
$res = sms_call(new \School\Students\Controller(), 'index', sms_request(2, 'GET', ['search' => '1002']));
ok('ค้นหารหัสนักเรียน', $ids($res), [11]);
$res = sms_call(new \School\Students\Controller(), 'index', sms_request(2, 'GET', ['search' => 'สาม']));
ok('ค้นหาชื่อ', $ids($res), [12]);

$res = sms_call(new \School\Students\Controller(), 'action', sms_request(2, 'POST', ['action' => 'class_2', 'ids' => [12]]));
ok('ย้ายชั้น', (int) sms_row('SELECT class FROM {prefix}_student WHERE id = 12')['class'], 2);
$res = sms_call(new \School\Students\Controller(), 'action', sms_request(6, 'POST', ['action' => 'room_2', 'ids' => [12]]));
ok('ผู้ให้คะแนนย้ายห้องไม่ได้', $res['__status'], 403);
sms_call(new \School\Students\Controller(), 'action', sms_request(2, 'POST', ['action' => 'graduate', 'ids' => [12]]));
ok('จบการศึกษา', (int) sms_row('SELECT active FROM {prefix}_user WHERE id = 12')['active'], 0);
sms_call(new \School\Students\Controller(), 'action', sms_request(2, 'POST', ['action' => 'studying', 'ids' => [12]]));
ok('กลับมากำลังศึกษา', (int) sms_row('SELECT active FROM {prefix}_user WHERE id = 12')['active'], 1);
sms_call(new \School\Students\Controller(), 'action', sms_request(2, 'POST', ['action' => 'graduate', 'ids' => [2]]));
ok('จบการศึกษาเปลี่ยนเฉพาะบัญชีนักเรียน', (int) sms_row('SELECT active FROM {prefix}_user WHERE id = 2')['active'], 1);
sms_call(new \School\Students\Controller(), 'action', sms_request(2, 'POST', ['action' => 'update', 'field' => 'number', 'ids' => [11], 'value' => 9]));
ok('แก้เลขที่ในตาราง', (int) sms_row('SELECT number FROM {prefix}_student WHERE id = 11')['number'], 9);
$res = sms_call(new \School\Students\Controller(), 'action', sms_request(2, 'POST', ['action' => 'view', 'id' => 10]));
ok('modal รายละเอียดนักเรียน', sms_action($res, 'modal')['template'] ?? null, 'school/studentview.html');
ok('ครูไม่เห็นเลขประชาชน', $res['data']['data']['id_card'] ?? null, '');
ok('ชื่อผู้ปกครอง', $res['data']['data']['parent'] ?? null, 'นายพ่อ หนึ่ง');
$res = sms_call(new \School\Student\Controller(), 'view', sms_request(1, 'GET', ['id' => 10]));
ok('ผู้ตั้งค่าระบบเห็นเลขประชาชน', $res['data']['data']['id_card'] ?? null, '1200000000010');

echo "school: ฟอร์มนักเรียน\n";
$res = sms_call(new \School\Student\Controller(), 'get', sms_request(3, 'GET', ['id' => 0, 'class' => 2]));
ok('รหัสนักเรียนถัดไป', $res['data']['data']['student_id'] ?? null, '1005');
ok('ชั้นจากตัวกรอง', $res['data']['data']['class'] ?? null, '2');
$new = ['id' => 0, 'name' => 'ด.ญ.ใหม่ ทดสอบ', 'student_id' => '1005', 'id_card' => '1200000000099', 'birthday' => '2008-08-09', 'sex' => 'f',
    'phone' => '', 'address' => 'บ้านใหม่', 'parent' => 'แม่ใหม่', 'parent_phone' => '0890000000', 'department' => 1, 'class' => 2, 'room' => 4];
$res = sms_call(new \School\Student\Controller(), 'save', sms_request(6, 'POST', $new));
ok('ผู้ให้คะแนนเพิ่มนักเรียนไม่ได้', $res['__status'], 403);
$res = sms_call(new \School\Student\Controller(), 'save', sms_request(3, 'POST', $new));
ok('เพิ่มนักเรียน', $res['success'] ?? null, true);
$u = sms_row("SELECT U.*, S.class, S.room, S.student_id sid FROM {prefix}_user U JOIN {prefix}_student S ON S.id = U.id WHERE U.name = 'ด.ญ.ใหม่ ทดสอบ'");
ok('สถานะนักเรียน', (int) $u['status'], 0);
ok('ชื่อผู้ใช้ = เลขประชาชน', $u['username'], '1200000000099');
ok('รหัสผ่าน = วันเกิด พ.ศ.', \Index\Auth\Model::verifyPassword('25510809', $u['password'], $u['salt']));
ok('ชั้น/ห้อง/รหัส', [(int) $u['class'], (int) $u['room'], $u['sid']], [2, 4, '1005']);
$res = sms_call(new \School\Student\Controller(), 'save', sms_request(3, 'POST', ['name' => 'ซ้ำ', 'id_card' => ''] + $new));
ok('รหัสนักเรียนซ้ำ', $err($res, 'student_id'));
$res = sms_call(new \School\Student\Controller(), 'get', sms_request(11, 'GET', ['id' => 11]));
ok('นักเรียนเปิดข้อมูลตัวเอง', $res['data']['data']['is_self'] ?? null, true);
$res = sms_call(new \School\Student\Controller(), 'get', sms_request(11, 'GET', ['id' => 10]));
ok('นักเรียนเปิดข้อมูลคนอื่นไม่ได้', $res['__status'], 403);
$res = sms_call(new \School\Student\Controller(), 'save', sms_request(11, 'POST', [
    'id' => 11, 'name' => 'ด.ญ.สอง ขยันมาก', 'student_id' => '9999', 'id_card' => '1200000000011', 'birthday' => '2007-03-04', 'sex' => 'f',
    'phone' => '0900000011', 'address' => 'ที่อยู่ใหม่', 'parent' => 'แม่สอง', 'parent_phone' => '0892222222', 'class' => 6, 'room' => 8
]));
ok('นักเรียนแก้ข้อมูลตัวเอง', $res['success'] ?? null, true);
$s11 = sms_row('SELECT S.*, U.name FROM {prefix}_student S JOIN {prefix}_user U ON U.id = S.id WHERE S.id = 11');
ok('แก้ชื่อ/ที่อยู่ได้', [$s11['name'], $s11['address']], ['ด.ญ.สอง ขยันมาก', 'ที่อยู่ใหม่']);
ok('แก้รหัสนักเรียน/ชั้น/ห้องของตัวเองไม่ได้', [$s11['student_id'], (int) $s11['class'], (int) $s11['room']], ['1002', 1, 1]);

echo "school: ลบนักเรียน\n";
$res = sms_call(new \School\Students\Controller(), 'action', sms_request(2, 'POST', ['action' => 'delete', 'ids' => [10, 12]]));
ok('ลบได้เฉพาะคนที่ยังไม่มีผลการเรียน', [(int) sms_row('SELECT COUNT(*) c FROM {prefix}_student WHERE id = 10')['c'], (int) sms_row('SELECT COUNT(*) c FROM {prefix}_user WHERE id = 12')['c']], [1, 0]);
ok('แจ้งว่าลบไม่ได้ 1 รายการ', strpos($res['message'] ?? '', '1') !== false);

echo "school: รายวิชา\n";
$res = sms_call(new \School\Courses\Controller(), 'index', sms_request(2, 'GET', ['sort' => 'id asc']));
ok('ครูเห็นเฉพาะรายวิชาของตัวเอง ปีปัจจุบัน', $ids($res), [50]);
ok('จำนวนนักเรียนในรายวิชา', (int) ($res['data']['data'][0]['student'] ?? 0), 3);
ok('ปีการศึกษา/ภาคเรียน', $res['data']['data'][0]['year_text'] ?? null, '2567/1');
$res = sms_call(new \School\Courses\Controller(), 'index', sms_request(2, 'GET', ['year' => 0, 'term' => 0, 'sort' => 'id asc']));
ok('ทุกปีการศึกษา', $ids($res), [50, 53]);
$res = sms_call(new \School\Courses\Controller(), 'index', sms_request(2, 'GET', ['teacher' => 3]));
ok('ครูดูรายวิชาของครูคนอื่นไม่ได้', $ids($res), [50]);
ok('ตัวเลือกครูมีแค่ตัวเอง', array_column($res['data']['filters']['teacher'] ?? [], 'value'), ['2']);
$res = sms_call(new \School\Courses\Controller(), 'index', sms_request(3, 'GET', ['sort' => 'id asc']));
ok('ผู้จัดการรายวิชาเห็นทุกครู', $ids($res), [50, 51]);
$res = sms_call(new \School\Courses\Controller(), 'index', sms_request(3, 'GET', ['year' => 0, 'term' => 0, 'search' => 'ว21101']));
ok('รายวิชาต้นแบบไม่มีปีการศึกษา', [$ids($res), $res['data']['data'][0]['year_text'] ?? null, $res['data']['data'][0]['has_students'] ?? null], [[52], '', 0]);
$res = sms_call(new \School\Courses\Controller(), 'action', sms_request(2, 'POST', ['action' => 'delete', 'ids' => [51]]));
ok('ครูลบรายวิชาของคนอื่นไม่ได้', (int) sms_row('SELECT COUNT(*) c FROM {prefix}_course WHERE id = 51')['c'], 1);
$res = sms_call(new \School\Courses\Controller(), 'action', sms_request(6, 'POST', ['action' => 'delete', 'ids' => [50]]));
ok('ผู้ให้คะแนนลบรายวิชาไม่ได้', $res['__status'], 403);

echo "school: ฟอร์มรายวิชา\n";
$res = sms_call(new \School\Course\Controller(), 'get', sms_request(2, 'GET', ['id' => 51]));
ok('ครูเปิดรายวิชาของคนอื่นไม่ได้', $res['__status'], 403);
$res = sms_call(new \School\Course\Controller(), 'get', sms_request(2, 'GET', ['id' => 0]));
ok('รายวิชาใหม่ของครู', [$res['data']['data']['teacher_id'] ?? null, $res['data']['data']['year'] ?? null, $res['data']['data']['is_manager'] ?? null], ['2', 2567, false]);
$res = sms_call(new \School\Course\Controller(), 'save', sms_request(2, 'POST', ['id' => 0, 'course_code' => 'ง21101', 'course_name' => 'การงาน', 'credit' => '1.0', 'period' => 40,
    'type' => 1, 'class' => 1, 'year' => 2567, 'term' => 1, 'teacher_id' => 3]));
ok('ครูเพิ่มรายวิชา', $res['success'] ?? null, true);
ok('ผู้สอนเป็นตัวเองเสมอ', (int) sms_row("SELECT teacher_id FROM {prefix}_course WHERE course_code = 'ง21101'")['teacher_id'], 2);
$res = sms_call(new \School\Course\Controller(), 'save', sms_request(2, 'POST', ['id' => 0, 'course_code' => 'ง21101', 'course_name' => 'การงาน', 'credit' => '1.0',
    'type' => 1, 'class' => 1, 'year' => 2567, 'term' => 1]));
ok('รหัสวิชาซ้ำในภาคเรียนเดียวกัน', $err($res, 'course_code'));
$res = sms_call(new \School\Course\Controller(), 'save', sms_request(3, 'POST', ['id' => 0, 'course_code_text' => 'ศ21101', 'course_name' => 'ศิลปะ', 'credit' => '0.5',
    'type' => 2, 'class' => 1, 'year' => 2567, 'term' => 1, 'teacher_id' => 0]));
$c = sms_row("SELECT * FROM {prefix}_course WHERE course_code = 'ศ21101'");
ok('ไม่ระบุผู้สอน = ต้นแบบ ปี/ภาคเรียนเป็น 0 (และรับรหัสที่พิมพ์เอง)', [(int) ($c['year'] ?? -1), (int) ($c['term'] ?? -1), (int) ($c['teacher_id'] ?? -1)], [0, 0, 0]);
$res = sms_call(new \School\Course\Controller(), 'save', sms_request(3, 'POST', ['id' => 0, 'course_code' => 'x', 'course_name' => '', 'credit' => 1, 'type' => 1, 'class' => 1]));
ok('ต้องกรอกชื่อวิชา', $err($res, 'course_name'));
$res = sms_call(new \School\Course\Controller(), 'find', sms_request(2, 'GET', ['q' => 'ค21']));
$found = $res['data'] ?? [];
// เรียงรายวิชาต้นแบบ (ไม่มีครู) ก่อน เหมือนระบบเดิม — ข้อมูลตัวอย่างของระบบเดิมมี ค21101 ต้นแบบอยู่
ok('ค้นรหัสวิชา', [$found[0]['value'] ?? null, $found[0]['course_name'] ?? null, $found[0]['credit'] ?? null], ['ค21101', 'คณิตศาสตร์', '4.0']);

echo "school: ผลการเรียน\n";
$res = sms_call(new \School\Grades\Controller(), 'index', sms_request(10, 'GET', ['subject' => 50]));
ok('นักเรียนเปิดหน้าผลการเรียนของรายวิชาไม่ได้', $res['__status'], 403);
$res = sms_call(new \School\Grades\Controller(), 'index', sms_request(6, 'GET', ['subject' => 50]));
ok('ผู้ให้คะแนนเปิดรายวิชาที่ไม่ได้สอนไม่ได้', $res['__status'], 403);
$res = sms_call(new \School\Grades\Controller(), 'index', sms_request(2, 'GET', ['subject' => 50, 'sort' => 'room asc,number asc']));
ok('ครูผู้สอนเห็นนักเรียนในรายวิชา', $ids($res), [1, 2, 4]);
$fields = array_column($res['data']['columns'] ?? [], 'field');
ok('มีคอลัมน์คะแนน (โหมดคำนวณ)', [in_array('midterm', $fields, true), in_array('final', $fields, true)], [true, true]);
$cols = array_column($res['data']['columns'], null, 'field');
ok('ครูแก้เลขที่/ห้อง/คะแนนได้', [$cols['number']['cellElement'] ?? null, $cols['room']['cellElement'] ?? null, $cols['midterm']['cellElement'] ?? null], ['number', 'select', 'number']);
$res = sms_call(new \School\Grades\Controller(), 'index', sms_request(3, 'GET', ['subject' => 50, 'room' => 2]));
ok('ผู้จัดการเปิดได้ทุกรายวิชา + กรองห้อง', $ids($res), [4]);
$res = sms_call(new \School\Grades\Controller(), 'info', sms_request(2, 'GET', ['subject' => 50]));
ok('หัวข้อของหน้า', strpos($res['data']['title'] ?? '', 'คณิตศาสตร์พื้นฐาน (ค21101) ปีการศึกษา 2567/1') !== false);
$res = sms_call(new \School\Grades\Controller(), 'action', sms_request(2, 'POST', ['action' => 'update', 'field' => 'midterm', 'ids' => [1], 'value' => 40]));
ok('กรอกคะแนนกลางภาค คำนวณเกรดใหม่ (40+40 = 4)', [sms_row('SELECT grade FROM {prefix}_grade WHERE id = 1')['grade'], sms_action($res, 'update')['content'] ?? null], ['4', '4']);
ok('อัปเดตเซลล์เกรดในตาราง', sms_action($res, 'update')['target'] ?? null, '#school-grade-1');
sms_call(new \School\Grades\Controller(), 'action', sms_request(2, 'POST', ['action' => 'update', 'field' => 'type', 'ids' => [1], 'value' => 2]));
ok('เลือก มส.', sms_row('SELECT grade, type FROM {prefix}_grade WHERE id = 1'), ['grade' => 'มส.', 'type' => 2]);
sms_call(new \School\Grades\Controller(), 'action', sms_request(2, 'POST', ['action' => 'update', 'field' => 'type', 'ids' => [1], 'value' => 0]));
ok('กลับเป็นเกรด คำนวณจากคะแนน', sms_row('SELECT grade FROM {prefix}_grade WHERE id = 1')['grade'], '4');
sms_call(new \School\Grades\Controller(), 'action', sms_request(2, 'POST', ['action' => 'update', 'field' => 'final', 'ids' => [1], 'value' => 150]));
ok('คะแนนเกิน 100 ถูกจำกัด', (int) sms_row('SELECT final FROM {prefix}_grade WHERE id = 1')['final'], 100);
sms_call(new \School\Grades\Controller(), 'action', sms_request(2, 'POST', ['action' => 'update', 'field' => 'final', 'ids' => [1], 'value' => 9]));
ok('49 คะแนน = 0', sms_row('SELECT grade FROM {prefix}_grade WHERE id = 1')['grade'], '0');
sms_call(new \School\Grades\Controller(), 'action', sms_request(2, 'POST', ['action' => 'update', 'field' => 'room', 'ids' => [2], 'value' => 2]));
ok('แก้ห้องในตาราง', (int) sms_row('SELECT room FROM {prefix}_grade WHERE id = 2')['room'], 2);
$res = sms_call(new \School\Grades\Controller(), 'action', sms_request(2, 'POST', ['action' => 'update', 'field' => 'midterm', 'ids' => [3], 'value' => 10]));
ok('ครูให้คะแนนรายวิชาที่ไม่ได้สอนไม่ได้', [$res['__status'], (int) sms_row('SELECT midterm FROM {prefix}_grade WHERE id = 3')['midterm']], [403, 40]);
$res = sms_call(new \School\Grades\Controller(), 'action', sms_request(10, 'POST', ['action' => 'update', 'field' => 'midterm', 'ids' => [1], 'value' => 50]));
ok('นักเรียนแก้คะแนนไม่ได้ (ระบบเดิมเปิดช่องนี้ไว้)', $res['__status'], 403);
$res = sms_call(new \School\Grades\Controller(), 'action', sms_request(2, 'POST', ['action' => 'delete', 'ids' => [4]]));
ok('ลบนักเรียนออกจากรายวิชา', (int) sms_row('SELECT COUNT(*) c FROM {prefix}_grade WHERE id = 4')['c'], 0);

echo "school: ลงทะเบียนเรียน\n";
$res = sms_call(new \School\Registrations\Controller(), 'index', sms_request(2, 'GET', ['subject' => 51]));
ok('ครูลงทะเบียนรายวิชาที่ไม่ได้สอนไม่ได้', $res['__status'], 403);
$res = sms_call(new \School\Registrations\Controller(), 'index', sms_request(2, 'GET', ['subject' => 50, 'class' => 1, 'sort' => 'id asc']));
$reg = array_column($res['data']['data'] ?? [], 'registered', 'id');
ok('สถานะลงทะเบียน', [$reg[10] ?? null, $reg[11] ?? null, $reg[13] ?? null], [1, 1, 0]);
$res = sms_call(new \School\Registrations\Controller(), 'action', sms_request(2, 'POST', ['action' => 'register', 'subject' => 50, 'ids' => [11, 13]]));
$g = sms_row('SELECT * FROM {prefix}_grade WHERE course_id = 50 AND student_id = 13');
ok('ลงทะเบียนเฉพาะคนที่ยังไม่ได้ลง พร้อมเลขที่/ห้องของนักเรียน', [(int) sms_row('SELECT COUNT(*) c FROM {prefix}_grade WHERE course_id = 50 AND student_id = 11')['c'], (int) $g['room'], (int) $g['number']], [1, 2, 1]);

echo "school: รายงานผลการเรียน\n";
$res = sms_call(new \School\Transcript\Controller(), 'index', sms_request(11, 'GET', ['id' => 10]));
ok('นักเรียนดูผลการเรียนของคนอื่นไม่ได้', $res['__status'], 403);
$res = sms_call(new \School\Transcript\Controller(), 'index', sms_request(10, 'GET', ['id' => 10, 'sort' => 'type asc,course_code asc']));
$rows = $res['data']['data'] ?? [];
ok('ผลการเรียนภาคเรียนปัจจุบัน + แถวสรุป', array_column($rows, 'course_code'), ['ค21101', 'ท21101', '']);
$last = end($rows);
ok('หน่วยกิตรวมและเกรดเฉลี่ย ((0*1.5 + 4*1.5)/3)', [$last['credit'] ?? null, $last['grade'] ?? null, $last['is_summary'] ?? null], [3, '2.00', 1]);
ok('ปีการศึกษาที่มีผลการเรียน', array_column($res['data']['filters']['year'] ?? [], 'value'), ['2566', '2567']);
$res = sms_call(new \School\Transcript\Controller(), 'index', sms_request(2, 'GET', ['id' => 10, 'year' => 2566, 'term' => 2]));
ok('ภาคเรียนก่อน', array_column($res['data']['data'] ?? [], 'grade'), ['1', '1.00']);
$csv = sms_csv(sms_subprocess('\School\Transcript\Controller', 'export', 10, ['id' => 10, 'type' => 'csv']));
ok('CSV ผลการเรียน', [$csv[0] ?? null, $csv[3][0] ?? null, end($csv)[0] ?? null], [['ชื่อ นามสกุล', 'ด.ช.หนึ่ง เรียนดี'], 'รหัสวิชา', 'คะแนนเฉลี่ยที่ได้ในภาคเรียนนี้']);
$html = sms_subprocess('\School\Transcript\Controller', 'export', 10, ['id' => 10, 'type' => 'print']);
ok('หน้าพิมพ์แบบรายงาน', [strpos($html, 'แบบรายงานประจำตัวนักเรียน') !== false, strpos($html, 'คณิตศาสตร์พื้นฐาน') !== false, strpos($html, 'school-transcript') !== false], [true, true, true]);

echo "school: ดาวน์โหลด\n";
$csv = sms_csv(sms_subprocess('\School\Students\Controller', 'export', 2, ['type' => 'csv', 'class' => 1]));
ok('CSV รายชื่อนักเรียน', [$csv[0][0] ?? null, $csv[0][2] ?? null, count($csv) > 1], ['เลขที่', 'ชื่อ นามสกุล', true]);
$csv = sms_csv(sms_subprocess('\School\Courses\Controller', 'export', 3, ['type' => 'csv', 'year' => 2567, 'term' => 1]));
ok('CSV รายวิชา (หัวเดียวกับไฟล์นำเข้า)', $csv[0] ?? null, \School\Csv\Model::course());
$csv = sms_csv(sms_subprocess('\School\Grades\Controller', 'export', 2, ['type' => 'csv', 'subject' => 50]));
ok('CSV ผลการเรียนของรายวิชา', [$csv[0][3] ?? null, count($csv)], ['รหัสวิชา', 4]);
$csv = sms_csv(sms_subprocess('\School\Import\Controller', 'sample', 3, ['type' => 'student', 'department' => 1, 'class' => 2, 'room' => 4]));
ok('ไฟล์ตัวอย่างนักเรียน เติมหมวดหมู่ที่เลือก', array_slice($csv[1] ?? [], -3), ['1', '2', '4']);
$csv = sms_csv(sms_subprocess('\School\Import\Controller', 'sample', 2, ['type' => 'grade', 'course' => 'ค21101', 'room' => 1, 'year' => 2567, 'term' => 1]));
ok('ไฟล์ตัวอย่างผลการเรียน มีรายชื่อนักเรียนในห้อง', array_column(array_slice($csv, 1), 2), ['1001', '1002']);

echo "school: นำเข้า\n";
$tmp = tempnam(sys_get_temp_dir(), 'csv');
$f = fopen($tmp, 'w');
fputcsv($f, \School\Csv\Model::student(), ',', '"', '\\');
fputcsv($f, [1, '2001', 'นักเรียน นำเข้าหนึ่ง', '1200000000201', '2551-01-31', '0811110001', 'm', 'ที่อยู่', 'ผู้ปกครอง', '0822220001', '', '', ''], ',', '"', '\\');
fputcsv($f, [2, '2002', 'นักเรียน นำเข้าสอง', '', '', '', 'f', '', '', '', 1, 2, 5], ',', '"', '\\');
fputcsv($f, [3, '1001', 'ซ้ำรหัส', '', '', '', 'f', '', '', '', '', '', ''], ',', '"', '\\');
fclose($f);
$res = sms_call(new \School\Import\Controller(), 'save', sms_request(2, 'POST', ['type' => 'student', 'department' => 1, 'class' => 1, 'room' => 1], ['import' => ['path' => $tmp, 'name' => 'student.csv']]));
ok('ครูนำเข้านักเรียนไม่ได้ (ต้องมีสิทธิ์จัดการนักเรียน)', $res['__status'], 403);
$res = sms_call(new \School\Import\Controller(), 'save', sms_request(3, 'POST', ['type' => 'student', 'department' => 1, 'class' => 1, 'room' => 1], ['import' => ['path' => $tmp, 'name' => 'student.csv']]));
ok('นำเข้านักเรียน 2 รายการ', [$res['success'] ?? null, strpos($res['message'] ?? '', '2') !== false], [true, true]);
$s1 = sms_row("SELECT S.*, U.username, U.password, U.salt, U.birthday FROM {prefix}_student S JOIN {prefix}_user U ON U.id = S.id WHERE S.student_id = '2001'");
ok('ช่องหมวดหมู่ว่างใช้ค่าในฟอร์ม', [(int) $s1['department'], (int) $s1['class'], (int) $s1['room']], [1, 1, 1]);
ok('วันเกิด พ.ศ. + รหัสผ่าน', [$s1['birthday'], \Index\Auth\Model::verifyPassword('25510131', $s1['password'], $s1['salt'])], ['2008-01-31', true]);
$s2 = sms_row("SELECT * FROM {prefix}_student WHERE student_id = '2002'");
ok('หมวดหมู่จากไฟล์', [(int) $s2['class'], (int) $s2['room']], [2, 5]);

$tmp = tempnam(sys_get_temp_dir(), 'csv');
$f = fopen($tmp, 'w');
fputcsv($f, \School\Csv\Model::course(), ',', '"', '\\');
fputcsv($f, ['พ21101', 'พลศึกษา', '1.0', 40, 1, 1, 2567, 1, 3], ',', '"', '\\');
fputcsv($f, ['ส21101', 'สังคม', '1.5', 60, 1, 1, '', '', ''], ',', '"', '\\');
fclose($f);
$res = sms_call(new \School\Import\Controller(), 'save', sms_request(2, 'POST', ['type' => 'course'], ['import' => ['path' => $tmp, 'name' => 'course.csv']]));
ok('ครูนำเข้ารายวิชา', $res['success'] ?? null, true);
ok('ครูนำเข้าแล้วเป็นผู้สอนเอง', (int) sms_row("SELECT teacher_id FROM {prefix}_course WHERE course_code = 'พ21101' AND year = 2567")['teacher_id'], 2);
ok('ไม่ระบุครู = ต้นแบบ', sms_row("SELECT year, term, teacher_id FROM {prefix}_course WHERE course_code = 'ส21101'"), ['year' => 0, 'term' => 0, 'teacher_id' => 0]);

$tmp = tempnam(sys_get_temp_dir(), 'csv');
$f = fopen($tmp, 'w');
fputcsv($f, \School\Csv\Model::grade(), ',', '"', '\\');
fputcsv($f, ['ค21101', 1, '1001', 30, 45, '', 1, 2567, 1], ',', '"', '\\');
fputcsv($f, ['ค21101', 2, '1002', 0, 0, 'ร.', 1, 2568, 1], ',', '"', '\\');
fputcsv($f, ['ค21101', 3, '9999', 0, 0, '', 1, 2567, 1], ',', '"', '\\');
fclose($f);
$res = sms_call(new \School\Import\Controller(), 'save', sms_request(2, 'POST', ['type' => 'grade'], ['import' => ['path' => $tmp, 'name' => 'grade.csv']]));
ok('นำเข้าผลการเรียน 2 รายการ', [$res['success'] ?? null, strpos($res['message'] ?? '', '2') !== false], [true, true]);
ok('ปรับปรุงผลการเรียนเดิม (30+45 = 3.5)', sms_row('SELECT midterm, final, grade FROM {prefix}_grade WHERE id = 1'), ['midterm' => 30, 'final' => 45, 'grade' => '3.5']);
$new = sms_row("SELECT * FROM {prefix}_course WHERE course_code = 'ค21101' AND year = 2568");
ok('สร้างรายวิชาของปีใหม่ให้ ผู้สอนคือครูที่นำเข้า', (int) ($new['teacher_id'] ?? 0), 2);
ok('ร. จากไฟล์', sms_row('SELECT type, grade FROM {prefix}_grade WHERE course_id = ? AND student_id = 11', [$new['id']]), ['type' => 1, 'grade' => 'ร.']);

echo "school: ตั้งค่า\n";
$res = sms_call(new \School\Settings\Controller(), 'get', sms_request(2, 'GET'));
ok('ครูเปิดตั้งค่าไม่ได้', $res['__status'], 403);
$res = sms_call(new \School\Settings\Controller(), 'get', sms_request(1, 'GET'));
ok('ตัวเลือกสถานะไม่มีผู้ดูแลระบบ', array_column($res['data']['options']['teacher_status'] ?? [], 'value'), ['0', '2', '3']);
sms_call(new \School\Settings\Controller(), 'save', sms_request(1, 'POST', ['school_name' => 'โรงเรียนทดสอบ', 'provinceID' => '10', 'country' => 'TH', 'student_w' => 300, 'student_h' => 400,
    'teacher_status' => 2, 'student_status' => 0, 'academic_year' => 2568, 'term' => 2, 'csv_language' => 'TIS-620']));
$conf = include ROOT_PATH.'settings/config.php';
ok('บันทึกตั้งค่าโรงเรียน', [$conf['school_name'], $conf['academic_year'], $conf['term'], $conf['csv_language'], $conf['student_w']], ['โรงเรียนทดสอบ', 2568, 2, 'TIS-620', 300]);
$res = sms_call(new \School\Gradesettings\Controller(), 'get', sms_request(1, 'GET', ['default' => 1]));
ok('เกณฑ์เกรดค่าเริ่มต้น', count($res['data']['data']['options']['data'] ?? []), 8);
sms_call(new \School\Gradesettings\Controller(), 'save', sms_request(1, 'POST', ['score' => ['1' => 100, '2' => 49], 'grade' => ['1' => 'ผ่าน', '2' => 'ไม่ผ่าน']]));
$conf = include ROOT_PATH.'settings/config.php';
ok('บันทึกเกณฑ์เกรด (เรียงคะแนน)', $conf['school_grade_caculations'], [49 => 'ไม่ผ่าน', 100 => 'ผ่าน']);
sms_call(new \School\Gradesettings\Controller(), 'save', sms_request(1, 'POST', ['score' => [], 'grade' => []]));
$conf = include ROOT_PATH.'settings/config.php';
ok('ลบทุกแถว = กรอกเกรดเอง', $conf['school_grade_caculations'], []);

echo "school: เมนูและหน้าแรก\n";
$urls = function ($memberId) {
    $login = \Index\Auth\Model::getUserByToken(\Index\Auth\Model::generateTokens($memberId)['access_token']);
    $result = [];
    $menus = \Index\Menus\Controller::getMenus($login);
    array_walk_recursive($menus, function ($v, $k) use (&$result) {
        if ($k === 'url') {
            $result[] = $v;
        }
    });

    return $result;
};
$u2 = $urls(2);
ok('ครู: รายชื่อนักเรียน รายวิชา นำเข้ารายวิชา/ผลการเรียน', [in_array('/school-students', $u2, true), in_array('/school-import?type=course', $u2, true), in_array('/school-import?type=grade', $u2, true), in_array('/school-import?type=student', $u2, true)], [true, true, true, false]);
$u10 = $urls(10);
ok('นักเรียน: รายงานผลการเรียน + ข้อมูลนักเรียน', [in_array('/school-student?id=10', $u10, true), (bool) preg_grep('#^/school-grade\?id=10&#', $u10), in_array('/school-students', $u10, true)], [true, true, false]);
$u1 = $urls(1);
ok('ตั้งค่า: ชั้น ห้อง ภาคเรียน (แผนกอยู่หน้าบุคลากร)', [in_array('/school-categories?type=class', $u1, true), in_array('/school-categories?type=term', $u1, true), in_array('/school-categories?type=department', $u1, true)], [true, true, false]);
ok('ตั้งค่าระดับผลการเรียน (คีย์ภาษา SCHOOL_TYPIES)', in_array('/language?key=SCHOOL_TYPIES', $u1, true));
$student = \Index\Auth\Model::getUserByToken(\Index\Auth\Model::generateTokens(10)['access_token']);
$cards = \School\Init\Controller::initDashboardCards([], null, $student);
ok('การ์ดนักเรียน: รายงานผลการเรียน', $cards[0]['title'] ?? null, 'รายงานผลการเรียน');
$teacher = \Index\Auth\Model::getUserByToken(\Index\Auth\Model::generateTokens(2)['access_token']);
$cards = \School\Init\Controller::initDashboardCards([], null, $teacher);
ok('การ์ดครู: นักเรียน + รายวิชา', array_column($cards, 'title'), ['นักเรียน', 'รายวิชา']);

summary();
