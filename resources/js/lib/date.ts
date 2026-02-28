export function formatDate(date: string | null | undefined): string {
    if (!date) return '';
    return new Date(date).toLocaleDateString();
}

export function formatDateLong(date: string | null | undefined): string {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}
