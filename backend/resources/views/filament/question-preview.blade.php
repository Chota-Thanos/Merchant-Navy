{{-- Live preview in the question editor: maths drawn exactly as students will see it. Rendered by the "cb-math" script in admin-head. --}}
@php
    $filled = filled(trim(strip_tags((string) $body))) || collect($options)->filter(fn ($o) => filled(trim((string) $o)))->isNotEmpty();
@endphp
<div style="border:1px solid var(--gray-200);border-radius:.75rem;padding:1rem;background:var(--gray-50)">
    @if (! $filled)
        <p class="cb-muted">Start typing the question: formulas appear here as students will see them.</p>
    @else
        <div class="cb-math" style="font-size:1rem;line-height:1.6">{!! $body !!}</div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:.5rem;margin-top:.75rem">
            @foreach ($options as $n => $text)
                @php($isCorrect = (string) $correct === (string) $n)
                <div style="display:flex;gap:.5rem;align-items:baseline;border:1px solid {{ $isCorrect ? 'var(--success-500)' : 'var(--gray-200)' }};background:{{ $isCorrect ? 'rgba(34,197,94,.08)' : '#fff' }};border-radius:.5rem;padding:.5rem .75rem">
                    <b class="cb-muted" style="flex:none">{{ chr(64 + $n) }}</b>
                    <span class="cb-math" style="min-width:0">{{ $text }}</span>
                    @if ($isCorrect)<span class="cb-muted" style="margin-left:auto;color:var(--success-600)">✓ correct</span>@endif
                </div>
            @endforeach
        </div>

        @if (filled(trim(strip_tags((string) $explanation))))
            <div style="margin-top:.75rem;padding-top:.75rem;border-top:1px dashed var(--gray-300)">
                <b class="cb-muted">Solution</b>
                <div class="cb-math" style="line-height:1.6">{!! $explanation !!}</div>
            </div>
        @endif
    @endif
</div>
