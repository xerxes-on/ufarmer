@php
    $json = $getState();
@endphp

<div
    data-json-viewer
    class="overflow-hidden rounded-xl border border-gray-200 bg-gray-950 shadow-sm ring-1 ring-black/5 dark:border-white/10"
>
    <div class="flex items-center justify-between border-b border-white/10 bg-gray-900 px-4 py-2">
        <span class="text-xs font-semibold uppercase tracking-wider text-gray-300">JSON</span>
        <span class="text-xs text-gray-500">Read only</span>
    </div>

    <pre class="max-h-[36rem] overflow-auto whitespace-pre p-4 font-mono text-xs leading-6 text-emerald-300"><code>{{ $json }}</code></pre>
</div>
