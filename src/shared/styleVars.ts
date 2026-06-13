import type { SlrStyle } from './types';

function hexToRgb(hex: string): [number, number, number] {
  const cleaned = hex.replace('#', '').trim();
  const full =
    cleaned.length === 3
      ? cleaned.split('').map((c) => c + c).join('')
      : cleaned.length >= 6
        ? cleaned.slice(0, 6)
        : '';
  if (!/^[0-9a-f]{6}$/i.test(full)) return [214, 51, 108];
  return [parseInt(full.slice(0, 2), 16), parseInt(full.slice(2, 4), 16), parseInt(full.slice(4, 6), 16)];
}

function rgbToHex(rgb: [number, number, number]): string {
  return '#' + rgb.map((v) => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('');
}

function darken(hex: string, amount: number): string {
  const rgb = hexToRgb(hex).map((v) => v * (1 - amount)) as [number, number, number];
  return rgbToHex(rgb);
}

function mixWithWhite(hex: string, amount: number): string {
  const rgb = hexToRgb(hex).map((v) => v + (255 - v) * amount) as [number, number, number];
  return rgbToHex(rgb);
}

function rgba(hex: string, alpha: number): string {
  const [r, g, b] = hexToRgb(hex);
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

/**
 * Apply appearance CSS variables to any element (typically documentElement).
 * Inline custom properties beat external :root rules from bundled CSS.
 */
export function applyStyleVarsToElement(root: HTMLElement, style: SlrStyle | undefined): void {
  if (!style) return;

  const primary = style.primary || '#d6336c';

  root.style.setProperty('--slr-primary', primary);
  root.style.setProperty('--slr-primary-dark', darken(primary, 0.18));
  root.style.setProperty('--slr-primary-50', mixWithWhite(primary, 0.92));
  root.style.setProperty('--slr-primary-100', mixWithWhite(primary, 0.85));
  root.style.setProperty('--slr-background', style.background || '#ffffff');
  root.style.setProperty('--slr-text', style.text || '#111827');
  root.style.setProperty('--slr-blur', style.blur || '24px');
  root.style.setProperty('--slr-radius', style.radius || '12px');
  root.style.setProperty('--slr-spacing', style.spacing || '1rem');
  root.style.setProperty('--slr-shadow-focus', `0 0 0 3px ${rgba(primary, 0.18)}`);
  root.style.setProperty('--slr-shadow-primary', `0 8px 20px ${rgba(primary, 0.32)}`);
}

export function previewStageBackground(primary: string): string {
  return `radial-gradient(circle at 20% 20%, ${rgba(primary, 0.06)}, transparent 45%), radial-gradient(circle at 80% 80%, rgba(79, 70, 229, 0.05), transparent 40%), #f4f6f8`;
}
