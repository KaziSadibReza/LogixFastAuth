import type { ReactNode } from 'react';

export function SectionHeading({ children }: { children: ReactNode }) {
  return <div className="slr-section-heading">{children}</div>;
}
