-- ---------------------------------------------------------------------------
-- modules/personnel/install/database.sql — ตารางที่โมดูล personnel เป็นเจ้าของ
--
-- **ประกาศที่นี่ที่เดียว** ห้ามประกาศซ้ำใน install/database.sql ของโปรเจ็ค
--
-- personnel ต่อ 1:1 กับ user (personnel.id = user.id) เก็บข้อมูลที่เป็นของ
-- บุคลากรเท่านั้น ชื่อ เบอร์โทร วันเกิด สถานะ อยู่ที่ตาราง user
--
-- คอลัมน์ทุกตัวเหมือนระบบเดิม (SMS 6.x) ทุกประการ ไซต์ที่ย้ายมาจึงไม่ต้องย้ายข้อมูล
--   position / department = category_id ของหมวดหมู่ position / department
--   class / room          = ครูประจำชั้น (category_id ของ class / room)
--   custom                = ข้อมูลเพิ่มเติมตาม PERSONNEL_DETAILS เป็น JSON
--                           (ระบบเดิมเป็น PHP serialize — upgrade.php แปลงให้)
-- ---------------------------------------------------------------------------

CREATE TABLE `{prefix}_personnel` (
  `id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `department` int(11) NOT NULL,
  `order` tinyint(3) NOT NULL DEFAULT 0,
  `custom` text DEFAULT NULL,
  `id_card` varchar(13) DEFAULT NULL,
  `class` tinyint(1) DEFAULT 0,
  `room` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `id_card` (`id_card`),
  KEY `position` (`position`,`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- หมวดหมู่ของบุคลากร (ข้อมูลตั้งต้นเดียวกับตัวติดตั้งของระบบเดิม)
-- ---------------------------------------------------------------------------
INSERT INTO `{prefix}_category` (`type`, `category_id`, `language`, `topic`, `color`, `is_active`) VALUES
('position', '1', 'th', 'ผู้อำนวยการโรงเรียน', NULL, 1),
('position', '1', 'en', 'Director', NULL, 1),
('position', '2', 'th', 'รองผู้อำนวยการโรงเรียน', NULL, 1),
('position', '2', 'en', 'Vice-Director', NULL, 1),
('position', '3', 'th', 'ครู', NULL, 1),
('position', '3', 'en', 'Teacher', NULL, 1),
('department', '1', 'th', 'วิทยาศาสตร์และเทคโนโลยี', NULL, 1),
('department', '1', 'en', 'Science and Technology', NULL, 1),
('department', '2', 'th', 'คณิตศาสตร์', NULL, 1),
('department', '2', 'en', 'Mathematics', NULL, 1),
('department', '3', 'th', 'ภาษาไทย', NULL, 1),
('department', '3', 'en', 'Thai Language', NULL, 1),
('department', '4', 'th', 'สังคมศึกษาศาสนาและวัฒนธรรม', NULL, 1),
('department', '4', 'en', 'Social Studies, Religion and Culture', NULL, 1),
('department', '5', 'th', 'สุขศึกษาและพลศึกษา', NULL, 1),
('department', '5', 'en', 'Health and Physical Education', NULL, 1),
('department', '6', 'th', 'การงานอาชีพ', NULL, 1),
('department', '6', 'en', 'Occupations', NULL, 1),
('department', '7', 'th', 'ศิลปะ', NULL, 1),
('department', '7', 'en', 'Arts', NULL, 1),
('department', '8', 'th', 'ภาษาต่างประเทศ', NULL, 1),
('department', '8', 'en', 'Foreign Languages', NULL, 1);
