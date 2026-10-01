<div class="learning-showcase">
    <div class="learning-scene" data-learning-scene data-scene-state="fallback">
        <div class="learning-scene__heading"><span>A little curiosity. A new dimension.</span><span aria-hidden="true">↗</span></div>
        <div class="learning-scene__viewport" data-scene-viewport>
            <svg class="learning-scene__fallback" viewBox="0 0 560 410" fill="none" aria-hidden="true" focusable="false">
                <ellipse cx="280" cy="348" rx="170" ry="23" fill="currentColor" opacity=".35"/>
                <ellipse cx="285" cy="205" rx="215" ry="110" stroke="currentColor" stroke-width="1.5" transform="rotate(-28 285 205)"/>
                <g transform="translate(94 171) rotate(-12)">
                    <rect x="6" y="10" width="142" height="164" rx="14" class="learning-scene__blue"/>
                    <rect width="142" height="164" rx="14" class="learning-scene__paper" stroke="currentColor"/>
                    <text x="20" y="47" class="learning-scene__ink" font-size="32" font-family="sans-serif">01</text>
                    <path d="M20 78h70M20 90h45" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
                    <text x="20" y="138" class="learning-scene__ink" font-size="14" font-family="sans-serif">Explore.</text>
                </g>
                <g transform="translate(217 106) rotate(5)">
                    <rect x="6" y="10" width="142" height="164" rx="14" fill="#1934a4"/>
                    <rect width="142" height="164" rx="14" class="learning-scene__blue"/>
                    <text x="20" y="47" fill="#ffffff" font-size="32" font-family="sans-serif">02</text>
                    <path d="M20 78h70M20 90h45" stroke="#ffffff" stroke-opacity=".5" stroke-width="5" stroke-linecap="round"/>
                    <text x="20" y="138" fill="#ffffff" font-size="14" font-family="sans-serif">Understand.</text>
                </g>
                <g transform="translate(357 63) rotate(14)">
                    <rect x="6" y="10" width="142" height="164" rx="14" fill="#b5c779"/>
                    <rect width="142" height="164" rx="14" class="learning-scene__lime"/>
                    <text x="20" y="47" fill="#172128" font-size="32" font-family="sans-serif">03</text>
                    <path d="M20 78h70M20 90h45" stroke="#172128" stroke-opacity=".3" stroke-width="5" stroke-linecap="round"/>
                    <text x="20" y="138" fill="#172128" font-size="14" font-family="sans-serif">Create.</text>
                </g>
                <circle cx="115" cy="99" r="16" class="learning-scene__blue"/>
                <circle cx="451" cy="313" r="10" class="learning-scene__lime"/>
            </svg>
        </div>
        <div class="learning-scene__footer">
            <span class="learning-scene__hint" data-scene-hint>One step opens the next.</span>
            <div class="learning-scene__controls" data-scene-controls role="group" aria-label="Learning illustration controls" hidden>
                <button type="button" data-scene-turn="-1" aria-label="Rotate illustration left" title="Rotate left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m10 7-5 5 5 5m-5-5h14"/></svg>
                </button>
                <button type="button" data-scene-turn="1" aria-label="Rotate illustration right" title="Rotate right">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m14 7 5 5-5 5m5-5H5"/></svg>
                </button>
                <button type="button" data-scene-motion aria-label="Pause illustration animation">Pause</button>
            </div>
        </div>
    </div>
    <div class="learning-preview">
        @if($courses->first())
            <p class="eyebrow">Inside the curriculum</p>
            <h2>{{ $courses->first()->title }}</h2>
            <ol class="learning-preview__lessons">
                @foreach($courses->first()->modules->flatMap(fn($module) => $module->lessons)->take(3) as $lesson)
                    <li><span>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><strong>{{ $lesson->title }}</strong></li>
                @endforeach
            </ol>
            <a href="/courses/{{ $courses->first()->slug }}">See what you’ll learn →</a>
        @else
            <p class="eyebrow">Your next chapter</p>
            <h2>A clear path. A new perspective.</h2>
            <p>Our teaching team is preparing the next learning journey.</p>
        @endif
    </div>
</div>
