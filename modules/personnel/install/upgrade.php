<?php
/**
 * modules/personnel/install/upgrade.php — พาฐานเดิมมาถึงสคีมาของโมดูล personnel
 *
 * install/upgrade_core.php เรียกไฟล์นี้ให้เอง ตัวแปรที่ใช้ได้คือชุดเดียวกับที่
 * upgrade_core ใช้ : $db, $db_config, $prefix, $content, $config
 *
 * นิยามตารางอยู่ที่ modules/personnel/install/database.sql ที่เดียว
 * กฎเดียวกับ upgrade_core : ทุกเงื่อนไขถามว่า "ต้องแก้ไหม" ไม่ใช่ "ตอนนี้เป็นอะไร"
 */
if (!defined('ROOT_PATH')) {
    exit;
}

// =========================================================
// personnel
// =========================================================
$table_personnel = $prefix.'_personnel';
if (ensureTable($db, $prefix, $table_personnel)) {
    $content[] = '<li class="correct">personnel: สร้างตารางใหม่</li>';
} else {
    if (convertToInnoDB($db, $table_personnel)) {
        $content[] = '<li class="correct">personnel: แปลงเป็น InnoDB</li>';
    }
    // แปลง charset ก่อนปรับชนิดคอลัมน์ เพราะ CONVERT TO utf8mb4 ดัน TEXT ขึ้นเป็น MEDIUMTEXT
    if (convertToUtf8mb4($db, $table_personnel)) {
        $content[] = '<li class="correct">personnel: แปลงเป็น utf8mb4</li>';
    }
    if (ensureColumn($db, $table_personnel, 'custom', 'text', true)) {
        $content[] = '<li class="correct">personnel: ปรับคอลัมน์ custom</li>';
    }
    if (!$db->indexExists($table_personnel, 'PRIMARY')) {
        $db->query("ALTER TABLE `$table_personnel` ADD PRIMARY KEY (`id`)");
        $content[] = '<li class="correct">personnel: เพิ่ม PRIMARY KEY</li>';
    }
    foreach (ensureIndexes($db, $table_personnel, [
        'id_card' => '`id_card`',
        'position' => '`position`,`order`'
    ]) as $_index) {
        $content[] = '<li class="correct">personnel: เพิ่มดัชนี '.$_index.'</li>';
    }
    // custom : PHP serialize -> JSON
    // ระบบเดิมเก็บด้วย serialize() การอ่านกลับด้วย unserialize() กับข้อมูลที่ผู้ใช้กรอก
    // เปิดช่อง object injection ระบบใหม่อ่านเป็น JSON อย่างเดียว จึงต้องแปลงของเดิมให้หมด
    $_count = 0;
    foreach ($db->customQuery("SELECT `id`, `custom` FROM `$table_personnel` WHERE `custom` LIKE 'a:%'", true) as $_row) {
        $_custom = @unserialize($_row['custom'], ['allowed_classes' => false]);
        $_custom = is_array($_custom) && !empty($_custom) ? json_encode($_custom, JSON_UNESCAPED_UNICODE) : null;
        $db->update($table_personnel, (int) $_row['id'], ['custom' => $_custom]);
        ++$_count;
    }
    if ($_count > 0) {
        $content[] = '<li class="correct">personnel: แปลงข้อมูลเพิ่มเติม '.$_count.' รายการเป็น JSON</li>';
    }
    $content[] = '<li class="correct">personnel อัปเกรดสำเร็จ</li>';
}

// ค่ากำหนดของโมดูล — ไม่ทับค่าที่ไซต์ตั้งไว้แล้ว
if (!isset($config['personnel_w'])) {
    $config['personnel_w'] = 500;
}
if (!isset($config['personnel_h'])) {
    $config['personnel_h'] = 500;
}
if (!isset($config['teacher_status'])) {
    $config['teacher_status'] = 2;
}
