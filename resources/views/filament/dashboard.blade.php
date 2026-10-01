<x-filament-panels::page>
    <section class="studio-desk-intro">
        <span class="studio-desk-kicker">THE TEACHING STUDIO</span>
        <h2>Make the next lesson count.</h2>
        <p>Organize your curriculum, support your students and keep every learning record in view.</p>
        <a href="{{ \App\Filament\Resources\CourseResource::getUrl('create') }}" class="studio-desk-cta">Create a course →</a>
    </section>
    <div class="studio-desk-stats">
        @foreach($this->counts() as $label => $count)
            <div><strong>{{ $count }}</strong><span>{{ $label }}</span></div>
        @endforeach
    </div>
    <x-filament::section heading="The next step">
        <p>Draft courses stay private. Add the required lessons and accessible content, then use Publish to check that the course is ready for enrollment.</p>
        <p style="margin-top:12px">Payment and video actions use your configured providers. Check failed jobs and the activity log when a provider needs attention.</p>
    </x-filament::section>
</x-filament-panels::page>
