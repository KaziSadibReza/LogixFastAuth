import { useEffect } from 'react';
import { Smartphone } from 'lucide-react';
import { useAdminData } from '../context/AdminDataContext';
import { Card, Badge, EmptyState, SmsPageSkeleton } from '../ui';
import { Icon } from '../ui/Icon';

export function SmsPage() {
  const { smsProviders, smsLoading, ensureSmsProviders } = useAdminData();

  useEffect(() => {
    void ensureSmsProviders();
  }, [ensureSmsProviders]);

  if (smsLoading && smsProviders === null) {
    return <SmsPageSkeleton />;
  }

  const providers = smsProviders ?? [];

  return (
    <>
      <Card
        title="Registered providers"
        description="Providers exposed by your code or add-on plugins."
        actions={
          providers.length > 0 ? (
            <Badge variant="success" dot>{providers.length} active</Badge>
          ) : (
            <Badge variant="warning" dot>None</Badge>
          )
        }
      >
        {providers.length === 0 ? (
          <EmptyState
            icon={Smartphone}
            title="No SMS providers registered"
            description="Add a provider with the snippet below. Your class must implement SmsProviderInterface."
          />
        ) : (
          <div className="logixfast-auth-provider-list">
            {providers.map((p) => (
              <div key={p.name} className="logixfast-auth-provider-item">
                <div className="logixfast-auth-provider-item-info">
                  <Icon icon={Smartphone} size={18} />
                  <strong>{p.name}</strong>
                </div>
                <Badge variant="success" dot>Active</Badge>
              </div>
            ))}
          </div>
        )}
      </Card>

      <Card title="Register a provider" description="Add to functions.php or a small mu-plugin.">
        <pre className="logixfast-auth-code">
{`add_filter( 'logixfast_auth_sms_providers', function( $providers ) {
    require_once __DIR__ . '/MySmsProvider.php';
    $providers[] = new My_Sms_Provider();
    return $providers;
} );`}
        </pre>
      </Card>
    </>
  );
}
