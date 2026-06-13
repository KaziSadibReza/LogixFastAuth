import { Globe, GraduationCap, Link2, PenLine, Plug, ShoppingCart, type LucideIcon } from 'lucide-react';
import { useSettings } from '../context/SettingsContext';
import { Card, Icon, IntegrationIconCard, IntegrationToggleCard, IntegrationsPageSkeleton } from '../ui';

type IntegrationKey = 'replace_wp_login' | 'replace_woocommerce' | 'replace_tutor' | 'replace_elementor';

type IntegrationItem = {
  key: IntegrationKey;
  label: string;
  icon: LucideIcon;
  iconVariant: 'wp' | 'woo' | 'tutor' | 'elementor';
  description: string;
  requiresDedicatedPage?: boolean;
};

const loginIntegrations: IntegrationItem[] = [
  {
    key: 'replace_wp_login',
    label: 'WordPress',
    icon: Globe,
    iconVariant: 'wp',
    description: 'Redirect wp-login.php to SLR (logout and password reset still work).',
    requiresDedicatedPage: true,
  },
  {
    key: 'replace_woocommerce',
    label: 'WooCommerce',
    icon: ShoppingCart,
    iconVariant: 'woo',
    description: 'Replace My Account and checkout login forms.',
    requiresDedicatedPage: true,
  },
  {
    key: 'replace_tutor',
    label: 'Tutor LMS',
    icon: GraduationCap,
    iconVariant: 'tutor',
    description: 'Replace the Tutor dashboard login and login modal with SLR.',
    requiresDedicatedPage: true,
  },
  {
    key: 'replace_elementor',
    label: 'Elementor',
    icon: PenLine,
    iconVariant: 'elementor',
    description: 'Swap the Elementor Pro login widget for an SLR trigger.',
  },
];

const fieldSyncMappings = [
  {
    label: 'Full name',
    targets: ['billing_first_name', 'billing_last_name'],
    woo: 'WooCommerce billing name',
    tutor: 'Tutor profile name',
  },
  {
    label: 'Phone',
    targets: ['billing_phone', 'phone_number'],
    woo: 'WooCommerce billing phone',
    tutor: 'Tutor dashboard profile',
  },
  {
    label: 'Email',
    targets: ['billing_email'],
    woo: 'WooCommerce billing email',
    tutor: 'WordPress user email',
  },
];

export function IntegrationsPage() {
  const { settings, updateSection, loading } = useSettings();

  if (loading || !settings) {
    return <IntegrationsPageSkeleton />;
  }

  const integ = settings.integrations;
  const hasDedicatedPage = Boolean(settings.general.dedicated_page_id);

  return (
    <>
      <Card
        className="slr-card--flush-body"
        title={
          <span className="slr-card-title-row">
            <span className="slr-card-title-icon slr-card-title-icon--primary">
              <Icon icon={Plug} size={18} />
            </span>
            Login replacement
          </span>
        }
        description="Choose which native login flows SLR takes over."
        bodyClassName="slr-card-body--flush"
      >
        <div className="slr-integrations-panel">
          {!hasDedicatedPage && (
            <p className="slr-integrations-notice">
              Set a dedicated SLR login page under <strong>General</strong> so dashboard and wp-login redirects work.
            </p>
          )}
          <div className="slr-integration-toggle-grid">
            {loginIntegrations.map((item) => (
              <IntegrationToggleCard
                key={item.key}
                icon={item.icon}
                iconVariant={item.iconVariant}
                title={item.label}
                description={
                  item.requiresDedicatedPage && !hasDedicatedPage
                    ? `${item.description} Set a dedicated login page in General for redirects.`
                    : item.description
                }
                active={Boolean(integ[item.key])}
                onChange={(checked) => updateSection('integrations', { [item.key]: checked })}
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
                <Icon icon={Link2} size={18} />
              </span>
              Profile field sync
            </span>
          }
          description="Registration data syncs to existing WooCommerce and Tutor profile fields."
          bodyClassName="slr-card-body--flush"
        >
          <div className="slr-integrations-panel">
            <div className="slr-icon-card-grid">
              <IntegrationIconCard
                icon={ShoppingCart}
                iconVariant="woo"
                title="WooCommerce"
                description="Customer billing & profile fields"
                code="billing_*"
                badge={{ variant: 'default', label: 'Auto-sync' }}
              />
              <IntegrationIconCard
                icon={GraduationCap}
                iconVariant="tutor"
                title="Tutor LMS"
                description="My Profile dashboard fields"
                code="phone_number"
                badge={{ variant: 'success', label: 'Integrated' }}
              />
            </div>
          </div>
        </Card>

        <Card
          className="slr-card--flush-body"
          title={
            <span className="slr-card-title-row">
              <span className="slr-card-title-icon slr-card-title-icon--success">
                <Icon icon={Link2} size={18} />
              </span>
              Field mappings
            </span>
          }
          description="How SLR registration fields map to each platform."
          bodyClassName="slr-card-body--flush"
        >
          <div className="slr-integrations-panel">
            <div className="slr-field-mapping-list">
              {fieldSyncMappings.map((row) => (
                <div key={row.label} className="slr-field-mapping-row">
                  <div className="slr-field-mapping-label">{row.label}</div>
                  <div className="slr-field-mapping-targets">
                    {row.targets.map((code) => (
                      <code key={code} className="slr-icon-card__code">
                        {code}
                      </code>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </Card>
      </div>
    </>
  );
}
