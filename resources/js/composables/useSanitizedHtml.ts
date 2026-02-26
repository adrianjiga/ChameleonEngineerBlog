import DOMPurify from 'dompurify';

export type UseSanitizedHtmlReturn = {
    sanitize: (html: string) => string;
};

export function useSanitizedHtml(): UseSanitizedHtmlReturn {
    function sanitize(html: string): string {
        return DOMPurify.sanitize(html, {
            USE_PROFILES: { html: true },
        });
    }

    return { sanitize };
}
