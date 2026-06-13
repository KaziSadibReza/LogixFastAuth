import type { LucideIcon } from 'lucide-react';

interface IconProps {
  icon: LucideIcon;
  size?: number;
  className?: string;
  strokeWidth?: number;
}

export function Icon({ icon: Lucide, size = 18, className = '', strokeWidth = 2 }: IconProps) {
  return <Lucide size={size} strokeWidth={strokeWidth} className={`slr-icon ${className}`.trim()} aria-hidden="true" />;
}
