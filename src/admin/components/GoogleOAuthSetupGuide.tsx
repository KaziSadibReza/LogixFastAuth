export function GoogleOAuthSetupGuide() {
  return (
    <ol className="slr-help-steps">
      <li>
        Open{' '}
        <a href="https://console.cloud.google.com/" target="_blank" rel="noreferrer">
          Google Cloud Console
        </a>{' '}
        and create or select a project.
      </li>
      <li>
        Go to <strong>APIs &amp; Services → Library</strong> and enable <strong>Gmail API</strong>.
      </li>
      <li>
        Open <strong>OAuth consent screen</strong>, choose <strong>External</strong>, fill app name and support
        email, then add your Gmail under <strong>Test users</strong> while the app is in Testing.
      </li>
      <li>
        Go to <strong>Credentials → Create credentials → OAuth client ID</strong>, choose{' '}
        <strong>Web application</strong>.
      </li>
      <li>
        Paste the <strong>Authorized redirect URI</strong> from this page into Google (exact match, including trailing
        slash).
      </li>
      <li>Copy the generated <strong>Client ID</strong> and <strong>Client Secret</strong> into SLR, save, then click
        Sign in with Google.</li>
    </ol>
  );
}
