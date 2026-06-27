import type { LogixFastAuthStyle } from '@shared/types';
import { applyStyleVarsToElement } from '@shared/styleVars';

/**
 * Apply appearance settings to the document root via inline CSS variables.
 */
export function applyStyleVars(style: LogixFastAuthStyle | undefined): void {
  if (!style || typeof document === 'undefined') return;
  applyStyleVarsToElement(document.documentElement, style);
}
