interface LogixFastAuthFlagProps {
  code: string;
  size?: number;
}

const flagModules = import.meta.glob('../../../node_modules/country-flag-icons/3x2/*.svg', {
  eager: true,
  query: '?url',
  import: 'default',
}) as Record<string, string>;

const flagUrls = Object.fromEntries(
  Object.entries(flagModules).map(([path, url]) => [path.match(/([A-Z]{2})\.svg$/)?.[1] || '', url])
);

function flagEmoji(code: string): string {
  return code
    .toUpperCase()
    .replace(/[^A-Z]/g, '')
    .split('')
    .map((char) => String.fromCodePoint(127397 + char.charCodeAt(0)))
    .join('');
}

export function LogixFastAuthFlag({ code, size = 22 }: LogixFastAuthFlagProps) {
  const normalizedCode = code.toUpperCase().replace(/[^A-Z]/g, '');
  const flagUrl = flagUrls[normalizedCode];

  if (flagUrl) {
    return (
      <img
        className="logixfast-auth-flag-emoji"
        src={flagUrl}
        width={size}
        height={size}
        alt=""
        aria-hidden="true"
        loading="lazy"
      />
    );
  }

  return (
    <span
      className="logixfast-auth-flag-emoji"
      style={{
        fontSize: size,
        lineHeight: 1,
        fontFamily: '"Apple Color Emoji", "Segoe UI Emoji", "Noto Color Emoji", sans-serif',
      }}
      aria-hidden="true"
    >
      {flagEmoji(normalizedCode)}
    </span>
  );
}
