import PhoneInput from 'react-phone-number-input';
import 'react-phone-number-input/style.css';

interface LogixFastAuthPhoneFieldProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  required?: boolean;
}

export function LogixFastAuthPhoneField({ label, value, onChange, required }: LogixFastAuthPhoneFieldProps) {
  return (
    <div className="logixfast-auth-field logixfast-auth-phone-input">
      <label className="logixfast-auth-label">
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
