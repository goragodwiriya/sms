<?php
/**
 * install/cli-sample-export.php — ส่งออกข้อมูลตัวอย่างเป็นไฟล์สำเร็จรูปให้ตัวติดตั้งใช้ (สำหรับนักพัฒนา)
 *
 * ตัวติดตั้งไม่สร้างข้อมูลตัวอย่างเอง (ช้าและพึ่งโฮสต์) แต่รันไฟล์ที่ได้จากสคริปต์นี้แทน
 *   install/sample.sql              INSERT ของ user personnel student course grade edocument
 *                                   edocument_download logs number และหมวดหมู่ที่เพิ่ม
 *   install/sample-accounts.php     บัญชีที่เข้าระบบได้ (ชื่อผู้ใช้ => รหัสผ่าน) — รหัสผ่านต้องเข้ารหัสตอนติดตั้ง
 *                                   เพราะผูกกับ password_key ของแต่ละไซต์ บัญชีที่เหลือเข้าระบบไม่ได้ (password = '*')
 *   install/sample/edocument/       ไฟล์แนบของหนังสือเวียนตัวอย่าง
 *
 * ขั้นตอน (ทำกับฐาน "ว่างที่เพิ่งติดตั้ง" เท่านั้น ห้ามใช้ฐานจริง)
 *   php install/cli-sample.php --db=<ฐานว่าง>              สร้างข้อมูลตัวอย่างลงฐานนั้น
 *   php install/cli-sample-export.php --db=<ฐานเดิม>       ส่งออก
 *
 * ใช้:  php install/cli-sample-export.php --db=<dbname>
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
$root = dirname(__DIR__);
$db = '';
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--db=(.+)$/', $arg, $m)) {
        $db = $m[1];
    } else {
        fwrite(STDERR, "ไม่รู้จักตัวเลือก $arg\n");
        exit(1);
    }
}
if ($db === '') {
    fwrite(STDERR, "ต้องระบุ --db=<dbname>\n");
    exit(1);
}
$cfg = (include $root.'/settings/database.php')['mysql'];
$pdo = new PDO('mysql:host='.$cfg['hostname'].';port='.$cfg['port'].';dbname='.$db.';charset=utf8mb4', $cfg['username'], $cfg['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);
$prefix = $cfg['prefix'];
$q = function (string $sql) use ($pdo) {
    return $pdo->query($sql)->fetchAll();
};
// ต้องเป็นฐานที่มีข้อมูลตัวอย่างอยู่ และมีผู้ใช้ไม่เกินที่ตัวอย่างสร้าง (กันส่งออกฐานจริงโดยไม่ตั้งใจ)
$real = (int) $q("SELECT COUNT(*) c FROM `{$prefix}_user` WHERE id > 1 AND (username IS NULL OR username NOT LIKE '99%')")[0]['c'];
$sample = (int) $q("SELECT COUNT(*) c FROM `{$prefix}_user` WHERE username LIKE '99%'")[0]['c'];
if ($real > 0 || $sample === 0) {
    fwrite(STDERR, "ฐาน $db ไม่ใช่ฐานที่มีเฉพาะข้อมูลตัวอย่าง (สมาชิกอื่น $real · ตัวอย่าง $sample) ไม่ส่งออก\n");
    exit(1);
}

/**
 * ค่าเป็นตัวอักษร SQL บรรทัดเดียว (ตัวติดตั้งตัดบรรทัดว่างและ trim ทุกบรรทัด จึงห้ามมีขึ้นบรรทัดใหม่จริง)
 */
$value = function ($v) use ($pdo): string {
    if ($v === null) {
        return 'NULL';
    }
    if (is_int($v) || (is_string($v) && preg_match('/^-?[1-9][0-9]{0,14}$|^0$/', $v))) {
        return (string) $v;
    }

    return str_replace(["\r", "\n"], ['\\r', '\\n'], $pdo->quote((string) $v));
};
$sql = ["-- install/sample.sql — ข้อมูลตัวอย่างของ SMS สร้างโดย install/cli-sample-export.php อย่าแก้ด้วยมือ"];
$dump = function (string $table, array $rows, array $override = []) use (&$sql, $value) {
    if (empty($rows)) {
        return;
    }
    $columns = array_keys($rows[0]);
    $head = 'INSERT INTO `{prefix}_'.$table.'` (`'.implode('`, `', $columns).'`) VALUES';
    foreach (array_chunk($rows, 100) as $chunk) {
        $values = [];
        foreach ($chunk as $row) {
            $row = array_merge($row, $override);
            $values[] = '('.implode(', ', array_map($value, $row)).')';
        }
        $sql[] = $head.' '.implode(', ', $values).';';
    }
    echo str_pad($table, 20).count($rows)."\n";
};

// หมวดหมู่ที่ตัวอย่างเพิ่ม (ที่เหลือมากับ modules/*/install/database.sql อยู่แล้ว)
$dump('category', $q("SELECT * FROM `{$prefix}_category` WHERE (type = 'position' AND category_id + 0 > 3) OR (type = 'room' AND category_id + 0 > 8) ORDER BY type, category_id + 0, language"));
// รหัสผ่านใส่ตอนติดตั้ง ('*' = เข้าระบบไม่ได้)
$dump('user', $q("SELECT * FROM `{$prefix}_user` WHERE id > 1 ORDER BY id"), ['password' => '*', 'salt' => '', 'token_expires' => null, 'visited' => 0]);
$dump('personnel', $q("SELECT * FROM `{$prefix}_personnel` ORDER BY id"));
$dump('student', $q("SELECT * FROM `{$prefix}_student` ORDER BY id"));
$dump('course', $q("SELECT * FROM `{$prefix}_course` WHERE teacher_id > 1 ORDER BY id"));
$dump('grade', $q("SELECT * FROM `{$prefix}_grade` ORDER BY id"));
$dump('edocument', $q("SELECT * FROM `{$prefix}_edocument` ORDER BY id"));
$dump('edocument_download', $q("SELECT * FROM `{$prefix}_edocument_download` ORDER BY id"));
$dump('logs', $q("SELECT * FROM `{$prefix}_logs` ORDER BY id"));
$dump('number', $q("SELECT * FROM `{$prefix}_number`"));
file_put_contents($root.'/install/sample.sql', implode("\n", $sql)."\n");

// ไฟล์แนบ
$dir = $root.'/install/sample/edocument';
if (is_dir($dir)) {
    array_map('unlink', glob($dir.'/*'));
} else {
    mkdir($dir, 0755, true);
}
$files = 0;
foreach ($q("SELECT file FROM `{$prefix}_edocument`") as $row) {
    $from = $root.'/datas/edocument/'.$row['file'];
    if (is_file($from)) {
        copy($from, $dir.'/'.$row['file']);
        ++$files;
    }
}
echo str_pad('ไฟล์แนบ', 20).$files."\n";

// บัญชีที่เข้าระบบได้ — น้อยที่สุดที่ครบทุกบทบาท เพราะรหัสผ่านแต่ละบัญชีต้องเข้ารหัส (bcrypt) ตอนติดตั้งราว 0.3 วินาที
// บุคลากรที่ยังอยู่ 10 คนแรกตามลำดับ (ผู้บริหาร หัวหน้ากลุ่มสาระ) + ครูประจำชั้นทุกคน + งานทะเบียน + ธุรการ
// + นักเรียนเลขที่ 1 ห้อง 1 ของทุกชั้น รหัสผ่าน = วันเกิด พ.ศ. ปปปปดดวว
$accounts = [];
$birthday = function (string $date): string {
    return ((int) substr($date, 0, 4) + 543).substr($date, 5, 2).substr($date, 8, 2);
};
$pick = $q("SELECT U.username, U.birthday FROM `{$prefix}_user` U JOIN `{$prefix}_personnel` P ON P.id = U.id WHERE U.active = 1 AND U.username IS NOT NULL AND (P.class > 0 OR P.position = 6 OR U.permission LIKE '%can_manage_course%' OR P.id IN (SELECT id FROM (SELECT id FROM `{$prefix}_personnel` P2 ORDER BY P2.`order` LIMIT 10) T)) ORDER BY P.`order`");
foreach ($pick as $row) {
    $accounts[$row['username']] = $birthday($row['birthday']);
}
$pick = $q("SELECT U.username, U.birthday FROM `{$prefix}_user` U JOIN `{$prefix}_student` S ON S.id = U.id WHERE S.number = 1 AND S.room = (SELECT MIN(S2.room) FROM `{$prefix}_student` S2 WHERE S2.class = S.class) AND U.username IS NOT NULL ORDER BY S.class");
foreach ($pick as $row) {
    $accounts[$row['username']] = $birthday($row['birthday']);
}
// บัญชีที่ตัวติดตั้งแสดงบนหน้าจอสุดท้าย (ตามบทบาท)
$labels = [];
$role = function (string $label, string $where) use ($q, $prefix, &$labels, $birthday) {
    $row = $q("SELECT U.username, U.name FROM `{$prefix}_user` U JOIN `{$prefix}_personnel` P ON P.id = U.id WHERE $where ORDER BY P.id LIMIT 1")[0] ?? null;
    if ($row) {
        $labels[$row['username']] = $label.' ('.$row['name'].')';
    }
};
$role('ผู้อำนวยการ', 'P.position = 1');
$role('รองผู้อำนวยการ', 'P.position = 2');
$role('งานทะเบียน', "U.permission LIKE '%can_manage_course%' AND P.position = 3");
$role('ครูประจำชั้น', 'P.class > 0');
$role('ธุรการ', 'P.position = 6');
$row = $q("SELECT U.username, U.name FROM `{$prefix}_user` U JOIN `{$prefix}_student` S ON S.id = U.id WHERE S.number = 1 AND S.class = (SELECT MAX(class) FROM `{$prefix}_student`) ORDER BY S.room LIMIT 1")[0] ?? null;
if ($row) {
    $labels[$row['username']] = 'นักเรียน ('.$row['name'].')';
}
file_put_contents($root.'/install/sample-accounts.php', "<?php\n/* install/sample-accounts.php — สร้างโดย install/cli-sample-export.php */\nreturn ".var_export(['accounts' => $accounts, 'labels' => $labels], true).";\n");
echo str_pad('บัญชีเข้าระบบ', 20).count($accounts)."\n";
printf("เสร็จ: install/sample.sql %.0f KB\n", filesize($root.'/install/sample.sql') / 1024);
