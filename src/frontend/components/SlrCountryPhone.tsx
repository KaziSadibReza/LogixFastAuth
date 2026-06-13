import { useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { ChevronDown, Phone, Search } from 'lucide-react';
import { COUNTRIES, findCountry, type CountryOption } from '../data/countries';
import { SlrField } from './SlrField';
import { SlrFlag } from './SlrFlag';

interface SlrCountryPhoneProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  onLocalChange?: () => void;
  required?: boolean;
  hasError?: boolean;
  defaultCountry?: string;
  id?: string;
}

export function SlrCountryPhone({
  label,
  value,
  onChange,
  onLocalChange,
  required,
  hasError,
  defaultCountry = 'BD',
  id = 'slr-reg-phone',
}: SlrCountryPhoneProps) {
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
    <SlrField label={label} htmlFor={id} required={required} hasError={hasError}>
      <div className={`slr-phone-row ${hasError ? 'slr-phone-row--error' : ''}`} ref={wrapRef}>
        <button
          type="button"
          className="slr-country-trigger"
          onClick={() => setOpen((v) => !v)}
          aria-expanded={open}
          aria-haspopup="listbox"
          aria-label="Country code"
        >
          <SlrFlag code={selected.code} size={22} />
          <span className="slr-country-dial">{selected.dial}</span>
          <ChevronDown size={16} className={`slr-country-chevron ${open ? 'open' : ''}`} aria-hidden="true" />
        </button>

        <div className={`slr-input-wrap slr-phone-input-wrap ${hasError ? 'slr-input-wrap--error' : ''}`}>
          <span className="slr-input-icon" aria-hidden="true">
            <Phone size={18} strokeWidth={2} />
          </span>
          <input
            id={id}
            className="slr-input slr-phone-number"
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
            className="slr-country-dropdown slr-overlay-font"
            style={{ top: rect.bottom + 6, left: rect.left, width: Math.max(rect.width, 300) }}
            role="listbox"
          >
            <div className="slr-country-search">
              <Search size={16} aria-hidden="true" />
              <input
                type="search"
                placeholder="Search country..."
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                autoFocus
              />
            </div>
            <ul className="slr-country-list">
              {filtered.slice(0, 80).map((c) => (
                <li key={c.code}>
                  <button
                    type="button"
                    role="option"
                    aria-selected={c.code === country}
                    className={c.code === country ? 'active' : ''}
                    onClick={() => pickCountry(c)}
                  >
                    <SlrFlag code={c.code} size={20} />
                    <span className="slr-country-name">{c.name}</span>
                    <span className="slr-country-code">{c.dial}</span>
                  </button>
                </li>
              ))}
            </ul>
          </div>,
          document.body
        )}
    </SlrField>
  );
}
