<?php
/**
 * @filesource modules/edocument/filedownload.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */
@session_start();
// check if id and session variable are set
if (!isset($_GET['id']) || !isset($_SESSION[$_GET['id']])) {
    header('HTTP/1.0 404 Not Found');
    exit;
}
// retrieve file data from session
$file = $_SESSION[$_GET['id']];
// check if file exists and is a valid file
if (!is_file($file['file'])) {
    header('HTTP/1.0 404 Not Found');
    exit;
}
// open file for reading
$f = @fopen($file['file'], 'rb');
// check if file was opened successfully
if (!$f) {
    header('HTTP/1.0 500 Internal Server Error');
    exit;
}
// ดาวน์โหลดไฟล์
if ($file['name'] != '') {
    header('Content-Disposition: attachment; filename="'.$file['name'].'"');
} else {
    header('Content-Disposition: inline;');
    header('Content-Type: '.$file['mime']);
}
header('Content-Description: File Transfer');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: '.$file['size']);
readfile($file['file']);
