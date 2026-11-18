-- ---------------------------------------------------------------------------
-- modules/edocument/install/database.sql — ตารางที่โมดูล edocument เป็นเจ้าของ
--
-- **ประกาศที่นี่ที่เดียว** ห้ามประกาศซ้ำใน install/database.sql ของโปรเจ็ค
--
-- คอลัมน์ทุกตัวเหมือนระบบเดิม (SMS 6.x) ทุกประการ ไซต์ที่ย้ายมาจึงไม่ต้องย้ายข้อมูล
--   edocument.receiver    สถานะสมาชิกที่รับเอกสารได้ เก็บเป็น ",0,2,3," (ค้นด้วย LIKE '%,<สถานะ>,%')
--   edocument.last_update unix timestamp ของการส่ง/แก้ไขล่าสุด
--   edocument.file        ชื่อไฟล์ใน datas/edocument/
--   edocument.urgency     0 ด่วนมาก 1 ด่วน 2 ปกติ (คีย์ภาษา URGENCIES)
--   edocument_download    ประวัติการดาวน์โหลด หนึ่งแถวต่อเอกสารต่อสมาชิก
--                         (department_id ไม่มีโค้ดใช้ คงไว้เพื่อไม่ให้ข้อมูลเดิมหาย)
-- ---------------------------------------------------------------------------

CREATE TABLE `{prefix}_edocument` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `urgency` tinyint(1) NOT NULL DEFAULT 2,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `document_no` (`document_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `{prefix}_edocument_download` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(10) NOT NULL,
  `member_id` int(10) NOT NULL,
  `downloads` int(10) NOT NULL,
  `last_update` int(10) NOT NULL,
  `department_id` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`,`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
