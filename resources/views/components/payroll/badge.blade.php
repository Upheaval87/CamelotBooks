@props(['status', 'type' => 'employee', 'label' => null])

@php
    $employeeMap = [
        'active'    => ['class' => 'pr-b-act',  'dot' => true],
        'on_leave'  => ['class' => 'pr-b-leave', 'dot' => true],
        'contract'  => ['class' => 'pr-b-contract', 'dot' => true],
        'terminated'=> ['class' => 'pr-term',   'dot' => true],
    ];

    $runMap = [
        'draft'             => ['class' => 'pr-b-draft',  'dot' => true],
        'calculated'        => ['class' => 'pr-b-pend',   'dot' => true],
        'pending_approval'  => ['class' => 'pr-b-pend',   'dot' => true],
        'approved'          => ['class' => 'pr-b-act',    'dot' => true],
        'posted'            => ['class' => 'pr-b-act',    'dot' => true],
        'partially_paid'    => ['class' => 'pr-b-act',    'dot' => true],
        'fully_paid'        => ['class' => 'pr-b-act',    'dot' => true],
        'locked'            => ['class' => 'pr-b-lock',   'dot' => true],
    ];

    $chipMap = [
        'permanent' => ['class' => 'pr-tchip'],
        'casual'    => ['class' => 'pr-tchip pr-tchip-amber'],
        'contract'  => ['class' => 'pr-tchip pr-tchip-steel'],
        'taxable'   => ['class' => 'pr-tchip pr-tchip-green'],
    ];

    $maps = ['employee' => $employeeMap, 'run' => $runMap, 'chip' => $chipMap];
    $map = $maps[$type] ?? $employeeMap;
    $info = $map[$status] ?? ['class' => 'pr-b-draft', 'dot' => true];
    $displayLabel = $label ?? ucwords(str_replace('_', ' ', $status));
@endphp

<span class="pr-badge {{ $info['class'] }}">
    @if($info['dot'] ?? false)
        <span class="pr-bdot"></span>
    @endif
    {{ $displayLabel }}
</span>
