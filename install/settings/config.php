<?php
/* config.php */
return array(
    'version' => '6.0.5',
    'edocument_send_mail' => 1,
    'edocument_file_typies' => array(
        0 => 'doc',
        1 => 'ppt',
        2 => 'pptx',
        3 => 'docx',
        4 => 'rar',
        5 => 'zip',
        6 => 'jpg',
        7 => 'pdf'
    ),
    'edocument_upload_size' => 2097152,
    'web_title' => 'SMS',
    'web_description' => 'School Management System',
    'timezone' => 'Asia/Bangkok',
    'skin' => 'skin/booking',
    'member_status' => array(
        0 => 'นักเรียน',
        1 => 'ผู้ดูแลระบบ',
        2 => 'ครู-อาจารย์',
        3 => 'บริหาร'
    ),
    'color_status' => array(
        0 => '#259B24',
        1 => '#FF0000',
        2 => '#0E0EDA',
        3 => '#660000'
    ),
    'default_icon' => 'icon-elearning',
    'student_w' => 200,
    'student_h' => 250,
    'personnel_w' => 200,
    'personnel_h' => 250,
    'teacher_status' => 2,
    'student_status' => 0,
    'academic_year' => date('Y') + 543,
    'term' => 1,
    'csv_language' => 'UTF-8',
    'school_grade_caculations' => array(
        49 => '0',
        54 => '1',
        59 => '1.5',
        64 => '2',
        69 => '2.5',
        74 => '3',
        79 => '3.5',
        100 => '4'
    ),
    'header_bg_color' => '#769E51',
    'warpper_bg_color' => '#D2D2D2',
    'header_color' => '#FFFFFF',
    'footer_color' => '#7E7E7E',
    'logo_color' => '#000000',
    'login_header_color' => '#000000',
    'login_footer_color' => '#7E7E7E',
    'login_color' => '#000000',
    'login_bg_color' => '#D2D2D2',
    'theme_width' => 'wide'
);
