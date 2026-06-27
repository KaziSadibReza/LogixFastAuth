import { useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { ChevronDown, Phone, Search } from 'lucide-react';
import { COUNTRIES, findCountry, type CountryOption } from '../data/countries';
import { LogixFastAuthField } from './LogixFastAuthField';
import { LogixFastAuthFlag } from './LogixFastAuthFlag';

interface LogixFastAuthCountryPhoneProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  onLocalChange?: () => void;
  required?: boolean;
  hasError?: boolean;
  defaultCountry?: string;
  id?: string;
}

export function LogixFastAuthCountryPhone({
  label,
  value,
  onChange,
  onLocalChange,
  required,
  hasError,
  defaultCountry = 'BD',
  id = 'logixfast-auth-reg-phone',
}: LogixFastAuthCountryPhoneProps) {
  const [country, setCountry] = useState(defaultCountry);
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');
  const [localNumber, setLocalNumber] = useState('');
  const wrapRef = useRef<HTMLDivElement>(null);
  const dropdownRef = useRef<HTMLDivElement>(null);

  const selected = findCountry(country) || findCountry(defaultCountry)!;

  const synced = useRef(false);

  useEffect(() => {
    if (synced.current || !value) return;
    synced.current = true;
    const match = COUNTRIES.find((c) => value.startsWith(c.dial));
    if (match) {
      setCountry(match.code);
      setLocalNumber(value.slice(match.dial.length).trim());
    }
  }, [value]);

  useEffect(() => {
    const full = localNumber
      ? `${selected.dial}${localNumber.replace(/^\s+/, '')}`
      : '';
    onChange(full);
  }, [country, localNumber, selected.dial, onChange]);

  useEffect(() => {
    if (!open) return;
    const onDoc = (e: MouseEvent) => {
      const t = e.target as Node;
      if (wrapRef.current?.contains(t) || dropdownRef.current?.contains(t)) return;
      setOpen(false);
    };
    document.addEventListener('mousedown', onDoc);
    return () => document.removeEventListener('mousedown', onDoc);
  }, [open]);

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return COUNTRIES;
    return COUNTRIES.filter(
      (c) =>
        c.name.toLowerCase().includes(q) ||
        c.code.toLowerCase().includes(q) ||
        c.dial.includes(q)
    );
  }, [query]);

  const pickCountry = (c: CountryOption) => {
    setCountry(c.code);
    setOpen(false);
    setQuery('');
    onLocalChange?.();
  };

  const rect = wrapRef.current?.getBoundingClientRect();

  return (
    <LogixFastAuthField label={label} htmlFor={id} required={required} hasError={hasError}>
      <div className={`logixfast-auth-phone-row ${hasError ? 'logixfast-auth-phone-row--error' : ''}`} ref={wrapRef}>
        <button
          type="button"
          className="logixfast-auth-country-trigger"
          onClick={() => setOpen((v) => !v)}
          aria-expanded={open}
          aria-haspopup="listbox"
          aria-label="Country code"
        >
          <LogixFastAuthFlag code={selected.code} size={22} />
          <span className="logixfast-auth-country-dial">{selected.dial}</span>
          <ChevronDown size={16} className={`logixfast-auth-country-chevron ${open ? 'open' : ''}`} aria-hidden="true" />
        </button>

        <div className={`logixfast-auth-input-wrap logixfast-auth-phone-input-wrap ${hasError ? 'logixfast-auth-input-wrap--error' : ''}`}>
          <span className="logixfast-auth-input-icon" aria-hidden="true">
            <Phone size={18} strokeWidth={2} />
          </span>
          <input
            id={id}
            className="logixfast-auth-input logixfast-auth-phone-number"
            type="tel"
            value={localNumber}
            onChange={(e) => {
              setLocalNumber(e.target.value.replace(/[^\d\s-]/g, ''));
              onLocalChange?.();
            }}
            placeholder="Phone number"
            autoComplete="tel-national"
            aria-invalid={hasError || undefined}
          />
        </div>
      </div>

      {open &&
        rect &&
        createPortal(
          <div
            ref={dropdownRef}
            className="logixfast-auth-country-dropdown logixfast-auth-overlay-font"
            style={{ top: rect.bottom + 6, left: rect.left, width: Math.max(rect.width, 300) }}
            role="listbox"
          >
            <div className="logixfast-auth-country-search">
              <Search size={16} aria-hidden="true" />
              <input
                type="search"
                placeholder="Search country..."
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                autoFocus
              />
            </div>
            <ul className="logixfast-auth-country-list">
              {filtered.slice(0, 80).map((c) => (
                <li key={c.code}>
                  <button
                    type="button"
                    role="option"
                    aria-selected={c.code === country}
                    className={c.code === country ? 'active' : ''}
                    onClick={() => pickCountry(c)}
                  >
                    <LogixFastAuthFlag code={c.code} size={20} />
                    <span className="logixfast-auth-country-name">{c.name}</span>
                    <span className="logixfast-auth-country-code">{c.dial}</span>
                  </button>
                </li>
              ))}
            </ul>
          </div>,
          document.body
        )}
    </LogixFastAuthField>
  );
}
