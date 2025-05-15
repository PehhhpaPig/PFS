// Login
import { api, $, prefix } from './core.js';

let pending2fa = false;          // flag between steps
loginBtn.disabled = false;
$('#loginBtn').onclick = async () => {
  
  if (!pending2fa) {
    /* ---------- STEP 1 : username + password ---------- */
    const out = await api('/auth/login.php', {
      method : 'POST',
      body   : JSON.stringify({
        username : $('#user').value.trim(),
        password : $('#pass').value
      }),
      credentials : 'include',
    });

    if (out.need_2fa) {
      pending2fa           = true;
      $('#passwordRow').hidden = true;
      $('#codeRow').hidden     = false;
      $('#code').focus();
      
    } else if (out.status === 'OK') {
      onLoginSuccess();
    } else {
            if (out.captcha_required) {
              
              document.getElementById("captcha").hidden = false;
              document.addEventListener("DOMContentLoaded", () => {
  const captchaForm = document.getElementById("captcha");
  const captchaImg = document.getElementById("captchaImg");
  const captchaHint = document.getElementById("captchaHint");
  const loginBtn = document.getElementById("loginBtn");

  if (captchaForm) {
    captchaForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      const code = captchaForm.querySelector("input[name='captcha_code']").value;

      try {
        const response = await fetch("/PFS/api/auth/captcha_verify.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({ captcha_code: code })
        });

        const result = await response.json();

        captchaHint.textContent = ""; // clear previous

        if (result.status === "ok") {
          alert(result.message);
          captchaForm.hidden = true;

          // Optional: Enable login button again
          if (loginBtn) loginBtn.disabled = false;

        } else {
          alert(result.message || "Incorrect CAPTCHA");
          if (result.hint) {
            captchaHint.textContent = result.hint;
          }

          if (result.reload && captchaImg) {
            captchaImg.src = "/PFS/securimage/securimage_show.php?" + Date.now();
          }
        }

      } catch (err) {
        console.error("CAPTCHA verification failed:", err);
        alert("Unable to verify CAPTCHA.");
      }
    });
  }
});

          }
      alert(out.error);
      generateCaptcha("pls");
       /*HEREHEREHEREHERE*/
      loginBtn.disabled = true;
    }
  } else {
    /* ---------- STEP 2 : 6‑digit code ---------- */
    const codeValue = $('#code').value.replace(/\D/g, ''); 
    if (codeValue.length !== 6) { alert('Enter 6 digits'); return; }

    const fd = new FormData();
    fd.append('code', codeValue);
    const res = await fetch('../api/auth/verify_2fa.php', {
      method      : 'POST',
      body        : fd,                  // browser sets multipart header
      credentials : 'include',
      headers     : { 'Accept': 'application/json' }
    });
    const out = await res.json();        // <-- create a new `out`

    if (out.ok || out.status === 'OK') {

      onLoginSuccess();
    } else {
        if (out.captcha_required) {
            document.getElementById("captcha").hidden = false;
            document.addEventListener("DOMContentLoaded", () => {
  const captchaForm = document.getElementById("captcha");
  const captchaImg = document.getElementById("captchaImg");
  const captchaHint = document.getElementById("captchaHint");
  const loginBtn = document.getElementById("loginBtn");

  if (captchaForm) {
    captchaForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      const code = captchaForm.querySelector("input[name='captcha_code']").value;

      try {
        const response = await fetch("/PFS/api/auth/captcha_verify.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({ captcha_code: code })
        });

        const result = await response.json();

        captchaHint.textContent = ""; // clear previous

        if (result.status === "ok") {
          alert(result.message);
          captchaForm.hidden = true;

          // Optional: Enable login button again
          if (loginBtn) loginBtn.disabled = false;

        } else {
          alert(result.message || "Incorrect CAPTCHA");

          if (result.hint) {
            captchaHint.textContent = result.hint;
          }

          if (result.reload && captchaImg) {
            captchaImg.src = "/PFS/securimage/securimage_show.php?" + Date.now();
          }
        }

      } catch (err) {
        console.error("CAPTCHA verification failed:", err);
        alert("Unable to verify CAPTCHA.");
      }
    });
  }
});

          }
      alert(out.error || 'Invalid code');
      loginBtn.disabled = true;
    }
  }
};

function onLoginSuccess () {
  $('#auth').hidden = true;
  $('#app').hidden  = false;
  loadItems();
  loadScans();
  header.innerHTML = "Welcome to NuTracker!";
}


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
async function generateCaptcha(offeringToTheBloodGod){
  const sendHelpYesterday = await fetch('/PFS/api/auth/generate_captcha.php', {
      method : 'POST',
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body   : JSON.stringify({
        this_took_me_too_long_to_figure_out_AI_is_useless_i_am_a_golden_god : ""
      })
    });
  
    const response = await sendHelpYesterday.json();
    try{
       document.getElementById("MyAncestorsAreSmilingOnMeImperial").innerHTML=response.godisdeadandthiscodekilledhim;
    }
    catch (err){
        alert("Well, it was working, ho hum... "+err);
    }
}



document.addEventListener("DOMContentLoaded", () => {
  const captchaForm = document.getElementById("captcha");
  const captchaImg = document.getElementById("captchaImg");
  const captchaHint = document.getElementById("captchaHint");
  const loginBtn = document.getElementById("loginBtn");

  if (captchaForm) {
    captchaForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      const code = captchaForm.querySelector("input[name='captcha_code']").value;

      try {
        const response = await fetch("/PFS/api/auth/captcha_verify.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({ captcha_code: code })
        });

        const result = await response.json();

        captchaHint.textContent = ""; // clear previous

        if (result.status === "ok") {
          alert(result.message);
          captchaForm.hidden = true;
          loginBtn.disabled = false;

        } else {
          alert(result.message + ", login form is locked until CAPTCHA is succesfull." || "Incorrect CAPTCHA" + ", login form is locked until CAPTCHA is succesfull.");
          // Block login fields if CAPTCHA is failed
          loginBtn.disabled = true;
          
          if (result.hint) {
            captchaHint.textContent = result.hint;
          }

          if (result.reload && captchaImg) {
            captchaImg.src = "/PFS/securimage/securimage_show.php?" + Date.now();
          }
        }

      } catch (err) {
        console.error("CAPTCHA verification failed:", err);
        alert("Unable to verify CAPTCHA.");
      }
    });
  }
});
