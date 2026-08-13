type WpI18n = {
  __(text: string, domain?: string): string;
  sprintf?(format: string, ...args: Array<string | number>): string;
};

declare global {
  interface Window { wp?: { i18n?: WpI18n } }
}

export const __ = (text: string): string => window.wp?.i18n?.__(text, "npcink-site-toolbox") || text;

export const sprintf = (format: string, ...args: Array<string | number>): string => {
  if (window.wp?.i18n?.sprintf) return window.wp.i18n.sprintf(format, ...args);

  let nextIndex = 0;
  return format.replace(/%(?:(\d+)\$)?[sd]/g, (_match, position?: string) => {
    const index = position ? Number(position) - 1 : nextIndex++;
    return String(args[index] ?? "");
  });
};
