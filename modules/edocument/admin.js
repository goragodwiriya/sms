/**
 * modules/edocument/admin.js
 *
 * ลงทะเบียน route ของโมดูลหนังสือเวียน
 */
EventManager.on('router:initialized', () => {
  // หนังสือรับ (สมาชิกทุกคน)
  RouterManager.register('/edocument', {
    template: 'edocument/inbox.html',
    title: '{LNG_Received document}',
    requireAuth: true
  });

  // หนังสือส่ง (can_upload_edocument)
  RouterManager.register('/edocument-sent', {
    template: 'edocument/outbox.html',
    title: '{LNG_Sent document}',
    requireAuth: true
  });

  RouterManager.register('/edocument-write', {
    template: 'edocument/document.html',
    title: '{LNG_Send Document}',
    menuPath: '/edocument-sent',
    requireAuth: true
  });

  RouterManager.register('/edocument-downloads', {
    template: 'edocument/downloads.html',
    title: '{LNG_Download history}',
    menuPath: '/edocument-sent',
    requireAuth: true
  });

  RouterManager.register('/edocument-settings', {
    template: 'edocument/settings.html',
    title: '{LNG_Module Settings} {LNG_E-Document}',
    requireAuth: true
  });
});

/**
 * ไอคอนชนิดไฟล์ (คอลัมน์ ext)
 */
function formatEdocumentExt(cell, rawValue, rowData) {
  const ext = Utils.string.escape(rawValue || '');
  cell.innerHTML = rowData?.ext_icon ? `<img class="edocument-ext" src="${Utils.string.escape(rowData.ext_icon)}" alt="${ext}" title="${ext}">` : '';
}

/**
 * ความเร่งด่วน 0 ด่วนมาก 1 ด่วน 2 ปกติ (คีย์ภาษา URGENCIES)
 */
function formatEdocumentUrgency(cell, rawValue, rowData) {
  const urgency = Number(rawValue);
  cell.innerHTML = `<span class="edocument-urgency urgency-${urgency}">${Utils.string.escape(rowData?.urgency_text || '')}</span>`;
}

/**
 * หนังสือที่ยังไม่ได้รับ (ยังไม่เคยดาวน์โหลด)
 */
function formatEdocumentNew(cell, rawValue) {
  cell.innerHTML = Number(rawValue) === 1 ? `<span class="icon-email edocument-new notext" title="${Utils.string.escape(Now.translate('New document'))}"></span>` : '';
}
