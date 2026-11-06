<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'HVACBusiness',
    'name' => \App\Support\SitioWeb::nombre(),
    'legalName' => 'Control Total Servicios Integrales S. de R.L.',
    'url' => url('/'),
    'telephone' => '+504 8958 6548',
    'email' => 'controltotalhn@gmail.com',
    'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Tegucigalpa', 'addressRegion' => 'Francisco Morazán', 'addressCountry' => 'HN'],
    'areaServed' => 'Tegucigalpa, Honduras',
    'description' => 'Instalación, mantenimiento y reparación de aire acondicionado y refrigeración.',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
