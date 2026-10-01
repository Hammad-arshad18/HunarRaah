@extends('public.layout')
@section('title', 'Your next chapter · '.config('platform.organization'))
@push('scripts')
    @vite('resources/js/home.ts')
@endpush
@section('content')
<div class="home-experience" data-home-experience>
    <div class="home-scroll-progress" aria-hidden="true"><span data-page-progress></span></div>
    <div class="wrap home-topline">
        <span>Small steps. A different future.</span>
        <button class="home-motion-toggle" type="button" data-page-motion aria-pressed="true" hidden>
            <span class="home-motion-dot" aria-hidden="true"></span><span data-motion-label>Motion on</span>
        </button>
    </div>

    <section class="wrap hero home-hero" data-home-hero aria-labelledby="home-heading">
        <div class="home-hero-copy">
            <p class="eyebrow" data-reveal>A modern teaching studio</p>
            <h1 id="home-heading" data-reveal data-reveal-order="1">Your next<br>chapter starts<br>with <em>a skill.</em></h1>
            <p class="lead" data-reveal data-reveal-order="2">Follow your curiosity. Build your understanding. Make something of what you learn.</p>
            <div class="home-hero-actions" data-reveal data-reveal-order="3">
                <a class="button" href="/courses">Find your course <span aria-hidden="true">↗</span></a>
                <a class="home-text-link" href="#the-journey">See the journey <span aria-hidden="true">↓</span></a>
            </div>
            <p class="fine" data-reveal data-reveal-order="4">Recorded learning · Live sessions · A clear way forward</p>
        </div>
        <div class="home-hero-art" data-hero-art>
            @include('public.learning-scene')
        </div>
        <div class="home-hero-foot" aria-hidden="true"><span>Keep your curiosity moving</span><span class="home-scroll-cue">↓</span><span>01 — THE BEGINNING</span></div>
    </section>

    <section class="home-manifesto" data-manifesto data-spotlight aria-labelledby="manifesto-heading">
        <div class="wrap">
            <p class="eyebrow" data-reveal>A little direction changes everything</p>
            <h2 id="manifesto-heading">From <span>“what if?”</span><br>to <span class="home-manifesto-answer">“I can.”</span></h2>
            <div class="home-manifesto-bottom" data-reveal><span class="home-asterisk" aria-hidden="true">✳</span><p>Learning makes space for possibilities.<br>Give your next one a place to begin.</p></div>
        </div>
        <span class="home-manifesto-line" aria-hidden="true"></span>
    </section>

    <div class="home-word-ribbon" data-word-ribbon aria-hidden="true">
        <div class="home-word-ribbon-track"><span>Curiosity</span><i>↗</i><span>Clarity</span><i>✳</i><span>Practice</span><i>↗</i><span>Progress</span><i>✳</i><span>Curiosity</span><i>↗</i></div>
    </div>

    <section id="the-journey" class="wrap home-journey home-section" data-journey aria-labelledby="journey-heading">
        <div class="home-journey-sticky">
            <p class="eyebrow">02 — A path with purpose</p>
            <h2 id="journey-heading">Not all at once.<br>One good step.</h2>
            <div class="home-journey-art" data-journey-art data-chapter="0" aria-hidden="true">
                <span class="home-journey-counter" data-chapter-number>01</span>
                <svg class="home-journey-route" viewBox="0 0 420 370" fill="none">
                    <path d="M58 294C58 154 214 290 214 180S360 198 360 67" stroke="var(--studio-border)" stroke-width="2"/>
                    <path class="home-journey-ink" data-journey-path d="M58 294C58 154 214 290 214 180S360 198 360 67" stroke="var(--studio-link)" stroke-width="3" pathLength="1"/>
                    <circle cx="58" cy="294" r="8" fill="var(--studio-link)"/>
                    <circle cx="214" cy="180" r="8" fill="var(--studio-link)"/>
                    <circle cx="360" cy="67" r="8" fill="var(--studio-link)"/>
                    <circle class="home-journey-marker" data-journey-marker cx="58" cy="294" r="11" fill="var(--studio-link)" stroke="var(--studio-surface)" stroke-width="4"/>
                </svg>
                <div class="home-journey-sheet home-journey-sheet--back"><span>03</span><strong>Create.</strong></div>
                <div class="home-journey-sheet home-journey-sheet--middle"><span>02</span><strong>Understand.</strong></div>
                <div class="home-journey-sheet home-journey-sheet--front"><span>01</span><strong>Explore.</strong><i>↗</i></div>
                <div class="home-journey-caption"><span data-chapter-label>Find your direction</span><span>01 / 03</span></div>
            </div>
            <nav class="home-chapter-nav" aria-label="Learning journey steps">
                <a href="#chapter-explore" data-chapter-link="0"><span>01</span> Explore</a>
                <a href="#chapter-understand" data-chapter-link="1"><span>02</span> Understand</a>
                <a href="#chapter-create" data-chapter-link="2"><span>03</span> Create</a>
            </nav>
        </div>
        <div class="home-journey-chapters">
            <article id="chapter-explore" class="home-chapter" data-chapter-step data-chapter-title="Find your direction">
                <span class="home-section-index" data-reveal>01 / EXPLORE</span>
                <h3 data-reveal>Start with<br>what moves you.</h3>
                <p data-reveal>Choose a course around your interests and the skills you want to build. See the outcomes, prerequisites and curriculum before you begin.</p>
                <a class="home-text-link" href="/courses" data-reveal>Explore the courses <span aria-hidden="true">↗</span></a>
                <div class="home-chapter-detail" data-reveal><span aria-hidden="true">◎</span><div><strong>A clear starting point</strong><p>Know what you’ll learn, and what you’ll need.</p></div></div>
            </article>
            <article id="chapter-understand" class="home-chapter" data-chapter-step data-chapter-title="Let it come together">
                <span class="home-section-index" data-reveal>02 / UNDERSTAND</span>
                <h3 data-reveal>Let the pieces<br>come together.</h3>
                <p data-reveal>Move through thoughtfully ordered lessons. Revisit a recording, follow the next chapter, or join your course’s scheduled live session.</p>
                <div class="home-chapter-detail" data-reveal><span aria-hidden="true">≋</span><div><strong>Learning with a thread</strong><p>A useful sequence, with space to take things in.</p></div></div>
            </article>
            <article id="chapter-create" class="home-chapter" data-chapter-step data-chapter-title="Make the learning yours">
                <span class="home-section-index" data-reveal>03 / CREATE</span>
                <h3 data-reveal>Take it beyond<br>the lesson.</h3>
                <p data-reveal>Return to the material as you practice. Track the lessons you complete and see how far you’ve come. Eligible courses offer a certificate of completion.</p>
                <div class="home-chapter-detail" data-reveal><span aria-hidden="true">↗</span><div><strong>Progress you can return to</strong><p>Pick up your learning from your own dashboard.</p></div></div>
            </article>
        </div>
    </section>

    <section class="home-courses-section" aria-labelledby="courses-heading">
        <div class="wrap home-section">
            <div class="home-section-heading" data-reveal>
                <div><p class="eyebrow">03 — Choose your next chapter</p><h2 id="courses-heading">Follow that<br><em>spark of interest.</em></h2></div>
                <a class="home-text-link" href="/courses">All courses <span aria-hidden="true">↗</span></a>
            </div>
            <div class="course-grid home-course-grid">
                @forelse($courses as $course)
                    <div class="home-course-item" data-reveal data-reveal-order="{{ $loop->index % 3 }}">@include('public.card')</div>
                @empty
                    <div class="empty" data-reveal><h3>A new chapter is taking shape.</h3><p>Our teaching team is preparing the next courses. Check back soon.</p></div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="wrap home-section home-approach" aria-labelledby="approach-heading">
        <div class="home-approach-intro" data-reveal><p class="eyebrow">04 — Made for real life</p><h2 id="approach-heading">Your time.<br>Your pace.<br><em>Your way forward.</em></h2><p>A learning space that gives each next step a little more clarity.</p></div>
        <div class="home-approach-list">
            <article data-reveal><span aria-hidden="true">↺</span><div><h3>Room to revisit.</h3><p>Return to available recordings and lessons when you need another look. Understanding doesn’t have to happen the first time.</p></div></article>
            <article data-reveal><span aria-hidden="true">◷</span><div><h3>Time to connect.</h3><p>Live courses bring learners together on a shared schedule. See session dates and timezones before you enroll.</p></div></article>
            <article data-reveal><span aria-hidden="true">✓</span><div><h3>A sense of progress.</h3><p>Keep track of completed lessons and the next step. Your learning dashboard gives you a place to return.</p></div></article>
        </div>
    </section>

    @if($courses->first())
        <section class="home-instructor-band" aria-labelledby="instructor-heading">
            <div class="wrap home-section home-instructor">
                <div class="home-instructor-art" data-reveal aria-hidden="true"><span class="home-instructor-orbit"></span><span class="home-instructor-initial">{{ mb_substr($courses->first()->instructor_name, 0, 1) }}</span><span class="home-instructor-art-label">BEHIND EVERY GOOD LESSON<br>A PERSON WHO CARES.</span></div>
                <div data-reveal data-reveal-order="1"><p class="eyebrow">05 — The human side of learning</p><h2 id="instructor-heading">Meet your<br>next guide.</h2><h3>{{ $courses->first()->instructor_name }}</h3><p class="home-instructor-bio">{{ $courses->first()->instructor_bio }}</p><a class="home-text-link" href="/courses/{{ $courses->first()->slug }}">Discover their course <span aria-hidden="true">↗</span></a></div>
            </div>
        </section>
    @endif

    <section class="wrap home-section home-faq" aria-labelledby="faq-heading">
        <div data-reveal><p class="eyebrow">A few things before you start</p><h2 id="faq-heading">Good questions.<br>Clear answers.</h2><p>Need a little more guidance?<br><a href="/support">We’re here to help ↗</a></p></div>
        <div class="home-faq-list">
            <details data-reveal><summary><span>01</span> How do I access a course?</summary><p>Create an account and verify your email, then enroll from the course page. For paid courses, access begins after our payment provider confirms your purchase.</p></details>
            <details data-reveal><summary><span>02</span> Can I learn at my own pace?</summary><p>Recorded lessons can be revisited during your course access period. Live sessions follow the published schedule. Check each course’s format and access terms before enrolling.</p></details>
            <details data-reveal><summary><span>03</span> How do live sessions work?</summary><p>Live courses have a shared schedule and use an external meeting provider. Enrolled learners join from the lesson page when the session’s join window opens.</p></details>
            <details data-reveal><summary><span>04</span> What does a certificate mean?</summary><p>Eligible courses offer a certificate for completing the required material. It is a certificate of completion, not an accredited qualification or an assessment of mastery.</p></details>
        </div>
    </section>

    <section class="home-finale" data-finale data-spotlight aria-labelledby="finale-heading">
        <div class="home-finale-orbits" aria-hidden="true"><span></span><span></span><span></span></div>
        <div class="wrap home-section">
            <p class="eyebrow" data-reveal>Every next chapter needs a first step.</p>
            <h2 id="finale-heading" data-reveal>Make it<br><em>this one.</em></h2>
            <div class="home-finale-actions" data-reveal><a class="button" href="/courses">Explore the courses <span aria-hidden="true">↗</span></a><span>A little curiosity goes a long way.</span></div>
            <div class="home-finale-signoff"><span>{{ config('platform.organization') }}</span><a href="#home-heading">Back to the beginning ↑</a></div>
        </div>
    </section>
</div>
@endsection
