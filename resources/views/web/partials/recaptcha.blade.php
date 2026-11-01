@if (config('services.recaptcha.site_key'))
    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}" style="margin:12px 0"></div>
    @error('g-recaptcha-response')<div class="err" style="color:#b00020;font-size:.9rem">{{ $message }}</div>@enderror
    @once
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endonce
@endif
