<?php
/* config.php */
// ค่าตั้งต้นของ "การติดตั้งใหม่" (การปรับรุ่นเติมค่าเดียวกันนี้จาก modules/<m>/install/upgrade.php
// โดยไม่ทับค่าเดิมของไซต์) — ถ้าแก้ค่าตั้งต้นของโมดูล ต้องแก้ทั้งสองที่ให้ตรงกัน
return [
    'version' => '7.0.4',
    'web_title' => 'SMS',
    'web_description' => 'School Management System',
    'timezone' => 'Asia/Bangkok',
    // สถานะสมาชิก: 0 นักเรียน · 1 ผู้ดูแลระบบ · 2 ครู-อาจารย์ · 3 บริหาร
    'member_status' => [
        0 => 'นักเรียน',
        1 => 'ผู้ดูแลระบบ',
        2 => 'ครู-อาจารย์',
        3 => 'บริหาร'
    ],
    'color_status' => [
        0 => '#259B24',
        1 => '#FF0000',
        2 => '#0E0EDA',
        3 => '#660000'
    ],
    // personnel + school
    'teacher_status' => 2,
    'student_status' => 0,
    'personnel_w' => 500,
    'personnel_h' => 500,
    'student_w' => 500,
    'student_h' => 500,
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
    'country' => 'TH',
    // ว่าง = กรอกเกรดเอง ตั้งตารางคะแนน→เกรดได้ที่ โรงเรียน → การตั้งค่า → การคำนวณเกรด
    'school_grade_caculations' => [],
    // edocument
    'edocument_prefix' => '',
    'edocument_format_no' => '%04d',
    'edocument_send_mail' => 1,
    'edocument_file_typies' => ['doc', 'ppt', 'pptx', 'docx', 'rar', 'zip', 'jpg', 'pdf'],
    'edocument_upload_size' => 2097152,
    'edocument_download_action' => 0
];
