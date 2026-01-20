[CmdletBinding()]
param(
  [string]$SourceDir = '',
  [string]$TargetDir = '',
  [string]$ArchiveDir = '',
  [switch]$IncludeCie = $true
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Assert-XmlValid {
  param(
    [Parameter(Mandatory = $true)][string]$Path,
    [Parameter(Mandatory = $true)][string]$Kind
  )

  if (-not (Test-Path -LiteralPath $Path)) {
    throw "File mancante: $Path"
  }
  if ((Get-Item -LiteralPath $Path).Length -le 0) {
    throw "File vuoto: $Path"
  }

  try {
    [xml]$xml = Get-Content -LiteralPath $Path -Raw
  } catch {
    throw "XML non valido per $Kind ($Path): $($_.Exception.Message)"
  }

  $entityId = $xml.DocumentElement.GetAttribute('entityID')
  if ([string]::IsNullOrWhiteSpace($entityId)) {
    throw "entityID mancante in $Kind ($Path)"
  }

  return $entityId
}

$scriptDir = $PSScriptRoot
if ([string]::IsNullOrWhiteSpace($scriptDir)) {
  $scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
}

if ([string]::IsNullOrWhiteSpace($SourceDir)) {
  $SourceDir = Join-Path $scriptDir '..\spid-proxy\metadata'
}
if ([string]::IsNullOrWhiteSpace($TargetDir)) {
  $TargetDir = Join-Path $scriptDir '..\spid-proxy\metadata-current'
}
if ([string]::IsNullOrWhiteSpace($ArchiveDir)) {
  $ArchiveDir = Join-Path $scriptDir '..\spid-proxy\metadata-archive'
}

$SourceDir = (Resolve-Path -LiteralPath $SourceDir).Path
if (-not (Test-Path -LiteralPath $TargetDir)) { New-Item -ItemType Directory -Path $TargetDir | Out-Null }
if (-not (Test-Path -LiteralPath $ArchiveDir)) { New-Item -ItemType Directory -Path $ArchiveDir | Out-Null }
$TargetDir = (Resolve-Path -LiteralPath $TargetDir).Path
$ArchiveDir = (Resolve-Path -LiteralPath $ArchiveDir).Path

Write-Host "[promote] SourceDir  = $SourceDir"
Write-Host "[promote] TargetDir  = $TargetDir (mounted by runtime)"
Write-Host "[promote] ArchiveDir = $ArchiveDir (NOT mounted)"

$timestamp = (Get-Date).ToUniversalTime().ToString('yyyyMMddTHHmmssZ')

$items = @(
  @{ Kind = 'spid'; Next = 'spid-metadata-next.xml'; Current = 'spid-metadata-current.xml' }
)

if ($IncludeCie) {
  $items += @{ Kind = 'cie'; Next = 'cie-metadata-next.xml'; Current = 'cie-metadata-current.xml' }
}

foreach ($it in $items) {
  $kind = $it.Kind
  $nextPath = Join-Path $SourceDir $it.Next
  $currentPath = Join-Path $TargetDir $it.Current

  Write-Host "[promote] Checking $kind NEXT: $nextPath"
  $entityId = Assert-XmlValid -Path $nextPath -Kind $kind
  Write-Host "[promote] $kind entityID = $entityId"

  if (Test-Path -LiteralPath $currentPath) {
    $backupPath = Join-Path $ArchiveDir ("{0}-metadata-current.{1}.bak.xml" -f $kind, $timestamp)
    Move-Item -LiteralPath $currentPath -Destination $backupPath -Force
    Write-Host "[promote] Archived CURRENT -> $backupPath"
  }

  Copy-Item -LiteralPath $nextPath -Destination $currentPath -Force
  Write-Host "[promote] PROMOTED ${kind}: $($it.Next) -> $($it.Current)"
}

Write-Host "[promote] Done."
