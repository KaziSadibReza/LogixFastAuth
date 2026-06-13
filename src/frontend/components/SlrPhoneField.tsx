import PhoneInput from 'react-phone-number-input';
import 'react-phone-number-input/style.css';

interface SlrPhoneFieldProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  required?: boolean;
}

export function SlrPhoneField({ label, value, onChange, required }: SlrPhoneFieldProps) {
  return (
    <div className="slr-field slr-phone-input">
      <label className="slr-label">
        {label}
        {required && ' '}
      </label>
      <PhoneInput
        international
        defaultCountry="BD"
        value={value}
        onChange={(v) => onChange(v || '')}
        placeholder="Enter phone number"
      />
    </div>
  );
}
