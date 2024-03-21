<?php
/* settings/database.php */

return array(
    'mysql' => array(
        'dbdriver' => 'mysql',
        'username' => 'root',
        'password' => '',
        'dbname' => 'school',
        'prefix' => 'sms',
    ),
    'tables' => array(
        'user' => 'user',
        'personnel' => 'personnel',
        'student' => 'student',
        'course' => 'course',
        'grade' => 'grade',
        'category' => 'category',
        'edocument' => 'edocument',
        'edocument_download' => 'edocument_download',
    ),
);
