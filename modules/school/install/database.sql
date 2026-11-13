-- ---------------------------------------------------------------------------
-- modules/school/install/database.sql — ตารางที่โมดูล school เป็นเจ้าของ
--
-- **ประกาศที่นี่ที่เดียว** ห้ามประกาศซ้ำใน install/database.sql ของโปรเจ็ค
--
-- คอลัมน์ทุกตัวเหมือนระบบเดิม (SMS 6.x) ทุกประการ ไซต์ที่ย้ายมาจึงไม่ต้องย้ายข้อมูล
--   student  ต่อ 1:1 กับ user (student.id = user.id) ชื่อ เบอร์ วันเกิด เพศ อยู่ที่ user
--            department / class / room = category_id ของหมวดหมู่ชนิดนั้น
--   course   รายวิชา ไม่ระบุผู้สอน (teacher_id = 0) = รายวิชาต้นแบบ ไม่มีปีการศึกษา/ภาคเรียน
--   grade    การลงทะเบียนเรียน + ผลการเรียน หนึ่งแถวต่อนักเรียนต่อรายวิชา
--            type 0 = คิดเกรดจากคะแนน, อื่น ๆ = ตามคีย์ภาษา SCHOOL_TYPIES (ร. มส. มผ. ผ.)
-- ---------------------------------------------------------------------------

CREATE TABLE `{prefix}_student` (
  `id` int(11) NOT NULL,
  `student_id` varchar(13) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `parent` varchar(100) DEFAULT NULL,
  `parent_phone` varchar(32) DEFAULT NULL,
  `department` int(11) NOT NULL,
  `class` int(11) NOT NULL,
  `room` int(11) NOT NULL,
  `number` int(11) DEFAULT NULL,
  `id_card` varchar(13) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_card` (`id_card`),
  KEY `student_id` (`student_id`),
  KEY `class` (`class`,`room`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `{prefix}_course` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_name` varchar(50) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `teacher_id` int(11) DEFAULT 0,
  `class` int(11) NOT NULL,
  `period` int(11) NOT NULL,
  `credit` decimal(2,1) NOT NULL,
  `type` tinyint(1) NOT NULL,
  `year` int(4) NOT NULL,
  `term` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `year` (`year`,`term`),
  KEY `course_code` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `{prefix}_grade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `number` int(11) DEFAULT NULL,
  `room` int(11) NOT NULL,
  `type` tinyint(2) NOT NULL,
  `midterm` int(11) DEFAULT NULL,
  `final` int(11) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- หมวดหมู่ของนักเรียนและภาคเรียน (ข้อมูลตั้งต้นเดียวกับตัวติดตั้งของระบบเดิม)
-- แผนกใช้ร่วมกับบุคลากร อยู่ใน modules/personnel/install/database.sql
-- ---------------------------------------------------------------------------
INSERT INTO `{prefix}_category` (`type`, `category_id`, `language`, `topic`, `color`, `is_active`) VALUES
('class', '1', 'th', 'มัธยมศึกษาปีที่ 1', NULL, 1),
('class', '1', 'en', 'Class 7', NULL, 1),
('class', '2', 'th', 'มัธยมศึกษาปีที่ 2', NULL, 1),
('class', '2', 'en', 'Class 8', NULL, 1),
('class', '3', 'th', 'มัธยมศึกษาปีที่ 3', NULL, 1),
('class', '3', 'en', 'Class 9', NULL, 1),
('class', '4', 'th', 'มัธยมศึกษาปีที่ 4', NULL, 1),
('class', '4', 'en', 'Class 10', NULL, 1),
('class', '5', 'th', 'มัธยมศึกษาปีที่ 5', NULL, 1),
('class', '5', 'en', 'Class 11', NULL, 1),
('class', '6', 'th', 'มัธยมศึกษาปีที่ 6', NULL, 1),
('class', '6', 'en', 'Class 12', NULL, 1),
('room', '1', 'th', '1/1', NULL, 1),
('room', '1', 'en', '1/1', NULL, 1),
('room', '2', 'th', '1/2', NULL, 1),
('room', '2', 'en', '1/2', NULL, 1),
('room', '4', 'th', '2/1', NULL, 1),
('room', '4', 'en', '2/1', NULL, 1),
('room', '5', 'th', '2/2', NULL, 1),
('room', '5', 'en', '2/2', NULL, 1),
('room', '7', 'th', '3/1', NULL, 1),
('room', '7', 'en', '3/1', NULL, 1),
('room', '8', 'th', '3/2', NULL, 1),
('room', '8', 'en', '3/2', NULL, 1),
('term', '1', 'th', 'เทอม 1', NULL, 1),
('term', '1', 'en', 'Term 1', NULL, 1),
('term', '2', 'th', 'เทอม 2', NULL, 1),
('term', '2', 'en', 'Term 2', NULL, 1);
