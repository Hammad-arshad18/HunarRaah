export default function StudioMark({ className }: { className?: string }) {
    return (
        <svg
            className={className || 'studio-mark'}
            viewBox="0 0 40 40"
            aria-hidden="true"
        >
            <rect width="40" height="40" rx="11" fill="#2448D8" />
            <path
                d="M11 12h18M11 20h13M11 28h8"
                stroke="#fff"
                strokeWidth="3"
            />
            <circle cx="29" cy="28" r="4" fill="#E9F2B7" />
        </svg>
    );
}
