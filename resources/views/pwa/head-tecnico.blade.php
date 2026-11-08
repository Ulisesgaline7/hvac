<link rel="manifest" href="/manifest-tecnico.webmanifest">
<meta name="theme-color" content="#0284c7">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Control Total Técnico">
<link rel="apple-touch-icon" href="/icons/tecnico-192.png">
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw-tecnico.js').catch(() => {}));
  }
</script>
<script>
  (function () {
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) return;
    const csrf = () => (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    const b64 = (s) => { const p = '='.repeat((4 - s.length % 4) % 4); const r = atob((s + p).replace(/-/g, '+').replace(/_/g, '/')); return Uint8Array.from([...r].map(c => c.charCodeAt(0))); };

    async function suscribir() {
      const reg = await navigator.serviceWorker.ready;
      let sub = await reg.pushManager.getSubscription();
      if (!sub) {
        const k = await (await fetch('/push/clave', { credentials: 'same-origin' })).json();
        if (!k.clave) return false;
        sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64(k.clave) });
      }
      const r = await fetch('/push/suscribir', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify(sub.toJSON()),
      });
      return r.ok;
    }

    window.addEventListener('load', () => {
      if (Notification.permission === 'granted') { suscribir().catch(() => {}); return; }
      if (Notification.permission !== 'default') return;
      const b = document.createElement('button');
      b.textContent = 'Activar avisos';
      b.style.cssText = 'position:fixed;right:12px;bottom:12px;z-index:9999;background:#0284c7;color:#fff;border:0;border-radius:8px;padding:10px 14px;font-size:14px;box-shadow:0 2px 8px rgba(0,0,0,.3)';
      b.onclick = async () => {
        const p = await Notification.requestPermission();
        if (p === 'granted') { await suscribir().catch(() => {}); }
        b.remove();
      };
      document.body.appendChild(b);
    });
  })();
</script>
