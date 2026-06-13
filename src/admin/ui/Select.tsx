import { useEffect, useLayoutEffect, useMemo, useRef, useState, type KeyboardEvent } from 'react';
import { createPortal } from 'react-dom';
import { ChevronDown, Check } from 'lucide-react';
import { Icon } from './Icon';

export interface SelectOption {
  value: string | number;
  label: string;
  description?: string;
}

interface SelectProps {
  value: string | number;
  options: SelectOption[];
  onChange: (value: string | number) => void;
  placeholder?: string;
  searchable?: boolean;
  disabled?: boolean;
  id?: string;
  hasError?: boolean;
}

const MENU_GAP = 6;
const MENU_MAX_HEIGHT = 280;

export function Select({
  value,
  options,
  onChange,
  placeholder = 'Select…',
  searchable = false,
  disabled,
  id,
  hasError,
}: SelectProps) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');
  const [highlight, setHighlight] = useState(0);
  const [menuRect, setMenuRect] = useState({ top: 0, left: 0, width: 0, maxHeight: MENU_MAX_HEIGHT });
  const [dropUp, setDropUp] = useState(false);
  const wrapperRef = useRef<HTMLDivElement>(null);
  const triggerRef = useRef<HTMLButtonElement>(null);
  const searchRef = useRef<HTMLInputElement>(null);

  const current = options.find((o) => String(o.value) === String(value));

  const filtered = useMemo(() => {
    if (!query.trim()) return options;
    const q = query.toLowerCase();
    return options.filter((o) => o.label.toLowerCase().includes(q));
  }, [options, query]);

  const updatePosition = () => {
    if (!triggerRef.current) return;

    const rect = triggerRef.current.getBoundingClientRect();
    const menu = document.getElementById('slr-select-menu-portal');
    const measuredHeight = menu?.getBoundingClientRect().height ?? MENU_MAX_HEIGHT;
    const menuHeight = Math.min(MENU_MAX_HEIGHT, measuredHeight);

    const spaceBelow = window.innerHeight - rect.bottom - MENU_GAP;
    const spaceAbove = rect.top - MENU_GAP;
    const shouldDropUp = menuHeight > spaceBelow && spaceAbove > spaceBelow;

    const maxHeight = shouldDropUp
      ? Math.min(MENU_MAX_HEIGHT, Math.max(120, spaceAbove - 8))
      : Math.min(MENU_MAX_HEIGHT, Math.max(120, spaceBelow - 8));

    const visibleHeight = Math.min(menuHeight, maxHeight);

    setDropUp(shouldDropUp);
    setMenuRect({
      top: shouldDropUp ? rect.top - visibleHeight - MENU_GAP : rect.bottom + MENU_GAP,
      left: rect.left,
      width: rect.width,
      maxHeight,
    });
  };

  useLayoutEffect(() => {
    if (!open) return;

    updatePosition();
    const frame = requestAnimationFrame(updatePosition);

    window.addEventListener('scroll', updatePosition, true);
    window.addEventListener('resize', updatePosition);

    return () => {
      cancelAnimationFrame(frame);
      window.removeEventListener('scroll', updatePosition, true);
      window.removeEventListener('resize', updatePosition);
    };
  }, [open, filtered.length]);

  useEffect(() => {
    if (!open) return;
    const handler = (e: MouseEvent) => {
      const target = e.target as Node;
      if (wrapperRef.current?.contains(target)) return;
      const menu = document.getElementById('slr-select-menu-portal');
      if (menu?.contains(target)) return;
      setOpen(false);
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [open]);

  useEffect(() => {
    if (open && searchable) {
      setTimeout(() => searchRef.current?.focus(), 0);
    }
    if (!open) setQuery('');
  }, [open, searchable]);

  useEffect(() => {
    setHighlight(0);
  }, [query]);

  const select = (val: string | number) => {
    onChange(val);
    setOpen(false);
    setQuery('');
  };

  const onKey = (e: KeyboardEvent) => {
    if (!open) {
      if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
        e.preventDefault();
        setOpen(true);
      }
      return;
    }
    if (e.key === 'Escape') {
      e.preventDefault();
      setOpen(false);
      return;
    }
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setHighlight((h) => Math.min(filtered.length - 1, h + 1));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setHighlight((h) => Math.max(0, h - 1));
    } else if (e.key === 'Enter') {
      e.preventDefault();
      const opt = filtered[highlight];
      if (opt) select(opt.value);
    }
  };

  const menu = open ? (
    <div
      id="slr-select-menu-portal"
      className={['slr-select-menu', 'slr-select-menu--portal', dropUp ? 'slr-select-menu--drop-up' : '']
        .filter(Boolean)
        .join(' ')}
      role="listbox"
      style={{
        position: 'fixed',
        top: menuRect.top,
        left: menuRect.left,
        width: menuRect.width,
        maxHeight: menuRect.maxHeight,
        zIndex: 100050,
      }}
    >
      {searchable && (
        <div className="slr-select-search">
          <input
            ref={searchRef}
            type="text"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            onKeyDown={onKey}
            placeholder="Search…"
          />
        </div>
      )}
      {filtered.length === 0 ? (
        <div className="slr-select-empty">No options found.</div>
      ) : (
        filtered.map((opt, i) => (
          <div
            key={opt.value}
            role="option"
            aria-selected={String(opt.value) === String(value)}
            className={[
              'slr-select-option',
              String(opt.value) === String(value) ? 'selected' : '',
              i === highlight ? 'highlighted' : '',
            ]
              .filter(Boolean)
              .join(' ')}
            onMouseEnter={() => setHighlight(i)}
            onMouseDown={(e) => e.preventDefault()}
            onClick={() => select(opt.value)}
          >
            <span>
              {opt.label}
              {opt.description && (
                <small className="slr-select-option-desc">{opt.description}</small>
              )}
            </span>
            {String(opt.value) === String(value) && (
              <Icon icon={Check} size={16} className="slr-select-option-check" />
            )}
          </div>
        ))
      )}
    </div>
  ) : null;

  return (
    <div className="slr-select" ref={wrapperRef}>
      <button
        ref={triggerRef}
        type="button"
        id={id}
        className="slr-select-trigger"
        onClick={() => !disabled && setOpen((o) => !o)}
        onKeyDown={onKey}
        aria-haspopup="listbox"
        aria-expanded={open}
        disabled={disabled}
        style={hasError ? { borderColor: 'var(--slr-danger)' } : undefined}
      >
        {current ? (
          <span className="slr-select-value">{current.label}</span>
        ) : (
          <span className="slr-select-placeholder">{placeholder}</span>
        )}
        <span className="slr-select-caret" aria-hidden="true">
          <Icon icon={ChevronDown} size={16} />
        </span>
      </button>
      {typeof document !== 'undefined' && menu && createPortal(menu, document.body)}
    </div>
  );
}
