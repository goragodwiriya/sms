/**
 * modules/personnel/admin.js
 *
 * ลงทะเบียน route ของโมดูลบุคลากร
 */
EventManager.on('router:initialized', () => {
  // รายชื่อบุคลากรปัจจุบัน (สมาชิกทุกคน)
  RouterManager.register('/personnel', {
    template: 'personnel/lists.html',
    title: '{LNG_Personnel list}',
    requireAuth: true
  });

  // จัดการบุคลากร (can_manage_personnel)
  RouterManager.register('/personnel-setup', {
    template: 'personnel/setup.html',
    title: '{LNG_List of} {LNG_Personnel}',
    requireAuth: true
  });

  RouterManager.register('/personnel-edit', {
    template: 'personnel/person.html',
    title: '{LNG_Personnel}',
    menuPath: '/personnel-setup',
    requireAuth: true
  });

  RouterManager.register('/personnel-import', {
    template: 'personnel/import.html',
    title: '{LNG_Import} {LNG_Personnel list}',
    requireAuth: true
  });

  RouterManager.register('/personnel-settings', {
    template: 'personnel/settings.html',
    title: '{LNG_Module Settings} {LNG_Personnel}',
    requireAuth: true
  });

  RouterManager.register('/personnel-categories', {
    template: 'personnel/categories.html',
    title: '{LNG_Personnel}',
    requireAuth: true
  });
});
