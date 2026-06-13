import { Link2 } from 'lucide-react';
import type { SlrLoggedInRedirectType, SlrRedirectType } from '@shared/types';
import type { PageOption } from '../api/settings';
import { Select } from './Select';
import { Input } from './Input';

const TYPE_OPTIONS = [
  { value: 'stay', label: 'Stay on current page', description: 'Close popup and remain on the same URL' },
  { value: 'default', label: 'Site default', description: 'Home or integration default (Tutor, etc.)' },
  { value: 'page', label: 'WordPress page', description: 'Redirect to a page you choose' },
  { value: 'url', label: 'Custom URL', description: 'Any full URL' },
] as const;

type RedirectControlType = SlrRedirectType | SlrLoggedInRedirectType;

interface RedirectControlProps {
  type: RedirectControlType;
  pageId: number;
  url: string;
  pages: PageOption[];
  pagesLoading?: boolean;
  onTypeChange: (type: RedirectControlType) => void;
  onPageChange: (pageId: number) => void;
  onUrlChange: (url: string) => void;
  urlPlaceholder?: string;
  allowStay?: boolean;
}

export function RedirectControl({
  type,
  pageId,
  url,
  pages,
  pagesLoading,
  onTypeChange,
  onPageChange,
  onUrlChange,
  urlPlaceholder = 'https://example.com/welcome',
  allowStay = true,
}: RedirectControlProps) {
  const typeOptions = allowStay ? TYPE_OPTIONS : TYPE_OPTIONS.filter((o) => o.value !== 'stay');

  const pageOptions = [
    { value: 0, label: '— Select a page —' },
    ...pages.map((p) => ({ value: p.id, label: p.title, description: p.url })),
  ];

  return (
    <div className="slr-redirect-control">
      <Select
        value={type}
        onChange={(v) => onTypeChange(String(v) as SlrRedirectType)}
        options={typeOptions.map((o) => ({ value: o.value, label: o.label, description: o.description }))}
      />
      {type === 'page' && (
        pagesLoading ? (
          <div className="slr-redirect-control-placeholder" />
        ) : (
          <Select
            value={pageId}
            onChange={(v) => onPageChange(Number(v))}
            options={pageOptions}
            searchable
            placeholder="Choose a page"
          />
        )
      )}
      {type === 'url' && (
        <Input type="url" value={url} onChange={(e) => onUrlChange(e.target.value)} placeholder={urlPlaceholder} icon={Link2} />
      )}
      {type === 'stay' && (
        <p className="slr-redirect-hint">Ideal for popups — e.g. user signs in from About and stays on About.</p>
      )}
    </div>
  );
}
