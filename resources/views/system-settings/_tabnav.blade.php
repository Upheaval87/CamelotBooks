@php
    $active = $active ?? '';
@endphp

@php
$tabs = [
    'company'       => ['label' => __('Company Profile'), 'url' => route('system-settings.index', 'company')],
    'regional'      => ['label' => __('Regional Settings'), 'url' => route('system-settings.index', 'regional')],
    'currency'      => ['label' => __('Currency Settings'), 'url' => route('system-settings.index', 'currency')],
    'accounts'      => ['label' => __('Account Mappings'), 'url' => route('system-settings.index', 'accounts')],
    'accounting'    => ['label' => __('Accounting Settings'), 'url' => route('system-settings.index', 'accounting')],
    'approval'      => ['label' => __('Approval Settings'), 'url' => route('system-settings.index', 'approval')],
    'notifications' => ['label' => __('Email'), 'url' => route('system-settings.index', 'notifications')],
    'data-hub'      => ['label' => __('Data Hub'), 'url' => route('system-settings.index', 'data-hub')],
    'import-export' => ['label' => __('Import/Export'), 'url' => route('system-settings.index', 'import-export')],
    'features'      => ['label' => __('Features'), 'url' => route('system-settings.features')],
    'backups'       => ['label' => __('Backups'), 'url' => route('admin.backups.index')],
    'audit-log'     => ['label' => __('Audit Log'), 'url' => route('system-settings.audit-log')],
];
@endphp

<div>
    <div class="glabel">{{ __('Navigate') }}</div>
    <div class="tbtns">
        @foreach($tabs as $key => $tab)
            <a href="{{ $tab['url'] }}" class="btn {{ $active === $key ? 'cta' : '' }}">{{ $tab['label'] }}</a>
        @endforeach
    </div>
</div>
