/**
 * Timezone utilities for Philippine Manila timezone
 */

export const MANILA_TIMEZONE = 'Asia/Manila';

/**
 * Get current Manila time in datetime-local format (YYYY-MM-DDTHH:MM)
 */
export function getCurrentManilaDateTime(): string {
    const now = new Date();
    
    // Get the Manila time using Intl.DateTimeFormat
    const manilaFormatter = new Intl.DateTimeFormat('en-CA', {
        timeZone: MANILA_TIMEZONE,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    });
    
    const parts = manilaFormatter.formatToParts(now);
    const year = parts.find(part => part.type === 'year')?.value;
    const month = parts.find(part => part.type === 'month')?.value;
    const day = parts.find(part => part.type === 'day')?.value;
    const hour = parts.find(part => part.type === 'hour')?.value;
    const minute = parts.find(part => part.type === 'minute')?.value;
    
    return `${year}-${month}-${day}T${hour}:${minute}`;
}

/**
 * Get current Manila date in date format (YYYY-MM-DD)
 */
export function getCurrentManilaDate(): string {
    const now = new Date();
    
    // Get the Manila date using Intl.DateTimeFormat
    const manilaFormatter = new Intl.DateTimeFormat('en-CA', {
        timeZone: MANILA_TIMEZONE,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
    
    const parts = manilaFormatter.formatToParts(now);
    const year = parts.find(part => part.type === 'year')?.value;
    const month = parts.find(part => part.type === 'month')?.value;
    const day = parts.find(part => part.type === 'day')?.value;
    
    return `${year}-${month}-${day}`;
}

/**
 * Format date string to Philippine locale with Manila timezone
 */
export function formatManilaDateTime(dateString: string): string {
    return new Date(dateString).toLocaleString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: MANILA_TIMEZONE
    });
}

/**
 * Format date string to Philippine locale date only with Manila timezone
 */
export function formatManilaDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        timeZone: MANILA_TIMEZONE
    });
}

/**
 * Format currency to Philippine peso format
 */
export function formatPhilippinePeso(amount: number): string {
    return '₱' + amount.toLocaleString('en-PH', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    });
}

/**
 * Convert a Manila time to ISO string for server submission
 */
export function manilaDateTimeToISO(manilaDateTime: string): string {
    // Assume the input is already in Manila timezone
    const date = new Date(manilaDateTime);
    return date.toISOString();
}