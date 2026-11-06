<style>
#ctCookies{position:fixed;left:16px;right:16px;bottom:16px;max-width:560px;margin:0 auto;background:#0f172a;color:#fff;padding:16px 18px;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.3);z-index:9999;display:none;font-size:14px;line-height:1.5}
#ctCookies a{color:#7dd3fc;text-decoration:underline}
#ctCookies .ct-b{display:flex;gap:10px;margin-top:12px;flex-wrap:wrap}
#ctCookies button{border:0;border-radius:8px;padding:9px 16px;font-weight:700;cursor:pointer;font-size:14px}
#ctCookies .ok{background:#38bdf8;color:#0f172a}
#ctCookies .no{background:#334155;color:#fff}
</style>
<div id="ctCookies" role="dialog" aria-label="Aviso de cookies">
    Usamos cookies necesarias para que el sitio funcione y, si lo permites, cookies analíticas para mejorarlo.
    Más información en el <a href="/cookies">Aviso de Cookies</a> y la <a href="/privacidad">Política de Privacidad</a>.
    <div class="ct-b">
        <button type="button" class="ok" id="ctOk">Aceptar</button>
        <button type="button" class="no" id="ctNo">Rechazar</button>
    </div>
</div>
<script>
(function () {
    var K = 'ct_cookies', GA = @json(config('services.ga4_id'));
    function get() { try { return localStorage.getItem(K); } catch (e) { return null; } }
    function set(v) { try { localStorage.setItem(K, v); } catch (e) {} }
    function analitica() {
        if (!GA || window.__gaCargado) return;
        window.__gaCargado = true;
        var s = document.createElement('script');
        s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(GA);
        document.head.appendChild(s);
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date()); gtag('config', GA, { anonymize_ip: true });
    }
    var box = document.getElementById('ctCookies'), v = get();
    if (v === 'si') analitica(); else if (v !== 'no') box.style.display = 'block';
    document.getElementById('ctOk').onclick = function () { set('si'); box.style.display = 'none'; analitica(); };
    document.getElementById('ctNo').onclick = function () { set('no'); box.style.display = 'none'; };
})();
</script>
