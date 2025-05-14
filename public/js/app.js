// Login
import { api, $, prefix } from './core.js';

let pending2fa = false;          // flag between steps
loadItems();
loadScans();
// Scan
$('#scanBtn').onclick = async () => {
  const tag = $('#tagInput').value.trim();
  const res = await api('/scan.php', { method: 'POST', body: JSON.stringify({ tagId: tag }) });
  if (res.status === 'OK') {
    alert(res.item ? `Item: ${res.item.name}\nStock: ${res.item.stock}\nLocation: ${res.item.location}`
                   : 'Tag not associated with any item');
    loadScans();
  } else {
    alert(res.error);
  }
};


// Add item
$('#addItemBtn').onclick = async () => {
  const data = {
    name: $('#itemName').value,
    stock: parseInt($('#itemStock').value || '0', 10),
    location: $('#itemLoc').value,
    rfid_tag: $('#itemTag').value || null,
  };
  const out = await api('/items.php', { method: 'POST', body: JSON.stringify(data) });
  if (out.status === 'created') {
    ['itemName','itemStock','itemLoc','itemTag'].forEach(id => $('#' + id).value = '');
    loadItems();
  } else alert(out.error);
};


// Assign tag
window.assignTag = async (id, current) => {
  const t = prompt('Enter RFID tag (blank to clear):', current || '');
  if (t === null) return;
  const out = await api(`/items.php?id=${id}`, { method: 'PUT', body: `id=${id}&rfid_tag=${encodeURIComponent(t)}` });
  out.status === 'updated' ? loadItems() : alert(out.error);
};

// Delete item button (admin)
window.delItem = async (id) => {
  if (!confirm('Delete item #' + id + '?')) return;
  const out = await api(`/items.php?id=${id}`, { method: 'DELETE' });
  if (out.status === 'deleted') loadItems();
};

function loadItems() {
  api('/items.php').then(items => {
    $('#itemsBody').innerHTML = items.map(i =>
      `<tr><td>${i.id}</td><td>${i.name}</td><td>${i.stock}</td><td>${i.location || ''}</td>` +
      `<td>${i.rfid_tag || ''}</td>` +
      `<td><button onclick=\"assignTag(${i.id},'${i.rfid_tag || ''}')\">Set Tag</button></td></tr>`).join('');
  });
}
function loadScans() {
  api('/scans.php').then(s => {
    $('#scansBody').innerHTML = s.map(r =>
      `<tr><td>${r.id}</td><td>${r.tag_id}</td><td>${r.username}</td><td>${r.scanned_at}</td></tr>`).join('');
  });
}