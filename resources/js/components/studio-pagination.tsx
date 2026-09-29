import { Link } from '@inertiajs/react';
export type PageLink = { url: string | null; label: string; active: boolean };
export default function Pagination({ links }: { links?: PageLink[] }) {
    return links && links.length > 3 ? (
        <nav aria-label="Pagination" className="actions">
            {links.map((l, i) =>
                l.url ? (
                    <Link
                        key={i}
                        href={l.url}
                        aria-current={l.active ? 'page' : undefined}
                        className="button secondary"
                    >
                        {l.label
                            .replace('&laquo;', '←')
                            .replace('&raquo;', '→')}
                    </Link>
                ) : (
                    <span key={i}>
                        {l.label
                            .replace('&laquo;', '←')
                            .replace('&raquo;', '→')}
                    </span>
                ),
            )}
        </nav>
    ) : null;
}
