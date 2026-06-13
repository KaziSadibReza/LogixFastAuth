interface SlrFlagProps {
  code: string;
  size?: number;
}

export function SlrFlag({ code, size = 22 }: SlrFlagProps) {
  const cc = code.toLowerCase();
  const height = Math.round(size * 0.68);

  return (
    <img
      className="slr-flag-img"
      src={`https://flagcdn.com/w40/${cc}.png`}
      srcSet={`https://flagcdn.com/w80/${cc}.png 2x`}
      width={size}
      height={height}
      alt=""
      loading="lazy"
      decoding="async"
      draggable={false}
    />
  );
}
