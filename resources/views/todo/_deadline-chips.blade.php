@props([
    'name' => 'deadline',
    'deadlineGranularity' => '',
    'deadlineDate' => '',
])

<div
    x-data="todoDeadline({
        name: '{{ $name }}',
        granularity: '{{ $deadlineGranularity }}',
        date: '{{ $deadlineDate }}',
    })"
    class="todo-deadline"
>
    <input type="hidden" :name="name + '_granularity'" x-model="granularity" />
    <input type="hidden" :name="name + '_date'" x-model="date" />

    <div class="todo-deadline-chips">
        <button type="button" @click="pick('day')" :class="chipClass('day', 'today')">{{ __('Today') }}</button>
        <button type="button" @click="pick('week')" :class="chipClass('week')">{{ __('This Week') }}</button>
        <button type="button" @click="pick('month')" :class="chipClass('month')">{{ __('This Month') }}</button>
        <button type="button" @click="pick('year')" :class="chipClass('year')">{{ __('This Year') }}</button>
        <button type="button" @click="pickCustom()" :class="chipClass('day', 'custom')">{{ __('Pick a date') }}</button>
    </div>

    <div x-show="customOpen" x-cloak class="todo-deadline-date">
        <input type="date" x-model="date" class="input todo-deadline-date-input" />
    </div>

    <span class="todo-deadline-preview" x-text="preview"></span>
</div>
