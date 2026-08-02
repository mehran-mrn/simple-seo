<#
.SYNOPSIS
	Deploy MRN SEO Profiles to zarsamgold.ir with rollback.
#>
[CmdletBinding()]
param(
	[string]$ArtifactPath = '',
	[switch]$StatusOnly
)

$ErrorActionPreference = 'Stop'
$pluginRoot = Split-Path -Parent $PSScriptRoot
$workspaceRoot = Split-Path -Parent (Split-Path -Parent $pluginRoot)
$pluginsRoot = Split-Path -Parent $pluginRoot
$header = Get-Content -LiteralPath (Join-Path $pluginRoot 'mrn-wds-seo.php') -Raw
if ($header -notmatch 'Version:\s*([0-9.]+)') {
	throw 'Plugin version was not found.'
}
$version = $matches[1]
$ArtifactPath = if ($ArtifactPath) { $ArtifactPath } else { Join-Path $pluginsRoot "mrn-wds-seo-$version.zip" }
$metadataPath = Join-Path $workspaceRoot 'Docs\.secrets\infrastructure.env'
$credentialPath = Join-Path $workspaceRoot 'Docs\.secrets\server-root.credential.xml'
$plinkPath = 'C:\Program Files\PuTTY\plink.exe'
$pscpPath = 'C:\Program Files\PuTTY\pscp.exe'

foreach ($requiredPath in @($metadataPath, $credentialPath, $plinkPath)) {
	if (-not (Test-Path -LiteralPath $requiredPath -PathType Leaf)) {
		throw "Required deployment file is missing: $requiredPath"
	}
}
if (-not $StatusOnly) {
	foreach ($requiredPath in @($ArtifactPath, $pscpPath)) {
		if (-not (Test-Path -LiteralPath $requiredPath -PathType Leaf)) {
			throw "Required deployment file is missing: $requiredPath"
		}
	}
}

$connection = @{}
Get-Content -LiteralPath $metadataPath | ForEach-Object {
	if ($_ -match '^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)\s*$') {
		$connection[$matches[1]] = $matches[2].Trim().Trim('"').Trim("'")
	}
}
$documentRoot = $connection['MRN_REMOTE_PUBLIC_HTML']
$credential = Import-Clixml -LiteralPath $credentialPath
$networkCredential = $credential.GetNetworkCredential()
$sshUser = $networkCredential.UserName
$sshPassword = $networkCredential.Password
$sshHost = $connection['MRN_SSH_FORWARD_HOST']
$sshPort = [int]$connection['MRN_SSH_FORWARD_PORT']
$expectedFingerprint = $connection['MRN_SSH_ED25519_FINGERPRINT']
$keyScanFile = New-TemporaryFile

try {
	$previousErrorAction = $ErrorActionPreference
	$ErrorActionPreference = 'Continue'
	$keyLines = & ssh-keyscan -p $sshPort -t ed25519 $sshHost 2>$null
	$ErrorActionPreference = $previousErrorAction
	if (-not $keyLines) { throw 'SSH key scan returned no ED25519 key.' }
	[System.IO.File]::WriteAllLines($keyScanFile.FullName, [string[]]$keyLines, [System.Text.UTF8Encoding]::new($false))
	$shaLine = & ssh-keygen -lf $keyScanFile.FullName 2>$null | Select-Object -First 1
	if ($shaLine -notmatch '(SHA256:[A-Za-z0-9+/=]+)' -or $matches[1] -ne $expectedFingerprint) {
		throw 'SSH host fingerprint mismatch.'
	}
	$md5Line = & ssh-keygen -l -E md5 -f $keyScanFile.FullName 2>$null | Select-Object -First 1
	if ($md5Line -notmatch '(MD5:[0-9a-f:]+)') { throw 'Could not derive the PuTTY host key.' }
	$puttyHostKey = $matches[1].Substring(4)
}
finally {
	Remove-Item -LiteralPath $keyScanFile.FullName -Force -ErrorAction SilentlyContinue
}

function Invoke-RemoteCommand {
	param([Parameter(Mandatory)][string]$Command)
	$arguments = @('-batch', '-ssh', '-P', "$sshPort", '-l', $sshUser, '-pw', $sshPassword, '-hostkey', $puttyHostKey, $sshHost, $Command)
	& $plinkPath @arguments
	if ($LASTEXITCODE -ne 0) { throw "Remote command failed with exit code $LASTEXITCODE." }
}

if ($StatusOnly) {
	Invoke-RemoteCommand -Command @"
set -eu
cd '$documentRoot'
if wp --allow-root plugin is-installed mrn-wds-seo; then
	printf 'installed=yes\nversion='
	wp --allow-root plugin get mrn-wds-seo --field=version
	printf 'active='
	if wp --allow-root plugin is-active mrn-wds-seo; then printf 'yes\n'; else printf 'no\n'; fi
else
	printf 'installed=no\n'
fi
"@
	exit
}

$deploymentId = Get-Date -Format 'yyyyMMdd-HHmmss'
$remoteStage = "/home/masnavi/.mrn-deploys/zarsam-seo-$deploymentId"
$remoteBackup = "/home/masnavi/backups/mrn-wds-seo-zarsam-$deploymentId"
$remotePackage = "$remoteStage/mrn-wds-seo-$version.zip"
$localHash = (Get-FileHash -LiteralPath $ArtifactPath -Algorithm SHA256).Hash.ToLowerInvariant()

Invoke-RemoteCommand -Command "set -eu; test -f '$documentRoot/wp-config.php'; mkdir -p '$remoteStage/extract' '$remoteBackup'; printf 'preflight=ok\n'"

$copyArguments = @('-batch', '-P', "$sshPort", '-l', $sshUser, '-pw', $sshPassword, '-hostkey', $puttyHostKey, $ArtifactPath, "${sshUser}@${sshHost}:$remotePackage")
& $pscpPath @copyArguments
if ($LASTEXITCODE -ne 0) { throw "Package upload failed with exit code $LASTEXITCODE." }

Invoke-RemoteCommand -Command @"
set -eu
stage='$remoteStage'
backup='$remoteBackup'
docroot='$documentRoot'
live="`$docroot/wp-content/plugins/mrn-wds-seo"
incoming="`$stage/extract/mrn-wds-seo"
previous="`$backup/mrn-wds-seo.previous"
changed=0
rollback() {
	set +e
	cd "`$docroot"
	wp --allow-root plugin deactivate mrn-wds-seo >/dev/null 2>&1
	if [ -d "`$live" ]; then mv "`$live" "`$stage/mrn-wds-seo.failed"; fi
	if [ -d "`$previous" ]; then mv "`$previous" "`$live"; wp --allow-root plugin activate mrn-wds-seo >/dev/null 2>&1; fi
	wp --allow-root cache flush >/dev/null 2>&1
}
trap rollback EXIT HUP INT TERM
printf '%s  %s\n' '$localHash' '$remotePackage' | sha256sum -c -
unzip -q '$remotePackage' -d "`$stage/extract"
test -f "`$incoming/mrn-wds-seo.php"
grep -q 'Version:[[:space:]]*$version' "`$incoming/mrn-wds-seo.php"
find "`$incoming" -type f -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
owner_group="`$(stat -c '%U:%G' "`$docroot")"
chown -R "`$owner_group" "`$incoming"
find "`$incoming" -type d -exec chmod 755 {} +
find "`$incoming" -type f -exec chmod 644 {} +
if [ -d "`$live" ]; then mv "`$live" "`$previous"; fi
changed=1
mv "`$incoming" "`$live"
cd "`$docroot"
wp --allow-root plugin activate mrn-wds-seo
wp --allow-root plugin is-active mrn-wds-seo
test "`$(wp --allow-root plugin get mrn-wds-seo --field=version)" = '$version'
wp --allow-root rewrite flush --hard >/dev/null
wp --allow-root cache flush >/dev/null
curl -fsSL 'https://zarsamgold.ir/sitemap_index.xml' | grep -q '<sitemapindex'
trap - EXIT HUP INT TERM
printf 'deploy=ok\nversion=$version\nbackup=%s\n' "`$backup"
"@

Write-Host ''
Write-Host 'MRN SEO Profiles deployment completed.' -ForegroundColor Green
Write-Host "Version: $version"
Write-Host "Backup: $remoteBackup"
