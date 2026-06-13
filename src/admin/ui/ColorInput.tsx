interface ColorInputProps {
  value: string;
  onChange: (v: string) => void;
  id?: string;
}

export function ColorInput({ value, onChange, id }: ColorInputProps) {
  return (
    <div className="slr-color-input">
      <input
        type="color"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        aria-label="Color picker"
      />
      <input
        id={id}
        type="text"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        spellCheck={false}
      />
    </div>
  );
}
