[CmdletBinding()]
param(
	[string]$Version = '1.2.0'
)

$ErrorActionPreference = 'Stop'
$pluginRoot = Split-Path -Parent $PSScriptRoot
$pluginsRoot = Split-Path -Parent $pluginRoot
$destination = Join-Path $pluginsRoot "mrn-wds-seo-$Version.zip"
$staging = Join-Path ([System.IO.Path]::GetTempPath()) ("mrn-wds-seo-" + [guid]::NewGuid().ToString('N'))
$packageRoot = Join-Path $staging 'mrn-wds-seo'

try {
	New-Item -ItemType Directory -Path $packageRoot -Force | Out-Null
	Get-ChildItem -LiteralPath $pluginRoot -Force |
		Where-Object { $_.Name -notin @('.git', '.github', 'tests', 'tools', 'phpcs.xml.dist') } |
		Copy-Item -Destination $packageRoot -Recurse -Force

	Add-Type -AssemblyName System.IO.Compression
	Add-Type -AssemblyName System.IO.Compression.FileSystem
	if (Test-Path -LiteralPath $destination) {
		Remove-Item -LiteralPath $destination -Force
	}

	$archiveStream = [System.IO.File]::Open($destination, [System.IO.FileMode]::CreateNew, [System.IO.FileAccess]::ReadWrite, [System.IO.FileShare]::None)
	try {
		$archive = [System.IO.Compression.ZipArchive]::new($archiveStream, [System.IO.Compression.ZipArchiveMode]::Create, $false)
		try {
			Get-ChildItem -LiteralPath $packageRoot -File -Recurse | ForEach-Object {
				$relativePath = $_.FullName.Substring($staging.Length).TrimStart([System.IO.Path]::DirectorySeparatorChar, [System.IO.Path]::AltDirectorySeparatorChar)
				[System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $_.FullName, $relativePath.Replace('\', '/'), [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
			}
		}
		finally {
			$archive.Dispose()
		}
	}
	finally {
		$archiveStream.Dispose()
	}

	Write-Host "Created $destination" -ForegroundColor Green
}
finally {
	if (Test-Path -LiteralPath $staging) {
		Remove-Item -LiteralPath $staging -Recurse -Force
	}
}
