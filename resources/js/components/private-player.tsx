import { useEffect, useRef, useState } from 'react';
type StreamPlayer = {
    currentTime: number;
    paused: boolean;
    addEventListener: (name: string, callback: () => void) => void;
    play: () => Promise<void>;
};
declare global {
    interface Window {
        Stream?: (frame: HTMLIFrameElement) => StreamPlayer;
    }
}
export default function PrivatePlayer({
    lessonId,
    position,
}: {
    lessonId: number;
    position: number;
}) {
    const frame = useRef<HTMLIFrameElement>(null);
    const player = useRef<StreamPlayer | null>(null);
    const lastPosition = useRef(position);
    const [url, setUrl] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const csrf = () =>
        document.querySelector<HTMLMetaElement>('meta[name=csrf-token]')
            ?.content || '';
    const save = () => {
        if (!player.current) return;
        lastPosition.current = Math.floor(player.current.currentTime);
        void fetch(`/lessons/${lessonId}/progress`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify({ position_seconds: lastPosition.current }),
            keepalive: true,
        })
            .then((r) => {
                if (!r.ok)
                    setError(
                        'Progress could not be saved. Check your access and connection.',
                    );
            })
            .catch(() =>
                setError(
                    'Your connection was interrupted. Progress may not be saved.',
                ),
            );
    };
    const refresh = async () => {
        setLoading(true);
        try {
            const r = await fetch(`/lessons/${lessonId}/playback-token`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            if (!r.ok)
                throw new Error(
                    'Private playback is unavailable. Check your access or retry.',
                );
            const data = await r.json();
            setUrl(data.url);
            setError('');
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Playback unavailable.');
            setUrl('');
        } finally {
            setLoading(false);
        }
    };
    useEffect(() => {
        if (!document.querySelector('script[data-stream-player]')) {
            const s = document.createElement('script');
            s.src = 'https://embed.cloudflarestream.com/embed/sdk.latest.js';
            s.dataset.streamPlayer = 'true';
            document.head.appendChild(s);
        }
        return () => {
            save();
        };
    }, [lessonId]);
    useEffect(() => {
        if (!url) return;
        const timer = setInterval(save, 15000);
        const refreshTimer = setInterval(
            () => {
                save();
                void refresh();
            },
            8 * 60 * 1000,
        );
        return () => {
            clearInterval(timer);
            clearInterval(refreshTimer);
        };
    }, [url, lessonId]);
    const connect = () => {
        if (frame.current && window.Stream) {
            player.current = window.Stream(frame.current);
            player.current.addEventListener('loadedmetadata', () => {
                if (player.current)
                    player.current.currentTime = lastPosition.current;
            });
            player.current.addEventListener('pause', save);
            player.current.addEventListener('ended', save);
        }
    };
    return (
        <section>
            <button
                className="button"
                disabled={loading}
                onClick={() => void refresh()}
            >
                {loading
                    ? 'Preparing playback…'
                    : url
                      ? 'Refresh private playback'
                      : 'Open private recording'}
            </button>
            {url && (
                <iframe
                    key={url}
                    ref={frame}
                    title="Private lesson recording"
                    src={url}
                    onLoad={connect}
                    allow="fullscreen"
                    allowFullScreen
                />
            )}
            {error && (
                <p className="error" role="alert">
                    {error}
                </p>
            )}
            <p className="fine">
                Progress is saved approximately every 15 seconds. Completion is
                your self-attestation.
            </p>
        </section>
    );
}
