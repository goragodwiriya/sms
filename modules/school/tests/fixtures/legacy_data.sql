-- ---------------------------------------------------------------------------
-- ข้อมูลตัวอย่างในรูปแบบของ "ระบบเดิม" (SMS 6.x) ใช้คู่กับ legacy_schema.sql
-- (สำเนา install/database.sql ของระบบเดิม) เพื่อพิสูจน์ว่าตัวปรับรุ่นพาข้อมูลจริงมาได้
--
-- รหัสผ่านใช้รูปแบบเก่า sha1(password_key . รหัสผ่าน . salt)
-- password_key = smsfixturekey (ค่าใน settings/config.php ของไซต์ตัวอย่าง)
--   admin / secret123
--   ครู/นักเรียน = เลขประชาชน / วันเกิด พ.ศ. ปปปปดดวว
-- ---------------------------------------------------------------------------

INSERT INTO `{prefix}_user` (`id`, `username`, `salt`, `password`, `token`, `status`, `permission`, `name`, `sex`, `id_card`, `phone`, `create_date`, `birthday`, `active`, `social`, `activatecode`) VALUES
(1, 'admin', 'abc', SHA1(CONCAT('smsfixturekey', 'secret123', 'abc')), NULL, 1, '', 'แอดมิน', 'm', NULL, NULL, '2024-01-01 08:00:00', NULL, 1, 0, ''),
(2, '1100000000001', 's2', SHA1(CONCAT('smsfixturekey', '25230515', 's2')), NULL, 2, ',can_teacher,can_upload_edocument,can_rate_student,', 'ครูสมชาย ใจดี', 'm', NULL, '0811111111', '2024-01-01 08:00:00', '1980-05-15', 1, 0, ''),
(3, '1100000000002', 's3', SHA1(CONCAT('smsfixturekey', '25250101', 's3')), NULL, 2, ',can_manage_course,can_manage_student,can_upload_edocument,', 'ครูสมหญิง รักเรียน', 'f', NULL, '0822222222', '2024-01-01 08:00:00', '1982-01-01', 1, 0, ''),
(4, '1100000000003', 's4', SHA1(CONCAT('smsfixturekey', '25131231', 's4')), NULL, 3, ',can_manage_personnel,can_handle_all_edocument,can_upload_edocument,', 'ผอ.บริหาร ดีเด่น', 'm', NULL, '0833333333', '2024-01-01 08:00:00', '1970-12-31', 1, 0, ''),
(5, NULL, 's5', 'x', NULL, 2, '', 'ครูเกษียณ แล้ว', 'f', NULL, '', '2024-01-01 08:00:00', NULL, 0, 0, ''),
(6, '1100000000006', 's6', SHA1(CONCAT('smsfixturekey', '25300606', 's6')), NULL, 2, ',can_rate_student,', 'ครูให้คะแนน อย่างเดียว', 'f', NULL, '0866666666', '2024-01-01 08:00:00', '1987-06-06', 1, 0, ''),
(10, '1200000000010', 's10', SHA1(CONCAT('smsfixturekey', '25500102', 's10')), NULL, 0, '', 'ด.ช.หนึ่ง เรียนดี', 'm', NULL, '0900000010', '2024-01-01 08:00:00', '2007-01-02', 1, 0, ''),
(11, '1200000000011', 's11', SHA1(CONCAT('smsfixturekey', '25500304', 's11')), NULL, 0, '', 'ด.ญ.สอง ขยัน', 'f', NULL, '0900000011', '2024-01-01 08:00:00', '2007-03-04', 1, 0, ''),
(12, NULL, 's12', 'x', NULL, 0, '', 'ด.ช.สาม ไม่มีบัตร', 'm', NULL, '', '2024-01-01 08:00:00', NULL, 1, 0, ''),
(13, '1200000000013', 's13', SHA1(CONCAT('smsfixturekey', '25500506', 's13')), NULL, 0, '', 'ด.ญ.สี่ ห้องสอง', 'f', NULL, '', '2024-01-01 08:00:00', '2007-05-06', 1, 0, ''),
(14, '1200000000014', 's14', SHA1(CONCAT('smsfixturekey', '25480708', 's14')), NULL, 0, '', 'นายห้า จบแล้ว', 'm', NULL, '', '2024-01-01 08:00:00', '2005-07-08', 0, 0, '');

INSERT INTO `{prefix}_personnel` (`id`, `position`, `department`, `order`, `custom`, `id_card`, `class`, `room`) VALUES
(2, 3, 2, 1, 'a:1:{s:7:"address";s:44:"123 หมู่ 1 ต.ในเมือง";}', '1100000000001', 1, 1),
(3, 3, 3, 2, NULL, '1100000000002', 0, 0),
(4, 1, 0, 0, 'a:1:{s:7:"address";s:37:"9/9 ถนนพหลโยธิน";}', '1100000000003', 0, 0),
(5, 3, 1, 3, NULL, NULL, 0, 0),
(6, 3, 1, 4, NULL, '1100000000006', 1, 2);

INSERT INTO `{prefix}_student` (`id`, `student_id`, `address`, `parent`, `parent_phone`, `department`, `class`, `room`, `number`, `id_card`) VALUES
(10, '1001', 'บ้านเลขที่ 1', 'นายพ่อ หนึ่ง', '0891111111', 1, 1, 1, 1, '1200000000010'),
(11, '1002', 'บ้านเลขที่ 2', 'นางแม่ สอง', '0892222222', 1, 1, 1, 2, '1200000000011'),
(12, '1003', NULL, NULL, NULL, 1, 1, 1, 3, NULL),
(13, '1004', 'บ้าน 4', 'ผู้ปกครอง สี่', '0894444444', 1, 1, 2, 1, '1200000000013'),
(14, '0999', 'บ้าน 5', 'ผู้ปกครอง ห้า', '0895555555', 1, 6, 1, 1, '1200000000014');

INSERT INTO `{prefix}_course` (`id`, `course_name`, `course_code`, `teacher_id`, `class`, `period`, `credit`, `type`, `year`, `term`) VALUES
(50, 'คณิตศาสตร์พื้นฐาน', 'ค21101', 2, 1, 60, 1.5, 1, 2567, 1),
(51, 'ภาษาไทย', 'ท21101', 3, 1, 60, 1.5, 1, 2567, 1),
(52, 'วิทยาศาสตร์', 'ว21101', 0, 1, 80, 2.0, 1, 0, 0),
(53, 'คณิตศาสตร์พื้นฐาน', 'ค21101', 2, 1, 60, 1.5, 1, 2566, 2);

INSERT INTO `{prefix}_grade` (`id`, `student_id`, `course_id`, `number`, `room`, `type`, `midterm`, `final`, `grade`) VALUES
(1, 10, 50, 1, 1, 0, 30, 40, '3'),
(2, 11, 50, 2, 1, 1, NULL, NULL, 'ร.'),
(3, 10, 51, 1, 1, 0, 40, 45, '4'),
(4, 13, 50, 1, 2, 0, 10, 20, '0'),
(5, 10, 53, 1, 1, 0, 25, 25, '1');

INSERT INTO `{prefix}_edocument` (`id`, `sender_id`, `receiver`, `last_update`, `document_no`, `detail`, `topic`, `ext`, `size`, `file`, `ip`, `urgency`) VALUES
(3, 2, ',2,3,', 1700000000, 'DOC-0003', 'รายละเอียดหนังสือเชิญประชุม', 'หนังสือเชิญประชุม', 'pdf', 1234, '1700000000.pdf', NULL, 0),
(4, 4, ',0,2,', 1700000100, 'DOC-0004', 'ประกาศถึงนักเรียนและครู', 'ประกาศ', 'pdf', 100, '1700000100.pdf', NULL, 1);

INSERT INTO `{prefix}_edocument_download` (`id`, `document_id`, `member_id`, `downloads`, `last_update`, `department_id`) VALUES
(1, 1, 2, 2, 1700000500, NULL),
(2, 3, 3, 1, 1700000600, NULL);

INSERT INTO `{prefix}_number` (`type`, `prefix`, `auto_increment`, `last_update`) VALUES
('edocument_format_no', '', 4, '2024-01-01');
