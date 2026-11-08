@php $md = @file_get_contents(resource_path('docs/CT-GAR-001.md')); @endphp
@if ($md)
<div class="cg" style="page-break-before: always; font-size: 8.5pt; line-height: 1.35;">
    <style>
        .cg h1 { font-size: 11pt; margin: 12pt 0 4pt; color: #082A4A; }
        .cg h2 { font-size: 12pt; color: #082A4A; }
        .cg p, .cg li { margin: 2pt 0; }
        .cg ul, .cg ol { margin: 2pt 0 4pt 14pt; padding: 0; }
        .cg hr { border: 0; border-top: 0.6pt solid #D9D9D9; margin: 8pt 0; }
    </style>
    {!! \Illuminate\Support\Str::markdown($md, ['html_input' => 'strip']) !!}
</div>
@endif
