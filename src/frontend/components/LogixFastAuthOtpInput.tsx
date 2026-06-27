import { useRef, KeyboardEvent, ClipboardEvent } from 'react';

interface LogixFastAuthOtpInputProps {
  length?: number;
  value: string;
  onChange: (value: string) => void;
}

export function LogixFastAuthOtpInput({ length = 6, value, onChange }: LogixFastAuthOtpInputProps) {
  const inputsRef = useRef<(HTMLInputElement | null)[]>([]);
  const digits = value.padEnd(length, ' ').split('').slice(0, length);

  const updateDigit = (index: number, digit: string) => {
    const arr = digits.map((d) => (d === ' ' ? '' : d));
    arr[index] = digit;
    onChange(arr.join('').trim());
  };

  const handleKeyDown = (index: number, e: KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Backspace' && !digits[index]?.trim() && index > 0) {
      e.preventDefault();
      updateDigit(index - 1, '');
      inputsRef.current[index - 1]?.focus();
    } else if (e.key === 'ArrowLeft' && index > 0) {
      inputsRef.current[index - 1]?.focus();
    } else if (e.key === 'ArrowRight' && index < length - 1) {
      inputsRef.current[index + 1]?.focus();
    }
  };

  const handlePaste = (e: ClipboardEvent<HTMLInputElement>) => {
    e.preventDefault();
    const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, length);
    onChange(pasted);
    const focusIndex = Math.min(pasted.length, length - 1);
    inputsRef.current[focusIndex]?.focus();
  };

  return (
    <div className="logixfast-auth-otp-inputs" role="group" aria-label="OTP verification code">
      {digits.map((digit, i) => (
        <input
          key={i}
          ref={(el) => { inputsRef.current[i] = el; }}
          type="text"
          inputMode="numeric"
          pattern="[0-9]*"
          maxLength={1}
          value={digit.trim()}
          aria-label={`Digit ${i + 1}`}
          placeholder="•"
          onChange={(e) => {
            const v = e.target.value.replace(/\D/g, '').slice(-1);
            updateDigit(i, v);
            if (v && i < length - 1) inputsRef.current[i + 1]?.focus();
          }}
          onKeyDown={(e) => handleKeyDown(i, e)}
          onPaste={handlePaste}
          autoFocus={i === 0}
        />
      ))}
    </div>
  );
}
