<?php
/**
 * bootstrap ของชุดทดสอบ SMS: โหลดเฟรมเวิร์กในสำเนาโปรเจ็ค (SMS_ROOT) แล้วปลอมการล็อกอิน
 *
 * ใช้โดย test*.php ทุกไฟล์ใน modules/{personnel,school,edocument}/tests/
 */
chdir(getenv('SMS_ROOT'));
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/api';
$_SERVER['SCRIPT_NAME'] = '/api.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include 'load.php';
Kotchasan::createWebApplication('Gcms\Config');
$cfg = \Kotchasan\Config::create();

/**
 * สร้าง Request พร้อม token ของผู้ใช้ที่ระบุ
 *
 * @param int $memberId
 * @param string $method
 * @param array $data
 * @param array $files [name => ['path' => ไฟล์จริง, 'name' => ชื่อไฟล์]]
 *
 * @return \Kotchasan\Http\Request
 */
function sms_request($memberId, $method = 'GET', array $data = [], array $files = [])
{
    // token ต้องมี session ในตาราง user_session ด้วย (Index\Auth\Model::getUserByToken)
    $tokens = \Index\Auth\Model::generateTokens($memberId);
    $csrf = bin2hex(random_bytes(32));
    $_SESSION[$csrf] = ['times' => 0, 'expired' => time() + 3600, 'created' => time()];
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer '.$tokens['access_token'];
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $csrf;
    $_SERVER['REQUEST_METHOD'] = $method;
    // ภาษาของผู้ใช้ (Gcms\Table::initLanguage อ่านจากคุกกี้หรือ Accept-Language)
    $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'th';
    $_COOKIE['my_lang'] = 'th';
    $_FILES = [];
    foreach ($files as $name => $file) {
        $tmp = tempnam(sys_get_temp_dir(), 'smsup');
        copy($file['path'], $tmp);
        $_FILES[$name] = [
            'name' => $file['name'],
            'type' => $file['type'] ?? 'application/octet-stream',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmp)
        ];
    }
    $request = new \Kotchasan\Http\Request();
    if ($method === 'GET') {
        $_GET = $data;
        $_POST = [];
        $_REQUEST = $data;

        return $request->withQueryParams($data);
    }
    $_GET = [];
    $_POST = $data;
    $_REQUEST = $data;

    return $request->withParsedBody($data);
}

/**
 * เรียก controller แล้วคืนค่า response ที่ decode แล้ว
 *
 * @param object $controller
 * @param string $method
 * @param \Kotchasan\Http\Request $request
 *
 * @return array
 */
function sms_call($controller, $method, $request)
{
    $response = $controller->{$method}($request);
    if (!$response instanceof \Kotchasan\Http\Response) {
        return ['__raw' => $response];
    }
    $json = json_decode((string) $response->getBody(), true);

    return is_array($json) ? $json + ['__status' => $response->getStatusCode()] : ['__status' => $response->getStatusCode(), '__body' => (string) $response->getBody()];
}

/**
 * ข้อมูลใน response ชั้นที่เก็บ data
 *
 * @param array $res
 *
 * @return mixed
 */
function sms_data($res)
{
    return $res['data']['data'] ?? ($res['data'] ?? null);
}

/**
 * ประเภท action แรกของ response
 *
 * @param array $res
 * @param string $type
 *
 * @return array|null
 */
function sms_action($res, $type)
{
    $actions = $res['data']['actions'] ?? ($res['actions'] ?? []);
    foreach ((array) $actions as $action) {
        if (($action['type'] ?? '') === $type) {
            return $action;
        }
    }

    return null;
}

/**
 * query ตรง ๆ
 *
 * @param string $sql ใช้ {prefix} แทนคำนำหน้าตาราง
 * @param array $params
 *
 * @return array
 */
function sms_rows($sql, array $params = [])
{
    static $pdo = null;
    if ($pdo === null) {
        $db = include ROOT_PATH.'settings/database.php';
        $m = $db['mysql'];
        $pdo = new PDO('mysql:host='.$m['hostname'].';port='.$m['port'].';dbname='.$m['dbname'].';charset=utf8mb4', $m['username'], $m['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $GLOBALS['sms_prefix'] = $m['prefix'];
    }
    $stmt = $pdo->prepare(str_replace('{prefix}', $GLOBALS['sms_prefix'], $sql));
    $stmt->execute($params);

    return $stmt->columnCount() > 0 ? $stmt->fetchAll() : [];
}

/**
 * แถวเดียว
 *
 * @param string $sql
 * @param array $params
 *
 * @return array|null
 */
function sms_row($sql, array $params = [])
{
    $rows = sms_rows($sql, $params);

    return $rows[0] ?? null;
}

/**
 * เรียก endpoint ที่ส่งไฟล์แล้ว exit (CSV) ในโปรเซสแยก แล้วคืนสิ่งที่พิมพ์ออกมา
 *
 * @param string $class คลาส controller
 * @param string $method
 * @param int $memberId
 * @param array $params query string
 *
 * @return string
 */
function sms_subprocess($class, $method, $memberId, array $params)
{
    $script = tempnam(sys_get_temp_dir(), 'smsx').'.php';
    file_put_contents($script, '<'.'?php
require '.var_export(getenv('SMS_BOOT'), true).';
$res = (new '.$class.'())->'.$method.'(sms_request('.(int) $memberId.', "GET", '.var_export($params, true).'));
if ($res instanceof \Kotchasan\Http\Response) {
    echo (string) $res->getBody();
}
');
    $output = shell_exec('SMS_ROOT='.escapeshellarg(getenv('SMS_ROOT')).' SMS_BOOT='.escapeshellarg(getenv('SMS_BOOT')).' php '.escapeshellarg($script).' 2>&1');
    @unlink($script);

    return (string) $output;
}

/**
 * แปลง CSV (ตัด BOM) เป็นแถว
 *
 * @param string $text
 *
 * @return array
 */
function sms_csv($text)
{
    $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
    $rows = [];
    foreach (preg_split('/\r?\n/', trim($text)) as $line) {
        $rows[] = str_getcsv($line, ',', '"', '\\');
    }

    return $rows;
}

$GLOBALS['sms_pass'] = 0;
$GLOBALS['sms_fail'] = 0;

/**
 * ตรวจผลลัพธ์
 *
 * @param string $label
 * @param mixed $actual
 * @param mixed $expected
 */
function ok($label, $actual, $expected = true)
{
    if ($actual === $expected) {
        $GLOBALS['sms_pass']++;
    } else {
        $GLOBALS['sms_fail']++;
        echo "  FAIL  $label\n        expected: ".var_export($expected, true)."\n        actual  : ".var_export($actual, true)."\n";
    }
}

/**
 * สรุปผล
 */
function summary()
{
    echo "\n".str_repeat('=', 50)."\n";
    printf("ผ่าน %d / ล้มเหลว %d\n", $GLOBALS['sms_pass'], $GLOBALS['sms_fail']);
    exit($GLOBALS['sms_fail'] > 0 ? 1 : 0);
}
