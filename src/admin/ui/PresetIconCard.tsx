interface PresetIconCardProps {
  name: string;
  primary: string;
  background: string;
  text: string;
  selected?: boolean;
  onClick: () => void;
}

export function PresetIconCard({ name, primary, background, text, selected = false, onClick }: PresetIconCardProps) {
  return (
    <button
      type="button"
      className={`logixfast-auth-preset-icon-card${selected ? ' logixfast-auth-preset-icon-card--selected' : ''}`}
      onClick={onClick}
      aria-pressed={selected}
    >
      <span className="logixfast-auth-preset-icon-card__swatches" aria-hidden="true">
        <span className="logixfast-auth-preset-icon-card__swatch" style={{ background: primary }} />
        <span className="logixfast-auth-preset-icon-card__swatch" style={{ background: background, border: '1px solid rgba(0,0,0,0.08)' }} />
        <span className="logixfast-auth-preset-icon-card__swatch" style={{ background: text }} />
      </span>
      <span className="logixfast-auth-preset-icon-card__name">{name}</span>
    </button>
  );
}
