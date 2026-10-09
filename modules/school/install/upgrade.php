<?php
/**
 * modules/school/install/upgrade.php — พาฐานเดิมมาถึงสคีมาของโมดูล school
 *
 * install/upgrade_core.php เรียกไฟล์นี้ให้เอง ตัวแปรที่ใช้ได้คือชุดเดียวกับที่
 * upgrade_core ใช้ : $db, $db_config, $prefix, $content, $config
 *
 * นิยามตารางอยู่ที่ modules/school/install/database.sql ที่เดียว
 * กฎเดียวกับ upgrade_core : ทุกเงื่อนไขถามว่า "ต้องแก้ไหม" ไม่ใช่ "ตอนนี้เป็นอะไร"
 */
if (!defined('ROOT_PATH')) {
    exit;
}

// [ตาราง => [AUTO_INCREMENT ของ id, ดัชนีที่ต้องมี]]
foreach ([
    'student' => [false, ['id_card' => '`id_card`', 'student_id' => '`student_id`', 'class' => '`class`,`room`']],
    'course' => [true, ['teacher_id' => '`teacher_id`', 'year' => '`year`,`term`', 'course_code' => '`course_code`']],
    'grade' => [true, ['course_id' => '`course_id`', 'student_id' => '`student_id`']]
] as $_table => $_spec) {
    $_name = $prefix.'_'.$_table;
    if (ensureTable($db, $prefix, $_name)) {
        $content[] = '<li class="correct">'.$_table.': สร้างตารางใหม่</li>';
        continue;
    }
    if (convertToInnoDB($db, $_name)) {
        $content[] = '<li class="correct">'.$_table.': แปลงเป็น InnoDB</li>';
    }
    if (convertToUtf8mb4($db, $_name)) {
        $content[] = '<li class="correct">'.$_table.': แปลงเป็น utf8mb4</li>';
    }
    if ($_spec[0]) {
        // ตัวติดตั้งรุ่นเก่าเติม AUTO_INCREMENT ด้วย ALTER ท้าย database.sql
        if (ensureAutoIncrement($db, $_name, 'id', 'int(11)')) {
            $content[] = '<li class="correct">'.$_table.': ปรับ id เป็น AUTO_INCREMENT</li>';
        }
    } elseif (!$db->indexExists($_name, 'PRIMARY')) {
        $db->query("ALTER TABLE `$_name` ADD PRIMARY KEY (`id`)");
        $content[] = '<li class="correct">'.$_table.': เพิ่ม PRIMARY KEY</li>';
    }
    foreach (ensureIndexes($db, $_name, $_spec[1]) as $_index) {
        $content[] = '<li class="correct">'.$_table.': เพิ่มดัชนี '.$_index.'</li>';
    }
    $content[] = '<li class="correct">'.$_table.' อัปเกรดสำเร็จ</li>';
}

// ค่ากำหนดของโมดูล — ไม่ทับค่าที่ไซต์ตั้งไว้แล้ว (ค่าปริยายเดียวกับระบบเดิม)
foreach ([
    'student_w' => 500,
    'student_h' => 500,
    'teacher_status' => 2,
    'student_status' => 0,
    'academic_year' => (int) date('Y') + 543,
    'term' => 1,
    'csv_language' => 'UTF-8',
    'school_name' => '',
    'phone' => '',
    'fax' => '',
    'address' => '',
    'province' => '',
    'provinceID' => '',
    'zipcode' => '',
    'country' => 'TH'
] as $_key => $_value) {
    if (!isset($config[$_key])) {
        $config[$_key] = $_value;
    }
}
// ไม่มีคีย์นี้ = กรอกเกรดเอง (ระบบเดิมใช้ค่าว่างเป็นสัญญาณนี้) จึงเติมเป็นค่าว่าง ไม่ใช่ตารางค่าเริ่มต้น
if (!isset($config['school_grade_caculations'])) {
    $config['school_grade_caculations'] = [];
}
