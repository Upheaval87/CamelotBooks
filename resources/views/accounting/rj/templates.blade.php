<x-app-layout>
    <div class="rj-wrap rj-rebuild">
        <div class="wrap">
            <div class="page-head">
                <div>
                    <h1>Journal Templates</h1>
                    <div class="sub">Reusable formats for common recurring entries.</div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="{{ route('accounting.rj.create') }}" class="btn btn-cta btn-sm">＋ New Template</a>
                </div>
            </div>

            @if($templates->isEmpty())
                <div class="card">
                    <div class="li-wrap" style="text-align:center;padding:40px 20px;color:var(--ink-muted)">
                        No templates yet. Create your first template to save time on recurring entries.
                    </div>
                </div>
            @else
                <div class="mcards">
                    @foreach($templates as $t)
                        <div class="mcard">
                            <div class="t">{{ $t->name }}</div>
                            <div class="d">
                                @foreach($t->templateLines as $line)
                                    @if($line->debit > 0)
                                        DR {{ $line->account?->name ?? '—' }}
                                    @endif
                                    @if($line->credit > 0)
                                        CR {{ $line->account?->name ?? '—' }}
                                    @endif
                                    · {{ number_format(max($line->debit, $line->credit), 2) }}
                                    · {{ ucfirst(str_replace('_', ' ', $t->frequency)) }}
                                    @if(!$loop->last)<br>@endif
                                @endforeach
                            </div>
                            <div class="foot">
                                <form method="POST" action="{{ route('accounting.rj.create') }}" style="display:inline">
                                    @csrf
                                    <input type="hidden" name="template_id" value="{{ $t->id }}">
                                    <button type="submit" class="btn btn-sec btn-sm">Use Template</button>
                                </form>
                                <a href="{{ route('accounting.rj.edit', $t) }}" class="btn btn-ghost btn-sm">✎ Edit</a>
                                <form method="POST" action="{{ route('accounting.rj.duplicate', $t) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm">⧉</button>
                                </form>
                                <form method="POST" action="{{ route('accounting.rj.destroy', $t) }}" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger-o btn-sm" onclick="return confirm('Delete this template?')">🗑</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
