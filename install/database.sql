-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 02, 2024 at 08:50 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 7.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_category`
--

CREATE TABLE `{prefix}_category` (
  `type` varchar(20) NOT NULL,
  `category_id` varchar(10) DEFAULT '0',
  `topic` varchar(150) NOT NULL,
  `color` varchar(16) DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `{prefix}_category`
--

INSERT INTO `{prefix}_category` (`type`, `category_id`, `topic`, `color`, `published`) VALUES
('repairstatus', 8, 'แจ้งซ่อม', '#660000', 1),
('repairstatus', 9, 'กำลังดำเนินการ', '#339900', 1),
('repairstatus', 10, 'รออะไหล่', '#FF3300', 1),
('position', 1, 'a:3:{s:2:\"en\";s:8:\"Director\";s:2:\"la\";s:57:\"ผู้อำนวยการโรงเรียน\";s:2:\"th\";s:57:\"ผู้อำนวยการโรงเรียน\";}', NULL, 1),
('position', 2, 'a:3:{s:2:\"en\";s:13:\"Vice-Director\";s:2:\"la\";s:66:\"รองผู้อำนวยการโรงเรียน\";s:2:\"th\";s:66:\"รองผู้อำนวยการโรงเรียน\";}', NULL, 1),
('position', 3, 'a:3:{s:2:\"en\";s:7:\"Teacher\";s:2:\"la\";s:9:\"ครู\";s:2:\"th\";s:9:\"ครู\";}', NULL, 1),
('term', 2, 'a:3:{s:2:\"en\";s:6:\"Term 2\";s:2:\"la\";s:14:\"เทอม 2\";s:2:\"th\";s:14:\"เทอม 2\";}', NULL, 1),
('term', 1, 'a:3:{s:2:\"en\";s:6:\"Term 1\";s:2:\"la\";s:14:\"เทอม 1\";s:2:\"th\";s:14:\"เทอม 1\";}', NULL, 1),
('room', 8, 'a:3:{s:2:\"en\";s:3:\"3/2\";s:2:\"la\";s:3:\"3/2\";s:2:\"th\";s:3:\"3/2\";}', NULL, 1),
('room', 7, 'a:3:{s:2:\"en\";s:3:\"3/1\";s:2:\"la\";s:3:\"3/1\";s:2:\"th\";s:3:\"3/1\";}', NULL, 1),
('room', 4, 'a:3:{s:2:\"en\";s:3:\"2/1\";s:2:\"la\";s:3:\"2/1\";s:2:\"th\";s:3:\"2/1\";}', NULL, 1),
('room', 5, 'a:3:{s:2:\"en\";s:3:\"2/2\";s:2:\"la\";s:3:\"2/2\";s:2:\"th\";s:3:\"2/2\";}', NULL, 1),
('room', 2, 'a:3:{s:2:\"en\";s:3:\"1/2\";s:2:\"la\";s:3:\"1/2\";s:2:\"th\";s:3:\"1/2\";}', NULL, 1),
('room', 1, 'a:3:{s:2:\"en\";s:3:\"1/1\";s:2:\"la\";s:3:\"1/1\";s:2:\"th\";s:3:\"1/1\";}', NULL, 1),
('class', 6, 'a:3:{s:2:\"en\";s:8:\"Class 12\";s:2:\"la\";s:47:\"มัธยมศึกษาปีที่ 6\";s:2:\"th\";s:47:\"มัธยมศึกษาปีที่ 6\";}', NULL, 1),
('class', 5, 'a:3:{s:2:\"en\";s:8:\"Class 11\";s:2:\"la\";s:47:\"มัธยมศึกษาปีที่ 5\";s:2:\"th\";s:47:\"มัธยมศึกษาปีที่ 5\";}', NULL, 1),
('class', 4, 'a:3:{s:2:\"en\";s:8:\"Class 10\";s:2:\"la\";s:47:\"มัธยมศึกษาปีที่ 4\";s:2:\"th\";s:47:\"มัธยมศึกษาปีที่ 4\";}', NULL, 1),
('class', 3, 'a:3:{s:2:\"en\";s:7:\"Class 9\";s:2:\"la\";s:47:\"มัธยมศึกษาปีที่ 3\";s:2:\"th\";s:47:\"มัธยมศึกษาปีที่ 3\";}', NULL, 1),
('class', 2, 'a:3:{s:2:\"en\";s:7:\"Class 8\";s:2:\"la\";s:47:\"มัธยมศึกษาปีที่ 2\";s:2:\"th\";s:47:\"มัธยมศึกษาปีที่ 2\";}', NULL, 1),
('class', 1, 'a:3:{s:2:\"en\";s:7:\"Class 7\";s:2:\"la\";s:47:\"มัธยมศึกษาปีที่ 1\";s:2:\"th\";s:47:\"มัธยมศึกษาปีที่ 1\";}', NULL, 1),
('department', 8, 'a:3:{s:2:\"la\";s:42:\"ภาษาต่างประเทศ\";s:2:\"th\";s:42:\"ภาษาต่างประเทศ\";s:2:\"en\";s:42:\"ภาษาต่างประเทศ\";}', NULL, 1),
('department', 7, 'a:3:{s:2:\"la\";s:15:\"ศิลปะ\";s:2:\"th\";s:15:\"ศิลปะ\";s:2:\"en\";s:15:\"ศิลปะ\";}', NULL, 1),
('department', 6, 'a:3:{s:2:\"la\";s:33:\"การงานอาชีพ\";s:2:\"th\";s:33:\"การงานอาชีพ\";s:2:\"en\";s:33:\"การงานอาชีพ\";}', NULL, 1),
('department', 5, 'a:3:{s:2:\"la\";s:54:\"สุขศึกษาและพลศึกษา\";s:2:\"th\";s:54:\"สุขศึกษาและพลศึกษา\";s:2:\"en\";s:54:\"สุขศึกษาและพลศึกษา\";}', NULL, 1),
('department', 4, 'a:3:{s:2:\"la\";s:78:\"สังคมศึกษาศาสนาและวัฒนธรรม\";s:2:\"th\";s:78:\"สังคมศึกษาศาสนาและวัฒนธรรม\";s:2:\"en\";s:78:\"สังคมศึกษาศาสนาและวัฒนธรรม\";}', NULL, 1),
('department', 3, 'a:3:{s:2:\"la\";s:21:\"ภาษาไทย\";s:2:\"th\";s:21:\"ภาษาไทย\";s:2:\"en\";s:21:\"ภาษาไทย\";}', NULL, 1),
('department', 2, 'a:3:{s:2:\"la\";s:30:\"คณิตศาสตร์\";s:2:\"th\";s:30:\"คณิตศาสตร์\";s:2:\"en\";s:30:\"คณิตศาสตร์\";}', NULL, 1),
('department', 1, 'a:3:{s:2:\"la\";s:69:\"วิทยาศาสตร์และเทคโนโลยี\";s:2:\"th\";s:69:\"วิทยาศาสตร์และเทคโนโลยี\";s:2:\"en\";s:69:\"วิทยาศาสตร์และเทคโนโลยี\";}', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_course`
--

CREATE TABLE `{prefix}_course` (
  `id` int(11) NOT NULL,
  `course_name` varchar(50) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `teacher_id` int(11) NOT NULL DEFAULT 0,
  `class` int(11) NOT NULL,
  `period` int(11) NOT NULL,
  `credit` decimal(2,1) NOT NULL,
  `type` tinyint(1) NOT NULL,
  `year` int(4) NOT NULL,
  `term` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `{prefix}_course`
--

INSERT INTO `{prefix}_course` (`id`, `course_name`, `course_code`, `teacher_id`, `class`, `period`, `credit`, `type`, `year`, `term`) VALUES
(146, 'ระบบการจัดการคลาวด์และการติดตั้ง', '11116', 0, 7, 3000, '8.0', 1, 0, 0),
(123, 'การโปรแกรมบนเว็บ', '0243212', 1997, 10, 60, '3.0', 2, 2561, 1),
(125, 'โครงสร้างข้อมูลและขั้นตอนวิธีการ', '0602221', 1997, 10, 120, '6.0', 1, 2561, 1),
(121, 'การออกแบบเว็บไซต์และประยุกต์', '0243211', 1660, 10, 60, '3.0', 2, 2561, 1),
(124, 'การโปรแกรมคอมพิวเตอร์', '0243215', 1660, 10, 60, '3.0', 1, 2561, 1),
(126, 'วิทยาศาสตร์สำหรับนักคอมพิวเตอร์', '2234132', 0, 10, 10, '1.0', 1, 0, 0),
(127, 'ระบบการจัดการคลาวด์และการติดตั้ง', '0232411', 0, 10, 3000, '8.0', 2, 0, 0),
(128, 'ระบบสารสนเทศสำหรับธุรกิจอุตสาหกรรม', '06231233', 1660, 11, 50, '3.0', 1, 2561, 1),
(129, 'พื้นฐานวิทยุโทรทัศน์และระบบกระจายเสียง', '0234534`', 1667, 11, 70, '3.0', 1, 2561, 1),
(153, 'ภาษาอังกฤษ2', '11111', 0, 7, 40, '4.0', 1, 0, 0),
(131, 'กระบวนการผลิตภาพยนตร์', '10542343', 0, 12, 120, '9.9', 2, 0, 0),
(132, 'งานโปรดักชันและการจัดแสง', '4234324', 1666, 11, 200, '3.0', 1, 2561, 1),
(133, 'ภาษาไทยเพื่อการสื่อสาร', '4533223', 1998, 10, 40, '1.5', 1, 2561, 1),
(135, 'อิเลกทรอนิกส์เบื้องต้น', '532345', 1997, 10, 30, '0.5', 9, 2561, 1),
(152, 'ระบบการจัดการคลาวด์และการติดตั้ง', '111177', 0, 7, 3000, '8.0', 1, 0, 0),
(151, 'คณิตศาสตร์', 'ค21144', 0, 7, 36, '4.0', 1, 0, 0),
(143, 'คณิตศาสตร์', 'ค21101', 0, 7, 36, '4.0', 1, 0, 0),
(142, 'ภาษาอังกฤษ', '1111', 0, 7, 0, '0.0', 1, 0, 0),
(150, 'ภาษาไทย', 'ท13201', 0, 7, 5, '5.0', 1, 0, 0),
(154, 'กระบวนการผลิตภาพยนตร์2', '10542341', 0, 7, 120, '9.9', 2, 0, 0),
(375, 'รายวิชาเพิ่มเติม เทคโนโลยี (วิทยาการคำนวณ)', 'ว21201', 0, 7, 20, '0.5', 2, 0, 0),
(156, 'ระบบการจัดการคลาวด์และการติดตั้ง', '023211411', 0, 7, 3000, '8.0', 2, 0, 0),
(374, 'รายวิชาเพิ่มเติม คณิตศาสตร์เพื่อการคำนวณ', 'ค21201', 0, 7, 20, '0.5', 2, 0, 0),
(373, 'รายวิชาเพิ่มเติม ภาษาไทยเพื่อการสื่อสาร', 'ท21201', 0, 7, 20, '0.5', 2, 0, 0),
(368, 'สุขศึกษาและพลศึกษา', 'พ21101', 0, 7, 0, '1.0', 1, 0, 0),
(399, 'ระบบการจัดการคลาวด์และการติดตั้ง', '023211411', 0, 7, 3000, '8.0', 2, 1, 127),
(389, 'รายวิชาเพิ่มเติม ภาษาไทยเพื่อการสื่อสาร', 'ท21202', 2812, 7, 20, '0.5', 2, 2562, 2),
(388, 'ภาษาอังกฤษ', 'อ21102', 2812, 7, 60, '1.5', 1, 2562, 2),
(387, 'การงานอาชีพ', 'ง21102', 2812, 7, 40, '1.0', 1, 2562, 2),
(386, 'ศิลปะดนตรีนาฏศิลป์', 'ศ21102', 2812, 7, 40, '1.0', 1, 2562, 2),
(385, 'สุขศึกษาและพลศึกษา', 'พ21102', 2812, 7, 40, '1.0', 1, 2562, 2),
(384, 'ประวัติศาสตร์', 'ส21104', 2812, 7, 20, '0.5', 1, 2562, 2),
(383, 'สังคมศึกษาศาสนาและวํฒนธรรม', 'ส21103', 2812, 7, 40, '1.0', 1, 2562, 2),
(382, 'วิทยาศาสตร์', 'ว21102', 2812, 7, 80, '2.0', 1, 2562, 2),
(381, 'คณิตศาสตร์', 'ค21102', 2812, 7, 40, '1.0', 1, 2562, 2),
(380, 'ภาษาไทย', 'ท21102', 2812, 7, 60, '1.5', 1, 2562, 2),
(398, 'ระบบการจัดการคลาวด์และการติดตั้ง', '023211411', 0, 7, 3000, '8.0', 2, 1, 127),
(378, 'รายวิชาเพิ่มเติม ไฟฟ้าเบื้องต้น', 'ง21201', 2873, 7, 20, '0.0', 2, 2562, 1),
(397, 'ระบบการจัดการคลาวด์และการติดตั้ง', '023211411', 0, 7, 3000, '8.0', 2, 4, 1),
(376, 'รายวิชาเพิ่มเติม หน้าที่พลเมือง', 'ส21231', 2812, 7, 20, '0.5', 2, 2562, 1),
(396, 'ภาษาไทย', 'ท23101', 2812, 8, 60, '1.5', 1, 2563, 1),
(395, 'รายวิชาเพิ่มเติม ภาษาอังกฤษเพื่อการสื่อสาร', 'อ21202', 2812, 7, 20, '0.5', 2, 2562, 2),
(394, 'รายวิชาเพิ่มเติม ไฟฟ้าเบื้องต้น', 'ง21202', 2812, 7, 20, '0.5', 2, 2562, 2),
(393, 'รายวิชาเพิ่มเติม พระพุทธศาสนากับการดำเนินชีวิต', 'ส21202', 2812, 7, 20, '0.5', 2, 2562, 2),
(392, 'รายวิชาเพิ่มเติม หน้าที่พลเมือง', 'ส21232', 2812, 7, 20, '0.5', 2, 2562, 2),
(391, 'รายวิชาเพิ่มเติม เทคโนโลยี (วิทยาการคำนวณ)', 'ว21202', 2812, 7, 20, '0.5', 1, 2562, 2),
(390, 'ราวิชาเพิ่มเติม คณิตศาสตร์เพื่อการคำนวณ', 'ค21202', 2812, 7, 20, '0.5', 2, 2562, 2),
(352, 'research', '342221', 0, 12, 2, '3.0', 1, 0, 0),
(353, 'ิ', 'ิ', 0, 7, 0, '0.0', 1, 0, 0),
(354, 'หน้าที่พลเมือง 5', 'ส20235', 0, 12, 20, '0.5', 2, 0, 0),
(361, 'ภาษาไทย', 'ท 21101', 0, 7, 60, '1.5', 1, 0, 0),
(360, 'ภาษาไทย', 'ท21101', 0, 7, 60, '1.5', 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_edocument`
--

CREATE TABLE `{prefix}_edocument` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) UNSIGNED NOT NULL,
  `department` text DEFAULT NULL,
  `receiver` text DEFAULT NULL,
  `urgency` tinyint(1) NOT NULL DEFAULT 2,
  `last_update` int(11) NOT NULL,
  `document_no` varchar(20) NOT NULL,
  `detail` text NOT NULL,
  `topic` varchar(255) NOT NULL,
  `ext` varchar(4) NOT NULL,
  `size` double UNSIGNED NOT NULL,
  `file` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `{prefix}_edocument`
--

INSERT INTO `{prefix}_edocument` (`id`, `sender_id`, `department`, `urgency`, `last_update`, `document_no`, `detail`, `topic`, `ext`, `size`, `file`) VALUES
(1, 2, ',,', 2, 1632052932, 'DOC-0009', 'ส่งให้แอดมิน', 'คำศัพท์ชื่อป้ายห้องในโรงเรียนเป็นภาษาอังกฤษแนบ', 'pdf', 457639, '1545666283.pdf'),
(2, 1, ',1,2,3,', 2, 1545664264, 'DOC-0008', 'ทดสอบ', 'CanonPixmaMP280-MP287-PrinterDriver', 'jpg', 18795, '1545662500.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_edocument_download`
--

CREATE TABLE `{prefix}_edocument_download` (
  `document_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `downloads` int(11) NOT NULL,
  `last_update` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_grade`
--

CREATE TABLE `{prefix}_grade` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `number` int(11) NOT NULL,
  `room` int(11) NOT NULL,
  `type` tinyint(2) NOT NULL,
  `midterm` int(11) DEFAULT NULL,
  `final` int(11) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_language`
--

CREATE TABLE `{prefix}_language` (
  `id` int(11) NOT NULL,
  `key` text NOT NULL,
  `type` varchar(5) NOT NULL,
  `owner` varchar(20) NOT NULL,
  `js` tinyint(1) NOT NULL,
  `th` text DEFAULT NULL,
  `en` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_logs`
--

CREATE TABLE `{prefix}_logs` (
  `id` int(11) NOT NULL,
  `src_id` int(11) NOT NULL,
  `module` varchar(20) NOT NULL,
  `action` varchar(20) NOT NULL,
  `create_date` datetime NOT NULL,
  `reason` text DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `topic` text NOT NULL,
  `datas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_number`
--

CREATE TABLE `{prefix}_number` (
  `type` varchar(20) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  `auto_increment` int(11) NOT NULL,
  `last_update` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_personnel`
--

CREATE TABLE `{prefix}_personnel` (
  `id` int(11) UNSIGNED NOT NULL,
  `position` int(11) UNSIGNED NOT NULL,
  `department` int(11) NOT NULL,
  `order` tinyint(3) NOT NULL DEFAULT 0,
  `custom` text CHARACTER SET utf8 DEFAULT NULL,
  `id_card` varchar(13) DEFAULT NULL,
  `class` tinyint(1) DEFAULT 0,
  `room` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_student`
--

CREATE TABLE `{prefix}_student` (
  `id` int(11) NOT NULL,
  `student_id` varchar(13) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `parent` varchar(100) DEFAULT NULL,
  `parent_phone` varchar(32) DEFAULT NULL,
  `department` int(11) NOT NULL,
  `class` int(11) NOT NULL,
  `room` int(11) NOT NULL,
  `number` int(11) NOT NULL,
  `id_card` varchar(13) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_user`
--

CREATE TABLE `{prefix}_user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `salt` varchar(32) NOT NULL,
  `password` varchar(50) NOT NULL,
  `token` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `permission` text NOT NULL,
  `name` varchar(150) NOT NULL,
  `sex` varchar(1) DEFAULT NULL,
  `id_card` varchar(13) DEFAULT NULL,
  `expire_date` date DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `provinceID` varchar(3) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `zipcode` varchar(10) DEFAULT NULL,
  `country` varchar(2) DEFAULT 'TH',
  `create_date` datetime DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `picture` varchar(40) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `social` tinyint(1) DEFAULT 0,
  `email` varchar(255) DEFAULT NULL,
  `line_uid` varchar(33) DEFAULT NULL,
  `activatecode` varchar(32) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_user_meta`
--

CREATE TABLE `{prefix}_user_meta` (
  `value` varchar(10) NOT NULL,
  `name` varchar(10) NOT NULL,
  `member_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Indexes for table `{prefix}_category`
--
ALTER TABLE `{prefix}_category`
  ADD KEY `type` (`type`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `{prefix}_course`
--
ALTER TABLE `{prefix}_course`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_edocument`
--
ALTER TABLE `{prefix}_edocument`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_edocument_download`
--
ALTER TABLE `{prefix}_edocument_download`
  ADD KEY `document_id` (`document_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `{prefix}_grade`
--
ALTER TABLE `{prefix}_grade`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `{prefix}_language`
--
ALTER TABLE `{prefix}_language`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_logs`
--
ALTER TABLE `{prefix}_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `src_id` (`src_id`),
  ADD KEY `module` (`module`),
  ADD KEY `action` (`action`);

--
-- Indexes for table `{prefix}_number`
--
ALTER TABLE `{prefix}_number`
  ADD PRIMARY KEY (`type`,`prefix`);

--
-- Indexes for table `{prefix}_personnel`
--
ALTER TABLE `{prefix}_personnel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_card` (`id_card`);

--
-- Indexes for table `{prefix}_student`
--
ALTER TABLE `{prefix}_student`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_card` (`id_card`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `{prefix}_user`
--
ALTER TABLE `{prefix}_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `line_uid` (`line_uid`),
  ADD KEY `username` (`username`),
  ADD KEY `token` (`token`),
  ADD KEY `phone` (`phone`),
  ADD KEY `id_card` (`id_card`),
  ADD KEY `activatecode` (`activatecode`);

--
-- Indexes for table `{prefix}_user_meta`
--
ALTER TABLE `{prefix}_user_meta`
  ADD KEY `member_id` (`member_id`,`name`);

--
-- AUTO_INCREMENT for table `{prefix}_course`
--
ALTER TABLE `{prefix}_course`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_edocument`
--
ALTER TABLE `{prefix}_edocument`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_grade`
--
ALTER TABLE `{prefix}_grade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_language`
--
ALTER TABLE `{prefix}_language`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_logs`
--
ALTER TABLE `{prefix}_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_user`
--
ALTER TABLE `{prefix}_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
