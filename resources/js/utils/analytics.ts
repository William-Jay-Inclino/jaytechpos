import axios from 'axios';

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

export async function sendAnalytics(payload: {
    referer: string | null,
    ua: string,
    page_url: string,
}) {
    try {
        const sessionId = getSessionId();
        
        const data = {
            session_id: sessionId,
            user_agent: payload.ua,
            referer: payload.referer,
            page_url: payload.page_url,
            device_type: getDeviceType(payload.ua),
            browser: getBrowser(payload.ua),
            os: getOS(payload.ua),
            is_bot: isBot(payload.ua),
            is_unique: false,
            page_views: 1,
            visited_at: new Date().toISOString(),
        };
    
        window.localStorage.setItem('site_session_id', sessionId);
        axios.post('/analytics/site-visit', data).catch(() => {});
    } catch (e) {
        // silent
    }
}