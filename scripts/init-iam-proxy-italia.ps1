[CmdletBinding()]
param(
  [string]$Ref = "master",
  [switch]$Force
)

$ErrorActionPreference = "Stop"

function Ensure-Dir([string]$Path) {
  if (-not (Test-Path -LiteralPath $Path)) {
    New-Item -ItemType Directory -Path $Path | Out-Null
  }
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$iamProxyRoot = Join-Path $repoRoot "iam-proxy"
$projectDst = Join-Path $repoRoot ".local\iam-proxy-italia-project"
$staticDst = Join-Path $iamProxyRoot "nginx\html\static"

Ensure-Dir $projectDst
Ensure-Dir $staticDst

$sslCrt = Join-Path $repoRoot "ssl\server.crt"
$sslKey = Join-Path $repoRoot "ssl\server.key"
if (-not (Test-Path -LiteralPath $sslCrt) -or -not (Test-Path -LiteralPath $sslKey)) {
  Write-Warning "Certificati TLS mancanti: ssl\\server.crt e/o ssl\\server.key. NGINX non partirà in HTTPS finché non li crei."
}

$zipUrl = "https://github.com/italia/iam-proxy-italia/archive/refs/heads/$Ref.zip"
$tmpRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("iam-proxy-italia-" + [guid]::NewGuid().ToString("n"))
$zipPath = Join-Path $tmpRoot "src.zip"

Ensure-Dir $tmpRoot

try {
  Write-Host "Downloading $zipUrl" -ForegroundColor Cyan
  Invoke-WebRequest -Uri $zipUrl -OutFile $zipPath -UseBasicParsing

  Write-Host "Extracting archive" -ForegroundColor Cyan
  Expand-Archive -Path $zipPath -DestinationPath $tmpRoot -Force

  $extractedRoot = Get-ChildItem -LiteralPath $tmpRoot -Directory | Where-Object { $_.Name -like "iam-proxy-italia-*" } | Select-Object -First 1
  if (-not $extractedRoot) {
    throw "Impossibile trovare la cartella estratta da $zipUrl"
  }

  $srcProject = Join-Path $extractedRoot.FullName "iam-proxy-italia-project"
  if (-not (Test-Path -LiteralPath $srcProject)) {
    throw "Impossibile trovare iam-proxy-italia-project dentro l'archivio ($srcProject)"
  }

  if (-not $Force) {
    $existingProxyConf = Join-Path $projectDst "proxy_conf.yaml"
    if (Test-Path -LiteralPath $existingProxyConf) {
      Write-Host "proxy_conf.yaml già presente in $projectDst. Nessuna modifica (usa -Force per sovrascrivere)." -ForegroundColor Yellow
      return
    }
  }

  if ($Force) {
    Write-Host "Cleaning destination folders" -ForegroundColor Cyan
    Get-ChildItem -LiteralPath $projectDst -Force | Where-Object { $_.Name -ne ".gitkeep" } | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
    Get-ChildItem -LiteralPath $staticDst -Force | Where-Object { $_.Name -ne ".gitkeep" } | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
  }

  Write-Host "Copying iam-proxy-italia-project" -ForegroundColor Cyan
  Copy-Item -Path (Join-Path $srcProject "*") -Destination $projectDst -Recurse -Force

  # Genera cert.pem e privkey.pem se mancano (dev)
  $certGenScript = Join-Path $projectDst "pki/generate-dev-certs.sh"
  if (Test-Path -LiteralPath $certGenScript) {
    Write-Host "Eseguo generate-dev-certs.sh per certificati di test..." -ForegroundColor Cyan
    bash $certGenScript
  } else {
    Write-Warning "Script generate-dev-certs.sh non trovato in $projectDst/pki."
  }

  $srcStatic = Join-Path $srcProject "static"
  if (Test-Path -LiteralPath $srcStatic) {
    Write-Host "Copying static files to nginx/html/static" -ForegroundColor Cyan
    Copy-Item -Path (Join-Path $srcStatic "*") -Destination $staticDst -Recurse -Force

    # In upstream la cartella static viene servita da NGINX, non da SATOSA.
    $dstStaticInsideProject = Join-Path $projectDst "static"
    if (Test-Path -LiteralPath $dstStaticInsideProject) {
      Remove-Item -LiteralPath $dstStaticInsideProject -Recurse -Force -ErrorAction SilentlyContinue
    }
  }

  Write-Host "Done. Ora puoi avviare: docker compose --profile iam-proxy up -d" -ForegroundColor Green
}
finally {
  if (Test-Path -LiteralPath $tmpRoot) {
    Remove-Item -LiteralPath $tmpRoot -Recurse -Force -ErrorAction SilentlyContinue
  }
}
