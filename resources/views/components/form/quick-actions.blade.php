@props(['title' => 'Quick Actions', 'groups' => []])

@include('components.quick-actions.sidebar', [
    'title' => $title,
    'groups' => $groups,
    'asideClass' => 'form-page-sidebar',
])
