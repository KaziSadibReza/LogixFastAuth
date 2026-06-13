import { forwardRef, type ButtonHTMLAttributes, type ReactNode } from 'react';
import type { LucideIcon } from 'lucide-react';
import { Icon } from './Icon';

export type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger';
export type ButtonSize = 'sm' | 'md' | 'lg' | 'icon';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
  loading?: boolean;
  block?: boolean;
  icon?: LucideIcon;
  customIcon?: ReactNode;
  iconRight?: LucideIcon;
  children?: ReactNode;
}

const iconSize: Record<ButtonSize, number> = {
  sm: 15,
  md: 16,
  lg: 18,
  icon: 18,
};

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
  { variant = 'secondary', size = 'md', loading, block, icon, customIcon, iconRight, children, className = '', disabled, type = 'button', ...rest },
  ref
) {
  const classes = [
    'slr-btn',
    `slr-btn--${variant}`,
    size !== 'md' ? `slr-btn--${size}` : '',
    block ? 'slr-btn--block' : '',
    loading ? 'loading' : '',
    className,
  ]
    .filter(Boolean)
    .join(' ');

  const sz = iconSize[size];

  return (
    <button ref={ref} type={type} className={classes} disabled={disabled || loading} {...rest}>
      {customIcon ?? (icon ? <Icon icon={icon} size={sz} /> : null)}
      {size !== 'icon' && children && <span className="slr-btn-label">{children}</span>}
      {iconRight && <Icon icon={iconRight} size={sz} />}
    </button>
  );
});
