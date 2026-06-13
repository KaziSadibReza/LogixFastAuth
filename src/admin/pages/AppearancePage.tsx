import { Droplets, Eye, LayoutGrid, Maximize2, Palette, Sparkles } from 'lucide-react';
import { AppearancePreview } from '../components/AppearancePreview';
import { useSettings } from '../context/SettingsContext';
import { AppearancePageSkeleton, Card, ColorInput, Field, Icon, Input, PresetIconCard } from '../ui';

const presets = [
  { name: 'Rose', primary: '#d6336c', background: '#ffffff', text: '#111827' },
  { name: 'Indigo', primary: '#4f46e5', background: '#ffffff', text: '#0f172a' },
  { name: 'Emerald', primary: '#10b981', background: '#ffffff', text: '#0f172a' },
  { name: 'Sunset', primary: '#f97316', background: '#fff7ed', text: '#1f2937' },
  { name: 'Midnight', primary: '#a78bfa', background: '#1e1b4b', text: '#f8fafc' },
];

export function AppearancePage() {
  const { settings, updateSection, loading } = useSettings();

  if (loading || !settings) {
    return <AppearancePageSkeleton />;
  }

  const a = settings.appearance;

  return (
    <>
      <Card
        className="slr-card--flush-body"
        title={
          <span className="slr-card-title-row">
            <span className="slr-card-title-icon slr-card-title-icon--primary">
              <Icon icon={Sparkles} size={18} />
            </span>
            Color presets
          </span>
        }
        description="One click to apply a palette — fine-tune individual colors below."
        bodyClassName="slr-card-body--flush"
      >
        <div className="slr-appearance-presets-panel">
          <div className="slr-preset-icon-grid">
            {presets.map((p) => (
              <PresetIconCard
                key={p.name}
                name={p.name}
                primary={p.primary}
                background={p.background}
                text={p.text}
                selected={a.primary === p.primary && a.background === p.background}
                onClick={() =>
                  updateSection('appearance', {
                    primary: p.primary,
                    background: p.background,
                    text: p.text,
                  })
                }
              />
            ))}
          </div>
        </div>
      </Card>

      <div className="slr-page-columns slr-page-columns--split">
        <Card
          className="slr-card--flush-body"
          title={
            <span className="slr-card-title-row">
              <span className="slr-card-title-icon">
                <Icon icon={Palette} size={18} />
              </span>
              Brand colors
            </span>
          }
          description="Buttons, card background and body text."
          bodyClassName="slr-card-body--flush"
        >
          <div className="slr-appearance-settings-panel">
            <Field label="Primary" help="Buttons, links and focus rings.">
              <ColorInput value={a.primary} onChange={(v) => updateSection('appearance', { primary: v })} />
            </Field>
            <Field label="Card background">
              <ColorInput value={a.background} onChange={(v) => updateSection('appearance', { background: v })} />
            </Field>
            <Field label="Text color">
              <ColorInput value={a.text} onChange={(v) => updateSection('appearance', { text: v })} />
            </Field>
          </div>
        </Card>

        <Card
          className="slr-card--flush-body"
          title={
            <span className="slr-card-title-row">
              <span className="slr-card-title-icon slr-card-title-icon--success">
                <Icon icon={LayoutGrid} size={18} />
              </span>
              Layout & overlay
            </span>
          }
          description="Popup glass effect, corners and internal spacing."
          bodyClassName="slr-card-body--flush"
        >
          <div className="slr-appearance-settings-panel">
            <Field label="Backdrop blur" help="Popup overlay frosted glass, e.g. 24px">
              <div className="slr-appearance-field-with-icon">
                <Icon icon={Droplets} size={16} className="slr-appearance-field-icon" />
                <Input value={a.blur} onChange={(e) => updateSection('appearance', { blur: e.target.value })} />
              </div>
            </Field>
            <Field label="Border radius" help="Card corners, e.g. 12px">
              <div className="slr-appearance-field-with-icon">
                <Icon icon={Maximize2} size={16} className="slr-appearance-field-icon" />
                <Input value={a.radius} onChange={(e) => updateSection('appearance', { radius: e.target.value })} />
              </div>
            </Field>
            <Field label="Spacing" help="Padding and gaps, e.g. 1rem">
              <div className="slr-appearance-field-with-icon">
                <Icon icon={LayoutGrid} size={16} className="slr-appearance-field-icon" />
                <Input value={a.spacing} onChange={(e) => updateSection('appearance', { spacing: e.target.value })} />
              </div>
            </Field>
          </div>
        </Card>
      </div>

      <Card
        className="slr-card--flush-body slr-card--preview"
        title={
          <span className="slr-card-title-row">
            <span className="slr-card-title-icon slr-card-title-icon--primary">
              <Icon icon={Eye} size={18} />
            </span>
            Live preview
          </span>
        }
        description="Switch login/register, popup vs dedicated page, and device size. Use fullscreen for a closer look."
        bodyClassName="slr-card-body--flush"
      >
        <div className="slr-appearance-preview-wrap">
          <AppearancePreview appearance={a} />
        </div>
      </Card>
    </>
  );
}
