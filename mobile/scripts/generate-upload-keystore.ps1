param(
    [string]$Alias = 'wariga-upload',
    [string]$DistinguishedName = 'CN=Wariga Bali Santih, OU=Mobile, O=Bali Santih, L=Denpasar, ST=Bali, C=ID'
)

$ErrorActionPreference = 'Stop'

$mobileRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$androidDirectory = Join-Path $mobileRoot 'android'
$keystorePath = Join-Path $androidDirectory 'upload-keystore.keystore'
$propertiesPath = Join-Path $androidDirectory 'keystore.properties'
$backupPath = Join-Path $androidDirectory 'upload-key-backup.txt'

foreach ($target in @($keystorePath, $propertiesPath, $backupPath)) {
    if (Test-Path -LiteralPath $target) {
        throw "Refusing to overwrite existing release credential file: $target"
    }
}

$keytool = (Get-Command keytool -ErrorAction Stop).Source
$randomBytes = New-Object byte[] 32
$randomNumberGenerator = [System.Security.Cryptography.RandomNumberGenerator]::Create()
try {
    $randomNumberGenerator.GetBytes($randomBytes)
}
finally {
    $randomNumberGenerator.Dispose()
}
$password = -join ($randomBytes | ForEach-Object { $_.ToString('x2') })

& $keytool -genkeypair -v `
    -storetype JKS `
    -keystore $keystorePath `
    -alias $Alias `
    -keyalg RSA `
    -keysize 4096 `
    -validity 10000 `
    -storepass $password `
    -keypass $password `
    -dname $DistinguishedName `
    -noprompt

if ($LASTEXITCODE -ne 0) {
    throw "keytool failed with exit code $LASTEXITCODE"
}

$utf8WithoutBom = New-Object System.Text.UTF8Encoding($false)
$properties = @(
    'storeFile=upload-keystore.keystore'
    "storePassword=$password"
    "keyAlias=$Alias"
    "keyPassword=$password"
) -join [Environment]::NewLine

$backup = @(
    'WARIGA ANDROID UPLOAD KEY - CONFIDENTIAL'
    ''
    "Created: $([DateTimeOffset]::Now.ToString('o'))"
    'Keystore file: upload-keystore.keystore'
    "Alias: $Alias"
    "Password: $password"
    "Certificate: $DistinguishedName"
    ''
    'Move this information and the keystore to encrypted storage immediately.'
    'After verifying the encrypted backup, delete this plaintext backup file.'
) -join [Environment]::NewLine

[System.IO.File]::WriteAllText($propertiesPath, $properties + [Environment]::NewLine, $utf8WithoutBom)
[System.IO.File]::WriteAllText($backupPath, $backup + [Environment]::NewLine, $utf8WithoutBom)

Write-Output 'Upload key generated successfully.'
Write-Output "Keystore: $keystorePath"
Write-Output "Signing properties: $propertiesPath"
Write-Output "Temporary backup record: $backupPath"
Write-Output "Alias: $Alias"
Write-Output 'The password was not printed. Move the backup record and keystore to encrypted storage.'
