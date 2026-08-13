type WpI18n = { __(text: string, domain?: string): string };

declare global {
  interface Window { wp?: { i18n?: WpI18n } }
}

export const t = (text: string): string => window.wp?.i18n?.__(text, "npcink-site-toolbox") || text;
