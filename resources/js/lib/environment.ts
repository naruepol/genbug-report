export interface DetectedEnvironment {
    browser: string;
    operating_system: string;
    device: string;
}

/**
 * Best-effort guess of the reporter's browser, OS and device from the user agent,
 * used only to pre-fill the optional fields of the bug report form (the reporter can edit them).
 */
export function detectEnvironment(): DetectedEnvironment {
    if (typeof navigator === 'undefined') {
        return { browser: '', operating_system: '', device: '' };
    }

    const ua = navigator.userAgent;
    const version = (pattern: RegExp) => ua.match(pattern)?.[1] ?? '';
    const isIPad = /iPad/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);

    let browser = '';
    if (/\bLine\//.test(ua)) browser = 'LINE in-app browser';
    else if (/FBAN|FBAV/.test(ua)) browser = 'Facebook in-app browser';
    else if (/Edg(A|iOS)?\//.test(ua)) browser = `Edge ${version(/Edg(?:A|iOS)?\/(\d+)/)}`;
    else if (/OPR\//.test(ua)) browser = `Opera ${version(/OPR\/(\d+)/)}`;
    else if (/SamsungBrowser\//.test(ua)) browser = `Samsung Internet ${version(/SamsungBrowser\/(\d+)/)}`;
    else if (/CriOS\//.test(ua)) browser = `Chrome ${version(/CriOS\/(\d+)/)}`;
    else if (/FxiOS\//.test(ua)) browser = `Firefox ${version(/FxiOS\/(\d+)/)}`;
    else if (/Firefox\//.test(ua)) browser = `Firefox ${version(/Firefox\/(\d+)/)}`;
    else if (/Chrome\//.test(ua)) browser = `Chrome ${version(/Chrome\/(\d+)/)}`;
    else if (/Safari\//.test(ua)) browser = `Safari ${version(/Version\/(\d+(?:\.\d+)?)/)}`;

    let os = '';
    if (/iPhone|iPod/.test(ua)) os = `iOS ${version(/OS (\d+(?:_\d+)?)/).replace('_', '.')}`;
    else if (isIPad) os = 'iPadOS';
    else if (/Android/.test(ua)) os = `Android ${version(/Android (\d+(?:\.\d+)?)/)}`;
    else if (/Windows NT 10/.test(ua)) os = 'Windows 10/11';
    else if (/Windows/.test(ua)) os = 'Windows';
    else if (/CrOS/.test(ua)) os = 'ChromeOS';
    else if (/Mac OS X/.test(ua)) os = 'macOS';
    else if (/Linux/.test(ua)) os = 'Linux';

    const type = isIPad || /Tablet/.test(ua) || (/Android/.test(ua) && !/Mobile/.test(ua))
        ? 'Tablet'
        : /Mobi|iPhone|Android/.test(ua)
          ? 'Mobile'
          : 'Desktop';

    return {
        browser: browser.trim(),
        operating_system: os.trim(),
        device: `${type} · ${window.innerWidth}×${window.innerHeight} viewport`,
    };
}
