import { Globe, GraduationCap, Link2, PenLine, Plug, RefreshCw, ShoppingCart, type LucideIcon } from 'lucide-react';
import { useCallback, useState } from 'react';
import { syncPhoneFields, type PhoneSyncTarget } from '../api/integrations';
import { useSettings } from '../context/SettingsContext';
import { Card, Icon, IntegrationIconCard, IntegrationToggleCard, IntegrationsPageSkeleton, useToast } from '../ui';

type IntegrationKey = 'replace_wp_login' | 'replace_woocommerce' | 'replace_tutor' | 'replace_elementor';
type PluginKey = 'wordpress' | 'woocommerce' | 'tutor' | 'elementor';

type IntegrationItem = {
  key: IntegrationKey;
  label: string;
  icon: LucideIcon;
  iconVariant: 'wp' | 'woo' | 'tutor' | 'elementor';
  description: string;
  requiresDedicatedPage?: boolean;
};

const pluginKeyByIntegration: Record<IntegrationKey, PluginKey> = {
  replace_wp_login: 'wordpress',
  replace_woocommerce: 'woocommerce',
  replace_tutor: 'tutor',
  replace_elementor: 'elementor',
};

const defaultPluginAvailability: Record<PluginKey, boolean> = {
  wordpress: true,
  woocommerce: false,
  tutor: false,
  elementor: false,
};

const loginIntegrations: IntegrationItem[] = [
  {
    key: 'replace_wp_login',
    label: 'WordPress',
    icon: Globe,
    iconVariant: 'wp',
    description: 'Redirect wp-login.php to LogixFastAuth (logout and password reset still work).',
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
    description: 'Replace the Tutor dashboard login and login modal with LogixFastAuth.',
    requiresDedicatedPage: true,
  },
  {
    key: 'replace_elementor',
    label: 'Elementor',
    icon: PenLine,
    iconVariant: 'elementor',
    description: 'Replace the Elementor Pro login widget and enable LogixFastAuth dynamic tags.',
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
  const { settings, updateSection, loading, reload } = useSettings();
  const toast = useToast();
  const [syncingTarget, setSyncingTarget] = useState<PhoneSyncTarget | null>(null);

  const runPhoneSync = useCallback(
    async (target: PhoneSyncTarget, label: string) => {
      setSyncingTarget(target);
      try {
        const result = await syncPhoneFields(target);
        await reload();
        if (result.updated_users <= 0) {
          toast.info('All phone fields are already in sync.', label);
          return;
        }
        toast.success(
          `Updated ${result.updated_users} user${result.updated_users === 1 ? '' : 's'} (${result.updated_fields} field${result.updated_fields === 1 ? '' : 's'}).`,
          label
        );
      } catch (e) {
        toast.error(e instanceof Error ? e.message : 'Phone sync failed.', label);
      } finally {
        setSyncingTarget(null);
      }
    },
    [reload, toast]
  );

  if (loading || !settings) {
    return <IntegrationsPageSkeleton />;
  }

  const integ = settings.integrations;
  const hasDedicatedPage = Boolean(settings.general.dedicated_page_id);
  const plugins = { ...defaultPluginAvailability, ...settings.integration_plugins };
  const showProfileSync = plugins.woocommerce || plugins.tutor;
  const phonePreview = settings.phone_sync_preview;

  const canSyncPhones = (target: 'woocommerce' | 'tutor') => {
    const preview = target === 'woocommerce' ? phonePreview?.woocommerce : phonePreview?.tutor;
    return Boolean(preview?.available && preview.pending > 0);
  };

  const getPhoneSyncAction = (target: PhoneSyncTarget, pending?: number) => {
    if (target !== 'woocommerce' && target !== 'tutor') {
      return undefined;
    }
    if (!canSyncPhones(target)) {
      return undefined;
    }

    return {
      label: `Sync phone numbers (${pending ?? 0} user${pending === 1 ? '' : 's'})`,
      icon: RefreshCw,
      loading: syncingTarget === target,
      disabled: syncingTarget !== null,
      onClick: () => {
        void runPhoneSync(target, target === 'woocommerce' ? 'WooCommerce phone sync' : 'Tutor phone sync');
      },
    };
  };

  return (
    <>
      <Card
        className="logixfast-auth-card--flush-body"
        title={
          <span className="logixfast-auth-card-title-row">
            <span className="logixfast-auth-card-title-icon logixfast-auth-card-title-icon--primary">
              <Icon icon={Plug} size={18} />
            </span>
            Login replacement
          </span>
        }
        description="Choose which native login flows LogixFastAuth takes over."
        bodyClassName="logixfast-auth-card-body--flush"
      >
        <div className="logixfast-auth-integrations-panel">
          {!hasDedicatedPage && (
            <p className="logixfast-auth-integrations-notice">
              Set a dedicated LogixFastAuth login page under <strong>General</strong> so dashboard and wp-login redirects work.
            </p>
          )}
          <div className="logixfast-auth-integration-toggle-grid">
            {loginIntegrations.map((item) => {
              const pluginKey = pluginKeyByIntegration[item.key];
              const isAvailable = plugins[pluginKey];
              const unavailableHint = `${item.label} is not installed or not active.`;
              const syncTarget =
                item.key === 'replace_woocommerce' ? 'woocommerce' : item.key === 'replace_tutor' ? 'tutor' : null;
              const syncAction =
                syncTarget && canSyncPhones(syncTarget)
                  ? getPhoneSyncAction(
                      syncTarget,
                      syncTarget === 'woocommerce'
                        ? phonePreview?.woocommerce.pending
                        : phonePreview?.tutor.pending
                    )
                  : undefined;

              return (
                <IntegrationToggleCard
                  key={item.key}
                  icon={item.icon}
                  iconVariant={item.iconVariant}
                  title={item.label}
                  description={
                    !isAvailable
                      ? unavailableHint
                      : item.requiresDedicatedPage && !hasDedicatedPage
                        ? `${item.description} Set a dedicated login page in General for redirects.`
                        : item.description
                  }
                  active={isAvailable && Boolean(integ[item.key])}
                  disabled={!isAvailable}
                  hint={unavailableHint}
                  syncAction={syncAction}
                  onChange={(checked) => {
                    if (!isAvailable) {
                      return;
                    }
                    updateSection('integrations', { [item.key]: checked });
                  }}
                />
              );
            })}
          </div>
        </div>
      </Card>

      {showProfileSync && (
        <div className="logixfast-auth-page-columns logixfast-auth-page-columns--split">
          <Card
            className="logixfast-auth-card--flush-body"
            title={
              <span className="logixfast-auth-card-title-row">
                <span className="logixfast-auth-card-title-icon">
                  <Icon icon={Link2} size={18} />
                </span>
                Profile field sync
              </span>
            }
            description="Registration data syncs to existing WooCommerce and Tutor profile fields. Use sync when you install WooCommerce or Tutor after LogixFastAuth has already collected phones."
            bodyClassName="logixfast-auth-card-body--flush"
          >
            <div className="logixfast-auth-integrations-panel">
              <div className="logixfast-auth-icon-card-grid">
                {plugins.woocommerce && (
                  <IntegrationIconCard
                    icon={ShoppingCart}
                    iconVariant="woo"
                    title="WooCommerce"
                    description={
                      phonePreview?.woocommerce.pending
                        ? `${phonePreview.woocommerce.pending} user${phonePreview.woocommerce.pending === 1 ? '' : 's'} need billing_phone synced from LogixFastAuth.`
                        : 'Customer billing phone is in sync.'
                    }
                    code="billing_phone"
                    badge={{
                      variant: phonePreview?.woocommerce.pending ? 'warning' : 'default',
                      label: phonePreview?.woocommerce.pending ? `${phonePreview.woocommerce.pending} pending` : 'Auto-sync',
                    }}
                    action={
                      canSyncPhones('woocommerce')
                        ? getPhoneSyncAction('woocommerce', phonePreview?.woocommerce.pending)
                        : undefined
                    }
                  />
                )}
                {plugins.tutor && (
                  <IntegrationIconCard
                    icon={GraduationCap}
                    iconVariant="tutor"
                    title="Tutor LMS"
                    description={
                      phonePreview?.tutor.pending
                        ? `${phonePreview.tutor.pending} user${phonePreview.tutor.pending === 1 ? '' : 's'} need phone_number synced from LogixFastAuth.`
                        : 'Tutor profile phone is in sync.'
                    }
                    code="phone_number"
                    badge={{
                      variant: phonePreview?.tutor.pending ? 'warning' : 'success',
                      label: phonePreview?.tutor.pending ? `${phonePreview.tutor.pending} pending` : 'Integrated',
                    }}
                    action={
                      canSyncPhones('tutor')
                        ? getPhoneSyncAction('tutor', phonePreview?.tutor.pending)
                        : undefined
                    }
                  />
                )}
              </div>
            </div>
          </Card>

          <Card
            className="logixfast-auth-card--flush-body"
            title={
              <span className="logixfast-auth-card-title-row">
                <span className="logixfast-auth-card-title-icon logixfast-auth-card-title-icon--success">
                  <Icon icon={Link2} size={18} />
                </span>
                Field mappings
              </span>
            }
            description="How LogixFastAuth registration fields map to each platform."
            bodyClassName="logixfast-auth-card-body--flush"
          >
            <div className="logixfast-auth-integrations-panel">
              <div className="logixfast-auth-field-mapping-list">
                {fieldSyncMappings.map((row) => (
                  <div key={row.label} className="logixfast-auth-field-mapping-row">
                    <div className="logixfast-auth-field-mapping-label">{row.label}</div>
                    <div className="logixfast-auth-field-mapping-targets">
                      {row.targets.map((code) => (
                        <code key={code} className="logixfast-auth-icon-card__code">
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
      )}
    </>
  );
}
