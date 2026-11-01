@if (config('services.recaptcha.site_key'))
    <div wire:ignore class="flex justify-center mt-4"
         x-data="{
             wid: null,
             init() {
                 const draw = () => {
                     this.wid = grecaptcha.render(this.$refs.box, {
                         sitekey: @js(config('services.recaptcha.site_key')),
                         callback: (t) => $wire.set('captcha', t, false),
                         'expired-callback': () => $wire.set('captcha', null, false),
                     });
                 };
                 if (window.grecaptcha && grecaptcha.render) { draw(); return; }
                 window.__recaptchaReady = draw;
                 const s = document.createElement('script');
                 s.src = 'https://www.google.com/recaptcha/api.js?onload=__recaptchaReady&render=explicit';
                 s.async = true;
                 document.head.appendChild(s);
             }
         }"
         x-on:recaptcha-reset.window="grecaptcha.reset(wid)">
        <div x-ref="box"></div>
    </div>
@endif
