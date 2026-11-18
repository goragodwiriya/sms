<?php
/**
 * modules/school/tests/run.php
 *
 * ชุดทดสอบ end-to-end ของระบบ SMS (โมดูล personnel, school, edocument)
 *   1. สร้างฐานข้อมูลทดสอบด้วยสคีมาและข้อมูลของ "ระบบเดิม" (fixtures/legacy_*.sql)
 *   2. คัดลอกโปรเจกต์ไปที่โฟลเดอร์ชั่วคราว แล้วรัน install/upgrade2.php ของจริง
 *      ผ่าน install/cli-upgrade.php เพื่อพิสูจน์ว่าข้อมูลเดิมอัปเกรดได้
 *   3. เรียก Controller ของแต่ละโมดูลตรง ๆ ตรวจพฤติกรรมทั้งหมด
 *      (ไฟล์ test_*.php ใน modules/{personnel,school,edocument}/tests/)
 *
 * วิธีใช้ (รันจากรากโปรเจกต์)
 *   php modules/school/tests/run.php [ชื่อชุด ...]
 *
 * ตัวแปรแวดล้อมที่ปรับได้
 *   SMS_TEST_DB    ชื่อฐานข้อมูลทดสอบ (ค่าปริยาย sms_module_test)
 *   SMS_KEEP       ไม่ลบฐานข้อมูลและสำเนาโปรเจกต์ทิ้งเมื่อจบ
 *
 * ชุดทดสอบสร้างและลบฐานข้อมูลของตัวเอง ไม่แตะฐานข้อมูลจริงและไฟล์ตั้งค่าของโปรเจกต์
 */
$projectRoot = realpath(__DIR__.'/../../..');
$testsDir = __DIR__;
$testDb = getenv('SMS_TEST_DB') ?: 'sms_module_test';

if (!is_file($projectRoot.'/settings/database.php')) {
    fwrite(STDERR, "ไม่พบ settings/database.php กรุณารันจากรากโปรเจกต์\n");
    exit(2);
}

$dbConfig = include $projectRoot.'/settings/database.php';
$mysql = $dbConfig['mysql'];
if ($testDb === $mysql['dbname']) {
    fwrite(STDERR, "ชื่อฐานทดสอบซ้ำกับฐานข้อมูลจริง กรุณากำหนด SMS_TEST_DB ให้ต่างออกไป\n");
    exit(2);
}

$dsn = 'mysql:host='.$mysql['hostname'].';port='.$mysql['port'].';charset=utf8mb4';
$pdo = new PDO($dsn, $mysql['username'], $mysql['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "1) สร้างฐานข้อมูลทดสอบ $testDb จากสคีมาของระบบเดิม\n";
$pdo->exec("DROP DATABASE IF EXISTS `$testDb`");
// ระบบเดิมเป็น utf8 (utf8mb3) ตั้งใจให้ตรงของจริง เพื่อทดสอบการแปลง charset ด้วย
$pdo->exec("CREATE DATABASE `$testDb` DEFAULT CHARACTER SET utf8");
$pdo->exec("USE `$testDb`");
foreach (['legacy_schema.sql', 'legacy_data.sql'] as $file) {
    $sql = str_replace('{prefix}', $mysql['prefix'], file_get_contents($testsDir.'/fixtures/'.$file));
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $statement) {
        if ($statement !== '' && stripos($statement, 'SET ') !== 0) {
            $pdo->exec($statement);
        }
    }
}

echo "2) เตรียมสำเนาโปรเจกต์และรัน install/upgrade2.php ของจริง\n";
$sandbox = sys_get_temp_dir().'/sms-tests-'.getmypid();
exec('rm -rf '.escapeshellarg($sandbox));
mkdir($sandbox, 0777, true);
exec('tar -cf - --exclude=./.git --exclude=./node_modules --exclude=./datas --exclude=./settings -C '.escapeshellarg($projectRoot).' . | tar -xf - -C '.escapeshellarg($sandbox));
foreach (['settings', 'datas/cache', 'datas/logs', 'datas/edocument', 'datas/personnel', 'datas/school'] as $dir) {
    mkdir($sandbox.'/'.$dir, 0777, true);
}
// สำเนาชี้ไปฐานทดสอบ ใช้ค่ากำหนดของระบบเดิม (config.php ของไซต์ที่ย้ายมา)
$sandboxDb = $dbConfig;
$sandboxDb['mysql']['dbname'] = $testDb;
file_put_contents($sandbox.'/settings/database.php', '<'."?php\n/* database.php */\nreturn ".var_export($sandboxDb, true).';');
copy($testsDir.'/fixtures/legacy_config.php', $sandbox.'/settings/config.php');
// ไฟล์เอกสารของระบบเดิม
file_put_contents($sandbox.'/datas/edocument/1631797416.jpg', 'JPEGDATA');
file_put_contents($sandbox.'/datas/edocument/1700000000.pdf', "%PDF-1.4 legacy 1700000000\n");
file_put_contents($sandbox.'/datas/edocument/1700000100.pdf', "%PDF-1.4 legacy 1700000100\n");

$upgradeOutput = [];
exec('cd '.escapeshellarg($sandbox).' && php install/cli-upgrade.php admin secret123 --confirm-backup 2>&1', $upgradeOutput);
$upgradeText = implode("\n", $upgradeOutput);
$upgradeOk = strpos($upgradeText, 'ปรับรุ่นเรียบร้อย') !== false || preg_match('/\[ok\].*บันทึก config\.php/u', $upgradeText);
$upgradeOk = $upgradeOk && strpos($upgradeText, '[FAIL]') === false && strpos($upgradeText, 'ไม่สำเร็จ') === false;
echo $upgradeOk ? "   ตัวติดตั้งทำงานสำเร็จ\n" : "   ตัวติดตั้งล้มเหลว\n$upgradeText\n";

// รันตัวปรับรุ่นซ้ำ ต้องผ่านและไม่แก้อะไรอีก (idempotent)
$again = [];
exec('cd '.escapeshellarg($sandbox).' && php install/cli-upgrade.php admin secret123 --confirm-backup 2>&1', $again);
$againText = implode("\n", $again);
$moduleChanges = array_filter($again, fn($line) => preg_match('/(personnel|student|course|grade|edocument)[a-z_]*: /u', $line));
$idempotent = strpos($againText, '[FAIL]') === false && empty($moduleChanges);
echo $idempotent ? "   รันตัวปรับรุ่นซ้ำ ไม่มีการแก้ซ้ำ\n" : "   รันตัวปรับรุ่นซ้ำแล้วยังแก้ตารางอีก\n".implode("\n", $moduleChanges)."\n";

echo "3) ทดสอบโมดูล\n";
$suites = [];
foreach (['personnel', 'school', 'edocument'] as $module) {
    foreach (glob($projectRoot.'/modules/'.$module.'/tests/test_*.php') ?: [] as $file) {
        $name = basename($file, '.php');
        if (count($argv) > 1 && !in_array($name, array_slice($argv, 1), true)) {
            continue;
        }
        $suites[$name] = $sandbox.'/modules/'.$module.'/tests/'.$name.'.php';
    }
}
$pass = ($upgradeOk ? 1 : 0) + ($idempotent ? 1 : 0);
$fail = ($upgradeOk ? 0 : 1) + ($idempotent ? 0 : 1);
foreach ($suites as $suite => $file) {
    $output = [];
    exec('SMS_ROOT='.escapeshellarg($sandbox).' SMS_BOOT='.escapeshellarg($sandbox.'/modules/school/tests/bootstrap.php')
        .' php '.escapeshellarg($file).' 2>&1', $output);
    $text = implode("\n", $output);
    if (preg_match('/ผ่าน (\d+) \/ ล้มเหลว (\d+)/u', $text, $m)) {
        $pass += (int) $m[1];
        $fail += (int) $m[2];
        printf("   %-20s ผ่าน %3d / ล้มเหลว %d\n", $suite, $m[1], $m[2]);
        if (getenv('SMS_VERBOSE')) {
            echo $text, "\n";
        } elseif ((int) $m[2] > 0) {
            foreach (explode("\n", $text) as $line) {
                if (strpos($line, 'FAIL') !== false || strpos($line, 'expected') !== false || strpos($line, 'actual') !== false || strpos($line, 'PHP ') !== false) {
                    echo '     ', trim($line), "\n";
                }
            }
        }
    } else {
        $fail++;
        printf("   %-20s ไม่สามารถรันได้\n%s\n", $suite, $text);
    }
}

if (getenv('SMS_KEEP')) {
    echo "เก็บสำเนาโปรเจกต์ไว้ที่ $sandbox และฐาน $testDb\n";
} else {
    exec('rm -rf '.escapeshellarg($sandbox));
    $pdo->exec("DROP DATABASE IF EXISTS `$testDb`");
}

echo str_repeat('=', 44), "\n";
printf("รวม: ผ่าน %d / ล้มเหลว %d\n", $pass, $fail);
exit($fail > 0 ? 1 : 0);
