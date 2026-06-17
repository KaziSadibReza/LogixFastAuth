import { useEffect, useId, useRef, useState, type ReactNode } from 'react';
import { CircleHelp } from 'lucide-react';
import { Icon } from './Icon';

interface HelpTooltipProps {
  label: string;
  children: ReactNode;
}

export function HelpTooltip({ label, children }: HelpTooltipProps) {
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);
  const panelId = useId();

  useEffect(() => {
    if (!open) return;

    const onPointerDown = (event: MouseEvent) => {
      if (!rootRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    };

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setOpen(false);
      }
    };

    document.addEventListener('mousedown', onPointerDown);
    document.addEventListener('keydown', onKeyDown);

    return () => {
      document.removeEventListener('mousedown', onPointerDown);
      document.removeEventListener('keydown', onKeyDown);
    };
  }, [open]);

  return (
    <div className="slr-help-tooltip" ref={rootRef}>
      <button
        type="button"
        className="slr-help-tooltip__trigger"
        aria-label={label}
        aria-expanded={open}
        aria-controls={panelId}
        onClick={() => setOpen((value) => !value)}
      >
        <Icon icon={CircleHelp} size={16} />
      </button>
      {open && (
        <div className="slr-help-tooltip__panel" id={panelId} role="tooltip">
          <strong className="slr-help-tooltip__title">{label}</strong>
          <div className="slr-help-tooltip__content">{children}</div>
        </div>
      )}
    </div>
  );
}
