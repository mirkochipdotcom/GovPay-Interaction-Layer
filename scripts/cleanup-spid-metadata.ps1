[CmdletBinding()]
param(
  [string]$MetadataDir = '',
  [string]$CurrentDir = '',
  [string]$ArchiveDir = '',
  [switch]$IncludeCie = $true
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$scriptDir = $PSScriptRoot
if ([string]::IsNullOrWhiteSpace($scriptDir)) {
  $scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
}

if ([string]::IsNullOrWhiteSpace($MetadataDir)) {
  $MetadataDir = Join-Path $scriptDir '..\spid-proxy\metadata'
}
if ([string]::IsNullOrWhiteSpace($CurrentDir)) {
  $CurrentDir = Join-Path $scriptDir '..\spid-proxy\metadata-current'
}
if ([string]::IsNullOrWhiteSpace($ArchiveDir)) {
  $ArchiveDir = Join-Path $scriptDir '..\spid-proxy\metadata-archive'
}

if (-not (Test-Path -LiteralPath $MetadataDir)) { New-Item -ItemType Directory -Path $MetadataDir | Out-Null }
if (-not (Test-Path -LiteralPath $CurrentDir)) { New-Item -ItemType Directory -Path $CurrentDir | Out-Null }
if (-not (Test-Path -LiteralPath $ArchiveDir)) { New-Item -ItemType Directory -Path $ArchiveDir | Out-Null }

$MetadataDir = (Resolve-Path -LiteralPath $MetadataDir).Path
$CurrentDir = (Resolve-Path -LiteralPath $CurrentDir).Path
$ArchiveDir = (Resolve-Path -LiteralPath $ArchiveDir).Path

Write-Host "[cleanup] MetadataDir = $MetadataDir"
Write-Host "[cleanup] CurrentDir  = $CurrentDir"
Write-Host "[cleanup] ArchiveDir  = $ArchiveDir"

$timestamp = (Get-Date).ToUniversalTime().ToString('yyyyMMddTHHmmssZ')

function Move-ToArchive {
  param(
    [Parameter(Mandatory = $true)][string]$Path
  )
  $name = Split-Path -Leaf $Path
  $dest = Join-Path $ArchiveDir $name
  if (Test-Path -LiteralPath $dest) {
    $dest = Join-Path $ArchiveDir ("{0}.{1}.xml" -f $name, $timestamp)
  }
  Move-Item -LiteralPath $Path -Destination $dest -Force
  Write-Host "[cleanup] Archived -> $dest"
}

function Ensure-CurrentInPlace {
  param(
    [Parameter(Mandatory = $true)][string]$Kind
  )

  $curName = "{0}-metadata-current.xml" -f $Kind
  $curInCurrent = Join-Path $CurrentDir $curName
  $curInMetadata = Join-Path $MetadataDir $curName

  if (-not (Test-Path -LiteralPath $curInCurrent) -and (Test-Path -LiteralPath $curInMetadata)) {
    Move-Item -LiteralPath $curInMetadata -Destination $curInCurrent -Force
    Write-Host "[cleanup] Moved CURRENT ($Kind) -> $curInCurrent"
  }
}

$kinds = @('spid')
if ($IncludeCie) { $kinds += 'cie' }

foreach ($kind in $kinds) {
  Ensure-CurrentInPlace -Kind $kind
}

# In metadata/ devono restare solo:
# - .gitkeep
# - *-metadata-next.xml
# Tutto il resto (snapshot timestampati, bak, current duplicati) viene archiviato.
Get-ChildItem -LiteralPath $MetadataDir -File | ForEach-Object {
  $name = $_.Name
  if ($name -ieq '.gitkeep') { return }
  if ($name -match '^(spid|cie)-metadata-next\.xml$') { return }
  Move-ToArchive -Path $_.FullName
}

Write-Host "[cleanup] Done."
