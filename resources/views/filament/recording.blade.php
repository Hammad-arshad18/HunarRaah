<div x-data="{url: null, error: '', busy: false, async play() { this.busy = true; this.error = ''; try { const response = await fetch('/admin/lessons/{{ $lesson->id }}/playback-token', {method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json'}}); if (!response.ok) throw new Error(); const data = await response.json(); this.url = data.url; } catch { this.error = 'Recording unavailable. Check provider configuration and try again.'; } finally { this.busy = false; } }}">
    <p>Private recording preview. Access is audited. Refresh the preview when its ten-minute token expires.</p>
    <button type="button" x-on:click="play()" x-bind:disabled="busy" class="studio-desk-cta">Open / refresh preview</button>
    <p x-text="error" role="alert" style="color:#b42318;margin-top:16px"></p>
    <template x-if="url"><iframe x-bind:src="url" title="Private lesson recording" style="width:100%;aspect-ratio:16/9;margin-top:16px" allow="fullscreen" allowfullscreen></iframe></template>
</div>
