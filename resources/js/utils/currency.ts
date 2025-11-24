// Format numbers as Philippine Peso with thousands separators, used for outstanding balances
export function formatCurrency(value: number | null | undefined): string {
    const num = Number(value || 0);
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
}