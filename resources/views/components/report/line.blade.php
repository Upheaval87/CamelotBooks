@props(['code' => '', 'desc' => '', 'amount' => '', 'href' => '', 'zero' => false])

<div class="report-line @if($zero) zero @endif">
    <span>
        @if($code)
            <span class="code">{{ $code }}</span>
        @endif
        @if($href)
            <a href="{{ $href }}" class="desc report-desc-link">{{ $desc }}</a>
        @else
            <span class="desc">{{ $desc }}</span>
        @endif
    </span>
    <span class="amt">{{ $amount }}</span>
</div>
