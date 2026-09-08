@php
    $showPeriod = $showPeriod ?? true;
    $withCostCenter = $withCostCenter ?? false;
    $filterAction = !empty($activePage) ? route('bi.' . $activePage) : route('bi.overview');
    $curBranch = (int) request('branch_id', 0);
    $curCostCenter = (int) request('cost_center_id', 0);
@endphp
<form method="GET" action="{{ $filterAction }}" class="an-filters">
    @if ($showPeriod)
        <span class="an-flabel">Period
            <select name="period" class="an-input">
                @foreach (['month' => 'This month', 'quarter' => 'This quarter', 'ytd' => 'Year to date'] as $pk => $lbl)
                    <option value="{{ $pk }}" @selected($period['key'] === $pk)>{{ $lbl }}</option>
                @endforeach
            </select>
        </span>
    @endif
    @if (($branches ?? null) && count($branches) > 1)
        <span class="an-flabel">Branch
            <select name="branch_id" class="an-input">
                <option value="">All branches</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected($curBranch === (int) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </span>
    @endif
    @if ($withCostCenter && ($costCenters ?? null) && count($costCenters) > 0)
        <span class="an-flabel">Cost centre
            <select name="cost_center_id" class="an-input">
                <option value="">All</option>
                @foreach ($costCenters as $cc)
                    <option value="{{ $cc->id }}" @selected($curCostCenter === (int) $cc->id)>{{ $cc->name }}</option>
                @endforeach
            </select>
        </span>
    @endif
    <button class="an-btn an-btn-cta" type="submit">Apply</button>
    <a href="{{ $filterAction }}" class="an-btn an-btn-ghost">Clear</a>
</form>