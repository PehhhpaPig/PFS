import { api, $ } from './core.js';

let pending2fa = false; // flag between steps

document.addEventListener('DOMContentLoaded', () => {
  const loginBtn = $('#loginBtn');
  if (loginBtn) {
    loginBtn.onclick = async () => {
      console.log('Login button clicked'); // Debugging log
      if (!pending2fa) {
        /* ---------- STEP 1 : username + password ---------- */
        const out = await api('/auth/login.php', {
          method: 'POST',
          body: JSON.stringify({
            username: $('#user').value.trim(),
            password: $('#pass').value,
          }),
        });

        console.log('Backend response:', out); // Debugging log

        if (out.need_2fa) {
          pending2fa = true;
          $('#passwordRow').hidden = true;
          $('#codeRow').hidden = false;
          $('#code').focus();
        } else if (out.status === 'OK') {
          console.log('Redirecting to app.html'); // Debugging log
          window.location.href = 'app.html'; // Redirect to the Main App UI
        } else {
          alert(out.error);
        }
      } else {
        /* ---------- STEP 2 : 6‑digit code ---------- */
        const codeValue = $('#code').value.replace(/\D/g, '');
        if (codeValue.length !== 6) {
          alert('Enter 6 digits');
          return;
        }

        const fd = new FormData();
        fd.append('code', codeValue);
        const res = await fetch('../api/auth/verify_2fa.php', {
          method: 'POST',
          body: fd, // browser sets multipart header
          credentials: 'include',
          headers: { Accept: 'application/json' },
        });
        const out = await res.json(); // <-- create a new `out`

        if (out.ok || out.status === 'OK') {
          console.log('Redirecting to app.html'); // Debugging log
          window.location.href = 'app.html'; // Redirect to the Main App UI
        } else {
          alert(out.error || 'Invalid code');
        }
      }
    };
  }
});
