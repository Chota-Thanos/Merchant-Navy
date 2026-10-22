<x-filament-panels::page>
    {{-- Logic: app/Filament/Resources/QuestionImportResource/Pages/ReviewImport.php --}}
    <div class="cb-wrap" @if ($import->isParsing()) wire:poll.3s @endif>

        {{-- ===== Still reading / failed ===== --}}
        @if ($import->isParsing())
            <div class="cb-card" style="padding:2rem;text-align:center">
                <x-filament::loading-indicator class="h-8 w-8" style="margin:0 auto" />
                <h3 class="cb-title" style="margin-top:.75rem">Reading the file… {{ $import->progress }}%</h3>
                <p class="cb-muted">
                    @if ($import->method === \App\Enums\ImportMethod::PdfAi)
                        The AI reads a few pages at a time. This takes a few minutes for a long paper; you can leave this page and come back from “Question imports”.
                        (It needs <code>php artisan queue:work</code> running unless the queue is set to sync.)
                    @else
                        This page updates by itself.
                    @endif
                </p>
                <div style="height:6px;background:var(--gray-200);border-radius:99px;max-width:24rem;margin:1rem auto 0;overflow:hidden">
                    <div style="height:100%;width:{{ max(3, $import->progress) }}%;background:var(--primary-600)"></div>
                </div>
            </div>
        @elseif ($import->status === 'failed')
            <div class="cb-card" style="padding:1.5rem;border-color:var(--danger-300)">
                <h3 class="cb-title" style="color:var(--danger-600)">The file could not be read</h3>
                <p class="cb-muted" style="margin-top:.25rem">{{ $import->error }}</p>
                <p class="cb-muted" style="margin-top:.5rem">Try “Read again”, or use another method (for a PDF: the AI reader understands any layout).</p>
            </div>
        @else
            {{-- ===== Summary ===== --}}
            <div class="cb-card" style="padding:1rem 1.125rem">
                <div class="cb-stats" style="padding:0">
                    <span class="cb-stat"><b>{{ $counts['total'] }}</b><span class="cb-muted">questions found</span></span>
                    <span class="cb-stat"><b style="color:var(--success-600)">{{ $counts['ok'] }}</b><span class="cb-muted">ready</span></span>
                    <span class="cb-stat"><b style="color:var(--warning-600)">{{ $counts['warning'] }}</b><span class="cb-muted">to check</span></span>
                    <span class="cb-stat"><b style="color:var(--danger-600)">{{ $counts['error'] }}</b><span class="cb-muted">need fixing</span></span>
                    <span class="cb-stat"><b>{{ $counts['added'] }}</b><span class="cb-muted">added</span></span>
                    @if ($counts['skipped'])<span class="cb-stat"><b>{{ $counts['skipped'] }}</b><span class="cb-muted">skipped</span></span>@endif
                </div>
                @foreach ($summary['notes'] ?? [] as $note)
                    <p class="cb-muted" style="margin-top:.5rem">
                        @if (str_contains($note, 'match the answer key')) ✅ @else ⚠ @endif {{ $note }}
                    </p>
                @endforeach
                @if (isset($summary['expected']) && $summary['expected'] === $counts['total'])
                    <p class="cb-muted" style="margin-top:.25rem">✅ The document says it has {{ $summary['expected'] }} questions, and {{ $counts['total'] }} were found.</p>
                @endif
                <p class="cb-muted" style="margin-top:.5rem">
                    Look through the list, fix or skip anything wrong, tick the questions you want, then press <b>Add selected to a test</b> (top right). Nothing is added until then.
                </p>
            </div>

            {{-- ===== Filters & selection ===== --}}
            <div class="cb-card" style="padding:.75rem">
                <div class="cb-row" style="flex-wrap:wrap;gap:.5rem">
                    <div style="flex:1 1 14rem">
                        <x-filament::input.wrapper inline-prefix prefix-icon="heroicon-m-magnifying-glass">
                            <x-filament::input type="search" wire:model.live.debounce.300ms="search" placeholder="Search the questions or a number, e.g. Q72" />
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="show">
                            <option value="all">All questions</option>
                            <option value="pending">Not added yet</option>
                            <option value="ok">Ready (no warnings)</option>
                            <option value="attention">Need a look</option>
                            <option value="added">Already added</option>
                            <option value="skipped">Skipped</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                    @if ($parts->count() > 1)
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="part">
                                <option value="">All parts</option>
                                @foreach ($parts as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    @endif
                </div>
                <div class="cb-row" style="flex-wrap:wrap;margin-top:.625rem;gap:.5rem">
                    <x-filament::button size="sm" color="gray" wire:click="selectAll(true)">Tick all ready</x-filament::button>
                    <x-filament::button size="sm" color="gray" wire:click="selectAll(false)">Tick all (including “to check”)</x-filament::button>
                    @if (count($selected))
                        <x-filament::button size="sm" color="gray" wire:click="$set('selected', [])">Untick all</x-filament::button>
                        <x-filament::button size="sm" color="danger" outlined wire:click="skipSelected">Skip the {{ count($selected) }} ticked</x-filament::button>
                    @endif
                    <span class="cb-muted" style="margin-left:auto">{{ $filteredCount }} shown · {{ count($selected) }} ticked</span>
                </div>
            </div>

            {{-- ===== The draft questions ===== --}}
            @forelse ($drafts as $d)
                @php($sev = $d->severity())
                <div wire:key="draft-{{ $d->id }}" class="ri-card ri-{{ $sev }} {{ $d->status !== 'pending' ? 'ri-done' : '' }}">
                    <div class="cb-row" style="gap:.625rem">
                        @if ($d->status === 'pending' && $sev !== 'error')
                            <x-filament::input.checkbox wire:model.live="selected" value="{{ $d->id }}" />
                        @else
                            <span style="width:1rem;flex:none"></span>
                        @endif
                        <span class="qb-no" style="min-width:2.25rem">{{ $d->number ? 'Q'.$d->number : $loop->iteration }}</span>
                        <div class="cb-grow cb-muted">
                            {{ collect([$d->part, $d->kind ? ucfirst($d->kind) : null, $d->difficulty ? ucfirst($d->difficulty) : null, $d->page ? ($import->method === \App\Enums\ImportMethod::Excel ? 'row '.$d->page : 'page '.$d->page) : null])->filter()->implode(' · ') }}
                        </div>
                        @if ($d->status === 'added')<x-filament::badge color="success" size="sm">Added</x-filament::badge>
                        @elseif ($d->status === 'skipped')<x-filament::badge color="gray" size="sm">Skipped</x-filament::badge>
                        @elseif ($sev === 'error')<x-filament::badge color="danger" size="sm">Needs fixing</x-filament::badge>
                        @elseif ($sev === 'warning')<x-filament::badge color="warning" size="sm">Check</x-filament::badge>
                        @else<x-filament::badge color="success" size="sm">Ready</x-filament::badge>@endif
                        @if ($d->status !== 'added')
                            <x-filament::button size="xs" color="gray" icon="heroicon-m-pencil-square" wire:click="mountAction('editDraft', { id: {{ $d->id }} })">Edit</x-filament::button>
                            <x-filament::button size="xs" color="gray" wire:click="toggleSkip({{ $d->id }})">{{ $d->status === 'skipped' ? 'Restore' : 'Skip' }}</x-filament::button>
                        @endif
                    </div>

                    <div class="qb-text cb-math" style="max-height:none;margin-top:.5rem">{!! $d->body !!}</div>

                    <div class="ri-options">
                        @foreach (range(1, 4) as $n)
                            <div class="ri-opt {{ $d->correct === $n ? 'is-correct' : '' }}">
                                <b>{{ chr(64 + $n) }}</b>
                                <span class="cb-math">{!! $d->{'option_'.$n} !!}</span>
                                @if ($d->correct === $n)<span class="ri-tick">✓ correct</span>@endif
                            </div>
                        @endforeach
                    </div>

                    @foreach ($d->warnings ?? [] as $w)
                        <div class="ri-warn ri-warn-{{ $w['level'] }}">{{ $w['level'] === 'error' ? '⛔' : '⚠' }} {{ $w['message'] }}</div>
                    @endforeach

                    @if ($d->explanation)
                        <details class="ri-expl">
                            <summary>Solution</summary>
                            <div class="cb-math">{!! $d->explanation !!}</div>
                        </details>
                    @endif
                </div>
            @empty
                <div class="cb-empty" style="padding:2rem">No questions match this filter.</div>
            @endforelse

            @if ($filteredCount > $drafts->count())
                <x-filament::button color="gray" wire:click="loadMore" style="align-self:center">Show more ({{ $filteredCount - $drafts->count() }} left)</x-filament::button>
            @endif
        @endif
    </div>
</x-filament-panels::page>
