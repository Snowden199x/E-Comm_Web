@props(['content' => null])

@once
<style>
.vendo-policy-content { min-width: 0; color: #374151; font-size: 14px; line-height: 1.75; white-space: normal; overflow-wrap: anywhere; }
.vendo-policy-content p { margin: 0 0 .85em; }
.vendo-policy-content ol, .vendo-policy-content ul { margin: .85em 0; padding-left: 1.75em; list-style-position: outside; }
.vendo-policy-content ol { list-style-type: decimal; }
.vendo-policy-content ul { list-style-type: disc; }
.vendo-policy-content li { display: list-item; padding-left: .25em; margin-bottom: .9em; }
.vendo-policy-content li::marker { color: #4b3153; font-weight: 600; }
.vendo-policy-content li > p { margin-bottom: .4em; }
.vendo-policy-content strong, .vendo-policy-content b { font-weight: 700; }
.vendo-policy-content em, .vendo-policy-content i { font-style: italic; }
.vendo-policy-content u { text-decoration: underline; }
.vendo-policy-content h1, .vendo-policy-content h2, .vendo-policy-content h3, .vendo-policy-content h4, .vendo-policy-content h5, .vendo-policy-content h6 { margin: 1em 0 .5em; font-weight: 700; line-height: 1.4; }
.vendo-policy-content h1 { font-size: 1.3em; }
.vendo-policy-content h2 { font-size: 1.2em; }
.vendo-policy-content h3 { font-size: 1.1em; }
.vendo-policy-content blockquote { margin: .85em 0; border-left: 3px solid #d8c8dc; padding-left: 1em; }
.vendo-policy-content .ql-align-center { text-align: center; }
.vendo-policy-content .ql-align-right { text-align: right; }
.vendo-policy-content .ql-align-justify { text-align: justify; }
.vendo-policy-content .ql-direction-rtl { direction: rtl; }
@for($level = 1; $level <= 8; $level++)
.vendo-policy-content .ql-indent-{{ $level }} { margin-left: {{ $level * 1.5 }}em; }
@endfor
.vendo-policy-content > :first-child { margin-top: 0; }
.vendo-policy-content > :last-child { margin-bottom: 0; }
</style>
@endonce

<div {{ $attributes->class(['vendo-policy-content']) }}>{!! \App\Support\PolicyContent::render($content) !!}</div>
