import { api, $ } from './core.js';

console.log('scan.js loaded');

document.addEventListener('DOMContentLoaded', () => {
  const scanBtn = $('#scanBtn');
  if (scanBtn) {
    scanBtn.onclick = async () => {
      const tag = $('#tagInput').value.trim();
      const res = await api('/scan.php', { method: 'POST', body: JSON.stringify({ tagId: tag }) });
      if (res.status === 'OK') {
        alert(res.item ? `Item: ${res.item.name}\nStock: ${res.item.stock}\nLocation: ${res.item.location}` : 'Tag not associated with any item');
        loadScans();
      } else {
        alert(res.error);
      }
    };
  }

  function loadScans() {
    api('/scans.php').then((s) => {
      $('#scansBody').innerHTML = s
        .map(
          (r) =>
            `<tr><td>${r.id}</td><td>${r.tag_id}</td><td>${r.username}</td><td>${r.scanned_at}</td></tr>`
        )
        .join('');
    });
  }

  loadScans();
  
});
