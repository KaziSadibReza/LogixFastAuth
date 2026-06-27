interface LogixFastAuthFlagProps {
  code: string;
  size?: number;
}

function flagEmoji(code: string): string {
  return code
    .toUpperCase()
    .replace(/[^A-Z]/g, '')
    .split('')
    .map((char) => String.fromCodePoint(127397 + char.charCodeAt(0)))
    .join('');
}

export function LogixFastAuthFlag({ code, size = 22 }: LogixFastAuthFlagProps) {
  return (
    <span
      className="logixfast-auth-flag-emoji"
      style={{ fontSize: size, lineHeight: 1 }}
      aria-hidden="true"
    >
      {flagEmoji(code)}
    </span>
  );
}
