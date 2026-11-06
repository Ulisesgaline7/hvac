@if(filled($whatsapp ?? null))
<a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp"
   style="position:fixed;right:18px;bottom:18px;width:56px;height:56px;border-radius:50%;background:#25d366;color:#fff;display:flex;align-items:center;justify-content:center;font-size:30px;box-shadow:0 4px 14px rgba(0,0,0,.3);z-index:9990;text-decoration:none">
    <i class="fab fa-whatsapp"></i>
</a>
@endif
