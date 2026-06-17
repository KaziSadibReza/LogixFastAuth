interface SlrFlagProps {
  code: string;
  size?: number;
}

const TWEMOJI_CDN = 'https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/svg';

/** Twemoji regional-indicator SVG (same assets WordPress emoji used to serve from s.w.org). */
function flagSvgUrl(code: string): string {
  const hex = code
    .toUpperCase()
    .replace(/[^A-Z]/g, '')
    .split('')
    .map((char) => (127397 + char.charCodeAt(0)).toString(16))
    .join('-');

  return `${TWEMOJI_CDN}/${hex}.svg`;
}

export function SlrFlag({ code, size = 22 }: SlrFlagProps) {
  return (
    <img
      className="slr-flag-emoji"
      src={flagSvgUrl(code)}
      alt=""
      width={size}
      height={size}
      draggable={false}
      loading="lazy"
      decoding="async"
      aria-hidden="true"
    />
  );
}
