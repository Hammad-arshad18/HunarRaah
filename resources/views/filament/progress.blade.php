<div>
    <p>{{ $enrollment->user->name }} · {{ $enrollment->course->title }}</p>
    <ul style="margin-top:20px;display:grid;gap:16px">
        @php($progress = $enrollment->progress->keyBy('lesson_id'))
        @foreach($enrollment->course->modules as $module)
            @foreach($module->lessons as $lesson)
                @php($entry = $progress->get($lesson->id))
                <li style="padding-bottom:16px;border-bottom:1px solid #dce1dd">
                    <strong>{{ $module->position }}.{{ $lesson->position }} {{ $lesson->title }}</strong>
                    <p>{{ $lesson->required ? 'Required' : 'Optional' }} · {{ $lesson->type }}</p>
                    <p>{{ $entry?->completed_at ? 'Completed '.$entry->completed_at->format('d M Y, H:i').' UTC · '.str_replace('_', ' ', $entry->completion_source) : 'Not completed' }}</p>
                </li>
            @endforeach
        @endforeach
    </ul>
</div>
