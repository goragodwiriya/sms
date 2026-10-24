-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 27, 2024 at 10:54 AM
-- Server version: 10.6.16-MariaDB-0ubuntu0.22.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_category`
--

CREATE TABLE `{prefix}_category` (
  `type` varchar(20) NOT NULL,
  `category_id` varchar(10) DEFAULT '0',
  `language` varchar(2) DEFAULT '',
  `topic` varchar(150) NOT NULL,
  `color` varchar(16) DEFAULT NULL,
  `published` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{prefix}_category`
--

INSERT INTO `{prefix}_category` (`type`, `category_id`, `language`, `topic`, `color`, `published`) VALUES
('repairstatus', '8', '', 'แจ้งซ่อม', '#660000', 1),
('repairstatus', '9', '', 'กำลังดำเนินการ', '#339900', 1),
('repairstatus', '10', '', 'รออะไหล่', '#FF3300', 1),
('position', '1', 'en', 'Director', NULL, 1),
('position', '1', 'la', 'ผู้อำนวยการโรงเรียน', NULL, 1),
('position', '1', 'th', 'ผู้อำนวยการโรงเรียน', NULL, 1),
('position', '2', 'en', 'Vice-Director', NULL, 1),
('position', '2', 'la', 'รองผู้อำนวยการโรงเรียน', NULL, 1),
('position', '2', 'th', 'รองผู้อำนวยการโรงเรียน', NULL, 1),
('position', '3', 'en', 'Teacher', NULL, 1),
('position', '3', 'la', 'ครู', NULL, 1),
('position', '3', 'th', 'ครู', NULL, 1),
('term', '2', 'en', 'Term 2', NULL, 1),
('term', '2', 'la', 'เทอม 2', NULL, 1),
('term', '2', 'th', 'เทอม 2', NULL, 1),
('term', '1', 'en', 'Term 1', NULL, 1),
('term', '1', 'la', 'เทอม 1', NULL, 1),
('term', '1', 'th', 'เทอม 1', NULL, 1),
('room', '8', 'en', '3/2', NULL, 1),
('room', '8', 'la', '3/2', NULL, 1),
('room', '8', 'th', '3/2', NULL, 1),
('room', '7', 'en', '3/1', NULL, 1),
('room', '7', 'la', '3/1', NULL, 1),
('room', '7', 'th', '3/1', NULL, 1),
('room', '4', 'en', '2/1', NULL, 1),
('room', '4', 'la', '2/1', NULL, 1),
('room', '4', 'th', '2/1', NULL, 1),
('room', '5', 'en', '2/2', NULL, 1),
('room', '5', 'la', '2/2', NULL, 1),
('room', '5', 'th', '2/2', NULL, 1),
('room', '2', 'en', '1/2', NULL, 1),
('room', '2', 'la', '1/2', NULL, 1),
('room', '2', 'th', '1/2', NULL, 1),
('room', '1', 'en', '1/1', NULL, 1),
('room', '1', 'la', '1/1', NULL, 1),
('room', '1', 'th', '1/1', NULL, 1),
('class', '6', 'en', 'Class 12', NULL, 1),
('class', '6', 'la', 'มัธยมศึกษาปีที่ 6', NULL, 1),
('class', '6', 'th', 'มัธยมศึกษาปีที่ 6', NULL, 1),
('class', '5', 'en', 'Class 11', NULL, 1),
('class', '5', 'la', 'มัธยมศึกษาปีที่ 5', NULL, 1),
('class', '5', 'th', 'มัธยมศึกษาปีที่ 5', NULL, 1),
('class', '4', 'en', 'Class 10', NULL, 1),
('class', '4', 'la', 'มัธยมศึกษาปีที่ 4', NULL, 1),
('class', '4', 'th', 'มัธยมศึกษาปีที่ 4', NULL, 1),
('class', '3', 'en', 'Class 9', NULL, 1),
('class', '3', 'la', 'มัธยมศึกษาปีที่ 3', NULL, 1),
('class', '3', 'th', 'มัธยมศึกษาปีที่ 3', NULL, 1),
('class', '2', 'en', 'Class 8', NULL, 1),
('class', '2', 'la', 'มัธยมศึกษาปีที่ 2', NULL, 1),
('class', '2', 'th', 'มัธยมศึกษาปีที่ 2', NULL, 1),
('class', '1', 'en', 'Class 7', NULL, 1),
('class', '1', 'la', 'มัธยมศึกษาปีที่ 1', NULL, 1),
('class', '1', 'th', 'มัธยมศึกษาปีที่ 1', NULL, 1),
('department', '7', 'en', 'ศิลปะ', NULL, 1),
('department', '7', 'la', 'ศิลปะ', NULL, 1),
('department', '7', 'th', 'ศิลปะ', NULL, 1),
('department', '8', 'en', 'ภาษาต่างประเทศ', NULL, 1),
('department', '8', 'la', 'ภาษาต่างประเทศ', NULL, 1),
('department', '8', 'th', 'ภาษาต่างประเทศ', NULL, 1),
('department', '6', 'en', 'การงานอาชีพ', NULL, 1),
('department', '6', 'la', 'การงานอาชีพ', NULL, 1),
('department', '6', 'th', 'การงานอาชีพ', NULL, 1),
('department', '5', 'en', 'สุขศึกษาและพลศึกษา', NULL, 1),
('department', '5', 'la', 'สุขศึกษาและพลศึกษา', NULL, 1),
('department', '5', 'th', 'สุขศึกษาและพลศึกษา', NULL, 1),
('department', '4', 'en', 'สังคมศึกษาศาสนาและวัฒนธรรม', NULL, 1),
('department', '4', 'la', 'สังคมศึกษาศาสนาและวัฒนธรรม', NULL, 1),
('department', '4', 'th', 'สังคมศึกษาศาสนาและวัฒนธรรม', NULL, 1),
('department', '2', 'en', 'คณิตศาสตร์', NULL, 1),
('department', '2', 'la', 'คณิตศาสตร์', NULL, 1),
('department', '2', 'th', 'คณิตศาสตร์', NULL, 1),
('department', '3', 'en', 'ภาษาไทย', NULL, 1),
('department', '3', 'la', 'ภาษาไทย', NULL, 1),
('department', '3', 'th', 'ภาษาไทย', NULL, 1),
('department', '1', 'en', 'วิทยาศาสตร์และเทคโนโลยี', NULL, 1),
('department', '1', 'la', 'วิทยาศาสตร์และเทคโนโลยี', NULL, 1),
('department', '1', 'th', 'วิทยาศาสตร์และเทคโนโลยี', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_course`
--

CREATE TABLE `{prefix}_course` (
  `id` int(11) NOT NULL,
  `course_name` varchar(50) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `teacher_id` int(11) DEFAULT 0,
  `class` int(11) NOT NULL,
  `period` int(11) NOT NULL,
  `credit` decimal(2,1) NOT NULL,
  `type` tinyint(1) NOT NULL,
  `year` int(4) NOT NULL,
  `term` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{prefix}_course`
--

INSERT INTO `{prefix}_course` (`id`, `course_name`, `course_code`, `teacher_id`, `class`, `period`, `credit`, `type`, `year`, `term`) VALUES
(1, 'การออกแบบเว็บไซต์และประยุกต์', '0243211', 1660, 10, 60, 3.0, 2, 2561, 1),
(2, 'การโปรแกรมบนเว็บ', '0243212', 1997, 10, 60, 3.0, 2, 2561, 1),
(3, 'การโปรแกรมคอมพิวเตอร์', '0243215', 1660, 10, 60, 3.0, 1, 2561, 1),
(4, 'โครงสร้างข้อมูลและขั้นตอนวิธีการ', '0602221', 1997, 10, 120, 6.0, 1, 2561, 1),
(5, 'วิทยาศาสตร์สำหรับนักคอมพิวเตอร์', '2234132', 0, 10, 10, 1.0, 1, 0, 0),
(6, 'ระบบการจัดการคลาวด์และการติดตั้ง', '0232411', 0, 10, 3000, 8.0, 2, 0, 0),
(7, 'ระบบสารสนเทศสำหรับธุรกิจอุตสาหกรรม', '06231233', 1660, 11, 50, 3.0, 1, 2561, 1),
(8, 'พื้นฐานวิทยุโทรทัศน์และระบบกระจายเสียง', '0234534`', 1667, 11, 70, 3.0, 1, 2561, 1),
(9, 'กระบวนการผลิตภาพยนตร์', '10542343', 0, 12, 120, 9.9, 2, 0, 0),
(10, 'งานโปรดักชันและการจัดแสง', '4234324', 1666, 11, 200, 3.0, 1, 2561, 1),
(11, 'ภาษาไทยเพื่อการสื่อสาร', '4533223', 1998, 10, 40, 1.5, 1, 2561, 1),
(12, 'อิเลกทรอนิกส์เบื้องต้น', '532345', 1997, 10, 30, 0.5, 9, 2561, 1),
(13, 'ภาษาอังกฤษ', '1111', 0, 7, 0, 0.0, 1, 0, 0),
(14, 'คณิตศาสตร์', 'ค21101', 0, 7, 36, 4.0, 1, 0, 0),
(15, 'ระบบการจัดการคลาวด์และการติดตั้ง', '11116', 0, 7, 3000, 8.0, 1, 0, 0),
(16, 'ภาษาไทย', 'ท13201', 0, 7, 5, 5.0, 1, 0, 0),
(17, 'คณิตศาสตร์', 'ค21144', 0, 7, 36, 4.0, 1, 0, 0),
(18, 'ระบบการจัดการคลาวด์และการติดตั้ง', '111177', 0, 7, 3000, 8.0, 1, 0, 0),
(19, 'ภาษาอังกฤษ2', '11111', 0, 7, 40, 4.0, 1, 0, 0),
(20, 'กระบวนการผลิตภาพยนตร์2', '10542341', 0, 7, 120, 9.9, 2, 0, 0),
(21, 'ระบบการจัดการคลาวด์และการติดตั้ง', '023211411', 0, 7, 3000, 8.0, 2, 0, 0),
(22, 'research', '342221', 0, 12, 2, 3.0, 1, 0, 0),
(23, 'หน้าที่พลเมือง 5', 'ส20235', 0, 12, 20, 0.5, 2, 0, 0),
(24, 'ภาษาไทย', 'ท21101', 0, 7, 60, 1.5, 1, 0, 0),
(25, 'ภาษาไทย', 'ท 21101', 0, 7, 60, 1.5, 1, 0, 0),
(26, 'สุขศึกษาและพลศึกษา', 'พ21101', 0, 7, 0, 1.0, 1, 0, 0),
(27, 'รายวิชาเพิ่มเติม ภาษาไทยเพื่อการสื่อสาร', 'ท21201', 0, 7, 20, 0.5, 2, 0, 0),
(28, 'รายวิชาเพิ่มเติม คณิตศาสตร์เพื่อการคำนวณ', 'ค21201', 0, 7, 20, 0.5, 2, 0, 0),
(29, 'รายวิชาเพิ่มเติม เทคโนโลยี (วิทยาการคำนวณ)', 'ว21201', 0, 7, 20, 0.5, 2, 0, 0),
(30, 'รายวิชาเพิ่มเติม หน้าที่พลเมือง', 'ส21231', 2812, 7, 20, 0.5, 2, 2562, 1),
(31, 'รายวิชาเพิ่มเติม ไฟฟ้าเบื้องต้น', 'ง21201', 2873, 7, 20, 0.0, 2, 2562, 1),
(32, 'ภาษาไทย', 'ท21102', 2812, 7, 60, 1.5, 1, 2562, 2),
(33, 'คณิตศาสตร์', 'ค21102', 2812, 7, 40, 1.0, 1, 2562, 2),
(34, 'วิทยาศาสตร์', 'ว21102', 2812, 7, 80, 2.0, 1, 2562, 2),
(35, 'สังคมศึกษาศาสนาและวํฒนธรรม', 'ส21103', 2812, 7, 40, 1.0, 1, 2562, 2),
(36, 'ประวัติศาสตร์', 'ส21104', 2812, 7, 20, 0.5, 1, 2562, 2),
(37, 'สุขศึกษาและพลศึกษา', 'พ21102', 2812, 7, 40, 1.0, 1, 2562, 2),
(38, 'ศิลปะดนตรีนาฏศิลป์', 'ศ21102', 2812, 7, 40, 1.0, 1, 2562, 2),
(39, 'การงานอาชีพ', 'ง21102', 2812, 7, 40, 1.0, 1, 2562, 2),
(40, 'ภาษาอังกฤษ', 'อ21102', 2812, 7, 60, 1.5, 1, 2562, 2),
(41, 'รายวิชาเพิ่มเติม ภาษาไทยเพื่อการสื่อสาร', 'ท21202', 2812, 7, 20, 0.5, 2, 2562, 2),
(42, 'ราวิชาเพิ่มเติม คณิตศาสตร์เพื่อการคำนวณ', 'ค21202', 2812, 7, 20, 0.5, 2, 2562, 2),
(43, 'รายวิชาเพิ่มเติม เทคโนโลยี (วิทยาการคำนวณ)', 'ว21202', 2812, 7, 20, 0.5, 1, 2562, 2),
(44, 'รายวิชาเพิ่มเติม หน้าที่พลเมือง', 'ส21232', 2812, 7, 20, 0.5, 2, 2562, 2),
(45, 'รายวิชาเพิ่มเติม พระพุทธศาสนากับการดำเนินชีวิต', 'ส21202', 2812, 7, 20, 0.5, 2, 2562, 2),
(46, 'รายวิชาเพิ่มเติม ไฟฟ้าเบื้องต้น', 'ง21202', 2812, 7, 20, 0.5, 2, 2562, 2),
(47, 'รายวิชาเพิ่มเติม ภาษาอังกฤษเพื่อการสื่อสาร', 'อ21202', 2812, 7, 20, 0.5, 2, 2562, 2),
(48, 'ภาษาไทย', 'ท23101', 2812, 8, 60, 1.5, 1, 2563, 1),
(49, 'ระบบการจัดการคลาวด์และการติดตั้ง', '023211411', 0, 7, 3000, 8.0, 2, 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_edocument`
--

CREATE TABLE `{prefix}_edocument` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver` text NOT NULL,
  `last_update` int(11) NOT NULL,
  `document_no` varchar(20) NOT NULL,
  `detail` text NOT NULL,
  `topic` varchar(255) NOT NULL,
  `ext` varchar(4) NOT NULL,
  `size` double NOT NULL,
  `file` varchar(15) NOT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `urgency` tinyint(1) NOT NULL DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `{prefix}_edocument`
--

INSERT INTO `{prefix}_edocument` (`id`, `sender_id`, `receiver`, `last_update`, `document_no`, `detail`, `topic`, `ext`, `size`, `file`, `ip`, `urgency`) VALUES
(1, 1, ',1,2,3,', 1631797416, 'ที่ ศธ1234/0001', 'แบบฟอร์มขอจดโดเมน ac.th', 'แบบฟอร์มขอจดโดเมน ac.th', 'jpg', 35592, '1631797416.jpg', '124.122.2.8', 2),
(2, 1, ',1,', 1553939958, 'ที่ ศธ1234/0002', 'ทดสอบครับ', 'ทดสอบ', 'jpg', 23734, '1553939958.jpg', '110.168.79.106', 2);

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_edocument_download`
--

CREATE TABLE `{prefix}_edocument_download` (
  `id` int(11) NOT NULL,
  `document_id` int(10) NOT NULL,
  `member_id` int(10) NOT NULL,
  `downloads` int(10) NOT NULL,
  `last_update` int(10) NOT NULL,
  `department_id` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_grade`
--

CREATE TABLE `{prefix}_grade` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `number` int(11) DEFAULT NULL,
  `room` int(11) NOT NULL,
  `type` tinyint(2) NOT NULL,
  `midterm` int(11) DEFAULT NULL,
  `final` int(11) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_number`
--

CREATE TABLE `{prefix}_number` (
  `type` varchar(20) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  `auto_increment` int(11) NOT NULL,
  `last_update` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_personnel`
--

CREATE TABLE `{prefix}_personnel` (
  `id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `department` int(11) NOT NULL,
  `order` tinyint(3) NOT NULL DEFAULT 0,
  `custom` text DEFAULT NULL,
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
  `number` int(11) DEFAULT NULL,
  `id_card` varchar(13) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_user`
--

CREATE TABLE `{prefix}_user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `salt` varchar(32) DEFAULT '',
  `password` varchar(50) NOT NULL,
  `token` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `permission` text DEFAULT '',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_user_meta`
--

CREATE TABLE `{prefix}_user_meta` (
  `value` varchar(10) NOT NULL,
  `name` varchar(20) NOT NULL,
  `member_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Indexes for table `{prefix}_category`
--
ALTER TABLE `{prefix}_category`
  ADD KEY `type` (`type`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `language` (`language`);

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
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `{prefix}_edocument_download`
--
ALTER TABLE `{prefix}_edocument_download`
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
