/**
 * modules/school/admin.js
 *
 * ลงทะเบียน route ของโมดูลโรงเรียน และฟังก์ชันเล็ก ๆ ที่ data-* ทำแทนไม่ได้
 */
EventManager.on('router:initialized', () => {
  RouterManager.register('/school-students', {
    template: 'school/students.html',
    title: '{LNG_Student list}',
    requireAuth: true
  });

  RouterManager.register('/school-student', {
    template: 'school/student.html',
    title: '{LNG_Student}',
    menuPath: '/school-students',
    requireAuth: true
  });

  RouterManager.register('/school-courses', {
    template: 'school/courses.html',
    title: '{LNG_Manage} {LNG_Courses}',
    requireAuth: true
  });

  RouterManager.register('/school-course', {
    template: 'school/course.html',
    title: '{LNG_Course}',
    menuPath: '/school-courses',
    requireAuth: true
  });

  RouterManager.register('/school-grades', {
    template: 'school/grades.html',
    title: '{LNG_Grade}',
    menuPath: '/school-courses',
    requireAuth: true
  });

  RouterManager.register('/school-register', {
    template: 'school/registrations.html',
    title: '{LNG_Register course}',
    menuPath: '/school-courses',
    requireAuth: true
  });

  RouterManager.register('/school-grade', {
    template: 'school/transcript.html',
    title: '{LNG_Grade Report}',
    requireAuth: true
  });

  RouterManager.register('/school-import', {
    template: 'school/import.html',
    title: '{LNG_Import}',
    requireAuth: true
  });

  RouterManager.register('/school-settings', {
    template: 'school/settings.html',
    title: '{LNG_Module Settings} {LNG_School}',
    requireAuth: true
  });

  RouterManager.register('/school-gradesettings', {
    template: 'school/gradesettings.html',
    title: '{LNG_Grade calculation}',
    requireAuth: true
  });

  RouterManager.register('/school-categories', {
    template: 'school/categories.html',
    title: '{LNG_School}',
    requireAuth: true
  });
});

/**
 * ฟอร์มรายวิชา: ไม่เลือกครูผู้สอน = รายวิชาต้นแบบ ไม่ต้องกรอกปีการศึกษาและภาคเรียน
 * (ระบบเดิม initTeacher) ฝั่ง PHP บังคับปี/ภาคเรียนเป็น 0 อยู่แล้ว ตรงนี้แค่ปิดช่องให้เห็น
 *
 * @param {HTMLElement} element ฟอร์ม
 * @returns {Function} cleanup
 */
function initSchoolCourse(element) {
  const form = element.closest('form') || element;
  const teacher = form.querySelector('[name="teacher_id"]');
  const toggle = () => {
    const off = !!teacher && String(teacher.value) === '0';
    ['year', 'term'].forEach(name => {
      const input = form.querySelector(`[name="${name}"]`);
      if (input) {
        input.disabled = off;
      }
    });
  };
  if (teacher) {
    teacher.addEventListener('change', toggle);
    toggle();
  }
  return () => {
    if (teacher) {
      teacher.removeEventListener('change', toggle);
    }
  };
}

/**
 * ปุ่มดาวน์โหลดไฟล์ตัวอย่างของหน้านำเข้า
 * ไฟล์ตัวอย่างเติมค่าตามตัวเลือกที่เลือกอยู่ในฟอร์ม (เหมือนระบบเดิม) จึงต้องอ่านค่าจากฟอร์มตอนกด
 *
 * @param {Event} event
 * @param {HTMLElement} element ปุ่มที่มี data-sample-url
 */
function schoolImportSample(event, element) {
  const form = element.closest('form');
  if (!form) {
    return;
  }
  const params = new URLSearchParams();
  form.querySelectorAll('select[name], input[name]').forEach(input => {
    if (input.type === 'file' || input.type === 'checkbox' || input.disabled || input.name === '_token') {
      return;
    }
    params.set(input.name, input.value);
  });
  const year = form.querySelector('[name="year"]');
  if (year && !year.disabled && params.get('type') !== 'student' && !parseInt(year.value, 10)) {
    NotificationManager.error(Now.translate('Please fill in') + ' ' + Now.translate('Academic year'));
    year.focus();
    return;
  }
  const url = element.dataset.sampleUrl;
  window.open(url + (url.indexOf('?') === -1 ? '?' : '&') + params.toString(), '_blank');
}
