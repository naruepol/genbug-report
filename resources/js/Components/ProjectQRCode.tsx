import { buttonClass } from '@/lib/ui';
import { Check, Copy, Download, Maximize2, QrCode, RefreshCw, X } from 'lucide-react';
import { useEffect, useState } from 'react';

interface ProjectQRCodeProps {
    projectName: string;
    projectCode: string;
    /** The public project URL encoded in the QR code. */
    targetUrl: string;
    /** URL of the generated SVG image, or null when it has not been generated yet. */
    svgUrl: string | null;
    downloads?: { png: string; svg: string };
    onGenerate?: () => void;
    generating?: boolean;
    note?: string;
}

/**
 * QR code for a project's public page, with copy, download and a full-screen
 * "Present" mode for showing it during the presentation.
 */
export default function ProjectQRCode({
    projectName,
    projectCode,
    targetUrl,
    svgUrl,
    downloads,
    onGenerate,
    generating = false,
    note,
}: ProjectQRCodeProps) {
    const [presenting, setPresenting] = useState(false);

    return (
        <div className="flex flex-col items-center gap-4 text-center">
            {svgUrl ? (
                <img
                    src={svgUrl}
                    alt={`QR code linking to ${targetUrl}`}
                    width={220}
                    height={220}
                    className="size-52 rounded-lg bg-white p-1 ring-1 ring-hairline sm:size-56"
                />
            ) : (
                <div className="flex size-52 flex-col items-center justify-center gap-2 rounded-lg bg-chip text-sm text-ink-secondary sm:size-56">
                    <QrCode className="size-8 text-ink-muted" aria-hidden="true" />
                    Not generated yet
                </div>
            )}

            <CopyableUrl url={targetUrl} />

            {note && <p className="text-xs text-ink-secondary">{note}</p>}

            <div className="flex flex-wrap justify-center gap-2">
                {svgUrl && (
                    <button type="button" onClick={() => setPresenting(true)} className={buttonClass('secondary', 'sm')}>
                        <Maximize2 className="size-3.5" aria-hidden="true" />
                        Present
                    </button>
                )}
                {downloads && (
                    <>
                        <a href={downloads.png} className={buttonClass('secondary', 'sm')} download>
                            <Download className="size-3.5" aria-hidden="true" />
                            PNG
                        </a>
                        <a href={downloads.svg} className={buttonClass('secondary', 'sm')} download>
                            <Download className="size-3.5" aria-hidden="true" />
                            SVG
                        </a>
                    </>
                )}
                {onGenerate && (
                    <button type="button" onClick={onGenerate} disabled={generating} className={buttonClass('ghost', 'sm')}>
                        <RefreshCw className={generating ? 'size-3.5 animate-spin' : 'size-3.5'} aria-hidden="true" />
                        {svgUrl ? 'Regenerate' : 'Generate QR code'}
                    </button>
                )}
            </div>

            {presenting && svgUrl && (
                <PresentOverlay
                    projectName={projectName}
                    projectCode={projectCode}
                    targetUrl={targetUrl}
                    svgUrl={svgUrl}
                    onClose={() => setPresenting(false)}
                />
            )}
        </div>
    );
}

function CopyableUrl({ url }: { url: string }) {
    const [copied, setCopied] = useState(false);

    async function copy() {
        try {
            await navigator.clipboard.writeText(url);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 2000);
        } catch {
            // Clipboard access needs HTTPS or localhost; the URL stays selectable as text.
        }
    }

    return (
        <div className="flex w-full max-w-xs items-center gap-1 rounded-lg bg-page px-2 py-1.5 ring-1 ring-hairline">
            <span className="min-w-0 flex-1 truncate text-left text-xs text-ink-secondary select-all" title={url}>
                {url}
            </span>
            <button
                type="button"
                onClick={copy}
                className="inline-flex size-7 shrink-0 items-center justify-center rounded-md text-ink-secondary hover:bg-chip hover:text-ink"
                aria-label={copied ? 'Link copied' : 'Copy link'}
                title={copied ? 'Copied' : 'Copy link'}
            >
                {copied ? <Check className="size-4 text-success-text" /> : <Copy className="size-4" />}
            </button>
        </div>
    );
}

function PresentOverlay({
    projectName,
    projectCode,
    targetUrl,
    svgUrl,
    onClose,
}: {
    projectName: string;
    projectCode: string;
    targetUrl: string;
    svgUrl: string;
    onClose: () => void;
}) {
    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') onClose();
        };

        document.addEventListener('keydown', onKeyDown);
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = '';
        };
    }, [onClose]);

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-label={`QR code for ${projectName}`}
            className="fixed inset-0 z-50 flex flex-col items-center justify-center gap-6 bg-white p-6 text-center"
        >
            <button
                type="button"
                onClick={onClose}
                autoFocus
                className="absolute top-4 right-4 inline-flex size-10 items-center justify-center rounded-full text-ink-secondary hover:bg-chip hover:text-ink"
                aria-label="Close"
            >
                <X className="size-6" />
            </button>
            <div>
                <p className="text-sm font-semibold tracking-widest text-ink-muted uppercase">{projectCode}</p>
                <h2 className="mt-1 text-3xl font-bold text-ink sm:text-4xl">{projectName}</h2>
                <p className="mt-2 text-lg text-ink-secondary">Scan to try the system and report bugs</p>
            </div>
            <img src={svgUrl} alt={`QR code linking to ${targetUrl}`} className="aspect-square w-[min(70vh,80vw)]" />
            <p className="text-lg font-medium break-all text-ink">{targetUrl}</p>
        </div>
    );
}
