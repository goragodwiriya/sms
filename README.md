# ระบบบริหารงานโรงเรียน School Management System (SMS)

เป็นระบบช่วยเหลือในการบริหารงานโรงเรียน หรือ สถานศึกษา ประกอบด้วย 3 ส่วนหลักๆ

- ระบบฐานข้อมูลบุคคลากร ผู้บริหาร ครู-อาจาร์ย (Personnel)
- ระบบสารบรรณ (E-Document) รับ-ส่ง เอกสารภายในหน่วยงาน
- ระบบเกรดของนักเรียน (School)

จุดประสงค์หลักของโปรเจ็คนี้ เพื่อพัฒนาระบบแสดงเกรดของนักเรียน โดยมีความสามารถเบื้องต้นในการจัดเก็บและรายงานผลการเรียนของนักเรียนเป็นรายบุคคล ระบบพื้นฐานแบ่งผู้ใช้งานออกเป็น 4 กลุ่ม (สามารถเพิ่มเติมได้)

- Admin สามารถทำได้ทุกอย่างบนไซต์
- เจ้าหน้าที่บริหาร ทำได้ทุกอย่างเช่นกัน ยกเว้นการตั้งค่าระบบ
- ครู-อาจาร์ย สามารถจัดการรายวิชาและผลการเรียนของนักเรียนในวิชาที่ตัวเองสอน สามารถจัดการข้อมูลนักเรียนได้ทุกคน
- นักเรียน สามารถเข้าดูผลการเรียนของตัวเอง สามารถดูย้อนหลังได้เป็นรายเทอม

รายละเอียดเพิ่มเติม https://www.kotchasan.com/index.php?module=knowledge&id=89

## ความต้องการของระบบ

- PHP 5.6 ขึ้นไป
- ext-mbstring
- PDO Mysql

## การติดตั้งและอัปเกรด

1.  ให้อัปโหลดโค้ดทั้งหมดจากที่ดาวน์โหลด ขึ้นไปบน Server
2.  เรียกตัวติดตั้ง http://domain.tld/install/ (เปลี่ยน domain.tld เป็น URL รวม path ที่ติดตั้งสคริปต์) และดำเนินการตามขั้นตอนการติดตั้งและอัปเกรดจนกว่าจะเสร็จสิ้น
3.  ลบไดเร็คทอรี่ install/ ออก

พบปัญหาการติดตั้ง แนะนำอ่านกระทู้นี้ก่อน https://www.kotchasan.com/forum.html?wbid=3809

## การใช้งาน

- เข้าระบบเป็นผู้ดูแลระบบ : `admin@localhost` และ Password : `admin`

## ข้อตกลงการนำไปใช้งาน

- สามารถนำไปใช้งานส่วนตัวได้
- สามารถพัฒนาต่อยอดได้
- มีข้อสงสัยสามารถสอบถามได้ที่บอร์ดของคชสาร https://www.kotchasan.com
- ต้องการให้ผู้เขียนพัฒนาเพิ่มเติม ติดต่อผู้เขียนได้โดยตรง (อาจมีค่าใช้จ่าย)
- ผู้เขียนไม่รับผิดชอบข้อผิดพลาดใดๆในการใช้งาน
- ห้ามขาย ถ้าต้องการนำไปพัฒนาต่อเพื่อขายให้ติดต่อผู้เขียนก่อน (เพื่อบริจาค)

## หากต้องการสนับสนุนผู้เขียน สามารถบริจาคช่วยเหลือค่า Server ได้ที่

```
ธนาคาร กสิกรไทย สาขากาญจนบุรี
เลขที่บัญชี 221-2-78341-5
ชื่อบัญชี กรกฎ วิริยะ
```
