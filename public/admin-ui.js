(() => {
    const mobile = matchMedia('(max-width: 1023px)');
    const closeMobileNavigation = () => {
        if (mobile.matches && window.Alpine?.store('sidebar')) window.Alpine.store('sidebar').close();
    };
    document.addEventListener('alpine:initialized', closeMobileNavigation);
    document.addEventListener('livewire:navigated', closeMobileNavigation);
    mobile.addEventListener('change', closeMobileNavigation);
    closeMobileNavigation();
    // Choices hides the original select: preserve the visible field label on its interactive control.
    const labelSelects = () => {
        document.querySelectorAll('.choices select[id]').forEach(select => {
            const label = document.querySelector('label[for="' + CSS.escape(select.id) + '"]');
            const wrapper = select.closest('.choices');
            if (!label || !wrapper) return;
            label.id ||= select.id + '-label';
            const control = wrapper.querySelector('[role="combobox"]') || wrapper;
            control.setAttribute('aria-labelledby', label.id);
            const search = wrapper.querySelector('input[type="search"], input.choices__input');
            if (search) search.setAttribute('aria-label', 'Search ' + label.textContent.replace('*', '').trim());
        });
    };
    document.addEventListener('DOMContentLoaded', () => {
        labelSelects();
        new MutationObserver(labelSelects).observe(document.body, {childList:true, subtree:true});
    });
})();
