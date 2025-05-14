export const prefix = window.location.pathname.includes('/PFS/') ? '/PFS/api' : '/api';
export const $ = (sel) => document.querySelector(sel);

export async function api(path, opts = {}) {
  const res = await fetch(`${prefix}${path}`, {
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    ...opts,
  });
  return res.json();
}