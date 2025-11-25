export function getDeviceType(ua: string): string {
    if (/mobile|android|touch|webos|hpwos/i.test(ua)) return 'mobile';
    if (/tablet|ipad/i.test(ua)) return 'tablet';
    return 'desktop';
}

export function getBrowser(ua: string): string {
    if (ua.includes('Chrome')) return 'Chrome';
    if (ua.includes('Firefox')) return 'Firefox';
    if (ua.includes('Safari') && !ua.includes('Chrome')) return 'Safari';
    if (ua.includes('Edge')) return 'Edge';
    if (ua.includes('Opera') || ua.includes('OPR')) return 'Opera';
    return 'Other';
}

export function getOS(ua: string): string {
    if (/Windows/i.test(ua)) return 'Windows';
    if (/Macintosh|Mac OS/i.test(ua)) return 'macOS';
    if (/Linux/i.test(ua)) return 'Linux';
    if (/Android/i.test(ua)) return 'Android';
    if (/iPhone|iPad|iPod/i.test(ua)) return 'iOS';
    return 'Other';
}

export function isBot(ua: string): boolean {
    return /bot|crawl|spider|slurp|bingpreview/i.test(ua);
}

export function getSessionId(): string {
    return window.localStorage.getItem('site_session_id') || Math.random().toString(36).substring(2);
}